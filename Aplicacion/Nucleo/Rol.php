<?php

namespace Aplicacion\Nucleo;


class Rol
{
    public const ADMINISTRADOR = 'Administrador';
    public const VENDEDOR = 'Vendedor';

    private const ROLES = [
        self::ADMINISTRADOR => [
            'nombre' => 'Administrador',
            'descripcion' => 'Administra usuarios, catálogos y todo el inventario',
        ],
        self::VENDEDOR => [
            'nombre' => 'Vendedor',
            'descripcion' => 'Trabaja con el inventario y las ventas',
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