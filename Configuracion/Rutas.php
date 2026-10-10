<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\RegistroControlador;
use Aplicacion\Controladores\SesionControlador;
use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    $enrutador->get('/', [InicioControlador::class, 'panel']);

    $enrutador->get('/ingresar', [AutenticacionControlador::class, 'mostrarLogin']);
    $enrutador->post('/ingresar', [AutenticacionControlador::class, 'iniciarSesion']);
    $enrutador->post('/salir', [AutenticacionControlador::class, 'cerrarSesion']);

    $enrutador->get('/registro/vendedor', [RegistroControlador::class, 'mostrarVendedor']);
    $enrutador->post('/registro/vendedor', [RegistroControlador::class, 'registrarVendedor']);
    $enrutador->get('/registro/cliente', [RegistroControlador::class, 'mostrarCliente']);
    $enrutador->post('/registro/cliente', [RegistroControlador::class, 'registrarCliente']);

    $enrutador->get('/perfil', [PerfilControlador::class, 'mostrar']);
    $enrutador->post('/perfil/actualizar', [PerfilControlador::class, 'actualizar']);
    $enrutador->get('/perfil/contrasena', [PerfilControlador::class, 'formularioContrasena']);
    $enrutador->post('/perfil/contrasena', [PerfilControlador::class, 'cambiarContrasena']);
    $enrutador->get('/fotos/perfil', [FotoControlador::class, 'mostrarPerfil']);
    $enrutador->get('/fotos/logo', [FotoControlador::class, 'mostrarLogo']);

    $enrutador->get('/sesiones', [SesionControlador::class, 'listar']);
};