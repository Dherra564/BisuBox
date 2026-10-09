<?php

namespace Aplicacion\Nucleo;


class Formato
{
    public static function colones(float|int|string|null $monto): string
    {
        return '₡' . number_format((float) $monto, 2, ',', ' ');
    }

    public static function minutos(int|string|null $minutos): string
    {
        return (int) $minutos . ' min';
    }

    public static function leerMonto(string $texto): ?string
    {
        $limpio = str_replace(' ', '', trim($texto));
        $limpio = str_replace(',', '.', $limpio);

        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $limpio)) {
            return null;
        }

        return number_format((float) $limpio, 2, '.', '');
    }
}