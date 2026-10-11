<?php

namespace Aplicacion\Nucleo;

class CostoPromedio
{
    /** @return array{existencia: float, costo: float} */
    public static function sumar(float $existencia, float $costo, float $cantidad, float $costoCompra): array
    {
        $existencia = max(0, $existencia);
        $nuevaExistencia = round($existencia + $cantidad, 2);
        $nuevoCosto = $nuevaExistencia > 0
            ? ($existencia * $costo + $cantidad * $costoCompra) / $nuevaExistencia
            : $costoCompra;

        return ['existencia' => $nuevaExistencia, 'costo' => round($nuevoCosto, 4)];
    }

    /** @return array{existencia: float, costo: float} */
    public static function restar(float $existencia, float $costo, float $cantidad, float $costoCompra): array
    {
        $nuevaExistencia = round($existencia - $cantidad, 2);
        if ($nuevaExistencia <= 0) {
            return ['existencia' => 0.0, 'costo' => $costo];
        }
        $nuevoCosto = ($existencia * $costo - $cantidad * $costoCompra) / $nuevaExistencia;

        return ['existencia' => $nuevaExistencia, 'costo' => round(max(0, $nuevoCosto), 4)];
    }

    /** @param array<int, array{cantidad: float, subtotal: float}> $lineas  @return array<int, float> */
    public static function repartirEnvio(array $lineas, float $costoEnvio): array
    {
        $totalSubtotales = array_sum(array_column($lineas, 'subtotal'));
        $totalCantidades = array_sum(array_column($lineas, 'cantidad'));

        $costosFinales = [];
        foreach ($lineas as $indice => $linea) {
            $parte = $totalSubtotales > 0
                ? $costoEnvio * $linea['subtotal'] / $totalSubtotales
                : ($totalCantidades > 0 ? $costoEnvio * $linea['cantidad'] / $totalCantidades : 0);
            $costosFinales[$indice] = $linea['cantidad'] > 0 ? round(($linea['subtotal'] + $parte) / $linea['cantidad'], 4) : 0;
        }

        return $costosFinales;
    }
}