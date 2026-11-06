<?php

namespace Aplicacion\Nucleo;

class CalculoDiseno
{
    public const MAXIMO_PIEZAS = 300;

    public const MENSAJE_TIPO = 'Elija el tipo de pieza';
    public const MENSAJE_VACIO = 'Agregue al menos una pieza';
    public const MENSAJE_NO_DISPONIBLE = 'Una de las piezas ya no está disponible. Recargue la página.';
    public const MENSAJE_ESPACIO = 'Las piezas ocupan más que el largo elegido. Quite alguna o aumente el largo.';
    public const MENSAJE_HILO = 'La tienda no tiene suficiente hilo para esta pieza.';

    public static function mensajeLargo(string $tipo): string
    {
        $datos = DatosPersonalizador::TIPOS_PIEZA[$tipo];
        return 'El largo de la ' . mb_strtolower($datos['nombre'], 'UTF-8') . ' debe estar entre '
            . self::cm($datos['minimo']) . ' y ' . self::cm($datos['maximo']) . ' cm';
    }

    public static function mensajeCerrar(string $tipo, float $faltanMm): string
    {
        return 'Faltan ' . self::cm($faltanMm) . ' cm para cerrar la '
            . mb_strtolower(DatosPersonalizador::TIPOS_PIEZA[$tipo]['nombre'], 'UTF-8') . '.';
    }

    public static function mensajeExistencia(string $nombre, int $hay, int $enCarrito): string
    {
        return 'No hay suficientes de «' . $nombre . '»: la tienda tiene ' . $hay
            . ($enCarrito > 0 ? ' y usted ya tiene ' . $enCarrito . ' en otras piezas del carrito' : '') . '.';
    }

    public static function cm(float $milimetros): string
    {
        $texto = number_format($milimetros / 10, 1, ',', '');
        return str_ends_with($texto, ',0') ? substr($texto, 0, -2) : $texto;
    }

    public static function leerEntrada(array $origen): array
    {
        $elegidas = [];
        $lista = $origen['piezas'] ?? [];
        if (is_array($lista)) {
            foreach (array_slice($lista, 0, self::MAXIMO_PIEZAS + 1) as $id) {
                $elegidas[] = is_string($id) && ctype_digit($id) && strlen($id) <= 10 ? (int) $id : -1;
            }
        }
        $largo = is_string($origen['largo'] ?? null) && ctype_digit(trim($origen['largo'])) ? (int) trim($origen['largo']) : null;

        return [
            'tipo' => is_string($origen['tipo'] ?? null) ? trim($origen['tipo']) : '',
            'largo' => $largo,
            'simetrico' => ($origen['simetrico'] ?? '') === '1',
            'elegidas' => $elegidas,
        ];
    }

    public static function listaFinal(array $elegidas, bool $simetrico): array
    {
        if (!$simetrico || $elegidas === []) {
            return $elegidas;
        }
        $centro = $elegidas[0];
        $lados = array_slice($elegidas, 1);
        return array_merge(array_reverse($lados), [$centro], $lados);
    }

