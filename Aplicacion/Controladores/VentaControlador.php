<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\PeriodoFechas;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\VendedorRepositorio;
use Aplicacion\Repositorios\VentaRepositorio;
use Configuracion\Configuracion;
use Throwable;

class VentaControlador
{
    private VentaRepositorio $ventaRepositorio;
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
        $this->idVendedor = (int) $vendedor->getIdVendedor();
    }

    public function listar(): void
    {
        $tipo = in_array($_GET['tipo'] ?? null, DatosVenta::TIPOS, true) ? $_GET['tipo'] : '';
        $estado = in_array($_GET['estado'] ?? null, DatosVenta::ESTADOS, true) ? $_GET['estado'] : '';
        $periodo = PeriodoFechas::leer($_GET);

        $base = [
            'tipo' => $tipo !== '' ? $tipo : null,
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
            'tipo' => $tipo,
            'estado' => $estado,
            'periodo' => $periodo,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => VentaRepositorio::POR_PAGINA,
            'hayVentas' => $this->ventaRepositorio->contarPorVendedor($this->idVendedor) > 0,
        ]);
    }

    public function cobrar(): void
    {
        $volver = '/ventas';
        if (is_string($_POST['volver'] ?? null) && str_starts_with($_POST['volver'], '/ventas')) {
            $volver = $_POST['volver'];
        }
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir($volver);
        }
        $idVenta = is_string($_POST['id'] ?? null) && ctype_digit($_POST['id']) ? (int) $_POST['id'] : 0;
        $venta = $idVenta > 0 ? $this->ventaRepositorio->buscar($idVenta, $this->idVendedor) : null;
        if ($venta === null) {
            Mensaje::error('No se encontró la venta.');
            $this->redirigir($volver);
        }
        if ($venta['estadoPago'] === DatosVenta::ESTADO_PAGADA) {
            Mensaje::advertencia('La venta #' . $idVenta . ' ya estaba pagada.');
            $this->redirigir($volver);
        }

        try {
            $this->ventaRepositorio->marcarPagada($idVenta, $this->idVendedor);
            Mensaje::exito('La venta #' . $idVenta . ' quedó pagada (' . Numero::moneda($venta['saldo']) . ').');
        } catch (Throwable $error) {
            error_log('No se pudo marcar como pagada la venta ' . $idVenta . ': ' . $error->getMessage());
            Mensaje::error('No se pudo guardar el pago. Intente de nuevo.');
        }
        $this->redirigir($volver);
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
