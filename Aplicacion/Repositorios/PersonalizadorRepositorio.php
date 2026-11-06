<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Nucleo\UnidadMedida;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class PersonalizadorRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function buscar(int $idVendedor): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbpersonalizador WHERE tbpersonalizadorvendedorid = ? ORDER BY tbpersonalizadorid DESC LIMIT 1'
        );
        $consulta->execute([$idVendedor]);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }
        return [
            'idHilo' => $fila['tbpersonalizadorhilocomponenteid'] !== null ? (int) $fila['tbpersonalizadorhilocomponenteid'] : null,
            'manoObra' => (float) $fila['tbpersonalizadormanoobra'],
            'ganancia' => (float) $fila['tbpersonalizadorganancia'],
            'activo' => (bool) $fila['tbpersonalizadoractivo'],
        ];
    }

    public function guardar(int $idVendedor, int $idHilo, float $manoObra, float $ganancia, bool $activo): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT tbpersonalizadorid FROM tbpersonalizador WHERE tbpersonalizadorvendedorid = ?
                 ORDER BY tbpersonalizadorid DESC LIMIT 1 FOR UPDATE'
            );
            $consulta->execute([$idVendedor]);
            $id = $consulta->fetchColumn();
            $ahora = (new DateTime())->format('Y-m-d H:i:s');

            if ($id !== false) {
                $cambio = $this->conexion->prepare(
                    'UPDATE tbpersonalizador SET tbpersonalizadorhilocomponenteid = ?, tbpersonalizadormanoobra = ?,
                        tbpersonalizadorganancia = ?, tbpersonalizadoractivo = ?, tbpersonalizadoractualizacionfecha = ?
                     WHERE tbpersonalizadorid = ?'
                );
                $cambio->execute([$idHilo, $manoObra, $ganancia, $activo ? 1 : 0, $ahora, (int) $id]);
            } else {
                $nuevo = $this->conexion->prepare(
                    'INSERT INTO tbpersonalizador (tbpersonalizadorid, tbpersonalizadorvendedorid, tbpersonalizadorhilocomponenteid,
                        tbpersonalizadormanoobra, tbpersonalizadorganancia, tbpersonalizadoractivo, tbpersonalizadoractualizacionfecha)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $nuevo->execute([
                    BaseDatos::generarId('tbpersonalizador', 'tbpersonalizadorid'),
                    $idVendedor,
                    $idHilo,
                    $manoObra,
                    $ganancia,
                    $activo ? 1 : 0,
                    $ahora,
                ]);
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

    public function listarHilos(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbcomponenteid, tbcomponentenombre, tbcomponenteexistencia, tbcomponentecostounitario FROM tbcomponente
             WHERE tbcomponentevendedorid = ? AND tbcomponenteactivo = 1 AND tbcomponenteunidad = ?
             ORDER BY tbcomponentenombre ASC'
        );
        $consulta->execute([$idVendedor, UnidadMedida::METRO]);

        $hilos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $hilos[(int) $fila['tbcomponenteid']] = [
                'idComponente' => (int) $fila['tbcomponenteid'],
                'nombre' => (string) $fila['tbcomponentenombre'],
                'existencia' => (float) $fila['tbcomponenteexistencia'],
                'costo' => (float) $fila['tbcomponentecostounitario'],
            ];
        }
        return $hilos;
    }
}
