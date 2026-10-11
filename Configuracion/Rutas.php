<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\RegistroControlador;
use Aplicacion\Controladores\SesionControlador;
use Aplicacion\Controladores\TiendaControlador;
use Aplicacion\Controladores\TipoComponenteControlador;
use Aplicacion\Controladores\ComponenteControlador;
use Aplicacion\Controladores\InventarioControlador;
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

    $enrutador->get('/tienda', [TiendaControlador::class, 'mostrar']);
    $enrutador->post('/tienda/actualizar', [TiendaControlador::class, 'actualizar']);

    $enrutador->get('/inventario', [InventarioControlador::class, 'mostrar']);
    $enrutador->get('/componentes/nuevo', [ComponenteControlador::class, 'nuevo']);
    $enrutador->post('/componentes/nuevo', [ComponenteControlador::class, 'insertar']);
    $enrutador->get('/componentes/editar', [ComponenteControlador::class, 'editar']);
    $enrutador->post('/componentes/editar', [ComponenteControlador::class, 'actualizar']);
    $enrutador->post('/componentes/estado', [ComponenteControlador::class, 'cambiarEstado']);

    $enrutador->get('/tipos-componente', [TipoComponenteControlador::class, 'listar']);
    $enrutador->post('/tipos-componente/sugeridos', [TipoComponenteControlador::class, 'agregarSugeridos']);
    $enrutador->get('/tipos-componente/nuevo', [TipoComponenteControlador::class, 'nuevo']);
    $enrutador->post('/tipos-componente/nuevo', [TipoComponenteControlador::class, 'insertar']);
    $enrutador->get('/tipos-componente/editar', [TipoComponenteControlador::class, 'editar']);
    $enrutador->post('/tipos-componente/editar', [TipoComponenteControlador::class, 'actualizar']);
    $enrutador->post('/tipos-componente/estado', [TipoComponenteControlador::class, 'cambiarEstado']);

    $enrutador->get('/fotos/perfil', [FotoControlador::class, 'mostrarPerfil']);
    $enrutador->get('/fotos/logo', [FotoControlador::class, 'mostrarLogo']);

    $enrutador->get('/sesiones', [SesionControlador::class, 'listar']);
};