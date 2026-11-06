<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\ComponenteVistaRepositorio;
use Aplicacion\Repositorios\DisenoRepositorio;
use Aplicacion\Repositorios\PersonalizadorRepositorio;

class ContextoPersonalizador
{
    public const MENSAJE_YA_NO_OFRECE = 'La tienda ya no ofrece piezas personalizadas. Quite esta pieza para poder pedir.';

    public static function cargar(int $idVendedor, bool $aunqueEsteOculto = false): ?array
    {
        $repositorio = new PersonalizadorRepositorio();
        $configuracion = $repositorio->buscar($idVendedor);
        if ($configuracion === null || (!$configuracion['activo'] && !$aunqueEsteOculto)) {
            return null;
        }
        $hilos = $repositorio->listarHilos($idVendedor);
        $hilo = $configuracion['idHilo'] !== null ? ($hilos[$configuracion['idHilo']] ?? null) : null;
        $piezas = (new ComponenteVistaRepositorio())->listarParaTienda($idVendedor);
        if ($hilo === null || $piezas === []) {
            return null;
        }
        return ['configuracion' => $configuracion, 'hilo' => $hilo, 'piezas' => $piezas];
    }

    public static function idClienteActual(): ?int
    {
        if (!UsuarioActual::haySesion()) {
            return null;
        }
        $cliente = (new ClienteRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        return $cliente !== null ? (int) $cliente->getIdCliente() : null;
    }

    public static function usoEnCarrito(array $disenos, ?int $idHilo): array
    {
        $componentes = [];
        $metros = 0.0;
        foreach ($disenos as $diseno) {
            foreach ($diseno['conteo'] as $idComponente => $cantidad) {
                $componentes[$idComponente] = ($componentes[$idComponente] ?? 0) + $cantidad;
            }
            if ($idHilo !== null && $diseno['idHilo'] === $idHilo) {
                $metros += $diseno['largo'] / 1000;
            }
        }
        return ['componentes' => $componentes, 'hilo' => $metros];
    }

    public static function elegidasDesdeLista(array $lista, bool $simetrico): array
    {
        if (!$simetrico || $lista === []) {
            return $lista;
        }
        $centro = intdiv(count($lista), 2);
        return array_merge([$lista[$centro]], array_slice($lista, $centro + 1));
    }

    public static function revisarCarrito(int $idVendedor, array $disenos): array
    {
        $contexto = self::cargar($idVendedor);
        $repositorio = new DisenoRepositorio();
        $revisados = [];
        $acumulado = ['componentes' => [], 'hilo' => 0.0];
        $cambioPrecio = false;

        foreach ($disenos as $diseno) {
            if ($contexto === null) {
                $revisados[] = ['diseno' => $diseno, 'aviso' => self::MENSAJE_YA_NO_OFRECE];
                continue;
            }
            $entrada = [
                'tipo' => $diseno['tipo'],
                'largo' => (int) round($diseno['largo']),
                'simetrico' => $diseno['simetrico'],
                'elegidas' => self::elegidasDesdeLista($diseno['lista'], $diseno['simetrico']),
            ];
            $resultado = CalculoDiseno::revisar($entrada, $contexto['piezas'], $contexto['configuracion'], $contexto['hilo'], $acumulado);
            $aviso = $resultado['errores'][0] ?? null;

            if ($resultado['diseno'] !== null) {
                $nuevo = $resultado['diseno'];
                if (abs($nuevo['precio'] - $diseno['precio']) >= 0.01) {
                    $repositorio->actualizarPrecio($diseno['id'], $nuevo['costo'], $nuevo['precio'], $nuevo['manoObra'], $nuevo['ganancia']);
                    $diseno['precio'] = $nuevo['precio'];
                    $diseno['costo'] = $nuevo['costo'];
                    $cambioPrecio = true;
                }
                if ($aviso === null) {
                    $uso = self::usoEnCarrito([$nuevo + ['idHilo' => $contexto['hilo']['idComponente']]], $contexto['hilo']['idComponente']);
                    foreach ($uso['componentes'] as $idComponente => $cantidad) {
                        $acumulado['componentes'][$idComponente] = ($acumulado['componentes'][$idComponente] ?? 0) + $cantidad;
                    }
                    $acumulado['hilo'] += $uso['hilo'];
                }
            }
            $revisados[] = ['diseno' => $diseno, 'aviso' => $aviso];
        }

        return ['lineas' => $revisados, 'cambioPrecio' => $cambioPrecio];
    }
}
