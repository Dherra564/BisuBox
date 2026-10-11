<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Repositorios\UsuarioRepositorio;

class FiltrosInventario
{
    public const ESTADOS = ['activos' => 'Activos', 'inactivos' => 'Desactivados', 'todos' => 'Todos'];

    public const ORDENES = [
        'nombre' => 'Nombre (A-Z)',
        'existencia' => 'Lo que se está acabando primero',
        'valor' => 'Mayor valor en existencia',
        'recientes' => 'Agregados recientemente',
    ];

    /** @param TipoComponente[] $tipos*/
    public static function leer(array $consulta, array $tipos): array
    {
        $texto = fn(string $clave): string => is_string($consulta[$clave] ?? null) ? trim($consulta[$clave]) : '';

        $tipo = null;
        foreach ($tipos as $tipoComponente) {
            if ((string) $tipoComponente->getIdTipoComponente() === $texto('tipo')) {
                $tipo = $tipoComponente;
            }
        }

        $datos = [];
        $datosElegidos = is_array($consulta['dato'] ?? null) ? $consulta['dato'] : [];
        foreach ($tipo !== null ? self::atributosFiltrables($tipo) : [] as $atributo) {
            $valor = $datosElegidos[$atributo->getIdAtributo()] ?? '';
            if (!is_string($valor) || $valor === '') {
                continue;
            }
            $esValido = $atributo->getClase() === ClaseDato::SINO
                ? in_array($valor, ['1', '0'], true)
                : in_array($valor, array_map(fn($opcion): string => (string) $opcion->getIdOpcion(), $atributo->getOpciones()), true);
            if ($esValido) {
                $datos[(int) $atributo->getIdAtributo()] = ['clase' => $atributo->getClase(), 'valor' => $valor];
            }
        }

        return [
            'buscar' => mb_substr(UsuarioRepositorio::limpiarEspacios($texto('buscar')), 0, 100, 'UTF-8'),
            'tipo' => $tipo?->getIdTipoComponente(),
            'existencia' => EstadoExistencia::existe($texto('existencia')) ? $texto('existencia') : '',
            'estado' => isset(self::ESTADOS[$texto('estado')]) ? $texto('estado') : 'activos',
            'orden' => isset(self::ORDENES[$texto('orden')]) ? $texto('orden') : 'nombre',
            'datos' => $datos,
        ];
    }

    /** @return \Aplicacion\Modelos\TipoComponenteAtributo[] */
    public static function atributosFiltrables(TipoComponente $tipo): array
    {
        return array_values(array_filter(
            $tipo->getAtributosActivos(),
            fn($atributo): bool => in_array($atributo->getClase(), [ClaseDato::LISTA, ClaseDato::SINO], true)
        ));
    }

    public static function hayFiltros(array $filtros): bool
    {
        return $filtros['buscar'] !== '' || $filtros['tipo'] !== null || $filtros['existencia'] !== ''
            || $filtros['estado'] !== 'activos' || $filtros['datos'] !== [];
    }

    public static function enlace(array $filtros, array $cambios = []): string
    {
        $parametros = [
            'buscar' => $filtros['buscar'],
            'tipo' => $filtros['tipo'],
            'existencia' => $filtros['existencia'],
            'estado' => $filtros['estado'] !== 'activos' ? $filtros['estado'] : '',
            'orden' => $filtros['orden'] !== 'nombre' ? $filtros['orden'] : '',
            'dato' => array_map(fn(array $dato): string => $dato['valor'], $filtros['datos']),
        ];
        if (array_key_exists('tipo', $cambios)) {
            $parametros['dato'] = [];
        }
        foreach ($cambios as $clave => $valor) {
            $parametros[$clave] = $valor;
        }

        $parametros = array_filter($parametros, fn($valor): bool => $valor !== null && $valor !== '' && $valor !== []);
        return '/inventario' . ($parametros !== [] ? '?' . http_build_query($parametros) : '');
    }
}