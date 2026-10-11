<?php

namespace Aplicacion\Nucleo;

class Numero
{
    public const MAXIMO = 9999999999.99;

    public static function leer(string $texto): ?float
    {
        $texto = str_replace(' ', '', trim($texto));
        if (!preg_match('/^[0-9]{1,10}([.,][0-9]{1,2})?$/', $texto)) {
            return null;
        }
        return (float) str_replace(',', '.', $texto);
    }

    public static function formatear(float $numero): string
    {
        $texto = number_format($numero, 2, ',', '.');
        return rtrim(rtrim($texto, '0'), ',');
    }

    public static function moneda(float $monto): string
    {
        return '₡' . number_format($monto, floor($monto) == $monto ? 0 : 2, ',', '.');
    }

    public static function paraCampo(?float $numero): string
    {
        if ($numero === null) {
            return '';
        }
        return floor($numero) == $numero ? (string) (int) $numero : str_replace('.', ',', (string) round($numero, 2));
    }
}