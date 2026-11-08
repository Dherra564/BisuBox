<?php

namespace Aplicacion\Nucleo;

class TipoIdentificacion
{
    public const CEDULA = 'Cedula';
    public const CEDULA_JURIDICA = 'CedulaJuridica';
    public const DIMEX = 'Dimex';
    public const PASAPORTE = 'Pasaporte';

    private const TIPOS = [
        self::CEDULA => [
            'nombre' => 'Cédula nacional',
            'patron' => '^[1-9][0-9]{8}$',
            'mensaje' => 'La cédula debe tener 9 dígitos y no empezar con 0, por ejemplo 1-0234-0567',
            'ayuda' => '9 dígitos, por ejemplo 1-0234-0567',
            'grupos' => '1-4-4',
            'soloNumeros' => true,
            'largoMaximo' => 9,
        ],
        self::CEDULA_JURIDICA => [
            'nombre' => 'Cédula jurídica',
            'patron' => '^3[0-9]{9}$',
            'mensaje' => 'La cédula jurídica debe tener 10 dígitos y empezar con 3, por ejemplo 3-101-123456',
            'ayuda' => '10 dígitos, por ejemplo 3-101-123456',
            'grupos' => '1-3-6',
            'soloNumeros' => true,
            'largoMaximo' => 10,
        ],
        self::DIMEX => [
            'nombre' => 'DIMEX',
            'patron' => '^[1-9][0-9]{10,11}$',
            'mensaje' => 'El DIMEX debe tener 11 o 12 dígitos y no empezar con 0',
            'ayuda' => '11 o 12 dígitos, sin ceros al inicio',
            'grupos' => '',
            'soloNumeros' => true,
            'largoMaximo' => 12,
        ],
        self::PASAPORTE => [
            'nombre' => 'Pasaporte',
            'patron' => '^[A-Z0-9]{6,20}$',
            'mensaje' => 'El pasaporte debe tener entre 6 y 20 letras o números',
            'ayuda' => 'Entre 6 y 20 letras o números',
            'grupos' => '',
            'soloNumeros' => false,
            'largoMaximo' => 20,
        ],
    ];

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
        return $tipo !== null && isset(self::TIPOS[$tipo]) ? self::TIPOS[$tipo]['nombre'] : 'Sin tipo';
    }

    public static function mensaje(string $tipo): string
    {
        return self::TIPOS[$tipo]['mensaje'];
    }

    public static function esValida(?string $tipo, string $numero): bool
    {
        return $tipo !== null && isset(self::TIPOS[$tipo])
            && preg_match('/' . self::TIPOS[$tipo]['patron'] . '/', $numero) === 1;
    }

    public static function limpiar(string $numero): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/', '', $numero));
    }

    public static function formatear(?string $tipo, ?string $numero): string
    {
        $numero = (string) $numero;
        if ($tipo === null || !isset(self::TIPOS[$tipo]) || self::TIPOS[$tipo]['grupos'] === '') {
            return $numero;
        }

        $grupos = array_map('intval', explode('-', self::TIPOS[$tipo]['grupos']));
        if (strlen($numero) !== array_sum($grupos)) {
            return $numero;
        }

        $partes = [];
        $inicio = 0;
        foreach ($grupos as $tamano) {
            $partes[] = substr($numero, $inicio, $tamano);
            $inicio += $tamano;
        }

        return implode('-', $partes);
    }
}