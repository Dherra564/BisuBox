<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\CompraProveedor;
use Aplicacion\Modelos\CompraProveedorDetalle;
use Aplicacion\Modelos\Componente;
use Aplicacion\Modelos\Proveedor;
use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use DateTime;

class DatosCompraProveedor
{
    public const NUEVO = 'nuevo';
    public const MAXIMO_LINEAS = 50;
    public const METROS = 'm';
    public const CENTIMETROS = 'cm';

    public static function leer(): array
    {
        $texto = fn(mixed $valor): string => is_string($valor) ? trim($valor) : '';

        $lineas = [];
        foreach (is_array($_POST['linea'] ?? null) ? $_POST['linea'] : [] as $clave => $linea) {
            if (!is_array($linea) || !preg_match('/^[0-9]{1,3}$/', (string) $clave)) {
                continue;
            }
            $leida = [
                'componente' => $texto($linea['componente'] ?? null),
                'tipo' => $texto($linea['tipo'] ?? null),
                'nombre' => UsuarioRepositorio::limpiarEspacios($texto($linea['nombre'] ?? null)),
                'unidad' => $texto($linea['unidad'] ?? null),
                'cantidad' => $texto($linea['cantidad'] ?? null),
                'medida' => ($linea['medida'] ?? null) === self::CENTIMETROS ? self::CENTIMETROS : self::METROS,
                'total' => $texto($linea['total'] ?? null),
                'valores' => is_array($linea['valor'] ?? null) ? array_map($texto, $linea['valor']) : [],
            ];
            $vacia = $leida['componente'] === '' && $leida['nombre'] === '' && $leida['cantidad'] === '' && $leida['total'] === '';
            if (!$vacia) {
                $lineas[(int) $clave] = $leida;
            }
        }

        return [
            'proveedor' => $texto($_POST['proveedor'] ?? null),
            'fecha' => $texto($_POST['fecha'] ?? null),
            'costoEnvio' => $texto($_POST['costoEnvio'] ?? null),
            'lineas' => $lineas,
        ];
    }

    public static function vacio(string $proveedor = ''): array
    {
        return [
            'proveedor' => $proveedor,
            'fecha' => '',
            'costoEnvio' => '',
            'lineas' => [],
        ];
    }

