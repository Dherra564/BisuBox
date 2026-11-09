<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\CompraProveedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\PeriodoFechas;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\CompraProveedorRepositorio;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\LineaProductoRepositorio;
use Aplicacion\Repositorios\ProveedorRepositorio;
use Aplicacion\Repositorios\TipoComponenteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class CompraProveedorControlador
{
    private const ESTADOS = ['todas' => 'Todas', 'porRecibir' => 'Por recibir', 'recibidas' => 'Recibidas', 'anuladas' => 'Anuladas'];

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
        $compra = $armada['compra'];
        try {
            $idCompraProveedor = $this->compraRepositorio->registrar(
                $compra,
                $armada['nuevos'],
                $compra->esCredito() ? null : $datos['metodoPago']
            );
        } catch (Throwable $error) {
            error_log('Error al registrar la compra: ' . $error->getMessage());
            Mensaje::error('No se pudo registrar la compra. No se cambió nada en el inventario; intente de nuevo.');
            $this->mostrarFormulario($datos, [], $opciones);
            return;
        }

        $cantidad = count($compra->getDetalles());
        $componentes = $cantidad === 1 ? 'Se sumó 1 componente' : 'Se sumaron ' . $cantidad . ' componentes';
        Mensaje::exito(($compra->estaRecibida()
            ? 'Compra registrada. ' . $componentes . ' al inventario.'
            : 'Compra registrada como por recibir. Cuando le llegue, márquela como recibida para sumarla al inventario.')
            . ($compra->esCredito() ? ' Quedó a crédito: debe ' . Numero::moneda($compra->getSaldo()) . '.' : ''));
        $this->redirigir('/compras-proveedor/detalle?id=' . $idCompraProveedor);
    }

    public function detalle(): void
    {
        $compra = $this->buscarCompra($_GET['id'] ?? null);
        $this->mostrarDetalle($compra, ['monto' => Numero::paraCampo($compra->getSaldo()), 'metodoPago' => '', 'nota' => ''], []);
    }

    public function recibir(): void
    {
        $this->verificarCsrf('/compras-proveedor');
        $compra = $this->buscarCompra($_POST['id'] ?? null);
        $volver = '/compras-proveedor/detalle?id=' . $compra->getIdCompraProveedor();

        try {
            $this->compraRepositorio->recibir($compra);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al recibir la compra: ' . $error->getMessage());
            Mensaje::error('No se pudo marcar como recibida. No se cambió nada en el inventario; intente de nuevo.');
            $this->redirigir($volver);
        }

        $cantidad = count($compra->getDetalles());
        Mensaje::exito('Compra recibida. ' . ($cantidad === 1 ? 'Se sumó 1 componente' : 'Se sumaron ' . $cantidad . ' componentes') . ' al inventario.');
        $this->redirigir($volver);
    }

    public function abonar(): void
    {
        $this->verificarCsrf('/compras-por-pagar');
        $compra = $this->buscarCompra($_POST['id'] ?? null);
        $volver = '/compras-proveedor/detalle?id=' . $compra->getIdCompraProveedor();
        if (!$compra->debe()) {
            Mensaje::advertencia('La compra #' . $compra->getIdCompraProveedor() . ' no tiene saldo pendiente.');
            $this->redirigir($volver);
        }

        $datos = DatosVenta::leerAbono();
        $validador = new Validador();
        $monto = DatosVenta::validarAbono($validador, $datos, $compra->getSaldo());
        if ($monto === null) {
            $this->mostrarDetalle($compra, $datos, $validador->errores());
            return;
        }

        try {
            $resultado = $this->compraRepositorio->abonar(
                (int) $compra->getIdCompraProveedor(),
                $this->idVendedor,
                $monto,
                $datos['metodoPago'],
                $datos['nota'] !== '' ? $datos['nota'] : null
            );
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('No se pudo registrar el abono de la compra ' . $compra->getIdCompraProveedor() . ': ' . $error->getMessage());
            Mensaje::error('No se pudo guardar el abono. Intente de nuevo.');
            $this->mostrarDetalle($compra, $datos, []);
            return;
        }

        Mensaje::exito($resultado['pagada']
            ? 'Abono de ' . Numero::moneda($monto) . ' guardado. La compra quedó pagada.'
            : 'Abono de ' . Numero::moneda($monto) . ' guardado. Todavía le debe ' . Numero::moneda($resultado['saldo']) . ' a «' . $compra->getNombreProveedor() . '».');
        $this->redirigir($volver);
    }

    public function porPagar(): void
    {
        $proveedores = (new ProveedorRepositorio())->listarTodos($this->idVendedor);
        $idsProveedores = array_map(fn($proveedor): int => (int) $proveedor->getIdProveedor(), $proveedores);
        $proveedor = is_string($_GET['proveedor'] ?? null) && in_array((int) $_GET['proveedor'], $idsProveedores, true)
            ? (int) $_GET['proveedor']
            : null;
        $vencidas = ($_GET['vista'] ?? '') === 'vencidas';
        $periodo = PeriodoFechas::leer($_GET);

        $base = ['proveedor' => $proveedor, 'desde' => $periodo['desde'], 'hasta' => $periodo['hasta']];
        $conteos = [
            'todas' => $this->compraRepositorio->contarPorPagar($this->idVendedor, $base),
            'vencidas' => $this->compraRepositorio->contarPorPagar($this->idVendedor, $base + ['vencidas' => true]),
        ];
        $filtros = $base + ['vencidas' => $vencidas];
        $total = $vencidas ? $conteos['vencidas'] : $conteos['todas'];
        $totalPaginas = max(1, (int) ceil($total / CompraProveedorRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('CompraProveedor/porPagar', [
            'compras' => $this->compraRepositorio->listarPorPagar($this->idVendedor, $filtros, $pagina),
            'resumen' => $this->compraRepositorio->resumenPorPagar($this->idVendedor, $base),
            'saldos' => $this->compraRepositorio->saldosPorProveedor($this->idVendedor),
            'proveedores' => $proveedores,
            'proveedor' => $proveedor,
            'vencidas' => $vencidas,
            'conteos' => $conteos,
            'periodo' => $periodo,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => CompraProveedorRepositorio::POR_PAGINA,
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

        Mensaje::exito($compra->getFechaRecepcion() !== null
            ? 'Compra anulada. Se restó del inventario lo que había entrado con ella.'
            : 'Compra anulada.');
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
        $lineaRepositorio = new LineaProductoRepositorio();

        return [
            'proveedores' => $proveedores,
            'tipos' => $tipos,
            'componentes' => $componentes,
            'lineas' => $lineaRepositorio->listar($this->idVendedor, true),
            'lineasProveedor' => $lineaRepositorio->porProveedor($this->idVendedor),
        ];
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

    private function mostrarDetalle(CompraProveedor $compra, array $datos, array $errores): void
    {
        $this->mostrarVista('CompraProveedor/detalle', [
            'compra' => $compra,
            'abonos' => $this->compraRepositorio->abonos((int) $compra->getIdCompraProveedor()),
            'sinExistencia' => $this->compraRepositorio->componentesSinExistencia($compra),
            'datos' => $datos,
            'errores' => $errores,
        ]);
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
        header('Location: ' . Configuracion::urlBase() . $ruta);
        exit;
    }
}