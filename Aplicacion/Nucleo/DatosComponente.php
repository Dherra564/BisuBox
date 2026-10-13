<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Componente;
use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Modelos\TipoComponenteAtributo;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;

class DatosComponente
{
    public const CENTIMETROS = 'cm';
    public const PATRON_NOMBRE = '/^[\p{L}0-9][\p{L}0-9 .,()#\/&\'-]*$/u';
    public const MENSAJE_NOMBRE = 'El nombre solo puede tener letras, números, espacios y los signos . , ( ) # / & \' -';
    public const MENSAJE_TEXTO = 'Solo puede tener letras, números, espacios y los signos . , ( ) # / & \' -';

    public static function validarNombre(Validador $validador, string $campo, string $nombre, string $mensajeRequerido): void
    {
        $validador->requerido($campo, $nombre, $mensajeRequerido)
            ->longitud($campo, $nombre, 2, 100, 'El nombre debe tener entre 2 y 100 caracteres');
        if ($validador->error($campo) === null && !preg_match(self::PATRON_NOMBRE, $nombre)) {
            $validador->agregarError($campo, self::MENSAJE_NOMBRE);
        }
    }

    public static function leer(TipoComponente $tipo): array
    {
        $texto = fn(mixed $valor): string => is_string($valor) ? trim($valor) : '';

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($texto($_POST['nombre'] ?? null)),
            'unidad' => $texto($_POST['unidad'] ?? null),
            'existencia' => $texto($_POST['existencia'] ?? null),
            'medida' => ($_POST['medida'] ?? null) === self::CENTIMETROS ? self::CENTIMETROS : 'm',
            'costoTotal' => $texto($_POST['costoTotal'] ?? null),
            'existenciaMinima' => $texto($_POST['existenciaMinima'] ?? null),
            'valores' => self::leerValores($tipo, $_POST['valor'] ?? null),
        ];
    }

    public static function leerValores(TipoComponente $tipo, mixed $enviados): array
    {
        $enviados = is_array($enviados) ? $enviados : [];

        $valores = [];
        foreach ($tipo->getAtributosActivos() as $atributo) {
            $valor = $enviados[$atributo->getIdAtributo()] ?? null;
            $valor = is_string($valor) ? trim($valor) : '';
            if ($atributo->getClase() === ClaseDato::SINO) {
                $valor = $valor === '1' ? '1' : '0';
            }
            $valores[(int) $atributo->getIdAtributo()] = UsuarioRepositorio::limpiarEspacios($valor);
        }

        return $valores;
    }

    
    public static function validarValores(Validador $validador, string $prefijo, TipoComponente $tipo, array $valores): void
    {
        foreach ($tipo->getAtributosActivos() as $atributo) {
            self::validarValor($validador, $prefijo . $atributo->getIdAtributo(), $atributo, $valores[(int) $atributo->getIdAtributo()] ?? '');
        }
    }

    
    public static function valoresParaGuardar(TipoComponente $tipo, array $valores): array
    {
        $guardar = [];
        foreach ($tipo->getAtributosActivos() as $atributo) {
            $valor = $valores[(int) $atributo->getIdAtributo()] ?? '';
            if ($atributo->getClase() === ClaseDato::NUMERO && $valor !== '') {
                $valor = (string) Numero::leer($valor);
            }
            $guardar[(int) $atributo->getIdAtributo()] = $valor;
        }

        return $guardar;
    }

    public static function validar(
        Validador $validador,
        array $datos,
        TipoComponente $tipo,
        int $idVendedor,
        ?Componente $original = null
    ): void {
        self::validarNombre($validador, 'nombre', $datos['nombre'], 'Escriba el nombre del componente, por ejemplo Perla rosada 8 mm');
        if (
            $validador->error('nombre') === null
            && (new ComponenteRepositorio())->existeNombre($idVendedor, $datos['nombre'], $original?->getIdComponente())
        ) {
            $validador->agregarError('nombre', 'Ya tiene un componente con ese nombre');
        }

        if (!UnidadMedida::existe($datos['unidad'])) {
            $validador->agregarError('unidad', 'Escoja cómo se mide este componente');
        }
        $enUnidades = $datos['unidad'] === UnidadMedida::UNIDAD;

        
        if ($original === null) {
            self::validarCantidad($validador, 'existencia', $datos['existencia'], $enUnidades, false);
            $enCentimetros = $datos['unidad'] === UnidadMedida::METRO && $datos['medida'] === self::CENTIMETROS;
            $existencia = Numero::leer($datos['existencia']) ?? 0;
            if ($validador->error('existencia') === null && $enCentimetros && floor($existencia) != $existencia) {
                $validador->agregarError('existencia', 'Los centímetros van sin decimales');
            }
            if ($validador->error('existencia') === null && $existencia > 0) {
                self::validarCantidad($validador, 'costoTotal', $datos['costoTotal'], false, true);
            }
        }
        self::validarCantidad($validador, 'existenciaMinima', $datos['existenciaMinima'], $enUnidades, false);

        self::validarValores($validador, 'valor.', $tipo, $datos['valores']);
    }

    public static function crearComponente(array $datos, TipoComponente $tipo, int $idVendedor, ?Componente $original = null): Componente
    {
        return new Componente(
            $original?->getIdComponente(),
            $idVendedor,
            (int) $tipo->getIdTipoComponente(),
            $datos['nombre'],
            $datos['unidad'],
            $original?->getExistencia() ?? self::existencia($datos),
            $original?->getCostoUnitario() ?? self::costoUnitario(self::existencia($datos), $datos['costoTotal']),
            Numero::leer($datos['existenciaMinima']) ?? 0,
            $original?->getActivo() ?? true,
            self::valoresParaGuardar($tipo, $datos['valores'])
        );
    }

    public static function paraFormulario(Componente $componente, TipoComponente $tipo): array
    {
        $valores = [];
        foreach ($tipo->getAtributosActivos() as $atributo) {
            $valor = $componente->getValor((int) $atributo->getIdAtributo());
            if ($atributo->getClase() === ClaseDato::NUMERO && $valor !== '') {
                $valor = Numero::paraCampo((float) $valor);
            }
            $valores[(int) $atributo->getIdAtributo()] = $valor;
        }

        return [
            'nombre' => $componente->getNombre(),
            'unidad' => $componente->getUnidad(),
            'existencia' => Numero::paraCampo($componente->getExistencia()),
            'medida' => 'm',
            'costoTotal' => '',
            'existenciaMinima' => $componente->getExistenciaMinima() > 0 ? Numero::paraCampo($componente->getExistenciaMinima()) : '',
            'valores' => $valores,
        ];
    }

    public static function vacio(TipoComponente $tipo): array
    {
        $valores = [];
        foreach ($tipo->getAtributosActivos() as $atributo) {
            $valores[(int) $atributo->getIdAtributo()] = $atributo->getClase() === ClaseDato::SINO ? '0' : '';
        }

        return [
            'nombre' => '',
            'unidad' => UnidadMedida::UNIDAD,
            'existencia' => '',
            'medida' => 'm',
            'costoTotal' => '',
            'existenciaMinima' => '',
            'valores' => $valores,
        ];
    }

    public static function mostrarValor(TipoComponenteAtributo $atributo, string $valor): string
    {
        if ($valor === '') {
            return '';
        }

        return match ($atributo->getClase()) {
            ClaseDato::NUMERO => Numero::formatear((float) $valor) . ($atributo->getUnidad() ? ' ' . $atributo->getUnidad() : ''),
            ClaseDato::SINO => $valor === '1' ? 'Sí' : 'No',
            ClaseDato::LISTA => self::nombreOpcion($atributo, $valor),
            default => $valor,
        };
    }

    private static function nombreOpcion(TipoComponenteAtributo $atributo, string $valor): string
    {
        foreach ($atributo->getOpciones() as $opcion) {
            if ((string) $opcion->getIdOpcion() === $valor) {
                return $opcion->getValor();
            }
        }
        return '';
    }

    
    private static function costoUnitario(float $cantidad, string $costoTotal): float
    {
        return $cantidad > 0 ? round((Numero::leer($costoTotal) ?? 0) / $cantidad, 4) : 0.0;
    }

    
    private static function existencia(array $datos): float
    {
        $cantidad = Numero::leer($datos['existencia']) ?? 0;
        return $datos['unidad'] === UnidadMedida::METRO && $datos['medida'] === self::CENTIMETROS ? round($cantidad / 100, 2) : $cantidad;
    }

    private static function validarCantidad(Validador $validador, string $campo, string $valor, bool $entera, bool $obligatoria): void
    {
        if ($valor === '') {
            if ($obligatoria) {
                $validador->agregarError($campo, 'Escriba cuánto pagó en total por lo que tiene. Si no lo sabe, ponga 0');
            }
            return;
        }

        $numero = Numero::leer($valor);
        if (str_starts_with($valor, '-')) {
            $validador->agregarError($campo, 'No puede ser un número negativo');
        } elseif ($numero === null || $numero > Numero::MAXIMO) {
            $validador->agregarError($campo, 'Escriba un número con hasta 2 decimales, por ejemplo 12 o 12,50');
        } elseif ($entera && floor($numero) != $numero) {
            $validador->agregarError($campo, 'Si se mide por unidades, use números enteros');
        }
    }

    private static function validarValor(Validador $validador, string $campo, TipoComponenteAtributo $atributo, string $valor): void
    {
        $clase = $atributo->getClase();

        if ($valor === '' || ($clase === ClaseDato::SINO)) {
            if ($valor === '') {
                $validador->agregarError($campo, 'Este tipo siempre pide ' . mb_strtolower($atributo->getNombre(), 'UTF-8'));
            }
            return;
        }

        if ($clase === ClaseDato::NUMERO && str_starts_with($valor, '-')) {
            $validador->agregarError($campo, 'No puede ser un número negativo');
        } elseif ($clase === ClaseDato::NUMERO && Numero::leer($valor) === null) {
            $validador->agregarError($campo, 'Escriba un número, por ejemplo 8 o 8,5');
        } elseif ($clase === ClaseDato::LISTA) {
            $opciones = array_map(fn($opcion): string => (string) $opcion->getIdOpcion(), $atributo->getOpciones());
            if (!in_array($valor, $opciones, true)) {
                $validador->agregarError($campo, 'Escoja una opción de la lista');
            }
        } elseif ($clase === ClaseDato::TEXTO) {
            $validador->longitud($campo, $valor, 1, 300, 'Puede tener como máximo 300 caracteres');
            if ($validador->error($campo) === null && !preg_match(self::PATRON_NOMBRE, $valor)) {
                $validador->agregarError($campo, self::MENSAJE_TEXTO);
            }
        }
    }
}