    /**
     * @param array<int, Proveedor> $proveedores
     * @param array<int, Componente> $componentes
     * @param array<int, TipoComponente> $tipos
     */
    public static function validar(
        Validador $validador,
        array $datos,
        int $idVendedor,
        array $proveedores,
        array $componentes,
        array $tipos
    ): void {
        if ($datos['proveedor'] === '') {
            $validador->agregarError('proveedor', 'Escoja a qué proveedor le compró');
        } elseif (!ctype_digit($datos['proveedor']) || !isset($proveedores[(int) $datos['proveedor']])) {
            $validador->agregarError('proveedor', 'Escoja un proveedor de la lista');
        }

        self::validarFecha($validador, $datos['fecha']);

        if ($datos['costoEnvio'] !== '') {
            $envio = Numero::leer($datos['costoEnvio']);
            if ($envio === null || $envio > Numero::MAXIMO) {
                $validador->agregarError('costoEnvio', 'Escriba un monto, por ejemplo 2500 o 2500,50. Si no pagó envío, déjelo vacío');
            }
        }

        if ($datos['lineas'] === []) {
            $validador->agregarError('lineas', 'Agregue al menos un componente a la compra');
            return;
        }
        if (count($datos['lineas']) > self::MAXIMO_LINEAS) {
            $validador->agregarError('lineas', 'Una compra puede tener como máximo ' . self::MAXIMO_LINEAS . ' líneas');
            return;
        }

        $componentesUsados = [];
        $nombresNuevos = [];
        $componenteRepositorio = new ComponenteRepositorio();
        foreach ($datos['lineas'] as $clave => $linea) {
            $campo = 'linea.' . $clave . '.';
            $unidad = null;

            if ($linea['componente'] === self::NUEVO) {
                $unidad = self::validarNuevo($validador, $campo, $linea, $tipos, $idVendedor, $componenteRepositorio, $nombresNuevos);
                if (isset($tipos[(int) $linea['tipo']]) && ctype_digit($linea['tipo'])) {
                    $tipo = $tipos[(int) $linea['tipo']];
                    DatosComponente::validarValores($validador, $campo . 'valor.', $tipo, DatosComponente::leerValores($tipo, $linea['valores']));
                }
            } elseif ($linea['componente'] === '') {
                $validador->agregarError($campo . 'componente', 'Escoja el componente o cree uno nuevo');
            } elseif (!ctype_digit($linea['componente']) || !isset($componentes[(int) $linea['componente']])) {
                $validador->agregarError($campo . 'componente', 'Escoja un componente de la lista');
            } elseif (in_array((int) $linea['componente'], $componentesUsados, true)) {
                $validador->agregarError($campo . 'componente', 'Este componente ya está en otra línea; sume las cantidades en una sola');
            } else {
                $componentesUsados[] = (int) $linea['componente'];
                $unidad = $componentes[(int) $linea['componente']]->getUnidad();
            }

            self::validarCantidad($validador, $campo . 'cantidad', $linea['cantidad'], $unidad, $linea['medida']);
            self::validarTotal($validador, $campo . 'total', $linea['total']);

            $cantidad = self::cantidadEnUnidad($linea, $unidad);
            if (
                $cantidad !== null && $validador->error($campo . 'cantidad') === null
                && isset($componentes[(int) $linea['componente']])
                && $componentes[(int) $linea['componente']]->getExistencia() + $cantidad > Numero::MAXIMO
            ) {
                $validador->agregarError($campo . 'cantidad', 'Con esta cantidad la existencia pasaría el máximo permitido');
            }
        }
    }

    /**
     * @param array<int, Componente> $componentes
     * @param array<int, TipoComponente> $tipos
     * @return array{compra: CompraProveedor, nuevos: array<int, Componente>}
     */
    public static function crearCompra(array $datos, int $idVendedor, array $componentes, array $tipos): array
    {
        $lineas = [];
        foreach (array_values($datos['lineas']) as $indice => $linea) {
            $unidad = $linea['componente'] === self::NUEVO ? $linea['unidad'] : $componentes[(int) $linea['componente']]->getUnidad();
            $cantidad = (float) self::cantidadEnUnidad($linea, $unidad);
            $total = round((float) Numero::leer($linea['total']), 2);
            $lineas[$indice] = $linea + ['cantidadNumero' => $cantidad, 'costoNumero' => round($total / $cantidad, 4), 'subtotal' => $total];
        }

        $costoEnvio = $datos['costoEnvio'] !== '' ? (float) Numero::leer($datos['costoEnvio']) : 0.0;
        $costosFinales = CostoPromedio::repartirEnvio(
            array_map(fn(array $linea): array => ['cantidad' => $linea['cantidadNumero'], 'subtotal' => $linea['subtotal']], $lineas),
            $costoEnvio
        );

        $detalles = [];
        $nuevos = [];
        foreach ($lineas as $indice => $linea) {
            $esNuevo = $linea['componente'] === self::NUEVO;
            if ($esNuevo) {
                
                $tipo = $tipos[(int) $linea['tipo']];
                $valores = DatosComponente::valoresParaGuardar($tipo, DatosComponente::leerValores($tipo, $linea['valores']));
                $nuevos[$indice] = new Componente(null, $idVendedor, (int) $linea['tipo'], $linea['nombre'], $linea['unidad'], 0, 0, 0, true, $valores);
            }
            $detalles[$indice] = new CompraProveedorDetalle(
                null,
                null,
                $esNuevo ? null : (int) $linea['componente'],
                $linea['cantidadNumero'],
                $linea['costoNumero'],
                $linea['subtotal'],
                $costosFinales[$indice],
                $esNuevo ? $linea['nombre'] : $componentes[(int) $linea['componente']]->getNombre()
            );
        }

        $subtotal = round(array_sum(array_column($lineas, 'subtotal')), 2);
        $compra = new CompraProveedor(
            null,
            $idVendedor,
            (int) $datos['proveedor'],
            PeriodoFechas::leerFecha($datos['fecha']),
            $subtotal,
            $costoEnvio,
            round($subtotal + $costoEnvio, 2),
            CompraProveedor::ACTIVA,
            null,
            $detalles
        );

        return ['compra' => $compra, 'nuevos' => $nuevos];
    }

