<?php

namespace Aplicacion\Nucleo;

class EstadoPersonalizado
{
    public const EN_REVISION = 'En revisión';
    public const COTIZADO = 'Cotizado';
    public const COMPRADO = 'Comprado';
    public const CANCELADO = 'Cancelado';

    private const CLASES = [
        self::EN_REVISION => 'etiquetaBajo',
        self::COTIZADO => 'etiquetaInfo',
        self::COMPRADO => 'etiquetaActivo',
        self::CANCELADO => 'etiquetaCancelado',
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