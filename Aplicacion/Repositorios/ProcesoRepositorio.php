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

    public function buscarPorId(int $idProceso): ?Proceso
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbproceso WHERE tbprocesoid = ?');
        $consulta->execute([$idProceso]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    // Todos los procesos que cumplen el filtro; la paginación se hace en PHP (el catálogo es corto)
    public function listar(string $busqueda, ?bool $activo): array
    {
        $condiciones = [];
        $valores = [];

        if ($busqueda !== '') {
            $condiciones[] = '(tbprocesonombre LIKE ? OR tbprocesodescripcion LIKE ?)';
            $texto = '%' . addcslashes($busqueda, '%_\\') . '%';
            array_push($valores, $texto, $texto);
        }
        if ($activo !== null) {
            $condiciones[] = 'tbprocesoactivo = ?';
            $valores[] = $activo ? 1 : 0;
        }

        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproceso'
            . ($condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones))
            . ' ORDER BY tbprocesonombre ASC, tbprocesoid ASC'
        );
        $consulta->execute($valores);

        return array_map(fn (array $fila): Proceso => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    // ¿Ya hay otro proceso con este nombre? (si se edita, se ignora el propio)
    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $consulta = $this->conexion->prepare('SELECT tbprocesoid FROM tbproceso WHERE tbprocesonombre = ?');
        $consulta->execute([$nombre]);

        foreach ($consulta->fetchAll() as $fila) {
            if ($excluirId === null || (int) $fila['tbprocesoid'] !== $excluirId) {
                return true;
            }
        }

        return false;
    }

    public function insertar(Proceso $proceso): int
    {
        // generarId exige estar dentro de una transacción (así dos altas a la vez no repiten el id)
        BaseDatos::iniciarTransaccion();

        try {
            $idProceso = BaseDatos::generarId('tbproceso', 'tbprocesoid');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbproceso
                    (tbprocesoid, tbprocesonombre, tbprocesodescripcion, tbprocesotiempoestimado,
                     tbprocesocostomanoobra, tbprocesoactivo)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idProceso,
                $proceso->getNombre(),
                $proceso->getDescripcion(),
                $proceso->getTiempoEstimado(),
                $proceso->getCostoManoObra(),
                $proceso->getActivo() ? 1 : 0,
            ]);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }

        $proceso->setIdProceso($idProceso);

        return $idProceso;
    }

    public function actualizar(Proceso $proceso): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproceso
             SET tbprocesonombre = ?, tbprocesodescripcion = ?, tbprocesotiempoestimado = ?, tbprocesocostomanoobra = ?
             WHERE tbprocesoid = ?'
        );
        $consulta->execute([
            $proceso->getNombre(),
            $proceso->getDescripcion(),
            $proceso->getTiempoEstimado(),
            $proceso->getCostoManoObra(),
            $proceso->getIdProceso(),
        ]);
    }

    public function cambiarEstado(Proceso $proceso, bool $activo): void
    {
        $consulta = $this->conexion->prepare('UPDATE tbproceso SET tbprocesoactivo = ? WHERE tbprocesoid = ?');
        $consulta->execute([$activo ? 1 : 0, $proceso->getIdProceso()]);

        $proceso->setActivo($activo);
    }

    private function crearDesdeFila(array $fila): Proceso
    {
        return new Proceso(
            (int) $fila['tbprocesoid'],
            (string) $fila['tbprocesonombre'],
            (string) $fila['tbprocesodescripcion'],
            (int) $fila['tbprocesotiempoestimado'],
            number_format((float) $fila['tbprocesocostomanoobra'], 2, '.', ''),
            (int) $fila['tbprocesoactivo'] === 1
        );
    }
}