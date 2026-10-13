<?php
namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Acceso;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\ManejadorSesion;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\SesionRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;

class AutenticacionControlador
{
    private const MENSAJE_FALLO = 'Correo o contraseña incorrectos';
    private const MENSAJE_DESACTIVADA = 'Su cuenta está desactivada.';
    private const CLAVE_CORREO_INTENTO = 'correoIntento';

    public function iniciarSesion(): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/ingresar');
        }

        $correo = is_string($_POST['correo'] ?? null) ? strtolower(trim($_POST['correo'])) : '';
        $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';

        if ($correo === '') {
            Mensaje::error('Ingrese su correo');
            $this->redirigir('/ingresar');
        }
        $this->recordarCorreo($correo);

        if ($contrasena === '') {
            Mensaje::error('Ingrese su contraseña');
            $this->redirigir('/ingresar');
        }

        $usuario = (new UsuarioRepositorio())->verificarCredenciales($correo, $contrasena);
        if ($usuario === null) {
            Mensaje::error(self::MENSAJE_FALLO);
            $this->redirigir('/ingresar');
        }

        if (!$usuario->getEstado()) {
            Mensaje::error(self::MENSAJE_DESACTIVADA);
            $this->redirigir('/ingresar');
        }

        if (!Rol::existe($usuario->getRol())) {
            Mensaje::error('Su cuenta no tiene un rol asignado.');
            $this->redirigir('/ingresar');
        }

        unset($_SESSION[self::CLAVE_CORREO_INTENTO]);
        Acceso::abrirSesion($usuario);

        $this->redirigir('/');
    }

    public function cerrarSesion(): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/');
        }

        $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
        if ($idSesionBd !== null) {
            (new SesionRepositorio())->cerrarPorId($idSesionBd);
        }

        UsuarioActual::cerrar();
        ManejadorSesion::destruir();
        ManejadorSesion::arrancar();
        ManejadorSesion::regenerarId();
        Mensaje::exito('Sesión cerrada correctamente');
        $this->redirigir('/ingresar');
    }

    public function mostrarLogin(): void
    {
        if (UsuarioActual::haySesion()) {
            $this->redirigir('/');
        }

        $correoIntento = (string) ($_SESSION[self::CLAVE_CORREO_INTENTO] ?? '');
        unset($_SESSION[self::CLAVE_CORREO_INTENTO]);

        $this->mostrarVista('Autenticacion/ingresar', ['correoIntento' => $correoIntento]);
    }

    private function recordarCorreo(string $correo): void
    {
        $_SESSION[self::CLAVE_CORREO_INTENTO] = mb_substr($correo, 0, 150, 'UTF-8');
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . Configuracion::urlBase() . $ruta);
        exit;
    }

    private function mostrarVista(string $vista, array $variablesVista = []): void
    {
        $variablesVista['mensajes'] = Mensaje::obtener();
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/' . $vista . '.php';
    }
}