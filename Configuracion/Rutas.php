<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\ComponenteControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\InventarioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\ProcesoControlador;
use Aplicacion\Controladores\ProveedorControlador;
use Aplicacion\Controladores\RegistroControlador;
use Aplicacion\Controladores\SesionControlador;
use Aplicacion\Controladores\TiendaControlador;
use Aplicacion\Controladores\TipoComponenteControlador;
use Aplicacion\Controladores\CompraProveedorControlador;
use Aplicacion\Controladores\ComponenteControlador;
use Aplicacion\Controladores\InventarioControlador;
use Aplicacion\Controladores\ProveedorControlador;
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

    $enrutador->get('/proveedores', [ProveedorControlador::class, 'listar']);
    $enrutador->get('/proveedores/detalle', [ProveedorControlador::class, 'detalle']);
    $enrutador->get('/proveedores/nuevo', [ProveedorControlador::class, 'nuevo']);
    $enrutador->post('/proveedores/crear', [ProveedorControlador::class, 'crear']);
    $enrutador->get('/proveedores/editar', [ProveedorControlador::class, 'editar']);
    $enrutador->post('/proveedores/actualizar', [ProveedorControlador::class, 'actualizar']);
    $enrutador->post('/proveedores/estado', [ProveedorControlador::class, 'cambiarEstado']);

    $enrutador->get('/procesos', [ProcesoControlador::class, 'listar']);
    $enrutador->get('/procesos/detalle', [ProcesoControlador::class, 'detalle']);
    $enrutador->get('/procesos/nuevo', [ProcesoControlador::class, 'nuevo']);
    $enrutador->post('/procesos/crear', [ProcesoControlador::class, 'crear']);
    $enrutador->get('/procesos/editar', [ProcesoControlador::class, 'editar']);
    $enrutador->post('/procesos/actualizar', [ProcesoControlador::class, 'actualizar']);
    $enrutador->post('/procesos/estado', [ProcesoControlador::class, 'cambiarEstado']);
    $enrutador->get('/compras-proveedor', [CompraProveedorControlador::class, 'listar']);
    $enrutador->get('/compras-proveedor/nueva', [CompraProveedorControlador::class, 'nueva']);
    $enrutador->post('/compras-proveedor/nueva', [CompraProveedorControlador::class, 'registrar']);
    $enrutador->get('/compras-proveedor/detalle', [CompraProveedorControlador::class, 'detalle']);
    $enrutador->post('/compras-proveedor/anular', [CompraProveedorControlador::class, 'anular']);

    $enrutador->get('/sesiones', [SesionControlador::class, 'listar']);
};
