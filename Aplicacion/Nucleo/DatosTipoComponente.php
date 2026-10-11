<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Modelos\TipoComponenteAtributo;
use Aplicacion\Modelos\TipoComponenteAtributoOpcion;
use Aplicacion\Repositorios\TipoComponenteRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;

class DatosTipoComponente
{
    public const MAXIMO_DATOS = 20;

    /** @return array{nombre: string, datos: array<string, array>} */
    public static function leer(): array
    {
        $texto = fn(mixed $valor): string => is_string($valor) ? trim($valor) : '';
        $datos = [];

        foreach (is_array($_POST['dato'] ?? null) ? $_POST['dato'] : [] as $clave => $dato) {
            if (!is_array($dato) || !preg_match('/^[an][0-9]{1,9}$/', (string) $clave)) {
                continue;
            }

            $idsOpcion = is_array($dato['opcionId'] ?? null) ? array_values($dato['opcionId']) : [];
            $opciones = [];
            foreach (is_array($dato['opcionValor'] ?? null) ? array_values($dato['opcionValor']) : [] as $indice => $valor) {
                $valor = UsuarioRepositorio::limpiarEspacios($texto($valor));
                if ($valor !== '') {
                    $opciones[] = ['id' => self::idONulo($idsOpcion[$indice] ?? null), 'valor' => $valor];
                }
            }

            $datos[(string) $clave] = [
                'id' => self::idONulo($dato['id'] ?? null),
                'nombre' => UsuarioRepositorio::limpiarEspacios($texto($dato['nombre'] ?? null)),
                'clase' => $texto($dato['clase'] ?? null),
                'unidad' => $texto($dato['unidad'] ?? null),
                'obligatorio' => ($dato['obligatorio'] ?? '') === '1',
                'opciones' => $opciones,
            ];
        }

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($texto($_POST['nombre'] ?? null)),
            'datos' => $datos,
        ];
    }

    public static function validar(Validador $validador, array $datos, int $idVendedor, ?TipoComponente $original = null): void
    {
        $validador->requerido('nombre', $datos['nombre'], 'Escriba el nombre del tipo, por ejemplo Perlas')
            ->longitud('nombre', $datos['nombre'], 2, 60, 'El nombre debe tener entre 2 y 60 caracteres')
            ->nombreTienda('nombre', $datos['nombre']);
        if (
            $validador->error('nombre') === null
            && (new TipoComponenteRepositorio())->existeNombre($idVendedor, $datos['nombre'], $original?->getIdTipoComponente())
        ) {
            $validador->agregarError('nombre', 'Ya tiene un tipo con ese nombre');
        }

        if (count($datos['datos']) > self::MAXIMO_DATOS) {
            $validador->agregarError('datos', 'Un tipo puede pedir como máximo ' . self::MAXIMO_DATOS . ' datos');
        }

        $nombresUsados = [];
        foreach ($datos['datos'] as $clave => $dato) {
            $campo = 'dato.' . $clave . '.';

            $validador->requerido($campo . 'nombre', $dato['nombre'], 'Escriba el nombre del dato o quítelo')
                ->longitud($campo . 'nombre', $dato['nombre'], 1, 60, 'El nombre puede tener como máximo 60 caracteres');
            $nombreComparable = mb_strtolower($dato['nombre'], 'UTF-8');
            if ($validador->error($campo . 'nombre') === null && in_array($nombreComparable, $nombresUsados, true)) {
                $validador->agregarError($campo . 'nombre', 'Ya hay otro dato con este nombre');
            }
            $nombresUsados[] = $nombreComparable;

            if (!ClaseDato::existe($dato['clase'])) {
                $validador->agregarError($campo . 'clase', 'Escoja qué se anota en este dato');
                continue;
            }

            if ($dato['clase'] === ClaseDato::NUMERO) {
                $validador->longitud($campo . 'unidad', $dato['unidad'], 1, 20, 'La unidad puede tener como máximo 20 caracteres');
            }

            if ($dato['clase'] === ClaseDato::LISTA) {
                self::validarOpciones($validador, $campo . 'opciones', $dato['opciones']);
            }
        }
    }

    public static function crearTipo(array $datos, int $idVendedor, ?TipoComponente $original = null): TipoComponente
    {
        $guardados = self::atributosPorId($original);
        $atributos = [];

        foreach (array_values($datos['datos']) as $indice => $dato) {
            $anterior = $dato['id'] !== null ? ($guardados[$dato['id']] ?? null) : null;
            $clase = $anterior !== null && $anterior->getTieneValores() ? $anterior->getClase() : $dato['clase'];

            $atributos[] = new TipoComponenteAtributo(
                $anterior?->getIdAtributo(),
                $original?->getIdTipoComponente(),
                $dato['nombre'],
                $clase,
                $dato['unidad'],
                $dato['obligatorio'],
                $indice + 1,
                true,
                array_map(
                    fn(array $opcion): TipoComponenteAtributoOpcion => new TipoComponenteAtributoOpcion($opcion['id'], null, $opcion['valor']),
                    $dato['opciones']
                )
            );
        }

        return new TipoComponente(
            $original?->getIdTipoComponente(),
            $idVendedor,
            $datos['nombre'],
            $original?->getActivo() ?? true,
            $atributos
        );
    }

    public static function paraFormulario(TipoComponente $tipoComponente): array
    {
        $datos = [];
        foreach ($tipoComponente->getAtributosActivos() as $atributo) {
            $datos['a' . $atributo->getIdAtributo()] = self::atributoParaFormulario($atributo);
        }

        return ['nombre' => $tipoComponente->getNombre(), 'datos' => $datos];
    }

    public static function ocultosParaFormulario(?TipoComponente $tipoComponente, array $datos): array
    {
        $ocultos = [];
        foreach ($tipoComponente?->getAtributos() ?? [] as $atributo) {
            $clave = 'a' . $atributo->getIdAtributo();
            if (!$atributo->getActivo() && !isset($datos['datos'][$clave])) {
                $ocultos[$clave] = self::atributoParaFormulario($atributo);
            }
        }

        return $ocultos;
    }

    public static function tieneValores(?TipoComponente $tipoComponente, ?int $idAtributo): bool
    {
        $atributo = $idAtributo !== null ? (self::atributosPorId($tipoComponente)[$idAtributo] ?? null) : null;

        return $atributo !== null && $atributo->getTieneValores();
    }

    private static function validarOpciones(Validador $validador, string $campo, array $opciones): void
    {
        if (count($opciones) < 2) {
            $validador->agregarError($campo, 'Una lista necesita al menos 2 opciones');
            return;
        }
        if (count($opciones) > ClaseDato::MAXIMO_OPCIONES) {
            $validador->agregarError($campo, 'Una lista puede tener como máximo ' . ClaseDato::MAXIMO_OPCIONES . ' opciones');
            return;
        }

        $valores = [];
        foreach ($opciones as $opcion) {
            if (mb_strlen($opcion['valor'], 'UTF-8') > 60) {
                $validador->agregarError($campo, 'Cada opción puede tener como máximo 60 caracteres');
                return;
            }
            $valores[] = mb_strtolower($opcion['valor'], 'UTF-8');
        }
        if (count(array_unique($valores)) !== count($valores)) {
            $validador->agregarError($campo, 'Hay opciones repetidas');
        }
    }

    private static function atributoParaFormulario(TipoComponenteAtributo $atributo): array
    {
        return [
            'id' => $atributo->getIdAtributo(),
            'nombre' => $atributo->getNombre(),
            'clase' => $atributo->getClase(),
            'unidad' => (string) $atributo->getUnidad(),
            'obligatorio' => $atributo->getObligatorio(),
            'opciones' => array_map(
                fn(TipoComponenteAtributoOpcion $opcion): array => ['id' => $opcion->getIdOpcion(), 'valor' => $opcion->getValor()],
                $atributo->getOpcionesActivas()
            ),
        ];
    }

    /** @return array<int, TipoComponenteAtributo> */
    private static function atributosPorId(?TipoComponente $tipoComponente): array
    {
        $atributos = [];
        foreach ($tipoComponente?->getAtributos() ?? [] as $atributo) {
            $atributos[(int) $atributo->getIdAtributo()] = $atributo;
        }

        return $atributos;
    }

    private static function idONulo(mixed $valor): ?int
    {
        return is_string($valor) && ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
    }
}