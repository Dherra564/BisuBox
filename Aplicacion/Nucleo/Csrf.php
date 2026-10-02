<?php

namespace Aplicacion\Nucleo;


class Csrf
{
    private const CLAVE = 'tokenCsrf';

    
    public static function obtenerToken(): string
    {
        if (empty($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CLAVE];
    }

    
    public static function campo(): string
    {
        $token = htmlspecialchars(self::obtenerToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . self::CLAVE . '" value="' . $token . '">';
    }

    
    public static function esValido(): bool
    {
        $enviado = $_POST[self::CLAVE] ?? '';
        $guardado = $_SESSION[self::CLAVE] ?? '';

        // hash_equals compara sin dar pistas por el tiempo que tarda
        return is_string($enviado) && $guardado !== '' && hash_equals($guardado, $enviado);
    }
}