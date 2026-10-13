<?php

use Aplicacion\Controladores\ErrorControlador;
use Aplicacion\Nucleo\Enrutador;
use Aplicacion\Nucleo\ManejadorSesion;
use Configuracion\Configuracion;

spl_autoload_register(function (string $clase): void {
    $archivo = dirname(__DIR__) . '/' . str_replace('\\', '/', $clase) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

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