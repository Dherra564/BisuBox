<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Componente;
use Aplicacion\Modelos\Producto;
use Aplicacion\Modelos\ProductoComponente;
use Aplicacion\Modelos\ProductoExtra;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;

class DatosProducto
{
    public const MAXIMO_COMPONENTES = 50;
    public const MAXIMO_EXTRAS = 20;
    public const GANANCIAS = [20, 30, 50, 70, 100];

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

        return self::leerDatosComunes() + [
            'cantidad' => $texto($_POST['cantidad'] ?? null),
            'horas' => $texto($_POST['horas'] ?? null),
            'minutos' => $texto($_POST['minutos'] ?? null),
            'tarifaHora' => $texto($_POST['tarifaHora'] ?? null),
            'componentes' => $componentes,
            'extras' => $extras,
        ];
    }

    // Lo único que se cambia al editar: lo que lleva ya quedó guardado
    public static function leerDatosComunes(): array
    {
        $texto = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($texto('nombre')),
            'descripcion' => (string) preg_replace('/\s+/u', ' ', $texto('descripcion')),
            'ganancia' => $texto('ganancia'),
            'precioVenta' => $texto('precioVenta'),
        ];
    }

    public static function vacio(?float $tarifaHora = null): array
    {
        return [
            'nombre' => '',
            'descripcion' => '',
            'ganancia' => '50',
            'precioVenta' => '',
            'cantidad' => '1',
            'horas' => '',
            'minutos' => '',
            'tarifaHora' => Numero::paraCampo($tarifaHora),
            'componentes' => [0 => ['componente' => '', 'cantidad' => '', 'medida' => DatosCompraProveedor::METROS]],
            'extras' => [],
        ];
    }

    public static function paraEditar(Producto $producto): array
    {
        $porcentaje = $producto->getPorcentajeGanancia();

        return [
            'nombre' => $producto->getNombre(),
            'descripcion' => (string) $producto->getDescripcion(),
            'ganancia' => $porcentaje !== null && in_array((int) $porcentaje, self::GANANCIAS, true) && floor($porcentaje) == $porcentaje
                ? (string) (int) $porcentaje : '',
            'precioVenta' => Numero::paraCampo($producto->getPrecioVenta()),
        ];
    }

    public static function validarDatosComunes(Validador $validador, array $datos, int $idVendedor, ?Producto $original = null): void
    {
        $validador->requerido('nombre', $datos['nombre'], 'Escriba el nombre del producto, por ejemplo Collar de perlas')
            ->longitud('nombre', $datos['nombre'], 3, 60, 'El nombre debe tener entre 3 y 60 caracteres')
            ->nombreComercial('nombre', $datos['nombre']);
        if (
            $validador->error('nombre') === null
            && (new ProductoRepositorio())->existeNombre($idVendedor, $datos['nombre'], $original?->getIdProducto())
        ) {
            $validador->agregarError('nombre', 'Ya tiene un producto con ese nombre');
        }

        $validador->longitud('descripcion', $datos['descripcion'], 1, 300, 'La descripción puede tener hasta 300 caracteres');

        if ($datos['precioVenta'] === '') {
            $validador->agregarError('precioVenta', 'Escriba en cuánto lo va a vender');
        } else {
            $precio = Numero::leer($datos['precioVenta']);
            if ($precio === null || $precio > Numero::MAXIMO) {
                $validador->agregarError('precioVenta', 'Escriba un monto, por ejemplo 8500 o 8500,50');
            } elseif ($precio <= 0) {
                $validador->agregarError('precioVenta', 'El precio debe ser mayor que 0');
            }
        }
    }

    /** @param array<int, Componente> $componentes los activos del vendedor, por id */
    public static function validar(Validador $validador, array $datos, array $componentes, int $idVendedor): void
    {
        self::validarDatosComunes($validador, $datos, $idVendedor);

        if ($datos['cantidad'] === '') {
            $validador->agregarError('cantidad', 'Escriba cuántos hizo');
        } elseif (!self::esCantidadValida($datos['cantidad'])) {
            $validador->agregarError('cantidad', 'Escriba cuántos hizo, con números enteros desde 1');
        }
        $cantidad = self::esCantidadValida($datos['cantidad']) ? (int) $datos['cantidad'] : 1;

        self::validarTiempo($validador, $datos['horas'], $datos['minutos']);

        if ($datos['tarifaHora'] === '') {
            $validador->agregarError('tarifaHora', 'Escriba cuánto cobra por hora de trabajo, o 0 si no cobra mano de obra');
        } else {
            $tarifa = Numero::leer($datos['tarifaHora']);
            if ($tarifa === null || $tarifa > Numero::MAXIMO) {
                $validador->agregarError('tarifaHora', 'Escriba un monto, por ejemplo 2000 o 2000,50');
            }
        }

        self::validarComponentes($validador, $datos['componentes'], $componentes, $cantidad);
        self::validarExtras($validador, $datos['extras']);
    }

    public static function esCantidadValida(string $cantidad): bool
    {
        return preg_match('/^[0-9]{1,4}$/', $cantidad) === 1 && (int) $cantidad >= 1;
    }

    /**
     * Arma el producto con los costos de uno solo
     * @param array<int, Componente> $componentes
     */
    public static function crearProducto(array $datos, int $idVendedor, array $componentes): Producto
    {
        $minutos = ((int) $datos['horas']) * 60 + (int) $datos['minutos'];
        $tarifaHora = (float) Numero::leer($datos['tarifaHora']);

        $lineas = [];
        $costoComponentes = 0.0;
        foreach ($datos['componentes'] as $linea) {
            $componente = $componentes[(int) $linea['componente']];
            $cantidad = (float) self::cantidadEnUnidad($linea, $componente);
            $costo = $componente->getCostoUnitario();
            $subtotal = round($cantidad * $costo, 2);
            $costoComponentes += $subtotal;

            $lineas[] = new ProductoComponente(
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
            $extras[] = new ProductoExtra(null, null, $extra['concepto'], $monto);
        }

        $costoComponentes = round($costoComponentes, 2);
        $costoExtras = round($costoExtras, 2);
        $costoManoObra = round($minutos / 60 * $tarifaHora, 2);

        return new Producto(
            null,
            $idVendedor,
            $datos['nombre'],
            $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
            null,
            $minutos,
            $tarifaHora,
            $costoComponentes,
            $costoManoObra,
            $costoExtras,
            round($costoComponentes + $costoManoObra + $costoExtras, 2),
            (float) Numero::leer($datos['precioVenta']),
            0,
            true,
            null,
            $lineas,
            $extras
        );
    }

    private static function validarTiempo(Validador $validador, string $horas, string $minutos): void
    {
        $mensaje = 'Escriba cuánto tarda en hacer uno, por ejemplo 0 horas y 45 minutos (los minutos van de 0 a 59)';

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
    private static function validarComponentes(Validador $validador, array $lineas, array $componentes, int $cantidadProductos): void
    {
        if ($lineas === []) {
            $validador->agregarError('lineas', 'Agregue al menos un componente de los que lleva');
            return;
        }
        if (count($lineas) > self::MAXIMO_COMPONENTES) {
            $validador->agregarError('lineas', 'Un producto puede llevar como máximo ' . self::MAXIMO_COMPONENTES . ' componentes');
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

            self::validarCantidad($validador, $campo . 'cantidad', $linea, $componente, $cantidadProductos);
        }
    }

    // La cantidad en la unidad del componente: los centímetros pasan a metros (75 cm = 0,75 m)
    private static function cantidadEnUnidad(array $linea, ?Componente $componente): ?float
    {
        $cantidad = Numero::leer($linea['cantidad']);
        if ($cantidad === null) {
            return null;
        }
        $enCentimetros = $componente?->getUnidad() === UnidadMedida::METRO && $linea['medida'] === DatosCompraProveedor::CENTIMETROS;

        return $enCentimetros ? round($cantidad / 100, 2) : $cantidad;
    }

    private static function validarCantidad(Validador $validador, string $campo, array $linea, ?Componente $componente, int $cantidadProductos): void
    {
        $valor = $linea['cantidad'];
        if ($valor === '') {
            $validador->agregarError($campo, 'Escriba cuánto lleva');
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
        $necesita = round($cantidad * $cantidadProductos, 2);

        if ($componente->getUnidad() === UnidadMedida::UNIDAD && floor($cantidad) != $cantidad) {
            $validador->agregarError($campo, 'Se cuenta por unidades: use números enteros');
        } elseif ($necesita > $componente->getExistencia() + 0.001) {
            $validador->agregarError(
                $campo,
                'No alcanza: para ' . $cantidadProductos . ' necesita ' . UnidadMedida::cantidad($necesita, $componente->getUnidad())
                . ' y solo tiene ' . UnidadMedida::cantidad($componente->getExistencia(), $componente->getUnidad())
            );
        } elseif (round($cantidad * $componente->getCostoUnitario(), 2) > Numero::MAXIMO) {
            $validador->agregarError($campo, 'Con esta cantidad el costo es demasiado alto');
        }
    }

    private static function validarExtras(Validador $validador, array $extras): void
    {
        if (count($extras) > self::MAXIMO_EXTRAS) {
            $validador->agregarError('extras', 'Un producto puede tener como máximo ' . self::MAXIMO_EXTRAS . ' gastos extra');
            return;
        }

        foreach ($extras as $clave => $extra) {
            $campo = 'extra.' . $clave . '.';

            $validador->requerido($campo . 'concepto', $extra['concepto'], 'Escriba para qué fue, por ejemplo Empaque')
                ->longitud($campo . 'concepto', $extra['concepto'], 2, 60, 'El concepto debe tener entre 2 y 60 caracteres')
                ->nombreComercial($campo . 'concepto', $extra['concepto'], 'El concepto solo puede tener letras, números, espacios y los signos . , & \' -');

            if ($extra['monto'] === '') {
                $validador->agregarError($campo . 'monto', 'Escriba cuánto fue');
                continue;
            }
            $monto = Numero::leer($extra['monto']);
            if ($monto === null || $monto > Numero::MAXIMO) {
                $validador->agregarError($campo . 'monto', 'Escriba un monto, por ejemplo 150 o 150,50');
            } elseif ($monto <= 0) {
                $validador->agregarError($campo . 'monto', 'El monto debe ser mayor que 0');
            }
        }
    }
}