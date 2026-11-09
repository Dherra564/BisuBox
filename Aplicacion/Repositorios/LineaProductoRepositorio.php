<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\LineaProducto;
use Configuracion\BaseDatos;
use PDO;
use Throwable;

class LineaProductoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function listar(int $idVendedor, bool $soloActivas = false): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT l.*,
                (SELECT COUNT(*) FROM tbproveedorlinea pl
                    INNER JOIN tbproveedor p ON p.tbproveedorid = pl.tbproveedorlineaproveedorid AND p.tbproveedoractivo = 1
                    WHERE pl.tbproveedorlinealineaid = l.tblineaproductoid) AS proveedores,
                (SELECT COUNT(*) FROM tbproducto pr
                    WHERE pr.tbproductolineaid = l.tblineaproductoid AND pr.tbproductoactivo = 1) AS productos
             FROM tblineaproducto l
             WHERE l.tblineaproductovendedorid = ?' . ($soloActivas ? ' AND l.tblineaproductoactivo = 1' : '') . '
             ORDER BY l.tblineaproductonombre ASC, l.tblineaproductoid ASC'
        );
        $consulta->execute([$idVendedor]);

        $lineas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $lineas[(int) $fila['tblineaproductoid']] = $this->crearDesdeFila($fila);
        }
        return $lineas;
    }

    public function buscarPorId(int $idLinea, int $idVendedor): ?LineaProducto
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tblineaproducto WHERE tblineaproductoid = ? AND tblineaproductovendedorid = ?'
        );
        $consulta->execute([$idLinea, $idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function existeNombre(int $idVendedor, string $nombre, ?int $excluirIdLinea = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tblineaproducto WHERE tblineaproductovendedorid = ? AND tblineaproductonombre = ? AND tblineaproductoid <> ?'
        );
        $consulta->execute([$idVendedor, trim($nombre), $excluirIdLinea ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(LineaProducto $linea): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idLinea = BaseDatos::generarId('tblineaproducto', 'tblineaproductoid');
            $this->conexion->prepare(
                'INSERT INTO tblineaproducto (tblineaproductoid, tblineaproductovendedorid, tblineaproductonombre, tblineaproductodescripcion,
                    tblineaproductoactivo)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                        $idLinea,
                        $linea->getIdVendedor(),
                        UsuarioRepositorio::limpiarEspacios($linea->getNombre()),
                        $linea->getDescripcion(),
                        $linea->getActivo() ? 1 : 0,
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

        $linea->setIdLinea($idLinea);

        return $idLinea;
    }

    public function actualizar(LineaProducto $linea): void
    {
        $this->conexion->prepare(
            'UPDATE tblineaproducto SET tblineaproductonombre = ?, tblineaproductodescripcion = ?
             WHERE tblineaproductoid = ? AND tblineaproductovendedorid = ?'
        )->execute([
                    UsuarioRepositorio::limpiarEspacios($linea->getNombre()),
                    $linea->getDescripcion(),
                    $linea->getIdLinea(),
                    $linea->getIdVendedor(),
                ]);
    }

    public function cambiarEstado(LineaProducto $linea, bool $activo): void
    {
        $this->conexion->prepare(
            'UPDATE tblineaproducto SET tblineaproductoactivo = ? WHERE tblineaproductoid = ? AND tblineaproductovendedorid = ?'
        )->execute([$activo ? 1 : 0, $linea->getIdLinea(), $linea->getIdVendedor()]);
        $linea->setActivo($activo);
    }

    public function idsDeProveedor(int $idProveedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbproveedorlinealineaid FROM tbproveedorlinea WHERE tbproveedorlineaproveedorid = ? ORDER BY tbproveedorlinealineaid ASC'
        );
        $consulta->execute([$idProveedor]);

        return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
    }

    public function porProveedor(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT pl.tbproveedorlineaproveedorid, pl.tbproveedorlinealineaid
             FROM tbproveedorlinea pl
             INNER JOIN tbproveedor p ON p.tbproveedorid = pl.tbproveedorlineaproveedorid
             WHERE p.tbproveedorvendedorid = ?'
        );
        $consulta->execute([$idVendedor]);

        $lineas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $lineas[(int) $fila['tbproveedorlineaproveedorid']][] = (int) $fila['tbproveedorlinealineaid'];
        }
        return $lineas;
    }

    public function guardarDeProveedor(int $idProveedor, array $idsLineas): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $this->conexion->prepare('DELETE FROM tbproveedorlinea WHERE tbproveedorlineaproveedorid = ?')->execute([$idProveedor]);
            $insertar = $this->conexion->prepare(
                'INSERT INTO tbproveedorlinea (tbproveedorlineaid, tbproveedorlineaproveedorid, tbproveedorlinealineaid) VALUES (?, ?, ?)'
            );
            foreach (array_unique($idsLineas) as $idLinea) {
                $insertar->execute([BaseDatos::generarId('tbproveedorlinea', 'tbproveedorlineaid'), $idProveedor, $idLinea]);
            }

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }

    private function crearDesdeFila(array $fila): LineaProducto
    {
        return new LineaProducto(
            (int) $fila['tblineaproductoid'],
            (int) $fila['tblineaproductovendedorid'],
            (string) $fila['tblineaproductonombre'],
            $fila['tblineaproductodescripcion'],
            (bool) $fila['tblineaproductoactivo'],
            (int) ($fila['proveedores'] ?? 0),
            (int) ($fila['productos'] ?? 0)
        );
    }
}