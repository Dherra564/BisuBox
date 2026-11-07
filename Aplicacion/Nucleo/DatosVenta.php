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

    public static function tipoDe(Pedido $pedido): string
    {
        $hechos = 0;
        $personalizados = 0;
        foreach ($pedido->getDetalles() as $detalle) {
            if ($detalle->esDiseno()) {
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
