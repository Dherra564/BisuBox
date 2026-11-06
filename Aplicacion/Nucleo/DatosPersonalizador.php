<?php

namespace Aplicacion\Nucleo;

class DatosPersonalizador
{
    public const TAMANO_MINIMO = 1;
    public const TAMANO_MAXIMO = 80;
    public const MANO_OBRA_MAXIMA = 100000;
    public const GANANCIA_MAXIMA = 300;
    public const PIEZAS_POR_FOTO = 40;
    public const REDONDEO_PRECIO = 50;

    public const MENSAJE_TAMANO = 'El tamaño debe ser un número entre 1 y 80 mm, con un decimal como máximo';
    public const MENSAJE_COMPONENTE = 'Elija a qué componente corresponde';
    public const MENSAJE_COMPONENTE_NO_EXISTE = 'Ese componente no existe, está desactivado o no se cuenta por unidad';
    public const MENSAJE_COMPONENTE_REPETIDO = 'Ese componente ya está elegido en otra pieza';
    public const MENSAJE_IMAGEN = 'La imagen recortada no es válida. Vuelva a procesar la foto.';
    public const MENSAJE_NINGUNA = 'Marque al menos una pieza para guardar';
    public const MENSAJE_HILO = 'Elija el hilo que se usa en las piezas';
    public const MENSAJE_MANO_OBRA = 'La mano de obra debe ser un monto entre ₡0 y ₡100 000';
    public const MENSAJE_GANANCIA = 'La ganancia debe ser un porcentaje entero entre 0 y 300';
    public const MENSAJE_SIN_PIEZAS = 'Para mostrar el personalizador en su tienda necesita al menos una pieza con foto';

    public const TIPOS_PIEZA = [
        'pulsera' => ['nombre' => 'Pulsera', 'minimo' => 150, 'maximo' => 220, 'inicial' => 180, 'forma' => 'circulo', 'debeCerrar' => true],
        'tobillera' => ['nombre' => 'Tobillera', 'minimo' => 220, 'maximo' => 280, 'inicial' => 240, 'forma' => 'circulo', 'debeCerrar' => true],
        'collar' => ['nombre' => 'Collar', 'minimo' => 380, 'maximo' => 600, 'inicial' => 450, 'forma' => 'collar', 'debeCerrar' => false],
    ];

    public const PROPORCION_PARA_CERRAR = 0.95;
    public const PASO_LARGO = 5;

    public static function leerTamano(mixed $texto): ?float
    {
        if (!is_string($texto)) {
            return null;
        }
        $texto = str_replace(',', '.', trim($texto));
        if (!preg_match('/^[0-9]{1,2}(\.[0-9])?$/', $texto)) {
            return null;
        }
        $valor = (float) $texto;
        return $valor >= self::TAMANO_MINIMO && $valor <= self::TAMANO_MAXIMO ? $valor : null;
    }

    public static function leerManoObra(mixed $texto): ?float
    {
        $valor = is_string($texto) ? Numero::leer($texto) : null;
        return $valor !== null && $valor <= self::MANO_OBRA_MAXIMA ? $valor : null;
    }

    public static function leerGanancia(mixed $texto): ?int
    {
        if (!is_string($texto) || !preg_match('/^[0-9]{1,3}$/', trim($texto))) {
            return null;
        }
        $valor = (int) trim($texto);
        return $valor <= self::GANANCIA_MAXIMA ? $valor : null;
    }

    public static function decimas(float $milimetros): int
    {
        return (int) round($milimetros * 10);
    }

    public static function precio(float $costoMateriales, float $manoObra, float $ganancia): float
    {
        $base = ($costoMateriales + $manoObra) * (1 + $ganancia / 100);
        return (float) (ceil(round($base, 2) / self::REDONDEO_PRECIO) * self::REDONDEO_PRECIO);
    }
}
