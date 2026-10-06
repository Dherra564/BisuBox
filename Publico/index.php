<?php

use Aplicacion\Controladores\ErrorControlador;
use Aplicacion\Nucleo\Enrutador;
use Aplicacion\Nucleo\ManejadorSesion;
use Configuracion\Configuracion;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    Configuracion::cargar();

    ManejadorSesion::arrancar();
    ManejadorSesion::enviarEncabezadosSinCache();

    $enrutador = new Enrutador();
    $registrarRutas = require dirname(__DIR__) . '/Configuracion/Rutas.php';
    $registrarRutas($enrutador);

    $enrutador->despachar();
} catch (Throwable $error) {
    error_log($error->getMessage() . ' en ' . $error->getFile() . ':' . $error->getLine());

    try {
        ErrorControlador::errorInterno($error->getMessage());
    } catch (Throwable) {
        http_response_code(500);
        echo 'Ocurrió un error. Intente de nuevo más tarde.';
    }
}