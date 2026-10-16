<?php

namespace Aplicacion\Controladores;

use Aplicacion\Controladores\ErrorControlador;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\UsuarioActual;
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

        $busqueda = is_string($_GET['buscar'] ?? null) ? mb_substr(trim($_GET['buscar']), 0, 60, 'UTF-8') : '';
        $productos = (new ProductoRepositorio())->listarActivos((int) $vendedor->getIdVendedor());
        if ($busqueda !== '') {
            $productos = array_values(array_filter($productos, fn($producto): bool =>
                mb_stripos($producto->getNombre() . ' ' . $producto->getDescripcion(), $busqueda, 0, 'UTF-8') !== false));
        }
        $tienda = $vendedor;
        $enCarrito = [];
        $mensajes = Mensaje::obtener();

        // Un cliente con sesión puede agregar al carrito; los demás ven el botón para ingresar
        if (UsuarioActual::esCliente()) {
            // Esta pasa a ser la tienda donde está comprando: su inicio y su carrito muestran solo esta
            Carrito::elegirTienda((int) $vendedor->getIdVendedor(), (string) $vendedor->getTiendaEnlace());
            $enCarrito = Carrito::deTienda((int) $vendedor->getIdVendedor());
        }

        // La misma página para todos: visitantes, clientes y vendedores
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Tienda/tienda.php';
    }
}
