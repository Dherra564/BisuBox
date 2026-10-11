<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Modelos\TipoComponenteAtributo;
use Aplicacion\Modelos\TipoComponenteAtributoOpcion;

class TipoComponenteSugerido
{
    private const TIPOS = [
        'Perlas' => [
            ['nombre' => 'Color', 'clase' => ClaseDato::TEXTO],
            ['nombre' => 'Diámetro', 'clase' => ClaseDato::NUMERO, 'unidad' => 'mm'],
            ['nombre' => 'Material', 'clase' => ClaseDato::LISTA, 'opciones' => ['Vidrio', 'Acrílico', 'Natural']],
        ],
        'Dijes' => [
            ['nombre' => 'Material', 'clase' => ClaseDato::LISTA, 'opciones' => ['Acero', 'Aleación', 'Plata']],
            ['nombre' => 'Forma', 'clase' => ClaseDato::TEXTO],
            ['nombre' => 'Baño', 'clase' => ClaseDato::LISTA, 'opciones' => ['Oro', 'Plata', 'Ninguno']],
        ],
        'Charms' => [
            ['nombre' => 'Tema', 'clase' => ClaseDato::TEXTO],
            ['nombre' => 'Color', 'clase' => ClaseDato::TEXTO],
            ['nombre' => 'Baño', 'clase' => ClaseDato::LISTA, 'opciones' => ['Oro', 'Plata', 'Ninguno']],
        ],
        'Hilos' => [
            ['nombre' => 'Grosor', 'clase' => ClaseDato::NUMERO, 'unidad' => 'mm'],
            ['nombre' => 'Color', 'clase' => ClaseDato::TEXTO],
            ['nombre' => 'Elástico', 'clase' => ClaseDato::SINO],
        ],
    ];

    /** @return array<string, array<int, array{nombre: string, clase: string, unidad?: string, opciones?: string[]}>> */
    public static function todos(): array
    {
        return self::TIPOS;
    }

    public static function existe(string $nombre): bool
    {
        return isset(self::TIPOS[$nombre]);
    }

    public static function crear(string $nombre, int $idVendedor): TipoComponente
    {
        $atributos = [];
        foreach (self::TIPOS[$nombre] as $indice => $dato) {
            $opciones = array_map(
                fn(string $valor): TipoComponenteAtributoOpcion => new TipoComponenteAtributoOpcion(null, null, $valor),
                $dato['opciones'] ?? []
            );
            $atributos[] = new TipoComponenteAtributo(
                null,
                null,
                $dato['nombre'],
                $dato['clase'],
                $dato['unidad'] ?? null,
                false,
                $indice + 1,
                true,
                $opciones
            );
        }

        return new TipoComponente(null, $idVendedor, $nombre, true, $atributos);
    }
}