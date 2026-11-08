<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Componente;

class DatosCotizacion
{
    public const MAXIMO_COMPONENTES = 50;
    public const MENSAJE_FOTO = 'Suba la foto de la pieza que quiere';
    public const MENSAJE_NO_EDITABLE = 'Esta solicitud ya no se puede cambiar porque la tienda ya la respondió o fue cancelada.';
    public const MENSAJE_NO_RESPONDER = 'Esta cotización ya no se puede responder: el cliente la aceptó, la rechazó o la canceló.';
    public const MENSAJE_NO_DECIDIR = 'Esta cotización ya no está esperando su respuesta.';
    public const MENSAJE_RESPUESTA = 'El mensaje solo puede tener letras, números, espacios y los signos . , ; : ¡ ! ¿ ? ( ) " \' / & % # + -';

        public const MENSAJE_PRECIO_CAMBIO = 'La tienda cambió el precio de esta cotización. Revise el precio nuevo antes de aceptar.';
    public static function leerSolicitud(): array
    {
        $descripcion = is_string($_POST['descripcion'] ?? null) ? trim($_POST['descripcion']) : '';

        return ['descripcion' => (string) preg_replace('/\s+/u', ' ', $descripcion)];
    }

    public static function validarSolicitud(Validador $validador, array $datos): void
    {
        $validador->requerido('descripcion', $datos['descripcion'], 'Cuéntenos cómo la quiere: colores, medidas o materiales')
            ->longitud('descripcion', $datos['descripcion'], 10, 300, 'La descripción debe tener entre 10 y 300 caracteres');

        if ($validador->error('descripcion') === null && !preg_match(DatosProducto::PATRON_DESCRIPCION, $datos['descripcion'])) {
            $validador->agregarError('descripcion', DatosProducto::MENSAJE_DESCRIPCION);
        }
        $validador->sinOfensas('descripcion', $datos['descripcion']);
    }

    public static function leerRespuesta(): array
    {
        $texto = fn(mixed $valor): string => is_string($valor) ? trim($valor) : '';

        $componentes = [];
        foreach (is_array($_POST['componente'] ?? null) ? $_POST['componente'] : [] as $clave => $linea) {
            if (!is_array($linea) || !preg_match('/^[0-9]{1,3}$/', (string) $clave)) {
                continue;
            }
            $leida = [
                'componente' => $texto($linea['componente'] ?? null),
                'cantidad' => $texto($linea['cantidad'] ?? null),
                'medida' => ($linea['medida'] ?? null) === DatosCompraProveedor::CENTIMETROS
                    ? DatosCompraProveedor::CENTIMETROS : DatosCompraProveedor::METROS,
            ];
            if ($leida['componente'] !== '' || $leida['cantidad'] !== '') {
                $componentes[(int) $clave] = $leida;
            }
        }

        return [
            'componentes' => $componentes,
            'manoObra' => $texto($_POST['manoObra'] ?? null),
            'ganancia' => $texto($_POST['ganancia'] ?? null),
            'precio' => $texto($_POST['precio'] ?? null),
            'respuesta' => (string) preg_replace('/\s+/u', ' ', $texto($_POST['respuesta'] ?? null)),
        ];
    }

    public static function respuestaVacia(): array
    {
        return [
            'componentes' => [0 => ['componente' => '', 'cantidad' => '', 'medida' => DatosCompraProveedor::METROS]],
            'manoObra' => '',
            'ganancia' => '50',
            'precio' => '',
            'respuesta' => '',
        ];
    }

