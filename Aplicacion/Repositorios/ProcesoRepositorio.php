<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Proceso;
use Configuracion\BaseDatos;
use PDO;
use Throwable;


class ProcesoRepositorio
{
    public const POR_PAGINA = 10;

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function buscarPorId(int $idProceso, int $idVendedor): ?Proceso
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproceso WHERE tbprocesoid = ? AND tbprocesovendedorid = ?'
        );
        $consulta->execute([$idProceso, $idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    /** @return Proceso[] */
    public function listar(int $idVendedor, string $busqueda, ?bool $activo, int $pagina): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $busqueda, $activo);

        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproceso' . $condiciones
            . ' ORDER BY tbprocesonombre ASC, tbprocesoid ASC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): Proceso => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    public function contar(int $idVendedor, string $busqueda = '', ?bool $activo = null): int
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $busqueda, $activo);

        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbproceso' . $condiciones);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    public function existeNombre(int $idVendedor, string $nombre, ?int $excluirIdProceso = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbproceso
             WHERE tbprocesovendedorid = ? AND tbprocesonombre = ? AND tbprocesoid <> ?'
        );
        $consulta->execute([$idVendedor, trim($nombre), $excluirIdProceso ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(Proceso $proceso): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idProceso = BaseDatos::generarId('tbproceso', 'tbprocesoid');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbproceso (tbprocesoid, tbprocesovendedorid, tbprocesonombre, tbprocesodescripcion,
                    tbprocesotiempoestimado, tbprocesocostomanoobra, tbprocesoactivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idProceso,
                $proceso->getIdVendedor(),
                UsuarioRepositorio::limpiarEspacios((string) $proceso->getNombre()),
                $proceso->getDescripcion(),
                $proceso->getTiempoEstimado(),
                $proceso->getCostoManoObra(),
                $proceso->getEstado() ? 1 : 0,
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

        $proceso->setIdProceso($idProceso);

        return $idProceso;
    }

    public function actualizar(Proceso $proceso): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproceso SET tbprocesonombre = ?, tbprocesodescripcion = ?,
                tbprocesotiempoestimado = ?, tbprocesocostomanoobra = ?
             WHERE tbprocesoid = ? AND tbprocesovendedorid = ?'
        );
        $consulta->execute([
            UsuarioRepositorio::limpiarEspacios((string) $proceso->getNombre()),
            $proceso->getDescripcion(),
            $proceso->getTiempoEstimado(),
            $proceso->getCostoManoObra(),
            $proceso->getIdProceso(),
            $proceso->getIdVendedor(),
        ]);
    }

    public function cambiarEstado(Proceso $proceso, bool $activo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproceso SET tbprocesoactivo = ? WHERE tbprocesoid = ? AND tbprocesovendedorid = ?'
        );
        $consulta->execute([$activo ? 1 : 0, $proceso->getIdProceso(), $proceso->getIdVendedor()]);

        $proceso->setEstado($activo);
    }

    private function construirFiltros(int $idVendedor, string $busqueda, ?bool $activo): array
    {
        $condiciones = ['tbprocesovendedorid = ?'];
        $valores = [$idVendedor];

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            $condiciones[] = '(tbprocesonombre LIKE ? OR tbprocesodescripcion LIKE ?)';
            array_push($valores, $patron, $patron);
        }

        if ($activo !== null) {
            $condiciones[] = 'tbprocesoactivo = ?';
            $valores[] = $activo ? 1 : 0;
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    private function crearDesdeFila(array $fila): Proceso
    {
        return new Proceso(
            (int) $fila['tbprocesoid'],
            (int) $fila['tbprocesovendedorid'],
            $fila['tbprocesonombre'],
            $fila['tbprocesodescripcion'],
            $fila['tbprocesotiempoestimado'] !== null ? (int) $fila['tbprocesotiempoestimado'] : null,
            $fila['tbprocesocostomanoobra'] !== null ? (float) $fila['tbprocesocostomanoobra'] : null,
            (bool) $fila['tbprocesoactivo']
        );
    }
}