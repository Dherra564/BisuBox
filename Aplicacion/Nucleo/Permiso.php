<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Controladores\ErrorControlador;
use Aplicacion\Modelos\Sesion;
use Aplicacion\Repositorios\SesionRepositorio;
use Configuracion\Configuracion;

class Permiso
{
    private const PERMISOS = [
        'panel.ver' => [Sesion::TIPO_SUPERADMIN, Sesion::TIPO_VENDEDOR],
        'perfil.ver' => [Sesion::TIPO_SUPERADMIN, Sesion::TIPO_VENDEDOR],
        'vendedores.gestionar' => [Sesion::TIPO_SUPERADMIN],
        'sesiones.ver' => [Sesion::TIPO_SUPERADMIN],
        'ayuda.ver' => [Sesion::TIPO_SUPERADMIN, Sesion::TIPO_VENDEDOR],
    ];

    public static function puede(string $permiso): bool
    {
        $tipo = UsuarioActual::tipo();

        return $tipo !== null
            && in_array($tipo, self::PERMISOS[$permiso] ?? [], true);
    }

    public static function exigirSesion(): void
    {
        if (!UsuarioActual::haySesion()) {
            Mensaje::advertencia('Inicie sesión para continuar.');
            self::redirigir('/ingresar');
        }

        if (ManejadorSesion::haExpirado()) {
            self::cerrarYAvisar('Su sesión se cerró por inactividad. Ingrese de nuevo.');
        }

        $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
        if ($idSesionBd !== null && !(new SesionRepositorio())->estaAbierta($idSesionBd)) {
            self::cerrarYAvisar('Su sesión ya no está activa. Ingrese de nuevo.');
        }

        ManejadorSesion::registrarActividad();
    }

    public static function exigir(string $permiso): void
    {
        self::exigirSesion();

        if (!self::puede($permiso)) {
            self::denegar();
        }
    }

    private static function cerrarYAvisar(string $aviso): never
    {
        $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
        if ($idSesionBd !== null) {
            (new SesionRepositorio())->cerrarPorId($idSesionBd);
        }

        UsuarioActual::cerrar();
        ManejadorSesion::destruir();
        ManejadorSesion::arrancar();
        ManejadorSesion::regenerarId();

        Mensaje::advertencia($aviso);
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