<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\PeriodoFechas;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Aplicacion\Repositorios\VentaRepositorio;
use Configuracion\Configuracion;
use Throwable;

class VentaControlador
{
    private VentaRepositorio $ventaRepositorio;
    private Vendedor $vendedor;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('ventas.ver');
        $this->ventaRepositorio = new VentaRepositorio();

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->vendedor = $vendedor;
        $this->idVendedor = (int) $vendedor->getIdVendedor();
    }

    public function listar(): void
    {
        $tipo = in_array($_GET['tipo'] ?? null, DatosVenta::TIPOS, true) ? $_GET['tipo'] : '';
        $estado = in_array($_GET['estado'] ?? null, DatosVenta::ESTADOS, true) ? $_GET['estado'] : '';
        $idCliente = is_string($_GET['cliente'] ?? null) && ctype_digit($_GET['cliente']) ? (int) $_GET['cliente'] : null;
        $periodo = PeriodoFechas::leer($_GET);

        $saldos = $this->ventaRepositorio->saldosPorCliente($this->idVendedor);
        $nombreCliente = null;
        if ($idCliente !== null) {
            $nombreCliente = (new PedidoRepositorio())->nombreClienteDeVendedor($idCliente, $this->idVendedor);
            if ($nombreCliente === null) {
                $idCliente = null;
            }
        }

        $base = [
            'tipo' => $tipo !== '' ? $tipo : null,
            'idCliente' => $idCliente,
            'desde' => $periodo['desde'],
            'hasta' => $periodo['hasta'],
        ];
        $conteos = ['' => $this->ventaRepositorio->contar($this->idVendedor, $base)];
        foreach (DatosVenta::ESTADOS as $codigo) {
            $conteos[$codigo] = $this->ventaRepositorio->contar($this->idVendedor, $base + ['estado' => $codigo]);
        }
        $filtros = $base + ['estado' => $estado !== '' ? $estado : null];

        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / VentaRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Venta/lista', [
            'ventas' => $this->ventaRepositorio->listar($this->idVendedor, $filtros, $pagina),
            'resumen' => $this->ventaRepositorio->resumen($this->idVendedor, $base),
            'saldos' => $saldos,
            'tipo' => $tipo,
            'estado' => $estado,
            'idCliente' => $idCliente,
            'nombreCliente' => $nombreCliente,
            'periodo' => $periodo,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => VentaRepositorio::POR_PAGINA,
            'hayVentas' => $this->ventaRepositorio->contarPorVendedor($this->idVendedor) > 0,
            'tienda' => (string) $this->vendedor->getTiendaNombre(),
        ]);
    }

    public function detalle(): void
    {
        $venta = $this->buscarVenta($_GET['id'] ?? null);
        $this->mostrarDetalle($venta, ['monto' => Numero::paraCampo($venta['saldo']), 'metodoPago' => '', 'nota' => ''], []);
    }

    public function abonar(): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/ventas');
        }
        $venta = $this->buscarVenta($_POST['id'] ?? null);
        $volver = '/ventas/detalle?id=' . $venta['id'];
        if ($venta['estadoPago'] !== DatosVenta::ESTADO_POR_COBRAR) {
            Mensaje::advertencia('La venta #' . $venta['id'] . ' ya estaba pagada.');
            $this->redirigir($volver);
        }

        $datos = DatosVenta::leerAbono();
        $validador = new Validador();
        $monto = DatosVenta::validarAbono($validador, $datos, $venta['saldo']);
        if ($monto === null) {
            $this->mostrarDetalle($venta, $datos, $validador->errores());
            return;
        }

        try {
            $resultado = $this->ventaRepositorio->abonar($venta['id'], $this->idVendedor, $monto, $datos['metodoPago'], $datos['nota'] !== '' ? $datos['nota'] : null);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('No se pudo registrar el abono de la venta ' . $venta['id'] . ': ' . $error->getMessage());
            Mensaje::error('No se pudo guardar el abono. Intente de nuevo.');
            $this->mostrarDetalle($venta, $datos, []);
            return;
        }

        Mensaje::exito($resultado['pagada']
            ? 'Abono de ' . Numero::moneda($monto) . ' guardado. La venta #' . $venta['id'] . ' quedó pagada.'
            : 'Abono de ' . Numero::moneda($monto) . ' guardado. Todavía debe ' . Numero::moneda($resultado['saldo']) . '.');
        $this->redirigir($volver);
    }

    private function buscarVenta(mixed $id): array
    {
        $venta = is_string($id) && ctype_digit($id) ? $this->ventaRepositorio->buscar((int) $id, $this->idVendedor) : null;
        if ($venta === null) {
            Mensaje::error('No se encontró la venta.');
            $this->redirigir('/ventas');
        }
        return $venta;
    }

    private function mostrarDetalle(array $venta, array $datos, array $errores): void
    {
        $this->mostrarVista('Venta/detalle', [
            'venta' => $venta,
            'abonos' => $this->ventaRepositorio->abonos($venta['id']),
            'datos' => $datos,
            'errores' => $errores,
            'tienda' => (string) $this->vendedor->getTiendaNombre(),
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