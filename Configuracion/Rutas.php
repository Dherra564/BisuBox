<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\VendedorControlador;
use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    // Inicio (Damian lo completa con el panel segun el rol)
    $enrutador->get('/', [InicioControlador::class, 'panel']);

    // Prueba temporal de mensajes. Se borra cuando la plantilla este revisada.
    $enrutador->get('/prueba/mensajes', [InicioControlador::class, 'probarMensajes']);

    // Autenticación (Damian)
    $enrutador->get('/ingresar', [AutenticacionControlador::class, 'mostrarLogin']);
    $enrutador->post('/ingresar', [AutenticacionControlador::class, 'iniciarSesion']);
    $enrutador->post('/salir', [AutenticacionControlador::class, 'cerrarSesion']);

    // Mi perfil (Allison). Hay un solo SuperAdmin: sus datos se editan aqui.
    $enrutador->get('/perfil', [PerfilControlador::class, 'mostrar']);
    $enrutador->post('/perfil/actualizar', [PerfilControlador::class, 'actualizar']);
    $enrutador->get('/perfil/contrasena', [PerfilControlador::class, 'formularioContrasena']);
    $enrutador->post('/perfil/contrasena', [PerfilControlador::class, 'cambiarContrasena']);
    $enrutador->get('/fotos/perfil', [FotoControlador::class, 'mostrarPerfil']);

    // Vendedores (Mariana). Solo para el SuperAdmin.
    $enrutador->get('/vendedores', [VendedorControlador::class, 'listar']);
    $enrutador->get('/vendedores/detalle', [VendedorControlador::class, 'detalle']);
    $enrutador->get('/vendedores/nuevo', [VendedorControlador::class, 'nuevo']);
    $enrutador->post('/vendedores/crear', [VendedorControlador::class, 'crear']);
    $enrutador->get('/vendedores/editar', [VendedorControlador::class, 'editar']);
    $enrutador->post('/vendedores/actualizar', [VendedorControlador::class, 'actualizar']);
    $enrutador->post('/vendedores/estado', [VendedorControlador::class, 'cambiarEstado']);

    // Sesiones (Damian)
};