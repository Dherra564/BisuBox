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

class AutenticacionControlador
{
    private const MENSAJE_FALLO = 'Correo o contraseña incorrectos';
    private const MENSAJE_DESACTIVADA = 'Su cuenta está desactivada. Comuníquese con el administrador.';

    // POST /ingresar
    public function iniciarSesion(): void
    {
        // 1. Token CSRF
        if (!Csrf::validar()) {                                   // ASUMIDO
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');   // ASUMIDO
            $this->redirigir('/ingresar');
        }

        // 2. Campos vacíos (el correo se normaliza a minúsculas)
        $correo = strtolower(trim($_POST['correo'] ?? ''));
        $contrasena = $_POST['contrasena'] ?? '';

        if ($correo === '') {
            Mensaje::error('Ingrese su correo');
            $this->redirigir('/ingresar');
        }
        if ($contrasena === '') {
            Mensaje::error('Ingrese su contraseña');
            $this->redirigir('/ingresar');
        }

        // 3. Credenciales: mensaje genérico, sin decir cuál de los dos falló
        $usuario = (new UsuarioRepositorio())->verificarCredenciales($correo, $contrasena);   // ASUMIDO: devuelve ?Usuario
        if ($usuario === null) {
            Mensaje::error(self::MENSAJE_FALLO);
            $this->redirigir('/ingresar');
        }

        // 4. Usuario activo
        if (!$usuario->getEstado()) {
            Mensaje::error(self::MENSAJE_DESACTIVADA);
            $this->redirigir('/ingresar');
        }

        // 5. Rol (un usuario tiene un solo rol) y rol activo
        $rol = $this->descubrirRol($usuario->getIdUsuario());
        if ($rol === null) {
            Mensaje::error('Su cuenta no tiene un rol asignado. Comuníquese con el administrador.');
            $this->redirigir('/ingresar');
        }
        if (!$rol['activo']) {
            Mensaje::error(self::MENSAJE_DESACTIVADA);
            $this->redirigir('/ingresar');
        }

        // 6. Todo correcto: nueva sesión de PHP y registro en tbsesion
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
        UsuarioActual::iniciar($usuario->getIdUsuario(), $rol['tipo'], $usuario->getNombreCompleto());   // ASUMIDO

        $this->redirigir('/');
    }

    // POST /salir
    public function cerrarSesion(): void
    {
        if (!Csrf::validar()) {                                   // ASUMIDO
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/');
        }

        // Guarda la fecha de cierre en tbsesion
        $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
        if ($idSesionBd !== null) {
            (new SesionRepositorio())->cerrarPorId($idSesionBd);
        }

        UsuarioActual::cerrar();                                  // ASUMIDO
        ManejadorSesion::destruir();

        // Se arranca una sesión nueva y vacía solo para poder mostrar el mensaje
        ManejadorSesion::arrancar();
        Mensaje::exito('Sesión cerrada correctamente');           // ASUMIDO
        $this->redirigir('/ingresar');
    }

    // Busca si el usuario es SuperAdmin o Vendedor y si ese rol está activo
    private function descubrirRol(int $idUsuario): ?array
    {
        $superAdmin = (new SuperAdminRepositorio())->buscarPorIdUsuario($idUsuario);
        if ($superAdmin !== null) {
            return ['tipo' => Sesion::TIPO_SUPERADMIN, 'activo' => $superAdmin->getEstadoSuperAdmin()];
        }

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario($idUsuario);   // ASUMIDO (es de Mariana)
        if ($vendedor !== null) {
            return ['tipo' => Sesion::TIPO_VENDEDOR, 'activo' => $vendedor->getEstadoVendedor()];
        }

        return null;
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . $ruta);                             // ASUMIDO: Allison puede tener su propio redirigir()
        exit;
    }
}