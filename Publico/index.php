<?php


use Aplicacion\Nucleo\Enrutador;
use Configuracion\Configuracion;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
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
    http_response_code(500);

    if (class_exists(Configuracion::class) && Configuracion::esDesarrollo()) {
        echo 'Error: ' . htmlspecialchars($error->getMessage());
    } else {
        
        echo 'Ocurrió un error. Intente de nuevo más tarde.';
    }
}