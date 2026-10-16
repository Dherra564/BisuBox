<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Repositorios\CarritoRepositorio;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;

// Carrito del cliente que inició sesión. Se guarda en la base de datos (tabla tbcarrito),
// así no se pierde al cerrar sesión ni al cerrar el navegador: solo sale lo que el cliente quita
// o lo que ya pidió. Va separado por tienda porque cada pedido es a una sola tienda:
// [idVendedor => [idProducto => cantidad]]
class Carrito
{
    public const MAXIMO_POR_PRODUCTO = 99;

    // Tienda en la que el cliente está comprando: la última cuya página abrió.
    // Su inicio y su carrito muestran solo esa tienda
    private const CLAVE_TIENDA = 'tiendaActual';

    private static ?int $idCliente = null;
    private static ?array $contenido = null;

    /** @return array<int, array<int, int>> */
    public static function todo(): array
    {
        $idCliente = self::idCliente();
        if ($idCliente === null) {
            return [];
        }
        if (self::$contenido === null) {
            self::$contenido = (new CarritoRepositorio())->listar($idCliente);
        }
        return self::$contenido;
    }

    /** @return array<int, int> */
    public static function deTienda(int $idVendedor): array
    {
        return self::todo()[$idVendedor] ?? [];
    }

    public static function cantidadDe(int $idVendedor, int $idProducto): int
    {
        return self::deTienda($idVendedor)[$idProducto] ?? 0;
    }

    public static function agregar(int $idVendedor, int $idProducto, int $cantidad): void
    {
        self::cambiar($idVendedor, $idProducto, self::cantidadDe($idVendedor, $idProducto) + $cantidad);
    }

    // Deja la cantidad exacta; con 0 o menos quita el producto
    public static function cambiar(int $idVendedor, int $idProducto, int $cantidad): void
    {
        $idCliente = self::idCliente();
        if ($idCliente === null) {
            return;
        }
        (new CarritoRepositorio())->guardarCantidad($idCliente, $idVendedor, $idProducto, min($cantidad, self::MAXIMO_POR_PRODUCTO));
        self::$contenido = null;
    }

    public static function vaciarTienda(int $idVendedor): void
    {
        $idCliente = self::idCliente();
        if ($idCliente === null) {
            return;
        }
        (new CarritoRepositorio())->vaciarTienda($idCliente, $idVendedor);
        self::$contenido = null;
    }

    // Unidades de la tienda actual, para el número que se muestra en el menú
    public static function unidades(): int
    {
        $tienda = self::tiendaActual();
        return $tienda !== null ? array_sum(self::deTienda($tienda['id'])) : 0;
    }

    public static function elegirTienda(int $idVendedor, string $enlace): void
    {
        $_SESSION[self::CLAVE_TIENDA] = ['id' => $idVendedor, 'enlace' => $enlace];
    }

    /**
     * Tienda en la que está comprando el cliente. Si todavía no abrió ninguna en esta sesión
     * (por ejemplo, acaba de ingresar), se toma la de lo último que agregó al carrito
     * @return array{id: int, enlace: string}|null
     */
    public static function tiendaActual(): ?array
    {
        $tienda = $_SESSION[self::CLAVE_TIENDA] ?? null;
        if (is_array($tienda)) {
            return $tienda;
        }

        $idCliente = self::idCliente();
        $idVendedor = $idCliente !== null ? (new CarritoRepositorio())->ultimaTienda($idCliente) : null;
        $vendedor = $idVendedor !== null ? (new VendedorRepositorio())->buscarPorId($idVendedor) : null;
        if ($vendedor === null) {
            return null;
        }
        self::elegirTienda((int) $vendedor->getIdVendedor(), (string) $vendedor->getTiendaEnlace());
        return $_SESSION[self::CLAVE_TIENDA];
    }

    // Lee una cantidad escrita por el cliente: entero de 1 a 99
    public static function leerCantidad(mixed $valor): ?int
    {
        if (!is_string($valor) || !preg_match('/^[0-9]{1,2}$/', trim($valor))) {
            return null;
        }
        $cantidad = (int) trim($valor);
        return $cantidad >= 1 ? $cantidad : null;
    }

    // El carrito es del cliente que inició sesión; los vendedores y visitantes no tienen
    private static function idCliente(): ?int
    {
        if (!UsuarioActual::esCliente()) {
            return null;
        }
        if (self::$idCliente === null) {
            $cliente = (new ClienteRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
            self::$idCliente = $cliente !== null ? (int) $cliente->getIdCliente() : null;
        }
        return self::$idCliente;
    }
}
