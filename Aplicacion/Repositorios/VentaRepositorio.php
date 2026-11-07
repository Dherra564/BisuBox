<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Nucleo\DatosVenta;
use Configuracion\BaseDatos;
use DateTime;
use PDO;

class VentaRepositorio
{
    public const POR_PAGINA = 20;

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function registrar(Pedido $pedido, string $pago, ?DateTime $vencimiento): int
    {
        $existente = $this->buscarPorPedido((int) $pedido->getIdPedido(), $pedido->getIdVendedor());
        if ($existente !== null) {
            return $existente['id'];
        }

        $porCobrar = $pago === DatosVenta::PAGO_PENDIENTE;
        $idVenta = BaseDatos::generarId('tbventa', 'tbventaid');
        $consulta = $this->conexion->prepare(
            'INSERT INTO tbventa (tbventaid, tbventavendedorid, tbventaclienteid, tbventapedidoid, tbventatipo, tbventafecha,
                tbventatotal, tbventasaldo, tbventavencimiento, tbventaestadopago)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $idVenta,
            $pedido->getIdVendedor(),
            $pedido->getIdCliente(),
            $pedido->getIdPedido(),
            DatosVenta::tipoDe($pedido),
            (new DateTime())->format('Y-m-d H:i:s'),
            $pedido->getTotal(),
            $porCobrar ? $pedido->getTotal() : 0,
            $porCobrar && $vencimiento !== null ? $vencimiento->format('Y-m-d') : null,
            $porCobrar ? DatosVenta::ESTADO_POR_COBRAR : DatosVenta::ESTADO_PAGADA,
        ]);
        return $idVenta;
    }

    public function buscar(int $idVenta, int $idVendedor): ?array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbventa WHERE tbventaid = ? AND tbventavendedorid = ?');
        $consulta->execute([$idVenta, $idVendedor]);
        $fila = $consulta->fetch();
        return $fila ? $this->desdeFila($fila) : null;
    }

    public function marcarPagada(int $idVenta, int $idVendedor): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbventa SET tbventasaldo = 0, tbventaestadopago = ?
             WHERE tbventaid = ? AND tbventavendedorid = ? AND tbventaestadopago = ?'
        );
        $consulta->execute([DatosVenta::ESTADO_PAGADA, $idVenta, $idVendedor, DatosVenta::ESTADO_POR_COBRAR]);
        return $consulta->rowCount() === 1;
    }

    public function buscarPorPedido(int $idPedido, int $idVendedor): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbventa WHERE tbventapedidoid = ? AND tbventavendedorid = ? ORDER BY tbventaid ASC LIMIT 1'
        );
        $consulta->execute([$idPedido, $idVendedor]);
        $fila = $consulta->fetch();
        return $fila ? $this->desdeFila($fila) : null;
    }

    public function listar(int $idVendedor, array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->filtros($idVendedor, $filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;
        $consulta = $this->conexion->prepare(
            "SELECT v.*, u.tbusuarionombrecompleto
             FROM tbventa v
             LEFT JOIN tbcliente c ON c.tbclienteid = v.tbventaclienteid
             LEFT JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid
             WHERE {$condiciones}
             ORDER BY v.tbventafecha DESC, v.tbventaid DESC
             LIMIT " . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);
        return array_map(fn(array $fila): array => $this->desdeFila($fila), $consulta->fetchAll());
    }

    public function contar(int $idVendedor, array $filtros): int
    {
        [$condiciones, $valores] = $this->filtros($idVendedor, $filtros);
        $consulta = $this->conexion->prepare("SELECT COUNT(*) FROM tbventa v WHERE {$condiciones}");
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    }

    public function contarPorVendedor(int $idVendedor): int
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbventa WHERE tbventavendedorid = ?');
        $consulta->execute([$idVendedor]);
        return (int) $consulta->fetchColumn();
    }

    public function resumen(int $idVendedor, array $filtros): array
    {
        [$condiciones, $valores] = $this->filtros($idVendedor, $filtros);
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*) AS ventas, COALESCE(SUM(v.tbventatotal), 0) AS total, COALESCE(SUM(v.tbventasaldo), 0) AS porcobrar,
                COALESCE(SUM(v.tbventatipo <> ?), 0) AS conpersonalizadas
             FROM tbventa v WHERE {$condiciones}"
        );
        $consulta->execute([DatosVenta::TIPO_HECHO, ...$valores]);
        $fila = $consulta->fetch();
        return [
            'ventas' => (int) $fila['ventas'],
            'total' => (float) $fila['total'],
            'porCobrar' => (float) $fila['porcobrar'],
            'conPersonalizadas' => (int) $fila['conpersonalizadas'],
        ];
    }

    private function filtros(int $idVendedor, array $filtros): array
    {
        $condiciones = ['v.tbventavendedorid = ?'];
        $valores = [$idVendedor];
        if (($filtros['tipo'] ?? null) !== null) {
            $condiciones[] = 'v.tbventatipo = ?';
            $valores[] = $filtros['tipo'];
        }
        if (($filtros['estado'] ?? null) !== null) {
            $condiciones[] = 'v.tbventaestadopago = ?';
            $valores[] = $filtros['estado'];
        }
        if (($filtros['desde'] ?? null) instanceof DateTime) {
            $condiciones[] = 'v.tbventafecha >= ?';
            $valores[] = $filtros['desde']->format('Y-m-d 00:00:00');
        }
        if (($filtros['hasta'] ?? null) instanceof DateTime) {
            $condiciones[] = 'v.tbventafecha <= ?';
            $valores[] = $filtros['hasta']->format('Y-m-d 23:59:59');
        }
        return [implode(' AND ', $condiciones), $valores];
    }

    private function desdeFila(array $fila): array
    {
        return [
            'id' => (int) $fila['tbventaid'],
            'idVendedor' => (int) $fila['tbventavendedorid'],
            'idCliente' => (int) $fila['tbventaclienteid'],
            'idPedido' => (int) $fila['tbventapedidoid'],
            'tipo' => (string) $fila['tbventatipo'],
            'fecha' => new DateTime((string) $fila['tbventafecha']),
            'total' => (float) $fila['tbventatotal'],
            'saldo' => (float) $fila['tbventasaldo'],
            'vencimiento' => $fila['tbventavencimiento'] !== null ? new DateTime((string) $fila['tbventavencimiento']) : null,
            'estadoPago' => (string) $fila['tbventaestadopago'],
            'cliente' => isset($fila['tbusuarionombrecompleto']) ? (string) $fila['tbusuarionombrecompleto'] : '',
        ];
    }
}
