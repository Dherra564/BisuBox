<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\AyudaControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\SesionControlador;
use Aplicacion\Controladores\VendedorControlador;
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

    $enrutador->get('/vendedores', [VendedorControlador::class, 'listar']);
    $enrutador->get('/vendedores/detalle', [VendedorControlador::class, 'detalle']);
    $enrutador->get('/vendedores/nuevo', [VendedorControlador::class, 'nuevo']);
    $enrutador->post('/vendedores/crear', [VendedorControlador::class, 'crear']);
    $enrutador->get('/vendedores/editar', [VendedorControlador::class, 'editar']);
    $enrutador->post('/vendedores/actualizar', [VendedorControlador::class, 'actualizar']);
    $enrutador->post('/vendedores/estado', [VendedorControlador::class, 'cambiarEstado']);

    $enrutador->get('/ayuda', [AyudaControlador::class, 'mostrar']);

    $enrutador->get('/sesiones', [SesionControlador::class, 'listar']);
};