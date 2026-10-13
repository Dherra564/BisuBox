<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Componente;
use Aplicacion\Modelos\Proceso;
use Aplicacion\Modelos\ProcesoComponente;
use Aplicacion\Modelos\ProcesoExtra;
use Aplicacion\Repositorios\UsuarioRepositorio;
use DateTime;

class DatosProceso
{
    public const MAXIMO_COMPONENTES = 50;
    public const MAXIMO_EXTRAS = 20;

    public static function leer(): array
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
                'medida' => ($linea['medida'] ?? null) === DatosCompraProveedor::CENTIMETROS ? DatosCompraProveedor::CENTIMETROS : DatosCompraProveedor::METROS,
            ];
            if ($leida['componente'] !== '' || $leida['cantidad'] !== '') {
                $componentes[(int) $clave] = $leida;
            }
        }

        $extras = [];
        foreach (is_array($_POST['extra'] ?? null) ? $_POST['extra'] : [] as $clave => $extra) {
            if (!is_array($extra) || !preg_match('/^[0-9]{1,3}$/', (string) $clave)) {
                continue;
            }
            $leido = [
                'concepto' => UsuarioRepositorio::limpiarEspacios($texto($extra['concepto'] ?? null)),
                'monto' => $texto($extra['monto'] ?? null),
            ];
            if ($leido['concepto'] !== '' || $leido['monto'] !== '') {
                $extras[(int) $clave] = $leido;
            }
        }

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($texto($_POST['nombre'] ?? null)),
            'descripcion' => (string) preg_replace('/\s+/u', ' ', $texto($_POST['descripcion'] ?? null)),
            'fecha' => $texto($_POST['fecha'] ?? null),
            'horas' => $texto($_POST['horas'] ?? null),
            'minutos' => $texto($_POST['minutos'] ?? null),
            'tarifaHora' => $texto($_POST['tarifaHora'] ?? null),
            'componentes' => $componentes,
            'extras' => $extras,
        ];
    }

    public static function vacio(?float $tarifaHora = null): array
    {
        return [
            'nombre' => '',
            'descripcion' => '',
            'fecha' => date('Y-m-d'),
            'horas' => '',
            'minutos' => '',
            'tarifaHora' => Numero::paraCampo($tarifaHora),
            'componentes' => [0 => ['componente' => '', 'cantidad' => '', 'medida' => DatosCompraProveedor::METROS]],
            'extras' => [],
        ];
    }

    /** @param array<int, Componente> $componentes los activos del vendedor, por id */
    public static function validar(Validador $validador, array $datos, array $componentes): void
    {
        $validador->requerido('nombre', $datos['nombre'], 'Escriba qué producto hizo, por ejemplo Collar de perlas')
            ->longitud('nombre', $datos['nombre'], 3, 60, 'El nombre debe tener entre 3 y 60 caracteres')
            ->nombreComercial('nombre', $datos['nombre']);

        $validador->longitud('descripcion', $datos['descripcion'], 1, 200, 'La descripción puede tener hasta 200 caracteres');

        self::validarFecha($validador, $datos['fecha']);
        self::validarTiempo($validador, $datos['horas'], $datos['minutos']);

        if ($datos['tarifaHora'] === '') {
            $validador->agregarError('tarifaHora', 'Escriba cuánto cobra por hora de trabajo, o 0 si no cobra mano de obra');
        } else {
            $tarifa = Numero::leer($datos['tarifaHora']);
            if ($tarifa === null || $tarifa > Numero::MAXIMO) {
                $validador->agregarError('tarifaHora', 'Escriba un monto, por ejemplo 2000 o 2000,50');
            }
        }

        self::validarComponentes($validador, $datos['componentes'], $componentes);
        self::validarExtras($validador, $datos['extras']);

        if ($validador->esValido()) {
            $proceso = self::crearProceso($datos, 0, $componentes);
            if ($proceso->getPrecio() > Numero::MAXIMO) {
                $validador->agregarError('lineas', 'El precio total es demasiado alto; revise las cantidades y los montos');
            }
        }
    }

    /** @param array<int, Componente> $componentes */
    public static function crearProceso(array $datos, int $idVendedor, array $componentes): Proceso
    {
        $minutos = ((int) $datos['horas']) * 60 + (int) $datos['minutos'];
        $tarifaHora = (float) Numero::leer($datos['tarifaHora']);

        $detalles = [];
        $costoComponentes = 0.0;
        foreach ($datos['componentes'] as $linea) {
            $componente = $componentes[(int) $linea['componente']];
            $cantidad = (float) self::cantidadEnUnidad($linea, $componente);
            $costo = $componente->getCostoUnitario();
            $subtotal = round($cantidad * $costo, 2);
            $costoComponentes += $subtotal;

            $detalles[] = new ProcesoComponente(
                null,
                null,
                (int) $componente->getIdComponente(),
                $cantidad,
                $costo,
                $subtotal,
                $componente->getNombre(),
                $componente->getNombreTipo(),
                $componente->getUnidad()
            );
        }

        $extras = [];
        $costoExtras = 0.0;
        foreach ($datos['extras'] as $extra) {
            $monto = (float) Numero::leer($extra['monto']);
            $costoExtras += $monto;
            $extras[] = new ProcesoExtra(null, null, $extra['concepto'], $monto);
        }

        $costoComponentes = round($costoComponentes, 2);
        $costoExtras = round($costoExtras, 2);
        $costoManoObra = round($minutos / 60 * $tarifaHora, 2);

        return new Proceso(
            null,
            $idVendedor,
            $datos['nombre'],
            $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
            PeriodoFechas::leerFecha($datos['fecha']) ?? new DateTime('today'),
            $minutos,
            $tarifaHora,
            $costoComponentes,
            $costoManoObra,
            $costoExtras,
            round($costoComponentes + $costoManoObra + $costoExtras, 2),
            Proceso::ACTIVO,
            null,
            $detalles,
            $extras
        );
    }

    private static function validarFecha(Validador $validador, string $fecha): void
    {
        if ($fecha === '') {
            $validador->agregarError('fecha', 'Escoja en el calendario el día en que hizo el producto');
            return;
        }
        $dia = PeriodoFechas::leerFecha($fecha);
        if ($dia === null) {
            $validador->agregarError('fecha', 'Escoja una fecha válida en el calendario');
        } elseif ($dia > new DateTime('today')) {
            $validador->agregarError('fecha', 'La fecha no puede ser después de hoy');
        } elseif ($dia < new DateTime('2000-01-01')) {
            $validador->agregarError('fecha', 'Escoja una fecha a partir del año 2000');
        }
    }

    private static function validarTiempo(Validador $validador, string $horas, string $minutos): void
    {
        $mensaje = 'Escriba cuánto tardó, por ejemplo 1 hora y 30 minutos (los minutos van de 0 a 59)';

        if (
            ($horas !== '' && !preg_match('/^[0-9]{1,3}$/', $horas))
            || ($minutos !== '' && !preg_match('/^[0-9]{1,2}$/', $minutos))
        ) {
            $validador->agregarError('tiempo', $mensaje);
            return;
        }

        if ((int) $minutos > 59 || ((int) $horas) * 60 + (int) $minutos < 1) {
            $validador->agregarError('tiempo', $mensaje);
        }
    }

    /** @param array<int, Componente> $componentes */
    private static function validarComponentes(Validador $validador, array $lineas, array $componentes): void
    {
        if ($lineas === []) {
            $validador->agregarError('lineas', 'Agregue al menos un componente de los que usó');
            return;
        }
        if (count($lineas) > self::MAXIMO_COMPONENTES) {
            $validador->agregarError('lineas', 'Un proceso puede tener como máximo ' . self::MAXIMO_COMPONENTES . ' componentes');
            return;
        }

        $usados = [];
        foreach ($lineas as $clave => $linea) {
            $campo = 'componente.' . $clave . '.';
            $componente = null;

            if ($linea['componente'] === '') {
                $validador->agregarError($campo . 'componente', 'Escoja el componente que usó');
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

    
    private static function cantidadEnUnidad(array $linea, ?Componente $componente): ?float
    {
        $cantidad = Numero::leer($linea['cantidad']);
        if ($cantidad === null) {
            return null;
        }
        $enCentimetros = $componente?->getUnidad() === UnidadMedida::METRO && $linea['medida'] === DatosCompraProveedor::CENTIMETROS;

        return $enCentimetros ? round($cantidad / 100, 2) : $cantidad;
    }

    private static function validarCantidad(Validador $validador, string $campo, array $linea, ?Componente $componente): void
    {
        $valor = $linea['cantidad'];
        if ($valor === '') {
            $validador->agregarError($campo, 'Escriba cuántos usó');
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
        $cantidad = self::cantidadEnUnidad($linea, $componente);

        if ($componente->getUnidad() === UnidadMedida::UNIDAD && floor($cantidad) != $cantidad) {
            $validador->agregarError($campo, 'Se cuenta por unidades: use números enteros');
        } elseif ($cantidad > $componente->getExistencia() + 0.001) {
            $validador->agregarError(
                $campo,
                'No alcanza: solo tiene ' . UnidadMedida::cantidad($componente->getExistencia(), $componente->getUnidad())
            );
        } elseif (round($cantidad * $componente->getCostoUnitario(), 2) > Numero::MAXIMO) {
            $validador->agregarError($campo, 'Con esta cantidad el costo es demasiado alto');
        }
    }

    private static function validarExtras(Validador $validador, array $extras): void
    {
        if (count($extras) > self::MAXIMO_EXTRAS) {
            $validador->agregarError('extras', 'Un proceso puede tener como máximo ' . self::MAXIMO_EXTRAS . ' costos extra');
            return;
        }

        foreach ($extras as $clave => $extra) {
            $campo = 'extra.' . $clave . '.';

            $validador->requerido($campo . 'concepto', $extra['concepto'], 'Escriba para qué fue, por ejemplo Luz o Transporte')
                ->longitud($campo . 'concepto', $extra['concepto'], 2, 60, 'El concepto debe tener entre 2 y 60 caracteres')
                ->nombreComercial($campo . 'concepto', $extra['concepto'], 'El concepto solo puede tener letras, números, espacios y los signos . , & \' -');

            if ($extra['monto'] === '') {
                $validador->agregarError($campo . 'monto', 'Escriba cuánto fue');
                continue;
            }
            $monto = Numero::leer($extra['monto']);
            if ($monto === null || $monto > Numero::MAXIMO) {
                $validador->agregarError($campo . 'monto', 'Escriba un monto, por ejemplo 1500 o 1500,50');
            } elseif ($monto <= 0) {
                $validador->agregarError($campo . 'monto', 'El monto debe ser mayor que 0');
            }
        }
    }
}