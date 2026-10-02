<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    // Prueba temporal: confirma que el enrutador funciona. Se borra cuando exista el panel de inicio.
    $enrutador->get('/', function (): void {
        echo 'BisuBox funcionando';
    });

    $enrutador->post('/ingresar', [AutenticacionControlador::class, 'iniciarSesion']);
    $enrutador->post('/salir', [AutenticacionControlador::class, 'cerrarSesion']);

};
