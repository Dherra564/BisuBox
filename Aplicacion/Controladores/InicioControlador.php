<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Configuracion\Configuracion;


class InicioControlador
{
    public function panel(): void
    {
        Permiso::exigir('panel.ver');

        // El inicio del cliente es la tienda donde está comprando
        $tienda = UsuarioActual::esCliente() ? Carrito::tiendaActual() : null;
        if ($tienda !== null) {
            header('Location: ' . Configuracion::urlBase() . '/tienda/' . $tienda['enlace']);
            exit;
        }

        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Inicio/panel.php';
    }
}