<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\AyudaControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\SesionControlador;
use Aplicacion\Controladores\UsuarioControlador;
use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    $enrutador->get('/', [InicioControlador::class, 'panel']);

    $enrutador->get('/ingresar', [AutenticacionControlador::class, 'mostrarLogin']);
    $enrutador->post('/ingresar', [AutenticacionControlador::class, 'iniciarSesion']);
    $enrutador->post('/salir', [AutenticacionControlador::class, 'cerrarSesion']);

    $enrutador->get('/perfil', [PerfilControlador::class, 'mostrar']);
    $enrutador->post('/perfil/actualizar', [PerfilControlador::class, 'actualizar']);
    $enrutador->get('/perfil/contrasena', [PerfilControlador::class, 'formularioContrasena']);
    $enrutador->post('/perfil/contrasena', [PerfilControlador::class, 'cambiarContrasena']);
    $enrutador->get('/fotos/perfil', [FotoControlador::class, 'mostrarPerfil']);

    $enrutador->get('/usuarios', [UsuarioControlador::class, 'listar']);
    $enrutador->get('/usuarios/detalle', [UsuarioControlador::class, 'detalle']);
    $enrutador->get('/usuarios/nuevo', [UsuarioControlador::class, 'nuevo']);
    $enrutador->post('/usuarios/crear', [UsuarioControlador::class, 'crear']);
    $enrutador->get('/usuarios/editar', [UsuarioControlador::class, 'editar']);
    $enrutador->post('/usuarios/actualizar', [UsuarioControlador::class, 'actualizar']);
    $enrutador->post('/usuarios/estado', [UsuarioControlador::class, 'cambiarEstado']);

    $enrutador->get('/ayuda', [AyudaControlador::class, 'mostrar']);

    $enrutador->get('/sesiones', [SesionControlador::class, 'listar']);
};