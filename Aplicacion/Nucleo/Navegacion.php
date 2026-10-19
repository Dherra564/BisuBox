<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\PersonalizadoRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Throwable;

class Navegacion
{
    public static function armar(?Vendedor $tiendaVista, string $paginaActual): array
    {
        $vendedorRepositorio = new VendedorRepositorio();
        $propia = UsuarioActual::esVendedor() ? $vendedorRepositorio->buscarPorIdUsuario((int) UsuarioActual::id()) : null;

        $tienda = $tiendaVista;
        if ($tienda === null) {
            $comprando = in_array($paginaActual, ['carrito', 'misPedidos'], true);
            if ($propia !== null && !$comprando) {
                $tienda = $propia;
            } else {
                $actual = Carrito::tiendaActual();
                $tienda = $actual !== null ? $vendedorRepositorio->buscarPorId($actual['id']) : null;
                $tienda = $tienda ?? $propia;
            }
        }

        $esDuenio = $propia !== null && $tienda !== null && (int) $tienda->getIdVendedor() === (int) $propia->getIdVendedor();
        $puedeComprar = $tienda !== null && !$esDuenio;
        $rutaTienda = $tienda !== null ? '/tienda/' . $tienda->getTiendaEnlace() : null;

        [$unidades, $total] = $puedeComprar ? self::resumenCarrito($tienda) : [0, 0.0];

        $numeros = ['pedidos' => 0, 'productos' => 0, 'inventario' => 0];
        if ($esDuenio) {
            try {
                $numeros = AvisosTienda::numeros((int) $propia->getIdVendedor());
            } catch (Throwable $error) {
                error_log('No se pudieron contar los avisos de la tienda: ' . $error->getMessage());
            }
        }

        $porDecidir = 0;
        if (UsuarioActual::haySesion()) {
            try {
                $cliente = (new ClienteRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
                $porDecidir = $cliente !== null ? (new PersonalizadoRepositorio())->contar([
                    'idCliente' => (int) $cliente->getIdCliente(),
                    'estado' => EstadoPersonalizado::COTIZADO,
                ]) : 0;
            } catch (Throwable $error) {
                error_log('No se pudieron contar los pedidos personalizados cotizados: ' . $error->getMessage());
            }
        }

        $whatsApp = null;
        if ($tienda !== null) {
            foreach ($tienda->getContactos() as $contacto) {
                if ($contacto->getTipo() === TipoContacto::WHATSAPP) {
                    $whatsApp = WhatsApp::enlace($contacto->getValor(), 'Hola, les escribo desde su tienda en línea.');
                    break;
                }
            }
        }

        return [
            'tienda' => $tienda,
            'propia' => $propia,
            'esDuenio' => $esDuenio,
            'puedeComprar' => $puedeComprar,
            'rutaTienda' => $rutaTienda,
            'unidadesCarrito' => $unidades,
            'totalCarrito' => $total,
            'whatsApp' => $esDuenio ? null : $whatsApp,
            'rutaPedidos' => self::rutaPedidos($esDuenio),
            'textoPedidos' => $esDuenio ? 'Pedidos' : (UsuarioActual::esVendedor() ? 'Mis compras' : 'Mis pedidos'),
            'avisos' => $propia !== null,
            'menu' => self::menu($paginaActual, $rutaTienda, $propia, $esDuenio, $unidades, $numeros, $porDecidir),
        ];
    }

    public static function resumenCarrito(Vendedor $tienda): array
    {
        $enCarrito = Carrito::deTienda((int) $tienda->getIdVendedor());
        if ($enCarrito === []) {
            return [0, 0.0];
        }

        $unidades = 0;
        $total = 0.0;
        foreach ((new ProductoRepositorio())->buscarVarios((int) $tienda->getIdVendedor(), array_keys($enCarrito)) as $idProducto => $producto) {
            $unidades += $enCarrito[$idProducto];
            $total += $enCarrito[$idProducto] * $producto->getPrecioVenta();
        }
        return [$unidades, $total];
    }

    private static function rutaPedidos(bool $esDuenio): string
    {
        if ($esDuenio) {
            return '/pedidos';
        }
        return UsuarioActual::haySesion() ? '/mis-pedidos' : '/ingresar?volver=' . urlencode('/mis-pedidos');
    }

    private static function menu(string $paginaActual, ?string $rutaTienda, ?Vendedor $propia, bool $esDuenio, int $unidades, array $numeros, int $porDecidir): array
    {
        $opcion = fn(string $clave, string $texto, string $ruta, string $icono, int $numero = 0): array => [
            'clave' => $clave,
            'texto' => $texto,
            'ruta' => $ruta,
            'icono' => $icono,
            'numero' => $numero,
            'activo' => $clave === $paginaActual,
        ];
        $separador = fn(string $texto): array => ['separador' => $texto];

        if (!UsuarioActual::haySesion()) {
            return [
                $opcion('inicio', 'Inicio', $rutaTienda ?? '/ingresar', 'inicio'),
                $opcion('carrito', 'Carrito', '/carrito', 'carrito', $unidades),
                $opcion('misPedidos', 'Mis pedidos', '/ingresar?volver=' . urlencode('/mis-pedidos'), 'bolsa'),
                $opcion('perfil', 'Mi perfil', '/ingresar?volver=' . urlencode('/perfil'), 'perfil'),
            ];
        }

        if (UsuarioActual::esCliente()) {
            return [
                $opcion('inicio', 'Inicio', $rutaTienda ?? '/', 'inicio'),
                $opcion('carrito', 'Carrito', '/carrito', 'carrito', $unidades),
                $opcion('misPedidos', 'Mis pedidos', '/mis-pedidos', 'bolsa', $porDecidir),
                $opcion('perfil', 'Mi perfil', '/perfil', 'perfil'),
                $opcion('sesiones', 'Historial de sesiones', '/sesiones', 'historial'),
                $opcion('emprender', 'Comenzar a emprender', '/emprender', 'emprender'),
            ];
        }

        $rutaPropia = $propia !== null ? '/tienda/' . $propia->getTiendaEnlace() : '/';

        if (!$esDuenio && $propia !== null) {
            return [
                $opcion('inicio', 'Inicio', $rutaTienda ?? $rutaPropia, 'inicio'),
                $opcion('carrito', 'Carrito', '/carrito', 'carrito', $unidades),
                $opcion('misPedidos', 'Mis compras', '/mis-pedidos', 'bolsa', $porDecidir),
                $separador('Mi negocio'),
                $opcion('panel', 'Panel de ' . $propia->getTiendaNombre(), $rutaPropia, 'panel'),
                $opcion('perfil', 'Mi perfil', '/perfil', 'perfil'),
                $opcion('sesiones', 'Historial de sesiones', '/sesiones', 'historial'),
            ];
        }

        return [
            $opcion('inicio', 'Inicio', $rutaPropia, 'inicio'),
            $opcion('pedidos', 'Pedidos', '/pedidos', 'recibo', $numeros['pedidos']),
            $opcion('clientes', 'Clientes', '/clientes', 'clientes'),
            $opcion('productos', 'Productos', '/productos', 'productos', $numeros['productos']),
            $opcion('inventario', 'Inventario', '/inventario', 'inventario', $numeros['inventario']),
            $opcion('proveedores', 'Proveedores', '/proveedores', 'proveedores'),
            $opcion('tienda', 'Mi tienda', '/tienda', 'tienda'),
            $separador('Mi cuenta'),
            $opcion('misPedidos', 'Mis compras', '/mis-pedidos', 'bolsa', $porDecidir),
            $opcion('perfil', 'Mi perfil', '/perfil', 'perfil'),
            $opcion('sesiones', 'Historial de sesiones', '/sesiones', 'historial'),
        ];
    }
}