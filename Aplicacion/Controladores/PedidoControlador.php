<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPago;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\PersonalizadoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
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
        $soloPorVerificar = ($_GET['pago'] ?? null) === 'verificar';
        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';
        $idCliente = is_string($_GET['cliente'] ?? null) && ctype_digit($_GET['cliente']) ? (int) $_GET['cliente'] : null;
        $nombreCliente = $idCliente !== null ? $this->pedidoRepositorio->nombreClienteDeVendedor($idCliente, $this->idVendedor) : null;
        if ($nombreCliente === null) {
            $idCliente = null;
        }

        $base = ['idVendedor' => $this->idVendedor, 'busqueda' => $busqueda] + ($idCliente !== null ? ['idCliente' => $idCliente] : [])
            + ($soloPorVerificar ? ['pago' => EstadoPago::POR_VERIFICAR] : []);
        $conteos = ['' => $this->pedidoRepositorio->contar($base)];
        foreach (EstadoPedido::todos() as $codigo) {
            $conteos[$codigo] = $this->pedidoRepositorio->contar($base + ['estado' => $codigo]);
        }

        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / PedidoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $personalizadoRepositorio = new PersonalizadoRepositorio();
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
            'porCotizar' => $personalizadoRepositorio->contar(['idVendedor' => $this->idVendedor, 'estado' => EstadoPersonalizado::EN_REVISION]),
            'pagosPorVerificar' => $this->pedidoRepositorio->contar([
                'idVendedor' => $this->idVendedor,
                'estado' => EstadoPedido::CONFIRMADO,
                'pago' => EstadoPago::POR_VERIFICAR,
            ]),
            'soloPorVerificar' => $soloPorVerificar,
            'avisosPersonalizados' => $personalizadoRepositorio->contarAvisosSinVer($this->idVendedor),
        ]);
    }

    public function detalle(): void
    {
        $this->mostrarVista('Pedido/detalle', [
            'pedido' => $this->buscarPedido($_GET['id'] ?? null),
        ]);
    }

    public function entregar(): void
    {
        $codigo = is_string($_POST['codigoRetiro'] ?? null) ? mb_substr($_POST['codigoRetiro'], 0, 20, 'UTF-8') : '';
        $pedido = $this->accion(fn(Pedido $pedido) => $this->pedidoRepositorio->entregar($pedido, $codigo), 'marcar como entregado');
        Mensaje::exito('Pedido #' . $pedido->getIdPedido() . ' marcado como entregado.');
        $this->redirigir('/pedidos/detalle?id=' . $pedido->getIdPedido());
    }

    public function cancelar(): void
    {
        $pedido = $this->accion(fn(Pedido $pedido) => $this->pedidoRepositorio->cancelar($pedido, Rol::VENDEDOR), 'cancelar');
        Mensaje::exito('Pedido #' . $pedido->getIdPedido() . ' cancelado. Los productos volvieron a la existencia.');
        $this->redirigir('/pedidos/detalle?id=' . $pedido->getIdPedido());
    }

    public function pagoRecibido(): void
    {
        $pedido = $this->accion(fn(Pedido $pedido) => $this->pedidoRepositorio->marcarPagoRecibido($pedido), 'marcar el pago como recibido');
        Mensaje::exito('Listo, marcó que le llegó el SINPE del pedido #' . $pedido->getIdPedido() . '. Ya puede entregarlo.');
        $this->redirigir('/pedidos/detalle?id=' . $pedido->getIdPedido());
    }

    public function pagoConProblema(): void
    {
        $this->verificarCsrf('/pedidos');
        $motivo = is_string($_POST['motivo'] ?? null) ? trim((string) preg_replace('/\s+/', ' ', $_POST['motivo'])) : '';
        $largo = mb_strlen($motivo, 'UTF-8');
        if ($largo < 5 || $largo > 300) {
            $id = is_string($_POST['id'] ?? null) && ctype_digit($_POST['id']) ? $_POST['id'] : '';
            Mensaje::error('Escriba qué pasó con el pago para que el cliente sepa (entre 5 y 300 caracteres).');
            $this->redirigir($id !== '' ? '/pedidos/detalle?id=' . $id : '/pedidos');
        }

        $pedido = $this->accion(fn(Pedido $pedido) => $this->pedidoRepositorio->marcarPagoConProblema($pedido, $motivo), 'avisar del problema con el pago');
        Mensaje::exito('Le avisamos al cliente que no le llegó el SINPE. Puede subir otro comprobante; si sigue sin llegar, cancele el pedido.');
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