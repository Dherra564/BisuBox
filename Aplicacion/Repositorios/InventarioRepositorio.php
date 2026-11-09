<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Nucleo\CostoPromedio;
use Aplicacion\Nucleo\MovimientoInventario;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\UsuarioActual;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use RuntimeException;
use Throwable;

class InventarioRepositorio
{
    public const POR_PAGINA = 25;

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function bloquear(string $articulo, int $idArticulo, int $idVendedor): ?array
    {
        if ($articulo === MovimientoInventario::PRODUCTO) {
            $consulta = $this->conexion->prepare(
                'SELECT tbproductoexistencia AS existencia, 0 AS costo, tbproductonombre AS nombre, tbproductoactivo AS activo
                 FROM tbproducto WHERE tbproductoid = ? AND tbproductovendedorid = ? FOR UPDATE'
            );
        } else {
            $consulta = $this->conexion->prepare(
                'SELECT tbcomponenteexistencia AS existencia, tbcomponentecostounitario AS costo, tbcomponentenombre AS nombre,
                    tbcomponenteactivo AS activo
                 FROM tbcomponente WHERE tbcomponenteid = ? AND tbcomponentevendedorid = ? FOR UPDATE'
            );
        }
        $consulta->execute([$idArticulo, $idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? [
            'existencia' => (float) $fila['existencia'],
            'costo' => (float) $fila['costo'],
            'nombre' => (string) $fila['nombre'],
            'activo' => (bool) $fila['activo'],
        ] : null;
    }

    public function mover(
        int $idVendedor,
        string $articulo,
        int $idArticulo,
        string $tipo,
        float $cantidad,
        string $motivo,
        ?string $referenciaTipo = null,
        ?int $idReferencia = null,
        ?float $costo = null,
        ?string $nota = null
    ): array {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $actual = $this->bloquear($articulo, $idArticulo, $idVendedor);
            if ($actual === null) {
                throw new RuntimeException('El ' . mb_strtolower($articulo, 'UTF-8') . ' ' . $idArticulo . ' no existe o es de otro vendedor');
            }
            $esProducto = $articulo === MovimientoInventario::PRODUCTO;
            $cantidad = $esProducto ? (float) (int) round($cantidad) : round($cantidad, 2);
            if ($cantidad <= 0) {
                throw new RuntimeException('La cantidad de un movimiento debe ser mayor que 0');
            }

            $nuevoCosto = $actual['costo'];
            if ($tipo === MovimientoInventario::ENTRADA) {
                if (!$esProducto && $costo !== null) {
                    $calculo = CostoPromedio::sumar($actual['existencia'], $actual['costo'], $cantidad, $costo);
                    $nueva = $calculo['existencia'];
                    $nuevoCosto = $calculo['costo'];
                } else {
                    $nueva = round($actual['existencia'] + $cantidad, 2);
                }
            } else {
                if ($actual['existencia'] + 0.001 < $cantidad) {
                    throw new OperacionNoPermitida(
                        'No hay suficiente «' . $actual['nombre'] . '»: ' . MovimientoInventario::mensajeNegativo($actual['existencia']) . '.'
                    );
                }
                if (!$esProducto && $costo !== null) {
                    $calculo = CostoPromedio::restar($actual['existencia'], $actual['costo'], $cantidad, $costo);
                    $nueva = $calculo['existencia'];
                    $nuevoCosto = $calculo['costo'];
                } else {
                    $nueva = max(0, round($actual['existencia'] - $cantidad, 2));
                }
            }

            if ($esProducto) {
                $this->conexion->prepare(
                    'UPDATE tbproducto SET tbproductoexistencia = ? WHERE tbproductoid = ? AND tbproductovendedorid = ?'
                )->execute([(int) round($nueva), $idArticulo, $idVendedor]);
            } else {
                $this->conexion->prepare(
                    'UPDATE tbcomponente SET tbcomponenteexistencia = ?, tbcomponentecostounitario = ?
                     WHERE tbcomponenteid = ? AND tbcomponentevendedorid = ?'
                )->execute([$nueva, $nuevoCosto, $idArticulo, $idVendedor]);
            }

            $this->conexion->prepare(
                'INSERT INTO tbmovimientoinventario (tbmovimientoinventarioid, tbmovimientoinventariovendedorid,
                    tbmovimientoinventarioarticulotipo, tbmovimientoinventarioarticuloid, tbmovimientoinventariotipo,
                    tbmovimientoinventariomotivo, tbmovimientoinventarionota, tbmovimientoinventariocantidad,
                    tbmovimientoinventarioexistenciaanterior, tbmovimientoinventarioexistencianueva,
                    tbmovimientoinventarioreferenciatipo, tbmovimientoinventarioreferenciaid, tbmovimientoinventariousuarioid,
                    tbmovimientoinventariofecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                        BaseDatos::generarId('tbmovimientoinventario', 'tbmovimientoinventarioid'),
                        $idVendedor,
                        $articulo,
                        $idArticulo,
                        $tipo,
                        $motivo,
                        $nota !== null && $nota !== '' ? $nota : null,
                        $cantidad,
                        $actual['existencia'],
                        $nueva,
                        $referenciaTipo,
                        $idReferencia,
                        UsuarioActual::id(),
                        (new DateTime())->format('Y-m-d H:i:s'),
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

        return ['anterior' => $actual['existencia'], 'nueva' => $nueva, 'nombre' => $actual['nombre']];
    }

    public function historial(int $idVendedor, string $articulo, int $idArticulo, int $pagina = 1): array
    {
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;
        $consulta = $this->conexion->prepare(
            'SELECT m.*, u.tbusuarionombrecompleto, p.tbproduccionproductoid
             FROM tbmovimientoinventario m
             LEFT JOIN tbusuario u ON u.tbusuarioid = m.tbmovimientoinventariousuarioid
             LEFT JOIN tbproduccion p ON m.tbmovimientoinventarioreferenciatipo = ? AND p.tbproduccionid = m.tbmovimientoinventarioreferenciaid
             WHERE m.tbmovimientoinventariovendedorid = ? AND m.tbmovimientoinventarioarticulotipo = ? AND m.tbmovimientoinventarioarticuloid = ?
             ORDER BY m.tbmovimientoinventariofecha DESC, m.tbmovimientoinventarioid DESC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute([MovimientoInventario::REFERENCIA_PRODUCCION, $idVendedor, $articulo, $idArticulo]);

        return array_map(fn(array $fila): array => [
            'id' => (int) $fila['tbmovimientoinventarioid'],
            'tipo' => (string) $fila['tbmovimientoinventariotipo'],
            'motivo' => (string) $fila['tbmovimientoinventariomotivo'],
            'nota' => $fila['tbmovimientoinventarionota'],
            'cantidad' => (float) $fila['tbmovimientoinventariocantidad'],
            'anterior' => (float) $fila['tbmovimientoinventarioexistenciaanterior'],
            'nueva' => (float) $fila['tbmovimientoinventarioexistencianueva'],
            'referenciaTipo' => $fila['tbmovimientoinventarioreferenciatipo'],
            'referenciaId' => $fila['tbmovimientoinventarioreferenciaid'] !== null ? (int) $fila['tbmovimientoinventarioreferenciaid'] : null,
            'idProductoProduccion' => $fila['tbproduccionproductoid'] !== null ? (int) $fila['tbproduccionproductoid'] : null,
            'usuario' => (string) ($fila['tbusuarionombrecompleto'] ?? ''),
            'fecha' => new DateTime((string) $fila['tbmovimientoinventariofecha']),
        ], $consulta->fetchAll());
    }

    public function contar(int $idVendedor, string $articulo, int $idArticulo): int
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbmovimientoinventario
             WHERE tbmovimientoinventariovendedorid = ? AND tbmovimientoinventarioarticulotipo = ? AND tbmovimientoinventarioarticuloid = ?'
        );
        $consulta->execute([$idVendedor, $articulo, $idArticulo]);

        return (int) $consulta->fetchColumn();
    }

    public function sumaMovimientos(int $idVendedor, string $articulo, int $idArticulo): float
    {
        $consulta = $this->conexion->prepare(
            'SELECT COALESCE(SUM(CASE WHEN tbmovimientoinventariotipo = ? THEN tbmovimientoinventariocantidad
                ELSE -tbmovimientoinventariocantidad END), 0)
             FROM tbmovimientoinventario
             WHERE tbmovimientoinventariovendedorid = ? AND tbmovimientoinventarioarticulotipo = ? AND tbmovimientoinventarioarticuloid = ?'
        );
        $consulta->execute([MovimientoInventario::ENTRADA, $idVendedor, $articulo, $idArticulo]);

        return round((float) $consulta->fetchColumn(), 2);
    }
}