<?php

namespace Aplicacion\Nucleo;

class UsuarioActual
{
    private const CLAVE_ID = 'idUsuario';
    private const CLAVE_ROL = 'rolUsuario';
    private const CLAVE_NOMBRE = 'nombreUsuario';
    private const CLAVE_FOTO = 'fotoUsuario';

    public static function iniciar(int $idUsuario, string $rol, string $nombre, ?string $foto = null): void
    {
        session_regenerate_id(true);
        $_SESSION[self::CLAVE_ID] = $idUsuario;
        $_SESSION[self::CLAVE_ROL] = $rol;
        $_SESSION[self::CLAVE_NOMBRE] = $nombre;
        $_SESSION[self::CLAVE_FOTO] = $foto;
    }

    public static function cerrar(): void
    {
        unset($_SESSION[self::CLAVE_ID], $_SESSION[self::CLAVE_ROL], $_SESSION[self::CLAVE_NOMBRE], $_SESSION[self::CLAVE_FOTO]);
        session_regenerate_id(true);
    }

    public static function haySesion(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::CLAVE_ID]) ? (int) $_SESSION[self::CLAVE_ID] : null;
    }

    public static function rol(): ?string
    {
        return $_SESSION[self::CLAVE_ROL] ?? null;
    }

    public static function nombre(): ?string
    {
        return $_SESSION[self::CLAVE_NOMBRE] ?? null;
    }

    public static function actualizarNombre(string $nombre): void
    {
        if (self::haySesion()) {
            $_SESSION[self::CLAVE_NOMBRE] = $nombre;
        }
    }

    public static function foto(): ?string
    {
        return $_SESSION[self::CLAVE_FOTO] ?? null;
    }

    public static function actualizarFoto(?string $foto): void
    {
        if (self::haySesion()) {
            $_SESSION[self::CLAVE_FOTO] = $foto;
        }
    }

    public static function esVendedor(): bool
    {
        return self::rol() === Rol::VENDEDOR;
    }

    public static function esCliente(): bool
    {
        return self::rol() === Rol::CLIENTE;
    }
}