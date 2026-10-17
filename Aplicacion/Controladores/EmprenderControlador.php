<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Usuario;
use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosTienda;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\TipoContacto;
use Aplicacion\Nucleo\TipoIdentificacion;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class EmprenderControlador
{
    private UsuarioRepositorio $usuarioRepositorio;
    private Usuario $usuario;

    public function __construct()
    {
        Permiso::exigir('emprender');
        $this->usuarioRepositorio = new UsuarioRepositorio();

        $usuario = $this->usuarioRepositorio->buscarPorId((int) UsuarioActual::id());
        if ($usuario === null) {
            Mensaje::error('No se encontró su cuenta.');
            $this->redirigir('/');
        }
        $this->usuario = $usuario;
    }

    public function mostrar(): void
    {
        $this->mostrarFormulario([
            'tipoIdentificacion' => $this->usuario->getTipoIdentificacion() ?? TipoIdentificacion::CEDULA,
            'numeroIdentificacion' => (string) $this->usuario->getNumeroIdentificacion(),
            'tiendaNombre' => '',
            'tiendaDescripcion' => '',
            'contactos' => [['tipo' => TipoContacto::WHATSAPP, 'valor' => (string) $this->usuario->getNumeroTelefonico()]],
        ], []);
    }

    public function guardar(): void
    {
        $this->verificarCsrf('/emprender');

        $datos = DatosTienda::leer();
        $datos['tipoIdentificacion'] = is_string($_POST['tipoIdentificacion'] ?? null) ? trim($_POST['tipoIdentificacion']) : '';
        $datos['numeroIdentificacion'] = TipoIdentificacion::limpiar(is_string($_POST['numeroIdentificacion'] ?? null) ? trim($_POST['numeroIdentificacion']) : '');

        $validador = new Validador();
        $validador->tipoIdentificacion('tipoIdentificacion', $datos['tipoIdentificacion']);
        $validador->requerido('numeroIdentificacion', $datos['numeroIdentificacion'], 'Ingrese la identificación')
            ->identificacion('numeroIdentificacion', $datos['tipoIdentificacion'], $datos['numeroIdentificacion']);
        if (
            $validador->error('numeroIdentificacion') === null
            && $this->usuarioRepositorio->existeIdentificacion($datos['numeroIdentificacion'], (int) $this->usuario->getIdUsuario())
        ) {
            $validador->agregarError('numeroIdentificacion', 'Ya existe una cuenta con esta identificación');
        }
        DatosTienda::validar($validador, $datos);

        $nombreLogo = (new SubidaArchivo())->recibir('tiendaLogo', SubidaArchivo::LOGO, $validador);
        $datos['tiendaLogoPendiente'] = $nombreLogo;
        if (!$validador->esValido()) {
            SubidaArchivo::recordarPendiente(SubidaArchivo::LOGO, $nombreLogo);
            $this->mostrarFormulario($datos, $validador->errores());
            return;
        }

        $vendedorRepositorio = new VendedorRepositorio();
        $vendedor = new Vendedor(
            idUsuario: $this->usuario->getIdUsuario(),
            tipoIdentificacion: $datos['tipoIdentificacion'],
            numeroIdentificacion: $datos['numeroIdentificacion'],
            nombreCompleto: $this->usuario->getNombreCompleto(),
            fotoPerfil: $this->usuario->getFotoPerfil(),
            correoUsuario: $this->usuario->getCorreoUsuario(),
            numeroTelefonico: $this->usuario->getNumeroTelefonico(),
            fechaRegistro: $this->usuario->getFechaRegistro(),
            tiendaNombre: $datos['tiendaNombre'],
            tiendaEnlace: $vendedorRepositorio->generarEnlace($datos['tiendaNombre']),
            tiendaDescripcion: $datos['tiendaDescripcion'],
            tiendaLogo: $nombreLogo,
            contactos: DatosTienda::crearContactos($datos['contactos'])
        );

        try {
            $vendedorRepositorio->abrirTienda($vendedor);
        } catch (Throwable $error) {
            error_log('Error al abrir la tienda de un cliente: ' . $error->getMessage());
            SubidaArchivo::recordarPendiente(SubidaArchivo::LOGO, $nombreLogo);
            Mensaje::error('No se pudo abrir su tienda. Intente de nuevo.');
            $this->mostrarFormulario($datos, []);
            return;
        }

        SubidaArchivo::olvidarPendiente(SubidaArchivo::LOGO, $nombreLogo);
        UsuarioActual::cambiarRol(Rol::VENDEDOR);
        Mensaje::exito('¡Su tienda quedó abierta! Así la ven sus clientes. Agregue sus productos desde el menú.');
        $this->redirigir('/tienda/' . $vendedor->getTiendaEnlace());
    }

    private function mostrarFormulario(array $datos, array $errores): void
    {
        $this->mostrarVista('Emprender/formulario', [
            'usuario' => $this->usuario,
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