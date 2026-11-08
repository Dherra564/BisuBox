<?php

namespace Aplicacion\Nucleo;

class EstadoCotizacion
{
    public const ENVIADA = 'Enviada';
    public const RESPONDIDA = 'Respondida';
    public const ACEPTADA = 'Aceptada';
    public const RECHAZADA = 'Rechazada';
    public const CANCELADA = 'Cancelada';

    private const CLASES = [
        self::ENVIADA => 'etiquetaBajo',
        self::RESPONDIDA => 'etiquetaInfo',
        self::ACEPTADA => 'etiquetaActivo',
        self::RECHAZADA => 'etiquetaCancelado',
        self::CANCELADA => 'etiquetaCancelado',
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