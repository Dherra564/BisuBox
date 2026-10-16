<?php

namespace Aplicacion\Nucleo;


class MetodoPago
{
    public const SINPE = 'SinpeMovil';
    public const TRANSFERENCIA = 'Transferencia';
    public const EFECTIVO = 'Efectivo';

    private const NOMBRES = [
        self::SINPE => 'SINPE Móvil',
        self::TRANSFERENCIA => 'Transferencia bancaria',
        self::EFECTIVO => 'Efectivo',
    ];

    /** @return array<string, string> */
    public static function todos(): array
    {
        return self::NOMBRES;
    }

    public static function existe(?string $metodo): bool
    {
        return $metodo !== null && isset(self::NOMBRES[$metodo]);
    }

    public static function nombre(?string $metodo): string
    {
        return self::NOMBRES[$metodo] ?? 'Sin indicar';
    }
}
