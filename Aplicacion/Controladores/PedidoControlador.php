<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\DisenoRepositorio;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Aplicacion\Repositorios\VentaRepositorio;
use Configuracion\Configuracion;
use Throwable;


class PedidoControlador
{
    private PedidoRepositorio $pedidoRepositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('pedidos.gestionar');
        $this->pedidoRepositorio = new PedidoRepositorio();

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
        $estado = EstadoPedido::existe($_GET['estado'] ?? null) ? $_GET['estado'] : '';
        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';
        $idCliente = is_string($_GET['cliente'] ?? null) && ctype_digit($_GET['cliente']) ? (int) $_GET['cliente'] : null;
        $nombreCliente = $idCliente !== null ? $this->pedidoRepositorio->nombreClienteDeVendedor($idCliente, $this->idVendedor) : null;
        if ($nombreCliente === null) {
            $idCliente = null;
        }

        $base = ['idVendedor' => $this->idVendedor, 'busqueda' => $busqueda] + ($idCliente !== null ? ['idCliente' => $idCliente] : []);
        $conteos = ['' => $this->pedidoRepositorio->contar($base)];
        foreach (EstadoPedido::todos() as $codigo) {
            $conteos[$codigo] = $this->pedidoRepositorio->contar($base + ['estado' => $codigo]);
        }

        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / PedidoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Pedido/lista', [
            'pedidos' => $this->pedidoRepositorio->listar($base + ['estado' => $estado], $pagina),
            'estado' => $estado,
            'busqueda' => $busqueda,
            'idCliente' => $idCliente,
            'nombreCliente' => $nombreCliente,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => PedidoRepositorio::POR_PAGINA,
            'hayPedidos' => $this->pedidoRepositorio->contar(['idVendedor' => $this->idVendedor]) > 0,
        ]);
    }

    public function detalle(): void
    {
        $pedido = $this->buscarPedido($_GET['id'] ?? null);

    
        $existencias = [];
        $faltanComponentes = [];
        if ($pedido->estaPendiente()) {
            $ids = array_values(array_filter(array_map(fn($detalle): ?int => $detalle->getIdProducto(), $pedido->getDetalles())));
            if ($ids !== []) {
                foreach ((new ProductoRepositorio())->buscarVarios($this->idVendedor, $ids) as $idProducto => $producto) {
                    $existencias[$idProducto] = $producto->getExistencia();
                }
            }
            $componenteRepositorio = new ComponenteRepositorio();
            foreach ($this->pedidoRepositorio->componentesDeDisenos($pedido) as $idComponente => $necesita) {
                $componente = $componenteRepositorio->buscarPorId($idComponente, $this->idVendedor);
                if ($componente === null || $componente->getExistencia() + 0.00001 < $necesita) {
                    $faltanComponentes[] = '«' . ($componente?->getNombre() ?? 'Un componente') . '»: tiene '
                        . Numero::formatear(max(0, $componente?->getExistencia() ?? 0))
                        . ' y las piezas personalizadas llevan ' . Numero::formatear($necesita);
                }
            }
        }

        $idsDisenos = array_values(array_filter(array_map(fn($detalle): ?int => $detalle->getIdDiseno(), $pedido->getDetalles())));
        $this->mostrarVista('Pedido/detalle', [
            'pedido' => $pedido,
            'existencias' => $existencias,
            'faltanComponentes' => $faltanComponentes,
            'disenos' => (new DisenoRepositorio())->buscarVarios($idsDisenos),
            'venta' => (new VentaRepositorio())->buscarPorPedido((int) $pedido->getIdPedido(), $this->idVendedor),
            'pagoElegido' => DatosVenta::leerPago($_GET['pago'] ?? null) ?? DatosVenta::PAGO_COMPLETO,
            'vencimientoElegido' => is_string($_GET['vencimiento'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['vencimiento'])
                ? $_GET['vencimiento']
                : DatosVenta::vencimientoInicial(),
        ]);
    }

    public function confirmar(): void
    {
        $pedido = $this->accion(fn(Pedido $pedido) => $this->pedidoRepositorio->confirmar($pedido), 'confirmar');
        Mensaje::exito('Pedido #' . $pedido->getIdPedido() . ' confirmado. Se rebajó de la existencia lo que lleva.');
        $this->redirigir('/pedidos/detalle?id=' . $pedido->getIdPedido());
    }

    public function entregar(): void
    {
        $pago = DatosVenta::leerPago($_POST['pago'] ?? null);
        $textoVencimiento = is_string($_POST['vencimiento'] ?? null) ? trim($_POST['vencimiento']) : '';
        $vencimiento = $pago === DatosVenta::PAGO_PENDIENTE ? DatosVenta::leerVencimiento($textoVencimiento) : null;
        $error = $pago === null
            ? DatosVenta::MENSAJE_PAGO
            : ($pago === DatosVenta::PAGO_PENDIENTE && $vencimiento === null ? DatosVenta::MENSAJE_VENCIMIENTO : null);
        if ($error !== null) {
            $this->verificarCsrf('/pedidos');
            $pedido = $this->buscarPedido($_POST['id'] ?? null);
            Mensaje::error($error);
            $this->redirigir('/pedidos/detalle?' . http_build_query([
                'id' => $pedido->getIdPedido(),
                'pago' => $pago ?? '',
                'vencimiento' => mb_substr($textoVencimiento, 0, 10),
            ]));
        }

        $pedido = $this->accion(
            fn(Pedido $pedido) => $this->pedidoRepositorio->entregar($pedido, $pago, $vencimiento),
            'marcar como entregado'
        );
        Mensaje::exito('Pedido #' . $pedido->getIdPedido() . ' entregado. '
            . ($pago === DatosVenta::PAGO_PENDIENTE
                ? 'La venta quedó por cobrar hasta el ' . $vencimiento->format('d/m/Y') . '.'
                : 'La venta quedó registrada como pagada.'));
        $this->redirigir('/pedidos/detalle?id=' . $pedido->getIdPedido());
    }

    public function cancelar(): void
    {
        $estabaConfirmado = false;
        $pedido = $this->accion(function (Pedido $pedido) use (&$estabaConfirmado) {
            $estabaConfirmado = $pedido->estaConfirmado();
            $this->pedidoRepositorio->cancelar($pedido, Rol::VENDEDOR);
        }, 'cancelar');
        Mensaje::exito('Pedido #' . $pedido->getIdPedido() . ' cancelado.'
            . ($estabaConfirmado ? ' Los productos volvieron a la existencia.' : ''));
        $this->redirigir('/pedidos/detalle?id=' . $pedido->getIdPedido());
    }

    
    private function accion(callable $hacer, string $queSeHace): Pedido
    {
        $this->verificarCsrf('/pedidos');
        $pedido = $this->buscarPedido($_POST['id'] ?? null);
        $volver = '/pedidos/detalle?id=' . $pedido->getIdPedido();

        try {
            $hacer($pedido);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al ' . $queSeHace . ' el pedido: ' . $error->getMessage());
            Mensaje::error('No se pudo ' . $queSeHace . ' el pedido. No se cambió nada; intente de nuevo.');
            $this->redirigir($volver);
        }

        return $pedido;
    }

    
    private function buscarPedido(mixed $id): Pedido
    {
        $pedido = is_string($id) && ctype_digit($id)
            ? $this->pedidoRepositorio->buscarParaVendedor((int) $id, $this->idVendedor)
            : null;

        if ($pedido === null) {
            Mensaje::error('No se encontró ese pedido.');
            $this->redirigir('/pedidos');
        }
        return $pedido;
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
