<?php

namespace Aplicacion\Nucleo;

class EstadoPago
{
    public const POR_VERIFICAR = 'Por verificar';
    public const RECIBIDO = 'Recibido';
    public const CON_PROBLEMA = 'Con problema';
    public const NO_LLEGO = 'No llegó';

    private const CLASES = [
        self::POR_VERIFICAR => 'etiquetaBajo',
        self::RECIBIDO => 'etiquetaActivo',
        self::CON_PROBLEMA => 'etiquetaAgotado',
        self::NO_LLEGO => 'etiquetaCancelado',
    ];

    public static function todos(): array
    {
        return array_keys(self::CLASES);
    }

    public static function existe(?string $estado): bool
    {
        return $estado !== null && isset(self::CLASES[$estado]);
    }

    public static function claseEtiqueta(string $estado): string
    {
        return self::CLASES[$estado] ?? 'etiquetaInactivo';
    }
}