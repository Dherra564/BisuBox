<?php

namespace Aplicacion\Nucleo;

class EstadoPedido
{
    public const PENDIENTE = 'Pendiente';
    public const CONFIRMADO = 'Confirmado';
    public const ENTREGADO = 'Entregado';
    public const CANCELADO = 'Cancelado';

    private const CLASES = [
        self::PENDIENTE => 'etiquetaBajo',
        self::CONFIRMADO => 'etiquetaInfo',
        self::ENTREGADO => 'etiquetaActivo',
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