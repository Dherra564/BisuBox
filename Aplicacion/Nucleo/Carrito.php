<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Repositorios\CarritoRepositorio;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;

class Carrito
{
    public const MAXIMO_POR_PRODUCTO = 99;

    private const CLAVE_TIENDA = 'tiendaActual';
    private const CLAVE_INVITADO = 'carritoInvitado';

    private static ?int $idCliente = null;
    private static bool $clienteBuscado = false;
    private static ?array $contenido = null;
    private static bool $tiendaRevisada = false;

    public static function todo(): array
    {
        if (!UsuarioActual::haySesion()) {
            return self::invitado();
        }
        $idCliente = self::idCliente(false);
        if ($idCliente === null) {
            return [];
        }
        if (self::$contenido === null) {
            self::$contenido = (new CarritoRepositorio())->listar($idCliente);
        }
        return self::$contenido;
    }

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

    public static function cambiar(int $idVendedor, int $idProducto, int $cantidad): void
    {
        $cantidad = min($cantidad, self::MAXIMO_POR_PRODUCTO);

        if (!UsuarioActual::haySesion()) {
            $carrito = self::invitado();
            if ($cantidad <= 0) {
                unset($carrito[$idVendedor][$idProducto]);
                if (empty($carrito[$idVendedor])) {
                    unset($carrito[$idVendedor]);
                }
            } else {
                $carrito[$idVendedor][$idProducto] = $cantidad;
            }
            $_SESSION[self::CLAVE_INVITADO] = $carrito;
            return;
        }

        $idCliente = self::idCliente(true);
        if ($idCliente === null) {
            return;
        }
        (new CarritoRepositorio())->guardarCantidad($idCliente, $idVendedor, $idProducto, $cantidad);
        self::$contenido = null;
    }

    public static function vaciarTienda(int $idVendedor): void
    {
        if (!UsuarioActual::haySesion()) {
            $carrito = self::invitado();
            unset($carrito[$idVendedor]);
            $_SESSION[self::CLAVE_INVITADO] = $carrito;
            return;
        }

        $idCliente = self::idCliente(false);
        if ($idCliente === null) {
            return;
        }
        (new CarritoRepositorio())->vaciarTienda($idCliente, $idVendedor);
        self::$contenido = null;
    }

    public static function pasarALaCuenta(): void
    {
        $invitado = self::invitado();
        unset($_SESSION[self::CLAVE_INVITADO]);
        self::$idCliente = null;
        self::$clienteBuscado = false;
        self::$contenido = null;

        if ($invitado === [] || !UsuarioActual::haySesion()) {
            return;
        }

        $propia = UsuarioActual::esVendedor()
            ? (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id())
            : null;

        foreach ($invitado as $idVendedor => $productos) {
            if ($propia !== null && (int) $propia->getIdVendedor() === (int) $idVendedor) {
                continue;
            }
            foreach ($productos as $idProducto => $cantidad) {
                self::agregar((int) $idVendedor, (int) $idProducto, (int) $cantidad);
            }
        }
    }

    public static function unidades(): int
    {
        $tienda = self::tiendaActual();
        return $tienda !== null ? array_sum(self::deTienda($tienda['id'])) : 0;
    }

    public static function elegirTienda(int $idVendedor, string $enlace): void
    {
        $_SESSION[self::CLAVE_TIENDA] = ['id' => $idVendedor, 'enlace' => $enlace];
        self::$tiendaRevisada = true;
    }

    public static function tiendaActual(): ?array
    {
        if (self::$tiendaRevisada) {
            return $_SESSION[self::CLAVE_TIENDA] ?? null;
        }
        self::$tiendaRevisada = true;

        $tienda = $_SESSION[self::CLAVE_TIENDA] ?? null;
        $idVendedor = is_array($tienda) ? (int) $tienda['id'] : null;
        if ($idVendedor === null && UsuarioActual::haySesion()) {
            $idCliente = self::idCliente(false);
            $idVendedor = $idCliente !== null ? (new CarritoRepositorio())->ultimaTienda($idCliente) : null;
        }

        $vendedor = $idVendedor !== null ? (new VendedorRepositorio())->buscarPorId($idVendedor) : null;
        if ($vendedor === null || !$vendedor->getTiendaActiva() || !$vendedor->getEstado() || self::esPropia($vendedor)) {
            unset($_SESSION[self::CLAVE_TIENDA]);
            return null;
        }

        self::elegirTienda((int) $vendedor->getIdVendedor(), (string) $vendedor->getTiendaEnlace());
        return $_SESSION[self::CLAVE_TIENDA];
    }

    public static function esPropia(Vendedor $tienda): bool
    {
        return UsuarioActual::esVendedor() && (int) $tienda->getIdUsuario() === (int) UsuarioActual::id();
    }

    public static function leerCantidad(mixed $valor): ?int
    {
        if (!is_string($valor) || !preg_match('/^[0-9]{1,2}$/', trim($valor))) {
            return null;
        }
        $cantidad = (int) trim($valor);
        return $cantidad >= 1 ? $cantidad : null;
    }

    private static function invitado(): array
    {
        $carrito = $_SESSION[self::CLAVE_INVITADO] ?? [];
        return is_array($carrito) ? $carrito : [];
    }

    private static function idCliente(bool $crear): ?int
    {
        if (!UsuarioActual::haySesion()) {
            return null;
        }
        if (self::$idCliente !== null) {
            return self::$idCliente;
        }

        $repositorio = new ClienteRepositorio();
        if ($crear) {
            self::$idCliente = $repositorio->asegurar((int) UsuarioActual::id());
            self::$clienteBuscado = true;
            return self::$idCliente;
        }

        if (!self::$clienteBuscado) {
            $cliente = $repositorio->buscarPorIdUsuario((int) UsuarioActual::id());
            self::$idCliente = $cliente !== null ? (int) $cliente->getIdCliente() : null;
            self::$clienteBuscado = true;
        }
        return self::$idCliente;
    }
}