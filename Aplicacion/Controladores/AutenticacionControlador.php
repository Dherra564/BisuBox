<?php
namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Sesion;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\ManejadorSesion;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\SesionRepositorio;
use Aplicacion\Repositorios\SuperAdminRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;

class AutenticacionControlador
{
    private const MENSAJE_FALLO       = 'Correo o contraseña incorrectos';
    private const MENSAJE_DESACTIVADA = 'Su cuenta está desactivada. Comuníquese con el administrador.';

    // POST /ingresar
    public function iniciarSesion(): void
    {
        // 1. Token CSRF
        if (! Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/ingresar');
        }

        $correo     = strtolower(trim($_POST['correo'] ?? ''));
        $contrasena = $_POST['contrasena'] ?? '';

        if ($correo === '') {
            Mensaje::error('Ingrese su correo');
            $this->redirigir('/ingresar');
        }
        if ($contrasena === '') {
            Mensaje::error('Ingrese su contraseña');
            $this->redirigir('/ingresar');
        }

        $usuario = (new UsuarioRepositorio())->verificarCredenciales($correo, $contrasena);
        if ($usuario === null) {
            Mensaje::error(self::MENSAJE_FALLO);
            $this->redirigir('/ingresar');
        }

        if (! $usuario->getEstado()) {
            Mensaje::error(self::MENSAJE_DESACTIVADA);
            $this->redirigir('/ingresar');
        }

        $rol = $this->descubrirRol($usuario->getIdUsuario());
        if ($rol === null) {
            Mensaje::error('Su cuenta no tiene un rol asignado. Comuníquese con el administrador.');
            $this->redirigir('/ingresar');
        }
        if (! $rol['activo']) {
            Mensaje::error(self::MENSAJE_DESACTIVADA);
            $this->redirigir('/ingresar');
        }

        ManejadorSesion::regenerarId();

        $repositorioSesion = new SesionRepositorio();
        // Opcional: cierra sesiones viejas que quedaron abiertas (por ejemplo, si cerró el navegador)
        // $repositorioSesion->cerrarTodasDeUsuario($usuario->getIdUsuario());

        $sesion = new Sesion(
            idUsuario: $usuario->getIdUsuario(),
            tipoUsuario: $rol['tipo']
        );
        $repositorioSesion->registrarInicio($sesion);

        ManejadorSesion::guardarIdSesionBd($sesion->getIdSesion());
        ManejadorSesion::registrarActividad();
        UsuarioActual::iniciar($usuario->getIdUsuario(), $rol['tipo'], $usuario->getNombreCompleto(), $usuario->getFotoPerfil());

        $this->redirigir('/');
    }

    // POST /salir
    public function cerrarSesion(): void
    {
        if (! Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/');
        }

        $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
        if ($idSesionBd !== null) {
            (new SesionRepositorio())->cerrarPorId($idSesionBd);
        }

        UsuarioActual::cerrar();
        ManejadorSesion::destruir();
        // Sesión nueva y vacía, con su propia cookie, solo para mostrar el mensaje en el login
        ManejadorSesion::arrancar();
        ManejadorSesion::regenerarId();
        Mensaje::exito('Sesión cerrada correctamente');

        ManejadorSesion::arrancar();
        Mensaje::exito('Sesión cerrada correctamente');
        $this->redirigir('/ingresar');
    }

    private function descubrirRol(int $idUsuario): ?array
    {
        $superAdmin = (new SuperAdminRepositorio())->buscarPorIdUsuario($idUsuario);
        if ($superAdmin !== null) {
            return ['tipo' => Sesion::TIPO_SUPERADMIN, 'activo' => $superAdmin->getEstadoSuperAdmin()];
        }

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario($idUsuario);
        if ($vendedor !== null) {
            return ['tipo' => Sesion::TIPO_VENDEDOR, 'activo' => $vendedor->getEstadoVendedor()];
        }

        return null;
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl', ''), '/') . $ruta);
        exit;
    }

    private function mostrarVista(string $vista): void
    {
        $mensajes = Mensaje::obtener();
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/' . $vista . '.php';
    }

    // GET /ingresar
    public function mostrarLogin(): void
    {
        // Si ya inició sesión, no tiene sentido ver el login
        if (UsuarioActual::haySesion()) {
            $this->redirigir('/');
        }

        $this->mostrarVista('Autenticacion/ingresar');
    }
}