    public static function respuestaParaEditar(array $cotizacion): array
    {
        $componentes = [];
        foreach ($cotizacion['detalles'] as $indice => $detalle) {
            $componentes[$indice] = [
                'componente' => (string) $detalle['idComponente'],
                'cantidad' => Numero::paraCampo($detalle['cantidad']),
                'medida' => DatosCompraProveedor::METROS,
            ];
        }

        return [
            'componentes' => $componentes !== [] ? $componentes : self::respuestaVacia()['componentes'],
            'manoObra' => Numero::paraCampo($cotizacion['manoObra']),
            'ganancia' => '',
            'precio' => Numero::paraCampo($cotizacion['precio']),
            'respuesta' => (string) $cotizacion['respuesta'],
        ];
    }

    public static function validarRespuesta(Validador $validador, array $datos, array $componentes): void
    {
        self::validarComponentes($validador, $datos['componentes'], $componentes);

        if ($datos['manoObra'] === '') {
            $validador->agregarError('manoObra', 'Escriba la mano de obra, o 0 si no la cobra');
        } elseif (str_starts_with($datos['manoObra'], '-')) {
            $validador->agregarError('manoObra', 'No puede ser un número negativo');
        } else {
            $manoObra = Numero::leer($datos['manoObra']);
            if ($manoObra === null || $manoObra > Numero::MAXIMO) {
                $validador->agregarError('manoObra', 'Escriba un monto, por ejemplo 2000 o 2000,50');
            }
        }

        if ($datos['precio'] === '') {
            $validador->agregarError('precio', 'Escriba el precio para el cliente');
        } elseif (str_starts_with($datos['precio'], '-')) {
            $validador->agregarError('precio', 'No puede ser un número negativo');
        } else {
            $precio = Numero::leer($datos['precio']);
            if ($precio === null || $precio > Numero::MAXIMO) {
                $validador->agregarError('precio', 'Escriba un monto, por ejemplo 8500 o 8500,50');
            } elseif ($precio <= 0) {
                $validador->agregarError('precio', 'El precio debe ser mayor que 0');
            }
        }

        $validador->longitud('respuesta', $datos['respuesta'], 0, 300, 'El mensaje puede tener hasta 300 caracteres');
        if ($validador->error('respuesta') === null && !preg_match(DatosProducto::PATRON_DESCRIPCION, $datos['respuesta'])) {
            $validador->agregarError('respuesta', self::MENSAJE_RESPUESTA);
        }
        $validador->sinOfensas('respuesta', $datos['respuesta']);

        if ($validador->esValido() && self::armarRespuesta($datos, $componentes)['costoTotal'] > Numero::MAXIMO) {
            $validador->agregarError('precio', 'El costo total pasa el máximo permitido; revise las cantidades y los montos');
        }
    }

    public static function armarRespuesta(array $datos, array $componentes): array
    {
        $lineas = [];
        $costoComponentes = 0.0;
        foreach ($datos['componentes'] as $linea) {
            $componente = $componentes[(int) $linea['componente']];
            $cantidad = (float) DatosProducto::cantidadEnUnidad($linea, $componente);
            $costoUnitario = $componente->getCostoUnitario();
            $subtotal = round($cantidad * $costoUnitario, 2);
            $costoComponentes += $subtotal;
            $lineas[] = [
                'idComponente' => (int) $componente->getIdComponente(),
                'cantidad' => $cantidad,
                'costoUnitario' => $costoUnitario,
                'subtotal' => $subtotal,
            ];
        }

        $costoComponentes = round($costoComponentes, 2);
        $manoObra = (float) Numero::leer($datos['manoObra']);

        return [
            'lineas' => $lineas,
            'costoComponentes' => $costoComponentes,
            'manoObra' => $manoObra,
            'costoTotal' => round($costoComponentes + $manoObra, 2),
            'precio' => (float) Numero::leer($datos['precio']),
        ];
    }

    public static function sugerirPrecio(float $costo, int $ganancia): float
    {
        return ceil($costo * (1 + $ganancia / 100) / 10) * 10;
    }

