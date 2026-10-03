<?php
namespace Aplicacion\Nucleo;

use Aplicacion\Controladores\ErrorControlador;
use Aplicacion\Modelos\Sesion;
use Aplicacion\Nucleo\ManejadorSesion;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\SesionRepositorio;
use Configuracion\Configuracion;

/**
 * Quién puede hacer qué. Se usa al inicio de cada acción de un controlador:
 *   Permiso::exigir('vendedores.gestionar');
 * y en las vistas, para mostrar solo lo que el rol permite:
 *   if (Permiso::puede('sesiones.ver')) { ... }
 */
class Permiso
{
    // permiso => roles que lo tienen
    private const PERMISOS = [
        'panel.ver'            => [Sesion::TIPO_SUPERADMIN, Sesion::TIPO_VENDEDOR],
        'perfil.ver'           => [Sesion::TIPO_SUPERADMIN, Sesion::TIPO_VENDEDOR],
        'vendedores.gestionar' => [Sesion::TIPO_SUPERADMIN],
        'sesiones.ver'         => [Sesion::TIPO_SUPERADMIN],
    ];

    // ¿El usuario conectado tiene este permiso? (no redirige, solo responde)
    public static function puede(string $permiso): bool
    {
        $tipo = UsuarioActual::tipo();

        return $tipo !== null
        && in_array($tipo, self::PERMISOS[$permiso] ?? [], true);
    }

    // Exige sesión iniciada y no expirada. Si falla, redirige al login.
    public static function exigirSesion(): void
    {
        if (! UsuarioActual::haySesion()) {
            Mensaje::advertencia('Inicie sesión para continuar.');
            self::redirigir('/ingresar');
        }

        if (ManejadorSesion::haExpirado()) {
            self::cerrarPorInactividad();
        }

        // Cada petición válida reinicia el contador de los 30 minutos
        ManejadorSesion::registrarActividad();
    }

    // Exige sesión y además el permiso. Si no lo tiene, responde 403.
    public static function exigir(string $permiso): void
    {
        self::exigirSesion();

        if (! self::puede($permiso)) {
            self::denegar();
        }
    }

    private static function cerrarPorInactividad(): never
    {
        // Mismo orden que en AutenticacionControlador::cerrarSesion()
        $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
        if ($idSesionBd !== null) {
            (new SesionRepositorio())->cerrarPorId($idSesionBd);
        }

        UsuarioActual::cerrar();
        ManejadorSesion::destruir();
        // Sesión nueva y vacía, con su propia cookie, solo para mostrar el aviso en el login
        ManejadorSesion::arrancar();
        ManejadorSesion::regenerarId();

        Mensaje::advertencia('Su sesión se cerró por inactividad. Ingrese de nuevo.');
        self::redirigir('/ingresar');
    }

    private static function denegar(): never
    {
        ErrorControlador::prohibido();
    }

    private static function redirigir(string $ruta): never
    {
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl', ''), '/') . $ruta);
        exit;
    }
}
