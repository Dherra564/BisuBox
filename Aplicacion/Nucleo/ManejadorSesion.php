<?php

namespace Aplicacion\Nucleo;

class ManejadorSesion
{

    public const MINUTOS_INACTIVIDAD = 30;

    private const CLAVE_ACTIVIDAD = 'ultimaActividad';
    private const CLAVE_SESION_BD = 'idSesionBd';

    
    public static function arrancar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,                          
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']), 
            'httponly' => true,                       
            'samesite' => 'Lax',                      
        ]);
        session_start();
    }

    
    public static function regenerarId(): void
    {
        session_regenerate_id(true);
    }

    
    public static function registrarActividad(): void
    {
        $_SESSION[self::CLAVE_ACTIVIDAD] = time();
    }

    
    public static function haExpirado(): bool
    {
        if (!isset($_SESSION[self::CLAVE_ACTIVIDAD])) {
            return false;   
        }
        return (time() - $_SESSION[self::CLAVE_ACTIVIDAD]) > self::MINUTOS_INACTIVIDAD * 60;
    }

    
    public static function guardarIdSesionBd(int $idSesion): void
    {
        $_SESSION[self::CLAVE_SESION_BD] = $idSesion;
    }

    public static function obtenerIdSesionBd(): ?int
    {
        return isset($_SESSION[self::CLAVE_SESION_BD]) ? (int) $_SESSION[self::CLAVE_SESION_BD] : null;
    }

    
    public static function destruir(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
        }

        session_destroy();
    }

    
    public static function enviarEncabezadosSinCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}