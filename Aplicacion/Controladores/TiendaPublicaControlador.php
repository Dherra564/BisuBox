<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\ContextoPersonalizador;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\CompraProveedorRepositorio;
use Aplicacion\Repositorios\CotizacionRepositorio;
use Aplicacion\Repositorios\LineaProductoRepositorio;
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
        $lineasConProductos = array_unique(array_filter(array_map(fn($producto): ?int => $producto->getIdLinea(), $todos)));
        $lineas = array_filter(
            (new LineaProductoRepositorio())->listar($idVendedor, true),
            fn($linea): bool => in_array($linea->getIdLinea(), $lineasConProductos, true)
        );
        $idLinea = is_string($_GET['linea'] ?? null) && ctype_digit($_GET['linea']) && isset($lineas[(int) $_GET['linea']])
            ? (int) $_GET['linea']
            : null;
        $productos = array_values(array_filter($todos, fn($producto): bool =>
            ($busqueda === '' || mb_stripos($producto->getNombre() . ' ' . $producto->getDescripcion(), $busqueda, 0, 'UTF-8') !== false)
            && ($idLinea === null || $producto->getIdLinea() === $idLinea)));
        $enCarrito = [];
        $pedidosPorEntregar = 0;
        $cotizacionesPorResponder = 0;
        $agotados = 0;
        $porAcabarse = [];
        $comprasPorPagar = ['saldo' => 0.0, 'vencido' => 0.0, 'vencidas' => 0];
        if ($esDuenio) {
            $pedidosPorEntregar = (new PedidoRepositorio())->contar(['idVendedor' => $idVendedor, 'estado' => EstadoPedido::CONFIRMADO]);
            $cotizacionesPorResponder = (new CotizacionRepositorio())->contarPorResponder($idVendedor);
            $agotados = count(array_filter($todos, fn($producto): bool => $producto->getExistencia() <= 0));
            $porAcabarse = (new ComponenteRepositorio())->listarPorAcabarse($idVendedor);
            $comprasPorPagar = (new CompraProveedorRepositorio())->resumenPorPagar($idVendedor);
        } else {
            Carrito::elegirTienda($idVendedor, (string) $vendedor->getTiendaEnlace());
            $enCarrito = Carrito::deTienda($idVendedor);
        }

        $hayPersonalizador = ContextoPersonalizador::cargar($idVendedor) !== null;
        $tiendaVista = $vendedor;
        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Tienda/tienda.php';
    }
}