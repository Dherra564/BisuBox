<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\CompraProveedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\PeriodoFechas;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\CompraProveedorRepositorio;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\ProveedorRepositorio;
use Aplicacion\Repositorios\TipoComponenteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class CompraProveedorControlador
{
    private const ESTADOS = ['todas' => 'Todas', 'activas' => 'Activas', 'anuladas' => 'Anuladas'];

    private CompraProveedorRepositorio $compraRepositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('comprasProveedor.gestionar');
        $this->compraRepositorio = new CompraProveedorRepositorio();

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->idVendedor = (int) $vendedor->getIdVendedor();
    }

    public function listar(): void
    {
        $proveedores = (new ProveedorRepositorio())->listarTodos($this->idVendedor);
        $idsProveedores = array_map(fn($proveedor): int => (int) $proveedor->getIdProveedor(), $proveedores);
        $proveedor = is_string($_GET['proveedor'] ?? null) && in_array((int) $_GET['proveedor'], $idsProveedores, true)
            ? (int) $_GET['proveedor']
            : null;
        $estado = is_string($_GET['estado'] ?? null) && isset(self::ESTADOS[$_GET['estado']]) ? $_GET['estado'] : 'todas';
        $periodo = PeriodoFechas::leer($_GET);

        $base = ['proveedor' => $proveedor, 'desde' => $periodo['desde'], 'hasta' => $periodo['hasta']];
        $conteos = [];
        foreach (array_keys(self::ESTADOS) as $codigo) {
            $conteos[$codigo] = $this->compraRepositorio->contar($this->idVendedor, ['estado' => $codigo] + $base);
        }

        $filtros = ['estado' => $estado] + $base;
        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / CompraProveedorRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('CompraProveedor/lista', [
            'compras' => $this->compraRepositorio->listar($this->idVendedor, $filtros, $pagina),
            'resumen' => $this->compraRepositorio->resumen($this->idVendedor, $filtros),
            'proveedores' => $proveedores,
            'proveedor' => $proveedor,
            'estado' => $estado,
            'estados' => self::ESTADOS,
            'conteos' => $conteos,
            'periodo' => $periodo,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => CompraProveedorRepositorio::POR_PAGINA,
            'hayCompras' => $this->compraRepositorio->contarPorVendedor($this->idVendedor) > 0,
        ]);
    }

    public function nueva(): void
    {
        $opciones = $this->opcionesFormulario();
        $proveedor = is_string($_GET['proveedor'] ?? null) && isset($opciones['proveedores'][(int) $_GET['proveedor']])
            ? $_GET['proveedor']
            : '';

        $this->mostrarFormulario(DatosCompraProveedor::vacio($proveedor), [], $opciones);
    }

    public function registrar(): void
    {
        $this->verificarCsrf('/compras-proveedor/nueva');
        $opciones = $this->opcionesFormulario();

        $datos = DatosCompraProveedor::leer();
        $validador = new Validador();
        DatosCompraProveedor::validar(
            $validador,
            $datos,
            $this->idVendedor,
            $opciones['proveedores'],
            $opciones['componentes'],
            $opciones['tipos']
        );
        if (!$validador->esValido()) {
            $this->mostrarFormulario($datos, $validador->errores(), $opciones);
            return;
        }

        $armada = DatosCompraProveedor::crearCompra($datos, $this->idVendedor, $opciones['componentes'], $opciones['tipos']);
        try {
            $idCompraProveedor = $this->compraRepositorio->registrar($armada['compra'], $armada['nuevos']);
        } catch (Throwable $error) {
            error_log('Error al registrar la compra: ' . $error->getMessage());
            Mensaje::error('No se pudo registrar la compra. No se cambió nada en el inventario; intente de nuevo.');
            $this->mostrarFormulario($datos, [], $opciones);
            return;
        }

        $cantidad = count($armada['compra']->getDetalles());
        Mensaje::exito('Compra registrada. Se actualizó la existencia de ' . ($cantidad === 1 ? '1 componente' : $cantidad . ' componentes') . ' en el inventario.');
        $this->redirigir('/compras-proveedor/detalle?id=' . $idCompraProveedor);
    }

    public function detalle(): void
    {
        $compra = $this->buscarCompra($_GET['id'] ?? null);

        $this->mostrarVista('CompraProveedor/detalle', [
            'compra' => $compra,
            'sinExistencia' => $compra->estaAnulada() ? [] : $this->compraRepositorio->componentesSinExistencia($compra),
        ]);
    }

    public function anular(): void
    {
        $this->verificarCsrf('/compras-proveedor');
        $compra = $this->buscarCompra($_POST['id'] ?? null);

        try {
            $this->compraRepositorio->anular($compra);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir('/compras-proveedor/detalle?id=' . $compra->getIdCompraProveedor());
        } catch (Throwable $error) {
            error_log('Error al anular la compra: ' . $error->getMessage());
            Mensaje::error('No se pudo anular la compra. Intente de nuevo.');
            $this->redirigir('/compras-proveedor/detalle?id=' . $compra->getIdCompraProveedor());
        }

        Mensaje::exito('Compra anulada. Se restó del inventario lo que había entrado con ella.');
        $this->redirigir('/compras-proveedor/detalle?id=' . $compra->getIdCompraProveedor());
    }

    private function opcionesFormulario(): array
    {
        $porId = function (array $lista, string $metodo): array {
            $resultado = [];
            foreach ($lista as $elemento) {
                $resultado[(int) $elemento->$metodo()] = $elemento;
            }
            return $resultado;
        };

        $proveedores = $porId((new ProveedorRepositorio())->listarTodos($this->idVendedor, true), 'getIdProveedor');
        if ($proveedores === []) {
            Mensaje::advertencia('Para registrar una compra, primero registre al proveedor.');
            $this->redirigir('/proveedores/nuevo');
        }

        $tipos = $porId((new TipoComponenteRepositorio())->listarPorVendedor($this->idVendedor, true), 'getIdTipoComponente');
        $componentes = $porId((new ComponenteRepositorio())->listarActivos($this->idVendedor), 'getIdComponente');
        if ($tipos === [] && $componentes === []) {
            Mensaje::advertencia('Para registrar una compra, primero cree sus tipos de componente, por ejemplo Perlas o Hilos.');
            $this->redirigir('/tipos-componente');
        }

        return ['proveedores' => $proveedores, 'tipos' => $tipos, 'componentes' => $componentes];
    }

    private function buscarCompra(mixed $id): CompraProveedor
    {
        $compra = is_string($id) && ctype_digit($id) ? $this->compraRepositorio->buscarPorId((int) $id, $this->idVendedor) : null;
        if ($compra === null) {
            Mensaje::error('No se encontró esa compra.');
            $this->redirigir('/compras-proveedor');
        }

        return $compra;
    }

    private function mostrarFormulario(array $datos, array $errores, array $opciones): void
    {
        $this->mostrarVista('CompraProveedor/formulario', ['datos' => $datos, 'errores' => $errores] + $opciones);
    }

    private function verificarCsrf(string $rutaSiFalla): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir($rutaSiFalla);
        }
    }

    private function mostrarVista(string $vista, array $variablesVista): void
    {
        $variablesVista['mensajes'] = Mensaje::obtener();
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/' . $vista . '.php';
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl'), '/') . $ruta);
        exit;
    }
}