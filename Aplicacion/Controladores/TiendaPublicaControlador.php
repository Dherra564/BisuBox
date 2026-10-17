<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;

class TiendaPublicaControlador
{
    public function mostrar(string $enlace): void
    {
        $vendedor = (new VendedorRepositorio())->buscarPorEnlace($enlace);
        $esDuenio = $vendedor !== null && Carrito::esPropia($vendedor);
        if ($vendedor === null || (!$esDuenio && (!$vendedor->getTiendaActiva() || !$vendedor->getEstado()))) {
            ErrorControlador::noEncontrado();
        }

        $idVendedor = (int) $vendedor->getIdVendedor();
        $busqueda = is_string($_GET['buscar'] ?? null) ? mb_substr(trim($_GET['buscar']), 0, 60, 'UTF-8') : '';
        $todos = (new ProductoRepositorio())->listarActivos($idVendedor);
        $productos = $busqueda === '' ? $todos : array_values(array_filter($todos, fn($producto): bool =>
            mb_stripos($producto->getNombre() . ' ' . $producto->getDescripcion(), $busqueda, 0, 'UTF-8') !== false));

        $enCarrito = [];
        $pedidosPendientes = 0;
        $agotados = 0;
        if ($esDuenio) {
            $pedidosPendientes = (new PedidoRepositorio())->contar(['idVendedor' => $idVendedor, 'estado' => EstadoPedido::PENDIENTE]);
            $agotados = count(array_filter($todos, fn($producto): bool => $producto->getExistencia() <= 0));
        } else {
            Carrito::elegirTienda($idVendedor, (string) $vendedor->getTiendaEnlace());
            $enCarrito = Carrito::deTienda($idVendedor);
        }

        $tiendaVista = $vendedor;
        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Tienda/tienda.php';
    }
}