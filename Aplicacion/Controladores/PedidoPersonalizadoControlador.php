<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Acceso;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\TipoPedido;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;

class PedidoPersonalizadoControlador
{
    private VendedorRepositorio $vendedorRepositorio;

    public function __construct()
    {
        if (UsuarioActual::haySesion()) {
            Permiso::exigir('carrito.usar');
        }
        $this->vendedorRepositorio = new VendedorRepositorio();
    }

    public function nuevo(string $enlace): void
    {
        $vendedor = $this->tiendaVisible($enlace);
        $this->exigirCuenta($enlace);
        $this->exigirNoPropia($vendedor, $enlace);

        $this->mostrarFormulario($vendedor, [
            'titulo' => '',
            'descripcion' => '',
            'fechaDeseada' => '',
            'entrega' => '',
            'direccion' => '',
            'metodoPago' => '',
        ], []);
    }

    public function crear(string $enlace): void
    {
        $vendedor = $this->tiendaVisible($enlace);
        $this->exigirCuenta($enlace);
        $this->exigirNoPropia($vendedor, $enlace);
        $this->verificarCsrf('/tienda/' . $enlace . '/personalizado');

        $datos = $this->leerDatos();
        $validador = $this->validarDatos($datos);
        if (!$validador->esValido()) {
            $this->mostrarFormulario($vendedor, $datos, $validador->errores());
            return;
        }

        $idCliente = (new ClienteRepositorio())->asegurar((int) UsuarioActual::id());

        $pedido = new Pedido(
            null,
            (int) $vendedor->getIdVendedor(),
            $idCliente,
            new DateTime(),
            $datos['entrega'],
            $datos['entrega'] === FormaEntrega::ENVIO ? $datos['direccion'] : null,
            $datos['metodoPago'],
            null,
            0.0,
            EstadoPedido::SOLICITADO
        );
        $pedido->setTipo(TipoPedido::PERSONALIZADO);

        try {
            $idPedido = (new PedidoRepositorio())->crearPersonalizado(
                $pedido,
                $datos['titulo'],
                $datos['descripcion'],
                $datos['fechaDeseada'] !== '' ? $datos['fechaDeseada'] : null
            );
        } catch (Throwable $error) {
            error_log('Error al crear el pedido personalizado: ' . $error->getMessage());
            Mensaje::error('No se pudo enviar la solicitud. Intente de nuevo.');
            $this->mostrarFormulario($vendedor, $datos, []);
            return;
        }

        Mensaje::exito('Solicitud enviada a ' . $vendedor->getTiendaNombre()
            . '. Cuando la tienda la cotice, verá el precio aquí.');
        $this->redirigir('/mis-pedidos/detalle?id=' . $idPedido);
    }

    private function tiendaVisible(string $enlace): Vendedor
    {
        $vendedor = $this->vendedorRepositorio->buscarPorEnlace($enlace);
        if ($vendedor === null || !$vendedor->getTiendaActiva() || !$vendedor->getEstado()) {
            ErrorControlador::noEncontrado();
        }
        return $vendedor;
    }

    private function exigirCuenta(string $enlace): void
    {
        if (UsuarioActual::haySesion()) {
            return;
        }
        Acceso::guardarDestino('/tienda/' . $enlace . '/personalizado');
        Mensaje::advertencia('Para pedir algo personalizado, ingrese o cree su cuenta.');
        $this->redirigir('/ingresar');
    }

    private function exigirNoPropia(Vendedor $vendedor, string $enlace): void
    {
        if (Carrito::esPropia($vendedor)) {
            Mensaje::advertencia('Esta es su propia tienda, no puede hacerse pedidos a usted mismo.');
            $this->redirigir('/tienda/' . $enlace);
        }
    }

    private function leerDatos(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'titulo' => (string) preg_replace('/\s+/', ' ', $leer('titulo')),
            'descripcion' => $leer('descripcion'),
            'fechaDeseada' => $leer('fechaDeseada'),
            'entrega' => $leer('entrega'),
            'direccion' => (string) preg_replace('/[ \t]+/', ' ', $leer('direccion')),
            'metodoPago' => $leer('metodoPago'),
        ];
    }

    private function validarDatos(array $datos): Validador
    {
        $validador = new Validador();

        $validador->requerido('titulo', $datos['titulo'], 'Escriba qué es lo que quiere')
            ->longitud('titulo', $datos['titulo'], 3, 60, 'El título debe tener entre 3 y 60 caracteres');
        $validador->requerido('descripcion', $datos['descripcion'], 'Cuéntele a la tienda cómo lo quiere')
            ->longitud('descripcion', $datos['descripcion'], 10, 500, 'La descripción debe tener entre 10 y 500 caracteres');

        if ($datos['fechaDeseada'] !== '') {
            $fecha = DateTime::createFromFormat('Y-m-d', $datos['fechaDeseada']);
            if ($fecha === false || $fecha->format('Y-m-d') !== $datos['fechaDeseada']) {
                $validador->agregarError('fechaDeseada', 'Escoja una fecha válida');
            } elseif ($fecha < new DateTime('today')) {
                $validador->agregarError('fechaDeseada', 'La fecha no puede ser anterior a hoy');
            }
        }

        if (!FormaEntrega::existe($datos['entrega'])) {
            $validador->agregarError('entrega', 'Escoja cómo quiere recibir el pedido');
        }
        if ($datos['entrega'] === FormaEntrega::ENVIO) {
            $validador->requerido('direccion', $datos['direccion'], 'Escriba la dirección de entrega')
                ->longitud('direccion', $datos['direccion'], 10, 250, 'La dirección debe tener entre 10 y 250 caracteres')
                ->direccion('direccion', $datos['direccion']);
        }
        if (!MetodoPago::existe($datos['metodoPago'])) {
            $validador->agregarError('metodoPago', 'Escoja cómo va a pagar');
        }

        return $validador;
    }

    private function mostrarFormulario(Vendedor $vendedor, array $datos, array $errores): void
    {
        $clienteRepositorio = new ClienteRepositorio();
        $clienteRepositorio->asegurar((int) UsuarioActual::id());

        // CORREGIDO: la carpeta de la vista es PedidosPersonalizados (en plural)
        $this->mostrarVista('PedidosPersonalizados/formulario', [
            'vendedor' => $vendedor,
            'cliente' => $clienteRepositorio->buscarPorIdUsuario((int) UsuarioActual::id()),
            'datos' => $datos,
            'errores' => $errores,
        ]);
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