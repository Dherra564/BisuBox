<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class VentaRepositorio
{
    public const POR_PAGINA = 20;

    private const SELECCION = 'SELECT v.*, u.tbusuarionombrecompleto, u.tbusuariotelefono
         FROM tbventa v
         LEFT JOIN tbcliente c ON c.tbclienteid = v.tbventaclienteid
         LEFT JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid';

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function registrar(Pedido $pedido, string $cobro, ?DateTime $vencimiento): int
    {
        $existente = $this->buscarPorPedido((int) $pedido->getIdPedido(), $pedido->getIdVendedor());
        if ($existente !== null) {
            return $existente['id'];
        }

        $porCobrar = $cobro === DatosVenta::PAGO_PENDIENTE;
        $ahora = (new DateTime())->format('Y-m-d H:i:s');
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
            $ahora,
            $pedido->getTotal(),
            $porCobrar ? $pedido->getTotal() : 0,
            $porCobrar && $vencimiento !== null ? $vencimiento->format('Y-m-d') : null,
            $porCobrar ? DatosVenta::ESTADO_POR_COBRAR : DatosVenta::ESTADO_PAGADA,
        ]);

        if (!$porCobrar) {
            $this->insertarAbono($idVenta, $pedido->getTotal(), $pedido->getMetodoPago(), DatosVenta::NOTA_PAGO_AL_ENTREGAR, $ahora);
        }

        return $idVenta;
    }

    public function abonar(int $idVenta, int $idVendedor, float $monto, string $metodoPago, ?string $nota): array
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare(
                'SELECT tbventasaldo, tbventaestadopago FROM tbventa WHERE tbventaid = ? AND tbventavendedorid = ? FOR UPDATE'
            );
            $consulta->execute([$idVenta, $idVendedor]);
            $fila = $consulta->fetch();
            if (!$fila) {
                throw new OperacionNoPermitida('No se encontró la venta.');
            }
            $saldo = (float) $fila['tbventasaldo'];
            if ($fila['tbventaestadopago'] !== DatosVenta::ESTADO_POR_COBRAR || $saldo <= 0) {
                throw new OperacionNoPermitida('Esta venta ya está pagada.');
            }
            if ($monto > $saldo + 0.001) {
                throw new OperacionNoPermitida(DatosVenta::mensajeSaldo($saldo));
            }

            $nuevoSaldo = max(0, round($saldo - $monto, 2));
            $this->insertarAbono($idVenta, $monto, $metodoPago, $nota, (new DateTime())->format('Y-m-d H:i:s'));
            $this->conexion->prepare(
                'UPDATE tbventa SET tbventasaldo = ?, tbventaestadopago = ? WHERE tbventaid = ?'
            )->execute([
                        $nuevoSaldo,
                        $nuevoSaldo <= 0 ? DatosVenta::ESTADO_PAGADA : DatosVenta::ESTADO_POR_COBRAR,
                        $idVenta,
                    ]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        return ['saldo' => $nuevoSaldo, 'pagada' => $nuevoSaldo <= 0];
    }

    public function abonos(int $idVenta): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbabonoventa WHERE tbabonoventaventaid = ? ORDER BY tbabonoventafecha ASC, tbabonoventaid ASC'
        );
        $consulta->execute([$idVenta]);

        return array_map(fn(array $fila): array => [
            'id' => (int) $fila['tbabonoventaid'],
            'monto' => (float) $fila['tbabonoventamonto'],
            'metodoPago' => (string) $fila['tbabonoventametodopago'],
            'nota' => $fila['tbabonoventanota'],
            'fecha' => new DateTime((string) $fila['tbabonoventafecha']),
        ], $consulta->fetchAll());
    }

    public function buscar(int $idVenta, int $idVendedor): ?array
    {
        $consulta = $this->conexion->prepare(self::SELECCION . ' WHERE v.tbventaid = ? AND v.tbventavendedorid = ?');
        $consulta->execute([$idVenta, $idVendedor]);
        $fila = $consulta->fetch();
        return $fila ? $this->desdeFila($fila) : null;
    }

    public function buscarPorPedido(int $idPedido, int $idVendedor): ?array
    {
        $consulta = $this->conexion->prepare(
            self::SELECCION . ' WHERE v.tbventapedidoid = ? AND v.tbventavendedorid = ? ORDER BY v.tbventaid ASC LIMIT 1'
        );
        $consulta->execute([$idPedido, $idVendedor]);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }
        $venta = $this->desdeFila($fila);
        $venta['abonos'] = $this->abonos($venta['id']);
        return $venta;
    }

    public function listar(int $idVendedor, array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->filtros($idVendedor, $filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;
        $consulta = $this->conexion->prepare(
            self::SELECCION . " WHERE {$condiciones}
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
                COALESCE(SUM(v.tbventatipo <> ?), 0) AS conpersonalizadas,
                COALESCE(SUM(CASE WHEN v.tbventasaldo > 0 AND v.tbventavencimiento < ? THEN v.tbventasaldo END), 0) AS vencido
             FROM tbventa v WHERE {$condiciones}"
        );
        $consulta->execute([DatosVenta::TIPO_HECHO, (new DateTime('today'))->format('Y-m-d'), ...$valores]);
        $fila = $consulta->fetch();
        return [
            'ventas' => (int) $fila['ventas'],
            'total' => (float) $fila['total'],
            'porCobrar' => (float) $fila['porcobrar'],
            'vencido' => (float) $fila['vencido'],
            'conPersonalizadas' => (int) $fila['conpersonalizadas'],
        ];
    }

    public function saldosPorCliente(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT v.tbventaclienteid, u.tbusuarionombrecompleto, u.tbusuariotelefono,
                COUNT(*) AS ventas, SUM(v.tbventasaldo) AS saldo,
                SUM(v.tbventavencimiento < ?) AS vencidas, MIN(v.tbventavencimiento) AS proximo
             FROM tbventa v
             LEFT JOIN tbcliente c ON c.tbclienteid = v.tbventaclienteid
             LEFT JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid
             WHERE v.tbventavendedorid = ? AND v.tbventaestadopago = ? AND v.tbventasaldo > 0
             GROUP BY v.tbventaclienteid, u.tbusuarionombrecompleto, u.tbusuariotelefono
             ORDER BY saldo DESC'
        );
        $consulta->execute([(new DateTime('today'))->format('Y-m-d'), $idVendedor, DatosVenta::ESTADO_POR_COBRAR]);

        return array_map(fn(array $fila): array => [
            'idCliente' => (int) $fila['tbventaclienteid'],
            'cliente' => (string) $fila['tbusuarionombrecompleto'],
            'telefono' => (string) $fila['tbusuariotelefono'],
            'ventas' => (int) $fila['ventas'],
            'saldo' => (float) $fila['saldo'],
            'vencidas' => (int) $fila['vencidas'],
            'proximo' => $fila['proximo'] !== null ? new DateTime((string) $fila['proximo']) : null,
        ], $consulta->fetchAll());
    }

    private function insertarAbono(int $idVenta, float $monto, string $metodoPago, ?string $nota, string $fecha): void
    {
        $this->conexion->prepare(
            'INSERT INTO tbabonoventa (tbabonoventaid, tbabonoventaventaid, tbabonoventamonto, tbabonoventametodopago,
                tbabonoventanota, tbabonoventafecha)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
                    BaseDatos::generarId('tbabonoventa', 'tbabonoventaid'),
                    $idVenta,
                    round($monto, 2),
                    $metodoPago,
                    $nota,
                    $fecha,
                ]);
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
        if (($filtros['idCliente'] ?? null) !== null) {
            $condiciones[] = 'v.tbventaclienteid = ?';
            $valores[] = (int) $filtros['idCliente'];
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
            'pagado' => round((float) $fila['tbventatotal'] - (float) $fila['tbventasaldo'], 2),
            'vencimiento' => $fila['tbventavencimiento'] !== null ? new DateTime((string) $fila['tbventavencimiento']) : null,
            'estadoPago' => (string) $fila['tbventaestadopago'],
            'cliente' => (string) ($fila['tbusuarionombrecompleto'] ?? ''),
            'telefono' => (string) ($fila['tbusuariotelefono'] ?? ''),
        ];
    }
}