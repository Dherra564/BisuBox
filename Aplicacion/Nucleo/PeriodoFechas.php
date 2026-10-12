<?php

namespace Aplicacion\Nucleo;

use DateTime;

class PeriodoFechas
{
    public const MES = 'mes';
    public const MES_PASADO = 'mesPasado';
    public const ANIO = 'anio';
    public const TODO = 'todo';
    public const RANGO = 'rango';

    public const PERIODOS = [
        self::TODO => 'Todo',
        self::MES => 'Este mes',
        self::MES_PASADO => 'Mes pasado',
        self::ANIO => 'Este año',
    ];

    /** @return array{periodo: string, desde: ?DateTime, hasta: ?DateTime} */
    public static function leer(array $consulta, string $porDefecto = self::TODO): array
    {
        $desde = self::leerFecha($consulta['desde'] ?? null);
        $hasta = self::leerFecha($consulta['hasta'] ?? null);
        $elegido = is_string($consulta['periodo'] ?? null) && isset(self::PERIODOS[$consulta['periodo']]) ? $consulta['periodo'] : null;

        if ($elegido !== null && ($desde !== null || $hasta !== null)) {
            $limites = self::limites($elegido);
            if ($desde?->format('Y-m-d') === $limites['desde']?->format('Y-m-d') && $hasta?->format('Y-m-d') === $limites['hasta']?->format('Y-m-d')) {
                return ['periodo' => $elegido] + $limites;
            }
        }

        if ($desde === null && $hasta === null && ($consulta['periodo'] ?? null) === self::RANGO) {
            return ['periodo' => self::RANGO, 'desde' => null, 'hasta' => null];
        }

        if ($desde !== null || $hasta !== null) {
            if ($desde !== null && $hasta !== null && $desde > $hasta) {
                [$desde, $hasta] = [$hasta, $desde];
            }
            return ['periodo' => self::RANGO, 'desde' => $desde, 'hasta' => $hasta];
        }

        $periodo = $elegido ?? $porDefecto;

        return ['periodo' => $periodo] + self::limites($periodo);
    }

    /** @return array{desde: ?DateTime, hasta: ?DateTime} */
    public static function limites(string $periodo): array
    {
        $hoy = new DateTime('today');

        return match ($periodo) {
            self::MES => ['desde' => (clone $hoy)->modify('first day of this month'), 'hasta' => (clone $hoy)->modify('last day of this month')],
            self::MES_PASADO => ['desde' => (clone $hoy)->modify('first day of last month'), 'hasta' => (clone $hoy)->modify('last day of last month')],
            self::ANIO => ['desde' => new DateTime($hoy->format('Y') . '-01-01'), 'hasta' => new DateTime($hoy->format('Y') . '-12-31')],
            default => ['desde' => null, 'hasta' => null],
        };
    }

    public static function enFrase(array $periodo): string
    {
        $formato = fn(DateTime $fecha): string => $fecha->format('d/m/Y');

        return match (true) {
            $periodo['periodo'] === self::MES => 'de este mes',
            $periodo['periodo'] === self::MES_PASADO => 'del mes pasado',
            $periodo['periodo'] === self::ANIO => 'de este año',
            $periodo['desde'] !== null && $periodo['hasta'] !== null => 'del ' . $formato($periodo['desde']) . ' al ' . $formato($periodo['hasta']),
            $periodo['desde'] !== null => 'desde el ' . $formato($periodo['desde']),
            $periodo['hasta'] !== null => 'hasta el ' . $formato($periodo['hasta']),
            default => '',
        };
    }

    public static function etiqueta(array $periodo): string
    {
        if (isset(self::PERIODOS[$periodo['periodo']])) {
            return $periodo['periodo'] === self::TODO ? '' : self::PERIODOS[$periodo['periodo']];
        }
        $formato = fn(?DateTime $fecha): string => $fecha?->format('d/m/Y') ?? '…';

        return $periodo['desde'] === null && $periodo['hasta'] === null ? '' : $formato($periodo['desde']) . ' – ' . $formato($periodo['hasta']);
    }

    public static function parametros(array $periodo): array
    {
        if ($periodo['periodo'] !== self::RANGO) {
            return ['periodo' => $periodo['periodo']];
        }
        $fechas = array_filter([
            'desde' => $periodo['desde']?->format('Y-m-d'),
            'hasta' => $periodo['hasta']?->format('Y-m-d'),
        ]);

        return $fechas !== [] ? $fechas : ['periodo' => self::RANGO];
    }

    public static function leerFecha(mixed $texto): ?DateTime
    {
        if (!is_string($texto) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return null;
        }
        $fecha = DateTime::createFromFormat('!Y-m-d', $texto);

        return $fecha !== false && $fecha->format('Y-m-d') === $texto ? $fecha : null;
    }
}