<?php

namespace Aplicacion\Nucleo;

class Rol
{
    public const VENDEDOR = 'Vendedor';
    public const CLIENTE = 'Cliente';

    private const ROLES = [
        self::VENDEDOR => [
            'nombre' => 'Vendedor',
            'descripcion' => 'Administra su negocio y vende en su propia tienda',
        ],
        self::CLIENTE => [
            'nombre' => 'Cliente',
            'descripcion' => 'Compra en las tiendas de los vendedores',
        ],
    ];

    /** @return array<string, array{nombre: string, descripcion: string}> */
    public static function todos(): array
    {
        return self::ROLES;
    }

    public static function existe(?string $rol): bool
    {
        return $rol !== null && isset(self::ROLES[$rol]);
    }

    public static function nombre(?string $rol): string
    {
        return self::existe($rol) ? self::ROLES[$rol]['nombre'] : 'Sin rol';
    }
}