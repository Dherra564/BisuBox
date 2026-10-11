<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Componente;
use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Modelos\TipoComponenteAtributo;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;

class DatosComponente
{
    public static function leer(TipoComponente $tipo): array
    {
        $texto = fn(mixed $valor): string => is_string($valor) ? trim($valor) : '';
        $enviados = is_array($_POST['valor'] ?? null) ? $_POST['valor'] : [];

        $valores = [];
        foreach ($tipo->getAtributosActivos() as $atributo) {
            $valor = $texto($enviados[$atributo->getIdAtributo()] ?? null);
            if ($atributo->getClase() === ClaseDato::SINO) {
                $valor = $valor === '1' ? '1' : '0';
            }
            $valores[(int) $atributo->getIdAtributo()] = UsuarioRepositorio::limpiarEspacios($valor);
        }

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($texto($_POST['nombre'] ?? null)),
            'unidad' => $texto($_POST['unidad'] ?? null),
            'existencia' => $texto($_POST['existencia'] ?? null),
            'costoUnitario' => $texto($_POST['costoUnitario'] ?? null),
            'existenciaMinima' => $texto($_POST['existenciaMinima'] ?? null),
            'valores' => $valores,
        ];
    }

    public static function validar(
        Validador $validador,
        array $datos,
        TipoComponente $tipo,
        int $idVendedor,
        ?Componente $original = null
    ): void {
        $validador->requerido('nombre', $datos['nombre'], 'Escriba el nombre del componente, por ejemplo Perla rosada 8 mm')
            ->longitud('nombre', $datos['nombre'], 2, 100, 'El nombre debe tener entre 2 y 100 caracteres');
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
            self::validarCantidad($validador, 'costoUnitario', $datos['costoUnitario'], false, true);
        }
        self::validarCantidad($validador, 'existenciaMinima', $datos['existenciaMinima'], $enUnidades, false);

        foreach ($tipo->getAtributosActivos() as $atributo) {
            self::validarValor($validador, $atributo, $datos['valores'][(int) $atributo->getIdAtributo()] ?? '');
        }
    }

    public static function crearComponente(array $datos, TipoComponente $tipo, int $idVendedor, ?Componente $original = null): Componente
    {
        $valores = [];
        foreach ($tipo->getAtributosActivos() as $atributo) {
            $valor = $datos['valores'][(int) $atributo->getIdAtributo()] ?? '';
            if ($atributo->getClase() === ClaseDato::NUMERO && $valor !== '') {
                $valor = (string) Numero::leer($valor);
            }
            $valores[(int) $atributo->getIdAtributo()] = $valor;
        }

        return new Componente(
            $original?->getIdComponente(),
            $idVendedor,
            (int) $tipo->getIdTipoComponente(),
            $datos['nombre'],
            $datos['unidad'],
            $original?->getExistencia() ?? (Numero::leer($datos['existencia']) ?? 0),
            $original?->getCostoUnitario() ?? (Numero::leer($datos['costoUnitario']) ?? 0),
            Numero::leer($datos['existenciaMinima']) ?? 0,
            $original?->getActivo() ?? true,
            $valores
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
            'costoUnitario' => Numero::paraCampo($componente->getCostoUnitario()),
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
            'costoUnitario' => '',
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

    private static function validarCantidad(Validador $validador, string $campo, string $valor, bool $entera, bool $obligatoria): void
    {
        if ($valor === '') {
            if ($obligatoria) {
                $validador->agregarError($campo, 'Escriba cuánto le costó cada unidad. Si no lo sabe, ponga 0');
            }
            return;
        }

        $numero = Numero::leer($valor);
        if ($numero === null || $numero > Numero::MAXIMO) {
            $validador->agregarError($campo, 'Escriba un número, por ejemplo 12 o 12,50');
        } elseif ($entera && floor($numero) != $numero) {
            $validador->agregarError($campo, 'Si se mide por unidades, use números enteros');
        }
    }

    private static function validarValor(Validador $validador, TipoComponenteAtributo $atributo, string $valor): void
    {
        $campo = 'valor.' . $atributo->getIdAtributo();
        $clase = $atributo->getClase();

        if ($valor === '' || ($clase === ClaseDato::SINO)) {
            if ($valor === '' && $atributo->getObligatorio()) {
                $validador->agregarError($campo, 'Este tipo siempre pide ' . mb_strtolower($atributo->getNombre(), 'UTF-8'));
            }
            return;
        }

        if ($clase === ClaseDato::NUMERO && Numero::leer($valor) === null) {
            $validador->agregarError($campo, 'Escriba un número, por ejemplo 8 o 8,5');
        } elseif ($clase === ClaseDato::LISTA) {
            $opciones = array_map(fn($opcion): string => (string) $opcion->getIdOpcion(), $atributo->getOpciones());
            if (!in_array($valor, $opciones, true)) {
                $validador->agregarError($campo, 'Escoja una opción de la lista');
            }
        } elseif ($clase === ClaseDato::TEXTO) {
            $validador->longitud($campo, $valor, 1, 300, 'Puede tener como máximo 300 caracteres');
        }
    }
}