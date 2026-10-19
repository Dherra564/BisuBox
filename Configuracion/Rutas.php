<?php

use Aplicacion\Controladores\AutenticacionControlador;
use Aplicacion\Controladores\CarritoControlador;
use Aplicacion\Controladores\ClienteControlador;
use Aplicacion\Controladores\ComponenteControlador;
use Aplicacion\Controladores\CompraProveedorControlador;
use Aplicacion\Controladores\EmprenderControlador;
use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\InventarioControlador;
use Aplicacion\Controladores\MisPedidosControlador;
use Aplicacion\Controladores\PedidoControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Controladores\PersonalizadoControlador;
use Aplicacion\Controladores\PersonalizarControlador;
use Aplicacion\Controladores\ProductoControlador;
use Aplicacion\Controladores\ProveedorControlador;
use Aplicacion\Controladores\RegistroControlador;
use Aplicacion\Controladores\SesionControlador;
use Aplicacion\Controladores\TiendaControlador;
use Aplicacion\Controladores\TiendaPublicaControlador;
use Aplicacion\Controladores\TipoComponenteControlador;
use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    $enrutador->get('/', [InicioControlador::class, 'panel']);

    $enrutador->get('/ingresar', [AutenticacionControlador::class, 'mostrarLogin']);
    $enrutador->post('/ingresar', [AutenticacionControlador::class, 'iniciarSesion']);
    $enrutador->post('/salir', [AutenticacionControlador::class, 'cerrarSesion']);

    $enrutador->get('/registro', [RegistroControlador::class, 'mostrar']);
    $enrutador->post('/registro', [RegistroControlador::class, 'registrar']);

    $enrutador->get('/emprender', [EmprenderControlador::class, 'mostrar']);
    $enrutador->post('/emprender', [EmprenderControlador::class, 'guardar']);

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
    $enrutador->get('/fotos/producto', [FotoControlador::class, 'mostrarProducto']);
    $enrutador->get('/fotos/componente', [FotoControlador::class, 'mostrarComponente']);
    $enrutador->get('/fotos/comprobante', [FotoControlador::class, 'mostrarComprobante']);

    $enrutador->get('/proveedores', [ProveedorControlador::class, 'listar']);
    $enrutador->get('/proveedores/detalle', [ProveedorControlador::class, 'detalle']);
    $enrutador->get('/proveedores/nuevo', [ProveedorControlador::class, 'nuevo']);
    $enrutador->post('/proveedores/crear', [ProveedorControlador::class, 'crear']);
    $enrutador->get('/proveedores/editar', [ProveedorControlador::class, 'editar']);
    $enrutador->post('/proveedores/actualizar', [ProveedorControlador::class, 'actualizar']);
    $enrutador->post('/proveedores/estado', [ProveedorControlador::class, 'cambiarEstado']);

    $enrutador->get('/productos', [ProductoControlador::class, 'listar']);
    $enrutador->get('/productos/nuevo', [ProductoControlador::class, 'nuevo']);
    $enrutador->post('/productos/nuevo', [ProductoControlador::class, 'insertar']);
    $enrutador->get('/productos/detalle', [ProductoControlador::class, 'detalle']);
    $enrutador->get('/productos/editar', [ProductoControlador::class, 'editar']);
    $enrutador->post('/productos/editar', [ProductoControlador::class, 'actualizar']);
    $enrutador->post('/productos/estado', [ProductoControlador::class, 'cambiarEstado']);
    $enrutador->post('/productos/producir', [ProductoControlador::class, 'producir']);
    $enrutador->post('/productos/anular-produccion', [ProductoControlador::class, 'anularProduccion']);

    $enrutador->get('/compras-proveedor', [CompraProveedorControlador::class, 'listar']);
    $enrutador->get('/compras-proveedor/nueva', [CompraProveedorControlador::class, 'nueva']);
    $enrutador->post('/compras-proveedor/nueva', [CompraProveedorControlador::class, 'registrar']);
    $enrutador->get('/compras-proveedor/detalle', [CompraProveedorControlador::class, 'detalle']);
    $enrutador->post('/compras-proveedor/anular', [CompraProveedorControlador::class, 'anular']);

    $enrutador->get('/carrito', [CarritoControlador::class, 'ver']);
    $enrutador->post('/carrito/agregar', [CarritoControlador::class, 'agregar']);
    $enrutador->post('/carrito/actualizar', [CarritoControlador::class, 'actualizar']);
    $enrutador->get('/carrito/confirmar', [CarritoControlador::class, 'confirmar']);
    $enrutador->post('/carrito/confirmar', [CarritoControlador::class, 'hacerPedido']);
    $enrutador->get('/mis-pedidos', [MisPedidosControlador::class, 'listar']);
    $enrutador->get('/mis-pedidos/detalle', [MisPedidosControlador::class, 'detalle']);
    $enrutador->post('/mis-pedidos/cancelar', [MisPedidosControlador::class, 'cancelar']);
    $enrutador->post('/mis-pedidos/pago', [MisPedidosControlador::class, 'reenviarPago']);

    $enrutador->get('/personalizar', [PersonalizarControlador::class, 'mostrar']);
    $enrutador->post('/personalizar', [PersonalizarControlador::class, 'enviar']);
    $enrutador->get('/mis-pedidos/personalizados', [PersonalizarControlador::class, 'listar']);
    $enrutador->get('/mis-pedidos/personalizado', [PersonalizarControlador::class, 'detalle']);
    $enrutador->post('/mis-pedidos/personalizado/cancelar', [PersonalizarControlador::class, 'cancelar']);
    $enrutador->get('/mis-pedidos/personalizado/comprar', [PersonalizarControlador::class, 'comprar']);
    $enrutador->post('/mis-pedidos/personalizado/comprar', [PersonalizarControlador::class, 'hacerCompra']);

    $enrutador->get('/pedidos', [PedidoControlador::class, 'listar']);
    $enrutador->get('/pedidos/detalle', [PedidoControlador::class, 'detalle']);
    $enrutador->post('/pedidos/entregar', [PedidoControlador::class, 'entregar']);
    $enrutador->post('/pedidos/cancelar', [PedidoControlador::class, 'cancelar']);
    $enrutador->post('/pedidos/pago/recibido', [PedidoControlador::class, 'pagoRecibido']);
    $enrutador->post('/pedidos/pago/problema', [PedidoControlador::class, 'pagoConProblema']);
    $enrutador->get('/pedidos/personalizados', [PersonalizadoControlador::class, 'listar']);
    $enrutador->get('/pedidos/personalizado', [PersonalizadoControlador::class, 'detalle']);
    $enrutador->post('/pedidos/personalizado/cotizar', [PersonalizadoControlador::class, 'cotizar']);
    $enrutador->post('/pedidos/personalizado/rechazar', [PersonalizadoControlador::class, 'rechazar']);
    $enrutador->get('/clientes', [ClienteControlador::class, 'listar']);

    $enrutador->get('/sesiones', [SesionControlador::class, 'listar']);
    $enrutador->get('/tienda/{enlace}', [TiendaPublicaControlador::class, 'mostrar']);
};