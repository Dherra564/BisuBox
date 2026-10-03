<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Sesion;

class UsuarioActual
{
    private const CLAVE_ID = 'idUsuario';
    private const CLAVE_TIPO = 'tipoUsuario';
    private const CLAVE_NOMBRE = 'nombreUsuario';

    public static function iniciar(int $idUsuario, string $tipoUsuario, string $nombre): void
    {
        
        session_regenerate_id(true);
        $_SESSION[self::CLAVE_ID] = $idUsuario;
        $_SESSION[self::CLAVE_TIPO] = $tipoUsuario;
        $_SESSION[self::CLAVE_NOMBRE] = $nombre;
    }

    public static function cerrar(): void
    {
        unset($_SESSION[self::CLAVE_ID], $_SESSION[self::CLAVE_TIPO], $_SESSION[self::CLAVE_NOMBRE]);
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

    public static function tipo(): ?string
    {
        return $_SESSION[self::CLAVE_TIPO] ?? null;
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

    public static function esSuperAdmin(): bool
    {
        return self::tipo() === Sesion::TIPO_SUPERADMIN;
    }
}