    public static function revisar(array $entrada, array $piezas, ?array $configuracion, ?array $hilo, array $yaEnCarrito = []): array
    {
        $tipo = $entrada['tipo'];
        if (!isset(DatosPersonalizador::TIPOS_PIEZA[$tipo])) {
            return ['errores' => [self::MENSAJE_TIPO], 'diseno' => null];
        }
        $datosTipo = DatosPersonalizador::TIPOS_PIEZA[$tipo];
        $largo = $entrada['largo'];
        if ($largo === null || $largo < $datosTipo['minimo'] || $largo > $datosTipo['maximo'] || $largo % DatosPersonalizador::PASO_LARGO !== 0) {
            return ['errores' => [self::mensajeLargo($tipo)], 'diseno' => null];
        }
        if ($entrada['elegidas'] === []) {
            return ['errores' => [self::MENSAJE_VACIO], 'diseno' => null];
        }
        if ($configuracion === null || $hilo === null) {
            return ['errores' => [self::MENSAJE_HILO], 'diseno' => null];
        }

        $lista = self::listaFinal($entrada['elegidas'], $entrada['simetrico']);
        if (count($lista) > self::MAXIMO_PIEZAS) {
            return ['errores' => [self::MENSAJE_ESPACIO], 'diseno' => null];
        }
        foreach ($lista as $id) {
            if (!isset($piezas[$id])) {
                return ['errores' => [self::MENSAJE_NO_DISPONIBLE], 'diseno' => null];
            }
        }

        $usadoDecimas = 0;
        foreach ($lista as $id) {
            $usadoDecimas += DatosPersonalizador::decimas($piezas[$id]['tamano']);
        }
        $largoDecimas = $largo * 10;
        $errores = [];
        if ($usadoDecimas > $largoDecimas) {
            $errores[] = self::MENSAJE_ESPACIO;
        } elseif ($datosTipo['debeCerrar'] && $usadoDecimas < $largoDecimas * DatosPersonalizador::PROPORCION_PARA_CERRAR) {
            $errores[] = self::mensajeCerrar($tipo, ($largoDecimas - $usadoDecimas) / 10);
        }

        $conteo = array_count_values($lista);
        foreach ($conteo as $id => $cantidad) {
            $enCarrito = (int) ($yaEnCarrito['componentes'][$id] ?? 0);
            if ($cantidad + $enCarrito > $piezas[$id]['existencia']) {
                $errores[] = self::mensajeExistencia($piezas[$id]['nombre'], $piezas[$id]['existencia'], $enCarrito);
            }
        }

        $metrosHilo = $largo / 1000;
        if ($metrosHilo + (float) ($yaEnCarrito['hilo'] ?? 0) > $hilo['existencia'] + 0.00001) {
            $errores[] = self::MENSAJE_HILO;
        }

        $costoPiezas = 0.0;
        $detalle = [];
        foreach ($lista as $posicion => $id) {
            $costoPiezas += $piezas[$id]['costo'];
            $detalle[] = [
                'posicion' => $posicion + 1,
                'idComponente' => $id,
                'nombre' => $piezas[$id]['nombre'],
                'foto' => $piezas[$id]['foto'],
                'tamano' => $piezas[$id]['tamano'],
                'costo' => $piezas[$id]['costo'],
            ];
        }
        $costoHilo = $metrosHilo * $hilo['costo'];
        $costo = round($costoPiezas + $costoHilo, 2);

        return [
            'errores' => $errores,
            'diseno' => [
                'tipo' => $tipo,
                'largo' => (float) $largo,
                'simetrico' => $entrada['simetrico'],
                'lista' => $lista,
                'detalle' => $detalle,
                'conteo' => $conteo,
                'idHilo' => $hilo['idComponente'],
                'nombreHilo' => $hilo['nombre'],
                'costoHilo' => $hilo['costo'],
                'metrosHilo' => $metrosHilo,
                'manoObra' => $configuracion['manoObra'],
                'ganancia' => $configuracion['ganancia'],
                'costo' => $costo,
                'precio' => DatosPersonalizador::precio($costoPiezas + $costoHilo, $configuracion['manoObra'], $configuracion['ganancia']),
            ],
        ];
    }

    public static function nombreDiseno(string $tipo): string
    {
        return (DatosPersonalizador::TIPOS_PIEZA[$tipo]['nombre'] ?? 'Pieza') . ' personalizada';
    }

    public static function datosDibujo(array $diseno, string $urlBase): string
    {
        $catalogo = [];
        foreach ($diseno['detalle'] as $linea) {
            $catalogo[$linea['idComponente']] = [
                'tamano' => DatosPersonalizador::decimas($linea['tamano']),
                'foto' => $urlBase . '/fotos/pieza?archivo=' . rawurlencode($linea['foto']),
            ];
        }
        $datos = [
            'forma' => DatosPersonalizador::TIPOS_PIEZA[$diseno['tipo']]['forma'] ?? 'circulo',
            'largo' => (int) round($diseno['largo'] * 10),
            'lista' => $diseno['lista'],
            'catalogo' => $catalogo,
            'texto' => '',
        ];
        return json_encode($datos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    }

    public static function materiales(array $diseno): array
    {
        $cuentas = [];
        foreach ($diseno['detalle'] as $linea) {
            $cuentas[$linea['nombre']] = ($cuentas[$linea['nombre']] ?? 0) + 1;
        }
        $lineas = [];
        foreach ($cuentas as $nombre => $cantidad) {
            $lineas[] = $cantidad . ' × ' . $nombre;
        }
        if ($diseno['nombreHilo'] !== '') {
            $lineas[] = self::cm($diseno['largo']) . ' cm de ' . $diseno['nombreHilo'];
        }
        return $lineas;
    }

    public static function descripcion(array $diseno): string
    {
        $piezas = count($diseno['detalle']);
        return ($piezas === 1 ? '1 pieza' : $piezas . ' piezas') . ' · ' . self::cm($diseno['largo']) . ' cm'
            . ($diseno['simetrico'] ? ' · simétrica' : '');
    }
}
