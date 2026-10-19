<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosPago;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\PersonalizadoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class MisPedidosControlador
{
    private PedidoRepositorio $pedidoRepositorio;
    private int $idCliente;

    public function __construct()
    {
        Permiso::exigir('misPedidos.ver');
        $this->pedidoRepositorio = new PedidoRepositorio();
        $this->idCliente = (new ClienteRepositorio())->asegurar((int) UsuarioActual::id());
    }

    public function listar(): void
    {
        $idCliente = $this->idCliente;
        $estado = EstadoPedido::existe($_GET['estado'] ?? null) ? $_GET['estado'] : '';

        $conteos = ['' => $this->pedidoRepositorio->contar(['idCliente' => $idCliente])];
        foreach (EstadoPedido::todos() as $codigo) {
            $conteos[$codigo] = $this->pedidoRepositorio->contar(['idCliente' => $idCliente, 'estado' => $codigo]);
        }

        $filtros = ['idCliente' => $idCliente, 'estado' => $estado];
        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / PedidoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('MisPedidos/lista', [
            'pedidos' => $this->pedidoRepositorio->listar($filtros, $pagina),
            'estado' => $estado,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => PedidoRepositorio::POR_PAGINA,
            'porDecidir' => (new PersonalizadoRepositorio())->contar(['idCliente' => $idCliente, 'estado' => EstadoPersonalizado::COTIZADO]),
        ]);
    }

    public function detalle(): void
    {
        $pedido = $this->buscarPedido($_GET['id'] ?? null);

        $this->mostrarDetalle($pedido, ['referencia' => '', 'comprobantePendiente' => null], []);
    }

    public function reenviarPago(): void
    {
        $this->verificarCsrf('/mis-pedidos');
        $pedido = $this->buscarPedido($_POST['id'] ?? null);
        $volver = '/mis-pedidos/detalle?id=' . $pedido->getIdPedido();

        $validador = new Validador();
        $referencia = is_string($_POST['referencia'] ?? null) ? trim($_POST['referencia']) : '';
        $comprobante = (new SubidaArchivo())->recibir('comprobante', SubidaArchivo::FOTO_COMPROBANTE, $validador);
        DatosPago::validarComprobante($validador, $referencia, $comprobante);
        $datos = ['referencia' => $referencia, 'comprobantePendiente' => $comprobante];
        if (!$validador->esValido()) {
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COMPROBANTE, $comprobante);
            $this->mostrarDetalle($pedido, $datos, $validador->errores());
            return;
        }

        try {
            $anterior = $this->pedidoRepositorio->reenviarComprobante($pedido, (string) $comprobante, $referencia);
        } catch (OperacionNoPermitida $error) {
            SubidaArchivo::eliminarComprobante($comprobante);
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al reenviar el comprobante: ' . $error->getMessage());
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COMPROBANTE, $comprobante);
            Mensaje::error('No se pudo enviar el comprobante. Intente de nuevo.');
            $this->mostrarDetalle($pedido, $datos, []);
            return;
        }

        SubidaArchivo::olvidarPendiente(SubidaArchivo::FOTO_COMPROBANTE, $comprobante);
        SubidaArchivo::eliminarComprobante($anterior);
        Mensaje::exito('Listo, le enviamos el nuevo comprobante a la tienda para que lo revise.');
        $this->redirigir($volver);
    }

    private function mostrarDetalle(Pedido $pedido, array $datos, array $errores): void
    {
        $vendedor = (new VendedorRepositorio())->buscarPorId($pedido->getIdVendedor());

        $this->mostrarVista('MisPedidos/detalle', [
            'pedido' => $pedido,
            'contactos' => $vendedor !== null ? $vendedor->getContactos() : [],
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    public function cancelar(): void
    {
        $this->verificarCsrf('/mis-pedidos');
        $pedido = $this->buscarPedido($_POST['id'] ?? null);
        $volver = '/mis-pedidos/detalle?id=' . $pedido->getIdPedido();

        try {
            $this->pedidoRepositorio->cancelar($pedido, Rol::CLIENTE);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al cancelar el pedido: ' . $error->getMessage());
            Mensaje::error('No se pudo cancelar. Intente de nuevo.');
            $this->redirigir($volver);
        }

        Mensaje::exito('Pedido #' . $pedido->getIdPedido() . ' cancelado.');
        $this->redirigir($volver);
    }

    private function buscarPedido(mixed $id): Pedido
    {
        $pedido = is_string($id) && ctype_digit($id)
            ? $this->pedidoRepositorio->buscarParaCliente((int) $id, $this->idCliente)
            : null;

        if ($pedido === null) {
            Mensaje::error('No se encontró ese pedido.');
            $this->redirigir('/mis-pedidos');
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