    private static function validarComponentes(Validador $validador, array $lineas, array $componentes): void
    {
        if ($lineas === []) {
            $validador->agregarError('lineas', 'Agregue al menos un componente de los que lleva la pieza');
            return;
        }
        if (count($lineas) > self::MAXIMO_COMPONENTES) {
            $validador->agregarError('lineas', 'Una cotización puede llevar como máximo ' . self::MAXIMO_COMPONENTES . ' componentes');
            return;
        }

        $usados = [];
        foreach ($lineas as $clave => $linea) {
            $campo = 'componente.' . $clave . '.';
            $componente = null;

            if ($linea['componente'] === '') {
                $validador->agregarError($campo . 'componente', 'Escoja el componente');
            } elseif (!ctype_digit($linea['componente']) || !isset($componentes[(int) $linea['componente']])) {
                $validador->agregarError($campo . 'componente', 'Escoja un componente de la lista');
            } elseif (in_array((int) $linea['componente'], $usados, true)) {
                $validador->agregarError($campo . 'componente', 'Este componente ya está en otra línea; sume las cantidades en una sola');
            } else {
                $usados[] = (int) $linea['componente'];
                $componente = $componentes[(int) $linea['componente']];
            }

            self::validarCantidad($validador, $campo . 'cantidad', $linea, $componente);
        }
    }

    private static function validarCantidad(Validador $validador, string $campo, array $linea, ?Componente $componente): void
    {
        $valor = $linea['cantidad'];
        if ($valor === '') {
            $validador->agregarError($campo, 'Escriba cuánto lleva');
            return;
        }
        if (str_starts_with($valor, '-')) {
            $validador->agregarError($campo, 'No puede ser un número negativo');
            return;
        }

        $cantidad = Numero::leer($valor);
        if ($cantidad === null || $cantidad > Numero::MAXIMO) {
            $validador->agregarError($campo, 'Escriba un número, por ejemplo 20 o 1,5');
            return;
        }
        if ($cantidad <= 0) {
            $validador->agregarError($campo, 'La cantidad debe ser mayor que 0');
            return;
        }
        if ($componente === null) {
            return;
        }

        $enCentimetros = $componente->getUnidad() === UnidadMedida::METRO && $linea['medida'] === DatosCompraProveedor::CENTIMETROS;
        if ($enCentimetros && floor($cantidad) != $cantidad) {
            $validador->agregarError($campo, 'Los centímetros van sin decimales');
            return;
        }

        $enUnidad = (float) DatosProducto::cantidadEnUnidad($linea, $componente);
        if ($componente->getUnidad() === UnidadMedida::UNIDAD && floor($enUnidad) != $enUnidad) {
            $validador->agregarError($campo, 'Se cuenta por unidades: use números enteros');
        } elseif ($enUnidad <= 0) {
            $validador->agregarError($campo, 'La cantidad debe ser mayor que 0');
        }
    }

        public static function leerEntrega(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'entrega' => $leer('entrega'),
            'direccion' => (string) preg_replace('/[ \t]+/', ' ', $leer('direccion')),
            'metodoPago' => $leer('metodoPago'),
            'nota' => $leer('nota'),
        ];
    }

    public static function validarEntrega(Validador $validador, array $datos): void
    {
        if (!FormaEntrega::existe($datos['entrega'])) {
            $validador->agregarError('entrega', 'Escoja cómo quiere recibir el pedido');
        }
        if ($datos['entrega'] === FormaEntrega::ENVIO) {
            $validador->requerido('direccion', $datos['direccion'], 'Escriba la dirección de entrega')
                ->longitud('direccion', $datos['direccion'], 10, 250, 'La dirección debe tener entre 10 y 250 caracteres')
                ->direccion('direccion', $datos['direccion']);
        }
        if (!MetodoPago::existe($datos['metodoPago'])) {
            $validador->agregarError('metodoPago', 'Escoja cómo va a pagar');
        }
        $validador->longitud('nota', $datos['nota'], 0, 300, 'La nota puede tener como máximo 300 caracteres');
    }
}