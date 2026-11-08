<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Acceso;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCotizacion;
use Aplicacion\Nucleo\EstadoCotizacion;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\CotizacionRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class MisCotizacionesControlador
{
    private const ENTREGA_VACIA = ['entrega' => '', 'direccion' => '', 'metodoPago' => '', 'nota' => ''];

    private CotizacionRepositorio $repositorio;

    public function __construct()
    {
        $this->repositorio = new CotizacionRepositorio();
    }

    public function nueva(string $enlace): void
    {
        $vendedor = $this->tiendaVisible((new VendedorRepositorio())->buscarPorEnlace($enlace));
        $this->exigirCuenta('/tienda/' . $enlace . '/cotizar');
        $this->exigirNoSerDuenio($vendedor);
        $this->mostrarSolicitud($vendedor, ['descripcion' => '', 'fotoPendiente' => null], [], null);
    }

    public function crear(): void
    {
        $vendedor = $this->tiendaVisible((new VendedorRepositorio())->buscarPorId($this->leerId($_POST['tienda'] ?? null)));
        $rutaFormulario = '/tienda/' . $vendedor->getTiendaEnlace() . '/cotizar';
        $this->exigirCuenta($rutaFormulario);
        $this->exigirNoSerDuenio($vendedor);
        $this->verificarCsrf($rutaFormulario);

        $datos = DatosCotizacion::leerSolicitud();
        $validador = new Validador();
        DatosCotizacion::validarSolicitud($validador, $datos);
        $foto = (new SubidaArchivo())->recibir('foto', SubidaArchivo::FOTO_COTIZACION, $validador);
        $datos['fotoPendiente'] = $foto;
        if ($foto === null && $validador->error('foto') === null) {
            $validador->agregarError('foto', DatosCotizacion::MENSAJE_FOTO);
        }
        if (!$validador->esValido()) {
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COTIZACION, $foto);
            $this->mostrarSolicitud($vendedor, $datos, $validador->errores(), null);
            return;
        }

        $idCliente = (new ClienteRepositorio())->asegurar((int) UsuarioActual::id());
        try {
            $id = $this->repositorio->crear($idCliente, (int) $vendedor->getIdVendedor(), $foto, $datos['descripcion']);
        } catch (Throwable $error) {
            error_log('Error al crear la cotización: ' . $error->getMessage());
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COTIZACION, $foto);
            Mensaje::error('No se pudo enviar la solicitud. Intente de nuevo.');
            $this->mostrarSolicitud($vendedor, $datos, [], null);
            return;
        }
        SubidaArchivo::olvidarPendiente(SubidaArchivo::FOTO_COTIZACION, $foto);

        Mensaje::exito('Solicitud enviada a ' . $vendedor->getTiendaNombre() . '. Cuando la tienda responda, la verá aquí.');
        $this->redirigir('/mis-cotizaciones/detalle?id=' . $id);
    }

    public function listar(): void
    {
        $idCliente = $this->idCliente();
        $estado = is_string($_GET['estado'] ?? null) && EstadoCotizacion::existe($_GET['estado']) ? $_GET['estado'] : '';

        $conteos = $this->repositorio->conteosPorEstado(['idCliente' => $idCliente]);
        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / CotizacionRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('MisCotizaciones/lista', [
            'cotizaciones' => $this->repositorio->listar(['idCliente' => $idCliente, 'estado' => $estado], $pagina),
            'estado' => $estado,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => CotizacionRepositorio::POR_PAGINA,
        ]);
    }

    public function detalle(): void
    {
        $this->mostrarDetalle($this->buscarCotizacion($_GET['id'] ?? null), self::ENTREGA_VACIA, []);
    }

    public function editar(): void
    {
        $cotizacion = $this->buscarCotizacion($_GET['id'] ?? null);
        $this->exigirEditable($cotizacion);
        $this->mostrarSolicitud(null, ['descripcion' => $cotizacion['descripcion'], 'fotoPendiente' => null], [], $cotizacion);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/mis-cotizaciones');
        $cotizacion = $this->buscarCotizacion($_POST['id'] ?? null);
        $this->exigirEditable($cotizacion);
        $volver = '/mis-cotizaciones/detalle?id=' . $cotizacion['id'];

        $datos = DatosCotizacion::leerSolicitud();
        $validador = new Validador();
        DatosCotizacion::validarSolicitud($validador, $datos);
        $foto = (new SubidaArchivo())->recibir('foto', SubidaArchivo::FOTO_COTIZACION, $validador);
        $datos['fotoPendiente'] = $foto;
        if (!$validador->esValido()) {
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COTIZACION, $foto);
            $this->mostrarSolicitud(null, $datos, $validador->errores(), $cotizacion);
            return;
        }

        try {
            $fotoAnterior = $this->repositorio->editarSolicitud($cotizacion['id'], $cotizacion['idCliente'], $datos['descripcion'], $foto);
        } catch (OperacionNoPermitida $error) {
            SubidaArchivo::olvidarPendiente(SubidaArchivo::FOTO_COTIZACION, $foto);
            SubidaArchivo::eliminarFotoCotizacion($foto);
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al editar la cotización: ' . $error->getMessage());
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COTIZACION, $foto);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarSolicitud(null, $datos, [], $cotizacion);
            return;
        }
        SubidaArchivo::olvidarPendiente(SubidaArchivo::FOTO_COTIZACION, $foto);
        SubidaArchivo::eliminarFotoCotizacion($fotoAnterior);

        Mensaje::exito('Solicitud actualizada');
        $this->redirigir($volver);
    }

    public function cancelar(): void
    {
        $this->cambiarEstado(
            fn(array $cotizacion): bool => $this->repositorio->cancelar($cotizacion['id'], $cotizacion['idCliente']),
            'Solicitud cancelada',
            DatosCotizacion::MENSAJE_NO_EDITABLE
        );
    }

    public function rechazar(): void
    {
        $this->cambiarEstado(
            fn(array $cotizacion): bool => $this->repositorio->rechazar($cotizacion['id'], $cotizacion['idCliente']),
            'Cotización rechazada',
            DatosCotizacion::MENSAJE_NO_DECIDIR
        );
    }

    public function aceptar(): void
    {
        $this->verificarCsrf('/mis-cotizaciones');
        $cotizacion = $this->buscarCotizacion($_POST['id'] ?? null);
        $volver = '/mis-cotizaciones/detalle?id=' . $cotizacion['id'];
        if ($cotizacion['estado'] !== EstadoCotizacion::RESPONDIDA) {
            Mensaje::error(DatosCotizacion::MENSAJE_NO_DECIDIR);
            $this->redirigir($volver);
        }

        $datos = DatosCotizacion::leerEntrega();
        $validador = new Validador();
        DatosCotizacion::validarEntrega($validador, $datos);
        if (!$validador->esValido()) {
            $this->mostrarDetalle($cotizacion, $datos, $validador->errores());
            return;
        }

        $precioVisto = is_string($_POST['precioVisto'] ?? null) ? Numero::leer($_POST['precioVisto']) : null;
        $entrega = [
            'entrega' => $datos['entrega'],
            'direccion' => $datos['entrega'] === FormaEntrega::ENVIO ? $datos['direccion'] : null,
            'metodoPago' => $datos['metodoPago'],
            'nota' => $datos['nota'] !== '' ? $datos['nota'] : null,
        ];

        try {
            $idPedido = $this->repositorio->aceptar($cotizacion['id'], $cotizacion['idCliente'], $entrega, (float) $precioVisto);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al aceptar la cotización: ' . $error->getMessage());
            Mensaje::error('No se pudo aceptar. Intente de nuevo.');
            $this->mostrarDetalle($cotizacion, $datos, []);
            return;
        }

        Mensaje::exito('Cotización aceptada. Su pedido #' . $idPedido . ' fue enviado a ' . $cotizacion['nombreTienda'] . '.');
        $this->redirigir('/mis-pedidos/detalle?id=' . $idPedido);
    }

    private function cambiarEstado(callable $accion, string $mensajeExito, string $mensajeFallo): void
    {
        $this->verificarCsrf('/mis-cotizaciones');
        $cotizacion = $this->buscarCotizacion($_POST['id'] ?? null);
        $volver = '/mis-cotizaciones/detalle?id=' . $cotizacion['id'];

        try {
            $hecho = $accion($cotizacion);
        } catch (Throwable $error) {
            error_log('Error al cambiar la cotización: ' . $error->getMessage());
            Mensaje::error('No se pudo completar la acción. Intente de nuevo.');
            $this->redirigir($volver);
        }

        if ($hecho) {
            Mensaje::exito($mensajeExito);
        } else {
            Mensaje::error($mensajeFallo);
        }
        $this->redirigir($volver);
    }

    private function exigirEditable(array $cotizacion): void
    {
        if ($cotizacion['estado'] !== EstadoCotizacion::ENVIADA) {
            Mensaje::error(DatosCotizacion::MENSAJE_NO_EDITABLE);
            $this->redirigir('/mis-cotizaciones/detalle?id=' . $cotizacion['id']);
        }
    }

    private function tiendaVisible(?Vendedor $vendedor): Vendedor
    {
        if ($vendedor === null || !$vendedor->getTiendaActiva() || !$vendedor->getEstado()) {
            ErrorControlador::noEncontrado();
        }
        return $vendedor;
    }

    private function exigirCuenta(string $destino): void
    {
        if (!UsuarioActual::haySesion()) {
            Acceso::guardarDestino($destino);
            Mensaje::advertencia('Para pedir una cotización, ingrese o cree su cuenta.');
            $this->redirigir('/ingresar');
        }
        Permiso::exigir('misCotizaciones.ver');
    }

    private function exigirNoSerDuenio(Vendedor $vendedor): void
    {
        if (Carrito::esPropia($vendedor)) {
            Mensaje::advertencia('No puede pedir una cotización a su propia tienda.');
            $this->redirigir('/tienda/' . $vendedor->getTiendaEnlace());
        }
    }

    private function idCliente(): int
    {
        Permiso::exigir('misCotizaciones.ver');
        return (new ClienteRepositorio())->asegurar((int) UsuarioActual::id());
    }

    private function buscarCotizacion(mixed $id): array
    {
        $idCliente = $this->idCliente();
        $cotizacion = is_string($id) && ctype_digit($id) ? $this->repositorio->buscarParaCliente((int) $id, $idCliente) : null;
        if ($cotizacion === null) {
            ErrorControlador::noEncontrado();
        }
        return $cotizacion;
    }

    private function mostrarSolicitud(?Vendedor $vendedor, array $datos, array $errores, ?array $cotizacion): void
    {
        $this->mostrarVista('MisCotizaciones/solicitud', [
            'tiendaVista' => $vendedor,
            'cotizacion' => $cotizacion,
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    private function mostrarDetalle(array $cotizacion, array $datos, array $errores): void
    {
        $this->mostrarVista('MisCotizaciones/detalle', [
            'cotizacion' => $cotizacion,
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    private function leerId(mixed $valor): int
    {
        return is_string($valor) && ctype_digit($valor) ? (int) $valor : 0;
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