<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\SesionControlador;
use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    // Inicio (Damian lo completa con el panel segun el rol)
    $enrutador->get('/', [InicioControlador::class, 'panel']);


    // Autenticacion (Damian)
    $enrutador->get('/ingresar', [AutenticacionControlador::class, 'mostrarLogin']);
    $enrutador->post('/ingresar', [AutenticacionControlador::class, 'iniciarSesion']);
    $enrutador->post('/salir', [AutenticacionControlador::class, 'cerrarSesion']);

    // Mi perfil (Allison). Hay un solo SuperAdmin: sus datos se editan aqui.
    $enrutador->get('/perfil', [PerfilControlador::class, 'mostrar']);
    $enrutador->post('/perfil/actualizar', [PerfilControlador::class, 'actualizar']);
    $enrutador->get('/perfil/contrasena', [PerfilControlador::class, 'formularioContrasena']);
    $enrutador->post('/perfil/contrasena', [PerfilControlador::class, 'cambiarContrasena']);
    $enrutador->get('/fotos/perfil', [FotoControlador::class, 'mostrarPerfil']);

    // Vendedores (Mariana)

    // Sesiones (Damian)
    $enrutador->get('/sesiones', [SesionControlador::class, 'listar']);

};
