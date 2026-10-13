<?php

namespace Aplicacion\Controladores;

use Aplicacion\Controladores\ErrorControlador;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;

class TiendaPublicaControlador
{
    public function mostrar(string $enlace): void
    {
        $vendedor = (new VendedorRepositorio())->buscarPorEnlace($enlace);
        if ($vendedor === null || !$vendedor->getTiendaActiva() || !$vendedor->getEstado()) {
            ErrorControlador::noEncontrado();
        }

        $productos = (new ProductoRepositorio())->listarActivos((int) $vendedor->getIdVendedor());

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Tienda/publica.php';
    }
}