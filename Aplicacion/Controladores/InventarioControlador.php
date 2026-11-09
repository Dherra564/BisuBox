<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\FiltrosInventario;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\MovimientoInventario;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UnidadMedida;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\InventarioRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\TipoComponenteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class InventarioControlador
{
    private Vendedor $vendedor;

    public function __construct()
    {
        Permiso::exigir('inventario.gestionar');

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->vendedor = $vendedor;
    }

    public function mostrar(): void
    {
        $idVendedor = (int) $this->vendedor->getIdVendedor();
        $componenteRepositorio = new ComponenteRepositorio();
        $tipos = (new TipoComponenteRepositorio())->listarPorVendedor($idVendedor);
        $filtros = FiltrosInventario::leer($_GET, $tipos);

        $this->mostrarVista('Inventario/inventario', [
            'tipos' => $tipos,
            'filtros' => $filtros,
            'componentes' => $componenteRepositorio->listar($idVendedor, $filtros),
            'resumen' => $componenteRepositorio->resumen($idVendedor),
            'hayComponentes' => $componenteRepositorio->contarPorVendedor($idVendedor) > 0,
        ]);
    }

    public function movimientos(): void
    {
        $articulo = $this->buscarArticulo($_GET['articulo'] ?? null, $_GET['id'] ?? null);
        $this->mostrarMovimientos($articulo, MovimientoInventario::ajusteVacio(), []);
    }

    public function ajustar(): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/inventario');
        }
        $articulo = $this->buscarArticulo($_POST['articulo'] ?? null, $_POST['id'] ?? null);
        $volver = '/inventario/movimientos?articulo=' . MovimientoInventario::parametro($articulo['tipo']) . '&id=' . $articulo['id'];

        $datos = MovimientoInventario::leerAjuste();
        $validador = new Validador();
        $cantidad = MovimientoInventario::validarAjuste($validador, $datos, $articulo['existencia'], $articulo['soloEnteros']);
        if ($cantidad === null) {
            $this->mostrarMovimientos($articulo, $datos, $validador->errores());
            return;
        }

        try {
            $resultado = (new InventarioRepositorio())->mover(
                (int) $this->vendedor->getIdVendedor(),
                $articulo['tipo'],
                $articulo['id'],
                $datos['tipo'],
                $cantidad,
                $datos['motivo'],
                MovimientoInventario::REFERENCIA_AJUSTE,
                null,
                null,
                $datos['nota']
            );
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('No se pudo ajustar la existencia: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar el ajuste. Intente de nuevo.');
            $this->mostrarMovimientos($articulo, $datos, []);
            return;
        }

        Mensaje::exito('Ajuste guardado. «' . $resultado['nombre'] . '» pasó de '
            . UnidadMedida::cantidad($resultado['anterior'], $articulo['unidad']) . ' a '
            . UnidadMedida::cantidad($resultado['nueva'], $articulo['unidad']) . '.');
        $this->redirigir($volver);
    }

    private function buscarArticulo(mixed $tipo, mixed $id): array
    {
        $articulo = MovimientoInventario::articulo($tipo);
        $idArticulo = is_string($id) && ctype_digit($id) ? (int) $id : 0;
        $idVendedor = (int) $this->vendedor->getIdVendedor();

        if ($articulo === MovimientoInventario::PRODUCTO && $idArticulo > 0) {
            $producto = (new ProductoRepositorio())->buscarPorId($idArticulo, $idVendedor);
            if ($producto !== null) {
                return [
                    'tipo' => $articulo,
                    'id' => $idArticulo,
                    'nombre' => $producto->getNombre(),
                    'existencia' => (float) $producto->getExistencia(),
                    'unidad' => UnidadMedida::UNIDAD,
                    'soloEnteros' => true,
                    'activo' => $producto->getActivo(),
                    'detalle' => '/productos/detalle?id=' . $idArticulo,
                ];
            }
        } elseif ($articulo === MovimientoInventario::COMPONENTE && $idArticulo > 0) {
            $componente = (new ComponenteRepositorio())->buscarPorId($idArticulo, $idVendedor);
            if ($componente !== null) {
                return [
                    'tipo' => $articulo,
                    'id' => $idArticulo,
                    'nombre' => $componente->getNombre(),
                    'existencia' => $componente->getExistencia(),
                    'unidad' => $componente->getUnidad(),
                    'soloEnteros' => $componente->getUnidad() === UnidadMedida::UNIDAD,
                    'activo' => $componente->getActivo(),
                    'detalle' => '/componentes/editar?id=' . $idArticulo,
                ];
            }
        }

        Mensaje::error('No se encontró lo que quiere revisar.');
        $this->redirigir($articulo === MovimientoInventario::PRODUCTO ? '/productos' : '/inventario');
    }

    private function mostrarMovimientos(array $articulo, array $datos, array $errores): void
    {
        $idVendedor = (int) $this->vendedor->getIdVendedor();
        $inventario = new InventarioRepositorio();
        $total = $inventario->contar($idVendedor, $articulo['tipo'], $articulo['id']);
        $totalPaginas = max(1, (int) ceil($total / InventarioRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Inventario/movimientos', [
            'articulo' => $articulo,
            'movimientos' => $inventario->historial($idVendedor, $articulo['tipo'], $articulo['id'], $pagina),
            'cuadra' => abs($inventario->sumaMovimientos($idVendedor, $articulo['tipo'], $articulo['id']) - $articulo['existencia']) < 0.005,
            'datos' => $datos,
            'errores' => $errores,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => InventarioRepositorio::POR_PAGINA,
        ]);
    }

    private function mostrarVista(string $vista, array $variablesVista): void
    {
        $variablesVista['mensajes'] = Mensaje::obtener();
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/' . $vista . '.php';
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . Configuracion::urlBase() . $ruta);
        exit;
    }
}