    private static function validarFecha(Validador $validador, string $fecha): void
    {
        if ($fecha === '') {
            $validador->agregarError('fecha', 'Escoja en el calendario el día de la compra');
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

    private static function validarNuevo(
        Validador $validador,
        string $campo,
        array $linea,
        array $tipos,
        int $idVendedor,
        ComponenteRepositorio $componenteRepositorio,
        array &$nombresNuevos
    ): ?string {
        if ($linea['tipo'] === '' || !ctype_digit($linea['tipo']) || !isset($tipos[(int) $linea['tipo']])) {
            $validador->agregarError($campo . 'tipo', 'Escoja el tipo del componente nuevo');
        }

        $validador->requerido($campo . 'nombre', $linea['nombre'], 'Escriba el nombre del componente nuevo')
            ->longitud($campo . 'nombre', $linea['nombre'], 2, 100, 'El nombre es muy corto');
        $nombreComparable = mb_strtolower($linea['nombre'], 'UTF-8');
        if ($validador->error($campo . 'nombre') === null) {
            if ($componenteRepositorio->existeNombre($idVendedor, $linea['nombre'])) {
                $validador->agregarError($campo . 'nombre', 'Ya tiene un componente con ese nombre; escójalo de la lista');
            } elseif (in_array($nombreComparable, $nombresNuevos, true)) {
                $validador->agregarError($campo . 'nombre', 'Otra línea ya crea un componente con este nombre');
            }
        }
        $nombresNuevos[] = $nombreComparable;

        if (!UnidadMedida::existe($linea['unidad'])) {
            $validador->agregarError($campo . 'unidad', 'Escoja cómo se cuenta');
            return null;
        }

        return $linea['unidad'];
    }

    
    private static function cantidadEnUnidad(array $linea, ?string $unidad): ?float
    {
        $cantidad = Numero::leer($linea['cantidad']);
        if ($cantidad === null) {
            return null;
        }

        return $unidad === UnidadMedida::METRO && $linea['medida'] === self::CENTIMETROS ? round($cantidad / 100, 2) : $cantidad;
    }

    private static function validarCantidad(Validador $validador, string $campo, string $valor, ?string $unidad, string $medida): void
    {
        if ($valor === '') {
            $validador->agregarError($campo, 'Escriba cuánto compró');
            return;
        }
        $cantidad = Numero::leer($valor);
        $enCentimetros = $unidad === UnidadMedida::METRO && $medida === self::CENTIMETROS;
        if ($cantidad === null || $cantidad > Numero::MAXIMO) {
            $validador->agregarError($campo, 'Escriba un número, por ejemplo 200 o 1,5');
        } elseif ($cantidad <= 0) {
            $validador->agregarError($campo, 'La cantidad debe ser mayor que 0');
        } elseif ($unidad === UnidadMedida::UNIDAD && floor($cantidad) != $cantidad) {
            $validador->agregarError($campo, 'Se cuenta por unidades: use números enteros');
        } elseif ($enCentimetros && floor($cantidad) != $cantidad) {
            $validador->agregarError($campo, 'Los centímetros van sin decimales');
        }
    }

    private static function validarTotal(Validador $validador, string $campo, string $valor): void
    {
        if ($valor === '') {
            $validador->agregarError($campo, 'Escriba cuánto pagó en total por este componente');
            return;
        }
        $total = Numero::leer($valor);
        if ($total === null || $total > Numero::MAXIMO) {
            $validador->agregarError($campo, 'Escriba un monto, por ejemplo 2400 o 2400,50');
        }
    }
}