<?php

namespace Aplicacion\Nucleo;

class TipoIdentificacion
{
    public const CEDULA = 'Cedula';
    public const DIMEX = 'Dimex';
    public const PASAPORTE = 'Pasaporte';

    private const TIPOS = [
        self::CEDULA => [
            'nombre' => 'Cédula de identidad',
            'patron' => '^[1-9][0-9]{8}$',
            'mensaje' => 'La cédula debe tener 9 dígitos y no empezar con 0, por ejemplo 1-0234-0567',
            'ayuda' => '9 dígitos, por ejemplo 1-0234-0567',
        ],
        self::DIMEX => [
            'nombre' => 'DIMEX',
            'patron' => '^[1-9][0-9]{10,11}$',
            'mensaje' => 'El DIMEX debe tener 11 o 12 dígitos y no empezar con 0',
            'ayuda' => '11 o 12 dígitos, sin ceros al inicio',
        ],
        self::PASAPORTE => [
            'nombre' => 'Pasaporte',
            'patron' => '^[A-Z0-9]{6,20}$',
            'mensaje' => 'El pasaporte debe tener entre 6 y 20 letras o números',
            'ayuda' => 'Entre 6 y 20 letras o números',
        ],
    ];

    /** @return array<string, array{nombre: string, patron: string, mensaje: string, ayuda: string}> */
    public static function todos(): array
    {
        return self::TIPOS;
    }

    public static function existe(?string $tipo): bool
    {
        return $tipo !== null && isset(self::TIPOS[$tipo]);
    }

    public static function nombre(?string $tipo): string
    {
        return self::existe($tipo) ? self::TIPOS[$tipo]['nombre'] : 'Sin tipo';
    }

    public static function mensaje(string $tipo): string
    {
        return self::TIPOS[$tipo]['mensaje'];
    }

    public static function esValida(?string $tipo, string $numero): bool
    {
        return self::existe($tipo) && preg_match('/' . self::TIPOS[$tipo]['patron'] . '/', $numero) === 1;
    }

    public static function limpiar(string $numero): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/', '', $numero));
    }

    public static function formatear(?string $tipo, ?string $numero): string
    {
        $numero = (string) $numero;
        if ($tipo === self::CEDULA && strlen($numero) === 9) {
            return substr($numero, 0, 1) . '-' . substr($numero, 1, 4) . '-' . substr($numero, 5);
        }
        return $numero;
    }
}