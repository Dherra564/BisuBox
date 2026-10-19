<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\PersonalizadoRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;

class AvisosTienda
{
    public const PEDIDO = 'pedido';
    public const AGOTADO = 'agotado';
    public const BAJO = 'bajo';
    public const PRODUCTO = 'producto';

    private const CLAVE_SESION = 'avisosTienda';
    private const NOMBRES_VISIBLES = 3;

    public static function revisar(int $idVendedor): array
    {
        $componentes = (new ComponenteRepositorio())->listarPorAcabarse($idVendedor);
        $productos = (new ProductoRepositorio())->listarAgotados($idVendedor);
        $pedidoRepositorio = new PedidoRepositorio();

        $avisos = [];
        $guardado = $_SESSION[self::CLAVE_SESION][$idVendedor] ?? null;
        if (!is_array($guardado)) {
            $guardado = ['componentes' => [], 'productos' => [], 'ultimoPedido' => $pedidoRepositorio->ultimoId($idVendedor)];
            $porEntregar = $pedidoRepositorio->contar(['idVendedor' => $idVendedor, 'estado' => EstadoPedido::CONFIRMADO]);
            if ($porEntregar > 0) {
                $avisos[] = self::aviso(
                    self::PEDIDO,
                    $porEntregar === 1 ? 'Tiene 1 pedido por entregar' : 'Tiene ' . $porEntregar . ' pedidos por entregar',
                    'Revise sus pedidos para prepararlos.',
                    '/pedidos?estado=' . urlencode(EstadoPedido::CONFIRMADO)
                );
            }
        }

        $pedidos = $pedidoRepositorio->listarNuevos($idVendedor, (int) $guardado['ultimoPedido']);
        $avisos = array_merge($avisos, self::avisosPedidos($pedidos));

        $agotados = [];
        $bajos = [];
        foreach ($componentes as $id => $componente) {
            $antes = $guardado['componentes'][$id] ?? null;
            if ($componente['estado'] === EstadoExistencia::AGOTADO && $antes !== EstadoExistencia::AGOTADO) {
                $agotados[] = $componente;
            } elseif ($componente['estado'] === EstadoExistencia::BAJO && $antes === null) {
                $bajos[] = $componente;
            }
        }
        $productosNuevos = array_values(array_diff_key($productos, $guardado['productos']));

        if ($agotados !== []) {
            $avisos[] = count($agotados) === 1
                ? self::aviso(self::AGOTADO, 'Se acabó un componente', 'Ya no le queda «' . $agotados[0]['nombre'] . '». Compre más para seguir haciendo piezas.', '/inventario?existencia=' . EstadoExistencia::AGOTADO)
                : self::aviso(self::AGOTADO, 'Se acabaron ' . count($agotados) . ' componentes', self::listaNombres(array_column($agotados, 'nombre')) . '.', '/inventario?existencia=' . EstadoExistencia::AGOTADO);
        }
        if ($productosNuevos !== []) {
            $avisos[] = count($productosNuevos) === 1
                ? self::aviso(self::PRODUCTO, 'Producto agotado', '«' . $productosNuevos[0] . '» se agotó. Sus clientes ya no lo pueden comprar.', '/productos?estado=agotados')
                : self::aviso(self::PRODUCTO, count($productosNuevos) . ' productos agotados', self::listaNombres($productosNuevos) . '. Sus clientes ya no los pueden comprar.', '/productos?estado=agotados');
        }
        if ($bajos !== []) {
            $avisos[] = count($bajos) === 1
                ? self::aviso(self::BAJO, 'Componente por acabarse', 'De «' . $bajos[0]['nombre'] . '» solo ' . ($bajos[0]['existencia'] == 1 ? 'queda ' : 'quedan ') . UnidadMedida::cantidad($bajos[0]['existencia'], $bajos[0]['unidad']) . '.', '/inventario?existencia=' . EstadoExistencia::BAJO)
                : self::aviso(self::BAJO, count($bajos) . ' componentes por acabarse', self::listaNombres(array_column($bajos, 'nombre')) . '.', '/inventario?existencia=' . EstadoExistencia::BAJO);
        }

        $ultimoPedido = (int) $guardado['ultimoPedido'];
        foreach ($pedidos as $pedido) {
            $ultimoPedido = max($ultimoPedido, $pedido['id']);
        }
        $_SESSION[self::CLAVE_SESION][$idVendedor] = [
            'componentes' => array_map(fn(array $componente): string => $componente['estado'], $componentes),
            'productos' => array_fill_keys(array_keys($productos), true),
            'ultimoPedido' => $ultimoPedido,
        ];

        return $avisos;
    }

    public static function numeros(int $idVendedor): array
    {
        $personalizadoRepositorio = new PersonalizadoRepositorio();
        $componentes = (new ComponenteRepositorio())->resumen($idVendedor);

        return [
            'pedidos' => (new PedidoRepositorio())->contar(['idVendedor' => $idVendedor, 'estado' => EstadoPedido::CONFIRMADO])
                + $personalizadoRepositorio->contar(['idVendedor' => $idVendedor, 'estado' => EstadoPersonalizado::EN_REVISION])
                + $personalizadoRepositorio->contarAvisosSinVer($idVendedor),
            'productos' => (new ProductoRepositorio())->resumen($idVendedor)['agotados'],
            'inventario' => $componentes['bajos'] + $componentes['agotados'],
        ];
    }

    private static function avisosPedidos(array $pedidos): array
    {
        if (count($pedidos) > self::NOMBRES_VISIBLES) {
            $total = array_sum(array_column($pedidos, 'total'));
            return [
                self::aviso(
                    self::PEDIDO,
                    count($pedidos) . ' compras nuevas',
                    'Sus clientes compraron ' . Numero::moneda($total) . ' en total.',
                    '/pedidos?estado=' . urlencode(EstadoPedido::CONFIRMADO)
                )
            ];
        }

        return array_map(fn(array $pedido): array => self::aviso(
            self::PEDIDO,
            'Nueva compra · pedido #' . $pedido['id'],
            $pedido['cliente'] . ' compró ' . Numero::moneda($pedido['total']) . '.'
            . ($pedido['metodoPago'] === MetodoPago::SINPE ? ' Pagó por SINPE: revise el comprobante.' : ''),
            '/pedidos/detalle?id=' . $pedido['id']
        ), $pedidos);
    }

    private static function listaNombres(array $nombres): string
    {
        $visibles = array_map(fn(string $nombre): string => '«' . $nombre . '»', array_slice($nombres, 0, self::NOMBRES_VISIBLES));
        $resto = count($nombres) - count($visibles);

        return implode(', ', $visibles) . ($resto > 0 ? ' y ' . $resto . ' más' : '');
    }

    private static function aviso(string $tipo, string $titulo, string $texto, string $ruta): array
    {
        return ['tipo' => $tipo, 'titulo' => $titulo, 'texto' => $texto, 'ruta' => $ruta];
    }
}