<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Sesion;

/**
 * Quien esta conectado en este momento (datos guardados en la sesion de PHP).
 *
 * Damian, en el inicio de sesion, despues de verificar correo y contraseña:
 *   UsuarioActual::iniciar($usuario->getIdUsuario(), Sesion::TIPO_SUPERADMIN, $usuario->getNombreCompleto(), $usuario->getFotoPerfil());
 * Y al cerrar sesion:
 *   UsuarioActual::cerrar();
 *
 * En los controladores:
 *   UsuarioActual::id()             id de tbusuario, o null si nadie inicio sesion
 *   UsuarioActual::esSuperAdmin()   true si el rol es SuperAdmin
 */
class UsuarioActual
{
    private const CLAVE_ID = 'idUsuario';
    private const CLAVE_TIPO = 'tipoUsuario';
    private const CLAVE_NOMBRE = 'nombreUsuario';
    private const CLAVE_FOTO = 'fotoUsuario';

     public static function iniciar(int $idUsuario, string $tipoUsuario, string $nombre, ?string $foto = null): void
    {
        
        session_regenerate_id(true);
        $_SESSION[self::CLAVE_ID] = $idUsuario;
        $_SESSION[self::CLAVE_TIPO] = $tipoUsuario;
        $_SESSION[self::CLAVE_NOMBRE] = $nombre;
        $_SESSION[self::CLAVE_FOTO] = $foto;
    }

    public static function cerrar(): void
    {
        unset($_SESSION[self::CLAVE_ID], $_SESSION[self::CLAVE_TIPO], $_SESSION[self::CLAVE_NOMBRE], $_SESSION[self::CLAVE_FOTO]);
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

        public static function foto(): ?string
    {
        return $_SESSION[self::CLAVE_FOTO] ?? null;
    }

    // Para que la barra superior muestre la foto nueva después de cambiarla en "Mi perfil"
    public static function actualizarFoto(?string $foto): void
    {
        if (self::haySesion()) {
            $_SESSION[self::CLAVE_FOTO] = $foto;
        }
    }

    public static function esSuperAdmin(): bool
    {
        return self::tipo() === Sesion::TIPO_SUPERADMIN;
    }
}