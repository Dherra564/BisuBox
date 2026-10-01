<?php


use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    // Prueba temporal: confirma que el enrutador funciona. Se borra cuando exista el panel de inicio.
    $enrutador->get('/', function (): void {
        echo 'BisuBox funcionando';
    });

};