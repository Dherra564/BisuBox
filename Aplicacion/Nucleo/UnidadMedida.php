<?php

namespace Aplicacion\Nucleo;

class UnidadMedida
{
    public const UNIDAD = 'Unidad';
    public const METRO = 'Metro';
    public const GRAMO = 'Gramo';

    private const UNIDADES = [
        self::UNIDAD => ['nombre' => 'Unidad', 'plural' => 'unidades', 'abreviatura' => 'u'],
        self::METRO => ['nombre' => 'Metro', 'plural' => 'metros', 'abreviatura' => 'm'],
        self::GRAMO => ['nombre' => 'Gramo', 'plural' => 'gramos', 'abreviatura' => 'g'],
    ];

    /** @return array<string, array{nombre: string, plural: string, abreviatura: string}> */
    public static function todas(): array
    {
        return self::UNIDADES;
    }

    public static function existe(?string $unidad): bool
    {
        return $unidad !== null && isset(self::UNIDADES[$unidad]);
    }

    public static function nombre(?string $unidad): string
    {
        return self::existe($unidad) ? self::UNIDADES[$unidad]['nombre'] : 'Sin unidad';
    }

    public static function cantidad(float $cantidad, ?string $unidad): string
    {
        $texto = Numero::formatear($cantidad);
        if (!self::existe($unidad)) {
            return $texto;
        }
        $palabra = $cantidad == 1 ? mb_strtolower(self::UNIDADES[$unidad]['nombre'], 'UTF-8') : self::UNIDADES[$unidad]['plural'];
        return $texto . ' ' . $palabra;
    }
}