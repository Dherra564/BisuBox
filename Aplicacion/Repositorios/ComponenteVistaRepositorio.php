<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Nucleo\ClaseDato;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class ComponenteVistaRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function listarParaVendedor(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT c.tbcomponenteid, c.tbcomponentenombre, c.tbcomponenteexistencia, c.tbcomponentecostounitario,
                    t.tbtipocomponentenombre, v.tbcomponentevistafoto, v.tbcomponentevistatamano
             FROM tbcomponente c
             INNER JOIN tbtipocomponente t ON t.tbtipocomponenteid = c.tbcomponentetipocomponenteid
             LEFT JOIN tbcomponentevista v ON v.tbcomponentevistacomponenteid = c.tbcomponenteid
                AND v.tbcomponentevistavendedorid = c.tbcomponentevendedorid AND v.tbcomponentevistaactivo = 1
             WHERE c.tbcomponentevendedorid = ? AND c.tbcomponenteactivo = 1 AND c.tbcomponenteunidad = ?
             ORDER BY t.tbtipocomponentenombre ASC, c.tbcomponentenombre ASC'
        );
        $consulta->execute([$idVendedor, UnidadMedida::UNIDAD]);
        $filas = $consulta->fetchAll();
        $sugeridos = $this->tamanosSugeridos(array_map(fn(array $fila): int => (int) $fila['tbcomponenteid'], $filas));

        return array_map(function (array $fila) use ($sugeridos): array {
            $id = (int) $fila['tbcomponenteid'];
            return [
                'idComponente' => $id,
                'nombre' => (string) $fila['tbcomponentenombre'],
                'tipo' => (string) $fila['tbtipocomponentenombre'],
                'existencia' => (int) floor((float) $fila['tbcomponenteexistencia']),
                'costo' => (float) $fila['tbcomponentecostounitario'],
                'foto' => $fila['tbcomponentevistafoto'] !== null ? (string) $fila['tbcomponentevistafoto'] : null,
                'tamano' => $fila['tbcomponentevistatamano'] !== null ? (float) $fila['tbcomponentevistatamano'] : null,
                'tamanoSugerido' => $sugeridos[$id] ?? null,
            ];
        }, $filas);
    }

    public function listarParaTienda(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT c.tbcomponenteid, c.tbcomponentenombre, c.tbcomponenteexistencia, c.tbcomponentecostounitario,
                    v.tbcomponentevistafoto, v.tbcomponentevistatamano
             FROM tbcomponentevista v
             INNER JOIN tbcomponente c ON c.tbcomponenteid = v.tbcomponentevistacomponenteid
                AND c.tbcomponentevendedorid = v.tbcomponentevistavendedorid
             WHERE v.tbcomponentevistavendedorid = ? AND v.tbcomponentevistaactivo = 1
                AND c.tbcomponenteactivo = 1 AND c.tbcomponenteunidad = ?
             ORDER BY c.tbcomponentenombre ASC'
        );
        $consulta->execute([$idVendedor, UnidadMedida::UNIDAD]);

        $piezas = [];
        foreach ($consulta->fetchAll() as $fila) {
            $piezas[(int) $fila['tbcomponenteid']] = [
                'idComponente' => (int) $fila['tbcomponenteid'],
                'nombre' => (string) $fila['tbcomponentenombre'],
                'existencia' => max(0, (int) floor((float) $fila['tbcomponenteexistencia'])),
                'costo' => (float) $fila['tbcomponentecostounitario'],
                'foto' => (string) $fila['tbcomponentevistafoto'],
                'tamano' => (float) $fila['tbcomponentevistatamano'],
            ];
        }
        return $piezas;
    }

    public function idsComponentesDeUnidad(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbcomponenteid, tbcomponentenombre FROM tbcomponente
             WHERE tbcomponentevendedorid = ? AND tbcomponenteactivo = 1 AND tbcomponenteunidad = ?'
        );
        $consulta->execute([$idVendedor, UnidadMedida::UNIDAD]);

        $componentes = [];
        foreach ($consulta->fetchAll() as $fila) {
            $componentes[(int) $fila['tbcomponenteid']] = (string) $fila['tbcomponentenombre'];
        }
        return $componentes;
    }

    public function guardar(int $idVendedor, int $idComponente, string $foto, float $tamano): ?string
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT tbcomponentevistaid, tbcomponentevistafoto, tbcomponentevistaactivo FROM tbcomponentevista
                 WHERE tbcomponentevistavendedorid = ? AND tbcomponentevistacomponenteid = ?
                 ORDER BY tbcomponentevistaactivo DESC, tbcomponentevistaid DESC LIMIT 1 FOR UPDATE'
            );
            $consulta->execute([$idVendedor, $idComponente]);
            $fila = $consulta->fetch();
            $ahora = (new DateTime())->format('Y-m-d H:i:s');
            $fotoAnterior = null;

            if ($fila) {
                $fotoAnterior = $fila['tbcomponentevistafoto'] !== null ? (string) $fila['tbcomponentevistafoto'] : null;
                $cambio = $this->conexion->prepare(
                    'UPDATE tbcomponentevista SET tbcomponentevistafoto = ?, tbcomponentevistatamano = ?,
                        tbcomponentevistaregistrofecha = ?, tbcomponentevistaactivo = 1
                     WHERE tbcomponentevistaid = ?'
                );
                $cambio->execute([$foto, $tamano, $ahora, (int) $fila['tbcomponentevistaid']]);
            } else {
                $nuevo = $this->conexion->prepare(
                    'INSERT INTO tbcomponentevista (tbcomponentevistaid, tbcomponentevistavendedorid, tbcomponentevistacomponenteid,
                        tbcomponentevistafoto, tbcomponentevistatamano, tbcomponentevistaregistrofecha, tbcomponentevistaactivo)
                     VALUES (?, ?, ?, ?, ?, ?, 1)'
                );
                $nuevo->execute([
                    BaseDatos::generarId('tbcomponentevista', 'tbcomponentevistaid'),
                    $idVendedor,
                    $idComponente,
                    $foto,
                    $tamano,
                    $ahora,
                ]);
            }

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
            return $fotoAnterior !== $foto ? $fotoAnterior : null;
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }

    public function cambiarTamano(int $idVendedor, int $idComponente, float $tamano): bool
    {
        if (!$this->tieneFoto($idVendedor, $idComponente)) {
            return false;
        }
        $consulta = $this->conexion->prepare(
            'UPDATE tbcomponentevista SET tbcomponentevistatamano = ?
             WHERE tbcomponentevistavendedorid = ? AND tbcomponentevistacomponenteid = ? AND tbcomponentevistaactivo = 1'
        );
        $consulta->execute([$tamano, $idVendedor, $idComponente]);
        return true;
    }

    public function quitar(int $idVendedor, int $idComponente): ?string
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT tbcomponentevistaid, tbcomponentevistafoto FROM tbcomponentevista
                 WHERE tbcomponentevistavendedorid = ? AND tbcomponentevistacomponenteid = ? AND tbcomponentevistaactivo = 1
                 FOR UPDATE'
            );
            $consulta->execute([$idVendedor, $idComponente]);
            $fila = $consulta->fetch();
            if (!$fila) {
                if ($transaccionPropia) {
                    BaseDatos::confirmarTransaccion();
                }
                return null;
            }
            $cambio = $this->conexion->prepare(
                'UPDATE tbcomponentevista SET tbcomponentevistaactivo = 0, tbcomponentevistafoto = NULL WHERE tbcomponentevistaid = ?'
            );
            $cambio->execute([(int) $fila['tbcomponentevistaid']]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
            return $fila['tbcomponentevistafoto'] !== null ? (string) $fila['tbcomponentevistafoto'] : null;
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }

    public function tieneFoto(int $idVendedor, int $idComponente): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT 1 FROM tbcomponentevista
             WHERE tbcomponentevistavendedorid = ? AND tbcomponentevistacomponenteid = ? AND tbcomponentevistaactivo = 1 LIMIT 1'
        );
        $consulta->execute([$idVendedor, $idComponente]);
        return (bool) $consulta->fetchColumn();
    }

    public function fotoEsPublica(string $foto): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT 1 FROM tbcomponentevista WHERE tbcomponentevistafoto = ? AND tbcomponentevistaactivo = 1 LIMIT 1'
        );
        $consulta->execute([$foto]);
        return (bool) $consulta->fetchColumn();
    }

    private function tamanosSugeridos(array $idsComponente): array
    {
        if ($idsComponente === []) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($idsComponente), '?'));
        $consulta = $this->conexion->prepare(
            "SELECT v.tbcomponentevalorcomponenteid, v.tbcomponentevalorvalor
             FROM tbcomponentevalor v
             INNER JOIN tbtipocomponenteatributo a ON a.tbtipocomponenteatributoid = v.tbcomponentevalortipocomponenteatributoid
             WHERE v.tbcomponentevalorcomponenteid IN ({$marcas})
               AND a.tbtipocomponenteatributoclase = ? AND LOWER(a.tbtipocomponenteatributounidad) = 'mm'
               AND a.tbtipocomponenteatributoactivo = 1
             ORDER BY a.tbtipocomponenteatributoorden ASC"
        );
        $consulta->execute([...$idsComponente, ClaseDato::NUMERO]);

        $sugeridos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $id = (int) $fila['tbcomponentevalorcomponenteid'];
            $valor = Numero::leer((string) $fila['tbcomponentevalorvalor']);
            if (!isset($sugeridos[$id]) && $valor !== null && $valor >= 1 && $valor <= 80) {
                $sugeridos[$id] = round($valor, 1);
            }
        }
        return $sugeridos;
    }
}
