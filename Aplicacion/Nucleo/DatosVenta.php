<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Pedido;
use DateTime;

class DatosVenta
{
    public const TIPO_HECHO = 'Producto hecho';
    public const TIPO_PERSONALIZADO = 'Personalizado';
    public const TIPO_MIXTA = 'Mixta';
    public const TIPOS = [self::TIPO_HECHO, self::TIPO_PERSONALIZADO, self::TIPO_MIXTA];

    public const PAGO_COMPLETO = 'completo';
    public const PAGO_PENDIENTE = 'pendiente';

    public const ESTADO_PAGADA = 'Pagada';
    public const ESTADO_POR_COBRAR = 'Por cobrar';
    public const ESTADOS = [self::ESTADO_PAGADA, self::ESTADO_POR_COBRAR];

    public const DIAS_INICIALES = 15;
    public const DIAS_MAXIMOS = 180;

    public const MENSAJE_PAGO = 'Indique si el cliente pagó todo o si queda debiendo';
    public const MENSAJE_VENCIMIENTO = 'La fecha límite para pagar debe estar entre hoy y dentro de 6 meses';
    public const MENSAJE_MONTO = 'Escriba un monto, por ejemplo 5000 o 5000,50';
    public const MENSAJE_METODO = 'Escoja cómo le pagó';
    public const NOTA_PAGO_AL_ENTREGAR = 'Pagado al entregar el pedido';

    public static function leerAbono(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'monto' => $leer('monto'),
            'metodoPago' => $leer('metodoPago'),
            'nota' => (string) preg_replace('/\s+/u', ' ', $leer('nota')),
        ];
    }

    public static function validarAbono(Validador $validador, array $datos, float $saldo): ?float
    {
        $monto = null;
        if ($datos['monto'] === '') {
            $validador->agregarError('monto', 'Escriba cuánto le pagó');
        } elseif (str_starts_with($datos['monto'], '-')) {
            $validador->agregarError('monto', 'No puede ser un número negativo');
        } else {
            $monto = Numero::leer($datos['monto']);
            if ($monto === null) {
                $validador->agregarError('monto', self::MENSAJE_MONTO);
            } elseif ($monto <= 0) {
                $validador->agregarError('monto', 'El abono debe ser mayor que 0');
            } elseif ($monto > $saldo + 0.001) {
                $validador->agregarError('monto', self::mensajeSaldo($saldo));
            }
        }
        if (!MetodoPago::existe($datos['metodoPago'])) {
            $validador->agregarError('metodoPago', self::MENSAJE_METODO);
        }
        $validador->longitud('nota', $datos['nota'], 0, 300, 'La nota puede tener como máximo 300 caracteres')
            ->sinOfensas('nota', $datos['nota']);

        return $validador->esValido() ? $monto : null;
    }

    public static function mensajeSaldo(float $saldo): string
    {
        return 'El abono no puede ser mayor que lo que debe (' . Numero::moneda($saldo) . ')';
    }

    public static function recordatorio(array $venta, string $tienda): string
    {
        $texto = 'Hola ' . explode(' ', trim($venta['cliente']))[0] . ', le escribe ' . $tienda
            . '. Le recordamos que tiene pendiente ' . Numero::moneda($venta['saldo']) . ' del pedido #' . $venta['idPedido'];
        if ($venta['vencimiento'] !== null) {
            $texto .= ', con fecha límite el ' . $venta['vencimiento']->format('d/m/Y');
        }
        return $texto . '. ¡Muchas gracias!';
    }

    public static function tipoDe(Pedido $pedido): string
    {
        $hechos = 0;
        $personalizados = 0;
        foreach ($pedido->getDetalles() as $detalle) {
            if (!$detalle->esDelCatalogo()) {
                $personalizados++;
            } else {
                $hechos++;
            }
        }
        if ($hechos > 0 && $personalizados > 0) {
            return self::TIPO_MIXTA;
        }
        return $personalizados > 0 ? self::TIPO_PERSONALIZADO : self::TIPO_HECHO;
    }

    public static function leerPago(mixed $texto): ?string
    {
        return in_array($texto, [self::PAGO_COMPLETO, self::PAGO_PENDIENTE], true) ? $texto : null;
    }

    public static function leerVencimiento(mixed $texto, ?DateTime $hoy = null): ?DateTime
    {
        if (!is_string($texto) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return null;
        }
        $fecha = DateTime::createFromFormat('!Y-m-d', $texto);
        if ($fecha === false || $fecha->format('Y-m-d') !== $texto) {
            return null;
        }
        $hoy = $hoy !== null ? (clone $hoy)->setTime(0, 0) : new DateTime('today');
        $limite = (clone $hoy)->modify('+' . self::DIAS_MAXIMOS . ' days');
        return $fecha >= $hoy && $fecha <= $limite ? $fecha : null;
    }

    public static function vencimientoInicial(): string
    {
        return (new DateTime('today'))->modify('+' . self::DIAS_INICIALES . ' days')->format('Y-m-d');
    }

    public static function vencimientoMaximo(): string
    {
        return (new DateTime('today'))->modify('+' . self::DIAS_MAXIMOS . ' days')->format('Y-m-d');
    }

    public static function claseEtiqueta(string $estadoPago, ?DateTime $vencimiento, float $saldo): string
    {
        if ($estadoPago === self::ESTADO_PAGADA || $saldo <= 0) {
            return 'etiquetaActivo';
        }
        return $vencimiento !== null && $vencimiento < new DateTime('today') ? 'etiquetaCancelado' : 'etiquetaBajo';
    }
}
