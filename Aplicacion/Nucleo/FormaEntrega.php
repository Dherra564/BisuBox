<?php

namespace Aplicacion\Nucleo;


class FormaEntrega
{
    public const RETIRO = 'Retiro';
    public const ENVIO = 'Envio';

    private const NOMBRES = [
        self::RETIRO => 'Retiro en la tienda',
        self::ENVIO => 'Envío a domicilio',
    ];

    /** @return array<string, string> */
    public static function todas(): array
    {
        return self::NOMBRES;
    }

    public static function existe(?string $forma): bool
    {
        return $forma !== null && isset(self::NOMBRES[$forma]);
    }

    public static function nombre(?string $forma): string
    {
        return self::NOMBRES[$forma] ?? 'Sin indicar';
    }
}
