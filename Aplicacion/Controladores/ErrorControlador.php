<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\UsuarioActual;
use Configuracion\Configuracion;


class ErrorControlador
{
    public static function noEncontrado(): never
    {
        self::mostrar(404, 'Página no encontrada', 'La página que busca no existe o fue movida.');
    }

    public static function prohibido(): never
    {
        self::mostrar(403, 'Acceso denegado', 'No tiene permiso para ver esta página.');
    }

    public static function metodoNoPermitido(): never
    {
        self::mostrar(405, 'Acción no permitida', 'Esta dirección no acepta esa acción. Vuelva al inicio e intente de nuevo.');
    }


    public static function errorInterno(?string $detalle = null): never
    {
        self::mostrar(
            500,
            'Ocurrió un error',
            'Algo salió mal de nuestro lado. Intente de nuevo más tarde.',
            Configuracion::esDesarrollo() ? $detalle : null
        );
    }

    private static function mostrar(int $codigo, string $titulo, string $mensaje, ?string $detalle = null): never
    {
        http_response_code($codigo);

        $urlBase = self::urlBase();
        $haySesion = UsuarioActual::haySesion();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Error/error.php';
        exit;
    }

    private static function urlBase(): string
    {
        $url = Configuracion::obtener('appUrl');
        if ($url !== null && $url !== '') {
            return rtrim($url, '/');
        }

        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    }
}