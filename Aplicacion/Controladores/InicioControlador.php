<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;

class InicioControlador
{
    public function panel(): void
    {
        if (!UsuarioActual::haySesion()) {
            $tienda = Carrito::tiendaActual();
            $this->redirigir($tienda !== null ? '/tienda/' . $tienda['enlace'] : '/ingresar');
        }

        Permiso::exigir('panel.ver');

        if (UsuarioActual::esVendedor()) {
            $propia = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
            if ($propia !== null) {
                $this->redirigir('/tienda/' . $propia->getTiendaEnlace());
            }
        }

        $tienda = Carrito::tiendaActual();
        if ($tienda !== null) {
            $this->redirigir('/tienda/' . $tienda['enlace']);
        }

        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Inicio/panel.php';
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . Configuracion::urlBase() . $ruta);
        exit;
    }
}