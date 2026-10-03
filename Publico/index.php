<?php

use Aplicacion\Controladores\ErrorControlador;
use Aplicacion\Nucleo\Enrutador;
use Aplicacion\Nucleo\ManejadorSesion;
use Configuracion\Configuracion;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    ManejadorSesion::arrancar();
    ManejadorSesion::enviarEncabezadosSinCache();
    Configuracion::cargar();

    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }

    $enrutador = new Enrutador();
    $registrarRutas = require dirname(__DIR__) . '/Configuracion/Rutas.php';
    $registrarRutas($enrutador);

    $enrutador->despachar();
} catch (Throwable $error) {
    error_log($error->getMessage() . ' en ' . $error->getFile() . ':' . $error->getLine());

    try {
        // Muestra la página de error con estilos; el detalle solo sale en modo desarrollo
        ErrorControlador::errorInterno($error->getMessage());
    } catch (Throwable) {
        // Último recurso: si hasta la página de error falla, texto simple
        http_response_code(500);
        echo 'Ocurrió un error. Intente de nuevo más tarde.';
    }
}