<?php

namespace Aplicacion\Nucleo;

class Mensaje
{
    public const EXITO = 'exito';
    public const ERROR = 'error';
    public const ADVERTENCIA = 'advertencia';

    private const CLAVE = 'mensajes';

    public static function exito(string $texto): void
    {
        self::agregar(self::EXITO, $texto);
    }

    public static function error(string $texto): void
    {
        self::agregar(self::ERROR, $texto);
    }

    public static function advertencia(string $texto): void
    {
        self::agregar(self::ADVERTENCIA, $texto);
    }

    
    public static function obtener(): array
    {
        $mensajes = $_SESSION[self::CLAVE] ?? [];
        unset($_SESSION[self::CLAVE]);
        return $mensajes;
    }

    private static function agregar(string $tipo, string $texto): void
    {
        $_SESSION[self::CLAVE][] = ['tipo' => $tipo, 'texto' => $texto];
    }
}