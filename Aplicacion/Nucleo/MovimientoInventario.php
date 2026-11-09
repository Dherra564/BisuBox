<?php

namespace Aplicacion\Nucleo;

class MovimientoInventario
{
    public const COMPONENTE = 'Componente';
    public const PRODUCTO = 'Producto';

    public const ENTRADA = 'Entrada';
    public const SALIDA = 'Salida';

    public const MOTIVO_INICIAL = 'Existencia inicial';
    public const MOTIVO_COMPRA = 'Compra recibida';
    public const MOTIVO_COMPRA_ANULADA = 'Compra anulada';
    public const MOTIVO_PRODUCCION = 'Producción';
    public const MOTIVO_PRODUCCION_ANULADA = 'Producción anulada';
    public const MOTIVO_PEDIDO = 'Pedido';
    public const MOTIVO_PEDIDO_CANCELADO = 'Pedido cancelado';

    public const MOTIVOS_AJUSTE = ['Merma', 'Conteo físico', 'Regalo', 'Devolución', 'Otro'];

    public const REFERENCIA_COMPRA = 'Compra';
    public const REFERENCIA_PRODUCCION = 'Produccion';
    public const REFERENCIA_PEDIDO = 'Pedido';
    public const REFERENCIA_AJUSTE = 'Ajuste';

    public const MENSAJE_TIPO = 'Escoja si entra o sale';
    public const MENSAJE_MOTIVO = 'Escoja el motivo del ajuste';
    public const MENSAJE_NOTA = 'La nota puede tener como máximo 300 caracteres';
    public const MENSAJE_OTRO = 'Explique en la nota qué pasó';

    public static function articulo(mixed $texto): ?string
    {
        return match ($texto) {
            'componente' => self::COMPONENTE,
            'producto' => self::PRODUCTO,
            default => null,
        };
    }

    public static function parametro(string $articulo): string
    {
        return $articulo === self::PRODUCTO ? 'producto' : 'componente';
    }

    public static function enlace(array $movimiento): ?string
    {
        return match ($movimiento['referenciaTipo']) {
            self::REFERENCIA_COMPRA => '/compras-proveedor/detalle?id=' . $movimiento['referenciaId'],
            self::REFERENCIA_PEDIDO => '/pedidos/detalle?id=' . $movimiento['referenciaId'],
            self::REFERENCIA_PRODUCCION => $movimiento['idProductoProduccion'] !== null
                ? '/productos/detalle?id=' . $movimiento['idProductoProduccion']
                : null,
            default => null,
        };
    }

    public static function textoReferencia(array $movimiento): string
    {
        return match ($movimiento['referenciaTipo']) {
            self::REFERENCIA_COMPRA => 'Compra #' . $movimiento['referenciaId'],
            self::REFERENCIA_PEDIDO => 'Pedido #' . $movimiento['referenciaId'],
            self::REFERENCIA_PRODUCCION => 'Producción #' . $movimiento['referenciaId'],
            default => '',
        };
    }

    public static function leerAjuste(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'tipo' => $leer('tipo'),
            'cantidad' => $leer('cantidad'),
            'motivo' => $leer('motivo'),
            'nota' => (string) preg_replace('/\s+/u', ' ', $leer('nota')),
        ];
    }

    public static function ajusteVacio(): array
    {
        return ['tipo' => self::ENTRADA, 'cantidad' => '', 'motivo' => '', 'nota' => ''];
    }

    public static function validarAjuste(Validador $validador, array $datos, float $existencia, bool $soloEnteros): ?float
    {
        if (!in_array($datos['tipo'], [self::ENTRADA, self::SALIDA], true)) {
            $validador->agregarError('tipo', self::MENSAJE_TIPO);
        }

        $cantidad = null;
        if ($datos['cantidad'] === '') {
            $validador->agregarError('cantidad', 'Escriba la cantidad');
        } elseif (str_starts_with($datos['cantidad'], '-')) {
            $validador->agregarError('cantidad', 'No puede ser un número negativo');
        } else {
            $cantidad = Numero::leer($datos['cantidad']);
            if ($cantidad === null) {
                $validador->agregarError('cantidad', 'Escriba un número, por ejemplo 12 o 12,50');
            } elseif ($cantidad <= 0) {
                $validador->agregarError('cantidad', 'La cantidad debe ser mayor que 0');
            } elseif ($soloEnteros && floor($cantidad) != $cantidad) {
                $validador->agregarError('cantidad', 'Se cuenta por unidades: use números enteros');
            } elseif ($datos['tipo'] === self::SALIDA && $cantidad > $existencia + 0.001) {
                $validador->agregarError('cantidad', self::mensajeNegativo($existencia));
            } elseif ($datos['tipo'] === self::ENTRADA && $existencia + $cantidad > Numero::MAXIMO) {
                $validador->agregarError('cantidad', 'Con esta cantidad la existencia pasaría el máximo permitido');
            }
        }

        if (!in_array($datos['motivo'], self::MOTIVOS_AJUSTE, true)) {
            $validador->agregarError('motivo', self::MENSAJE_MOTIVO);
        } elseif ($datos['motivo'] === 'Otro' && $datos['nota'] === '') {
            $validador->agregarError('nota', self::MENSAJE_OTRO);
        }
        $validador->longitud('nota', $datos['nota'], 0, 300, self::MENSAJE_NOTA)
            ->sinOfensas('nota', $datos['nota']);

        return $validador->esValido() ? $cantidad : null;
    }

    public static function mensajeNegativo(float $existencia): string
    {
        return 'No puede quedar en negativo: solo hay ' . Numero::formatear(max(0, $existencia));
    }
}