<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\PedidoPago;
use Configuracion\BaseDatos;
use DateTime;
use PDO;

class PedidoPagoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function insertar(PedidoPago $pago): int
    {
        $idPedidoPago = BaseDatos::generarId('tbpedidopago', 'tbpedidopagoid');

        $consulta = $this->conexion->prepare(
            'INSERT INTO tbpedidopago (tbpedidopagoid, tbpedidopagopedidoid, tbpedidopagonumerosinpe, tbpedidopagocodigo,
                tbpedidopagoreferencia, tbpedidopagocomprobante, tbpedidopagomonto, tbpedidopagoestado, tbpedidopagomotivo,
                tbpedidopagointentos, tbpedidopagoregistrofecha, tbpedidopagorevisionfecha)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)'
        );
        $consulta->execute([
            $idPedidoPago,
            $pago->getIdPedido(),
            $pago->getNumeroSinpe(),
            $pago->getCodigo(),
            $pago->getReferencia(),
            $pago->getComprobante(),
            $pago->getMonto(),
            $pago->getEstado(),
            $pago->getMotivo(),
            $pago->getIntentos(),
            $pago->getFechaRegistro()->format('Y-m-d H:i:s'),
        ]);
        $pago->setIdPedidoPago($idPedidoPago);

        return $idPedidoPago;
    }

    public function buscarPorPedidos(array $idsPedidos): array
    {
        $idsPedidos = array_values(array_filter(array_map('intval', $idsPedidos), fn(int $id): bool => $id > 0));
        if ($idsPedidos === []) {
            return [];
        }

        $marcas = implode(', ', array_fill(0, count($idsPedidos), '?'));
        $consulta = $this->conexion->prepare(
            "SELECT * FROM tbpedidopago WHERE tbpedidopagopedidoid IN ({$marcas}) ORDER BY tbpedidopagoid ASC"
        );
        $consulta->execute($idsPedidos);

        $pagos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $pagos[(int) $fila['tbpedidopagopedidoid']] = $this->crearDesdeFila($fila);
        }
        return $pagos;
    }

    public function bloquearPorPedido(int $idPedido): ?PedidoPago
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbpedidopago WHERE tbpedidopagopedidoid = ? FOR UPDATE');
        $consulta->execute([$idPedido]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function cambiarEstado(int $idPedidoPago, string $estado, ?string $motivo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbpedidopago SET tbpedidopagoestado = ?, tbpedidopagomotivo = ?, tbpedidopagorevisionfecha = ?
             WHERE tbpedidopagoid = ?'
        );
        $consulta->execute([$estado, $motivo, (new DateTime())->format('Y-m-d H:i:s'), $idPedidoPago]);
    }

    public function reemplazarComprobante(int $idPedidoPago, string $comprobante, string $referencia, string $estado): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbpedidopago SET tbpedidopagocomprobante = ?, tbpedidopagoreferencia = ?, tbpedidopagoestado = ?,
                tbpedidopagointentos = tbpedidopagointentos + 1, tbpedidopagoregistrofecha = ?
             WHERE tbpedidopagoid = ?'
        );
        $consulta->execute([$comprobante, $referencia, $estado, (new DateTime())->format('Y-m-d H:i:s'), $idPedidoPago]);
    }

    public function puedeVerComprobante(string $comprobante, int $idUsuario): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbpedidopago pp
             INNER JOIN tbpedido p ON p.tbpedidoid = pp.tbpedidopagopedidoid
             LEFT JOIN tbvendedor v ON v.tbvendedorid = p.tbpedidovendedorid
             LEFT JOIN tbcliente c ON c.tbclienteid = p.tbpedidoclienteid
             WHERE pp.tbpedidopagocomprobante = ? AND (v.tbvendedorusuarioid = ? OR c.tbclienteusuarioid = ?)'
        );
        $consulta->execute([$comprobante, $idUsuario, $idUsuario]);

        return (int) $consulta->fetchColumn() > 0;
    }

    private function crearDesdeFila(array $fila): PedidoPago
    {
        return new PedidoPago(
            (int) $fila['tbpedidopagoid'],
            (int) $fila['tbpedidopagopedidoid'],
            (string) $fila['tbpedidopagonumerosinpe'],
            (string) $fila['tbpedidopagocodigo'],
            (string) $fila['tbpedidopagoreferencia'],
            (string) $fila['tbpedidopagocomprobante'],
            (float) $fila['tbpedidopagomonto'],
            (string) $fila['tbpedidopagoestado'],
            $fila['tbpedidopagomotivo'],
            (int) $fila['tbpedidopagointentos'],
            new DateTime($fila['tbpedidopagoregistrofecha']),
            $fila['tbpedidopagorevisionfecha'] !== null ? new DateTime($fila['tbpedidopagorevisionfecha']) : null
        );
    }
}