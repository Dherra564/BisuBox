<?php

namespace Aplicacion\Nucleo;

class ClaseDato
{
    public const TEXTO = 'Texto';
    public const NUMERO = 'Numero';
    public const LISTA = 'Lista';
    public const SINO = 'SiNo';

    private const CLASES = [
        self::TEXTO => [
            'nombre' => 'Texto',
            'descripcion' => 'Algo que se escribe libre',
            'ejemplo' => 'Color: Rosado pastel',
            'icono' => 'texto',
        ],
        self::NUMERO => [
            'nombre' => 'Número',
            'descripcion' => 'Una medida o cantidad, con unidad si quiere',
            'ejemplo' => 'Diámetro: 8 mm',
            'icono' => 'numero',
        ],
        self::LISTA => [
            'nombre' => 'Lista de opciones',
            'descripcion' => 'Se escoge una opción de una lista',
            'ejemplo' => 'Material: vidrio, acrílico o natural',
            'icono' => 'lista',
        ],
        self::SINO => [
            'nombre' => 'Sí/No',
            'descripcion' => 'Algo que se tiene o no',
            'ejemplo' => 'Elástico: sí',
            'icono' => 'sino',
        ],
    ];

    public const MAXIMO_OPCIONES = 30;

    /** @return array<string, array{nombre: string, descripcion: string, ejemplo: string, icono: string}> */
    public static function todas(): array
    {
        return self::CLASES;
    }

    public static function existe(?string $clase): bool
    {
        return $clase !== null && isset(self::CLASES[$clase]);
    }

    public static function nombre(?string $clase): string
    {
        return self::existe($clase) ? self::CLASES[$clase]['nombre'] : 'Sin clase';
    }
}