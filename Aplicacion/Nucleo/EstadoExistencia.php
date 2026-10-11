<?php

namespace Aplicacion\Nucleo;

class EstadoExistencia
{
    public const AGOTADO = 'agotado';
    public const BAJO = 'bajo';
    public const SUFICIENTE = 'suficiente';

    private const ESTADOS = [
        self::AGOTADO => ['nombre' => 'Agotado', 'clase' => 'etiquetaAgotado'],
        self::BAJO => ['nombre' => 'Por acabarse', 'clase' => 'etiquetaBajo'],
        self::SUFICIENTE => ['nombre' => 'Suficiente', 'clase' => 'etiquetaNormal'],
    ];

    /** @return array<string, array{nombre: string, clase: string}> */
    public static function todos(): array
    {
        return self::ESTADOS;
    }

    public static function existe(?string $estado): bool
    {
        return $estado !== null && isset(self::ESTADOS[$estado]);
    }

    public static function calcular(float $existencia, float $existenciaMinima): string
    {
        if ($existencia <= 0) {
            return self::AGOTADO;
        }
        return $existenciaMinima > 0 && $existencia <= $existenciaMinima ? self::BAJO : self::SUFICIENTE;
    }

    public static function nombre(string $estado): string
    {
        return self::ESTADOS[$estado]['nombre'] ?? '';
    }

    public static function clase(string $estado): string
    {
        return self::ESTADOS[$estado]['clase'] ?? '';
    }
}