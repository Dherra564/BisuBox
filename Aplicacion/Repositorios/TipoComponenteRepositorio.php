<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\TipoComponente;
use Configuracion\BaseDatos;
use PDO;
use Throwable;

class TipoComponenteRepositorio
{
    private PDO $conexion;
    private TipoComponenteAtributoRepositorio $atributoRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->atributoRepositorio = new TipoComponenteAtributoRepositorio();
    }

    /** @return TipoComponente[] */
    public function listarPorVendedor(int $idVendedor, bool $soloActivos = false): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT t.*,
                (SELECT COUNT(*) FROM tbcomponente c WHERE c.tbcomponentetipocomponenteid = t.tbtipocomponenteid) AS cantidadcomponentes
             FROM tbtipocomponente t
             WHERE t.tbtipocomponentevendedorid = ?' . ($soloActivos ? ' AND t.tbtipocomponenteactivo = 1' : '') . '
             ORDER BY t.tbtipocomponentenombre ASC'
        );
        $consulta->execute([$idVendedor]);

        return array_map(fn(array $fila): TipoComponente => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    public function buscarPorId(int $idTipoComponente, int $idVendedor): ?TipoComponente
    {
        $consulta = $this->conexion->prepare(
            'SELECT t.*,
                (SELECT COUNT(*) FROM tbcomponente c WHERE c.tbcomponentetipocomponenteid = t.tbtipocomponenteid) AS cantidadcomponentes
             FROM tbtipocomponente t
             WHERE t.tbtipocomponenteid = ? AND t.tbtipocomponentevendedorid = ?'
        );
        $consulta->execute([$idTipoComponente, $idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function contarPorVendedor(int $idVendedor): int
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbtipocomponente WHERE tbtipocomponentevendedorid = ?');
        $consulta->execute([$idVendedor]);

        return (int) $consulta->fetchColumn();
    }

    public function existeNombre(int $idVendedor, string $nombre, ?int $excluirIdTipoComponente = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbtipocomponente
             WHERE tbtipocomponentevendedorid = ? AND tbtipocomponentenombre = ? AND tbtipocomponenteid <> ?'
        );
        $consulta->execute([$idVendedor, UsuarioRepositorio::limpiarEspacios($nombre), $excluirIdTipoComponente ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(TipoComponente $tipoComponente): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idTipoComponente = BaseDatos::generarId('tbtipocomponente', 'tbtipocomponenteid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbtipocomponente (tbtipocomponenteid, tbtipocomponentevendedorid, tbtipocomponentenombre, tbtipocomponenteactivo)
                 VALUES (?, ?, ?, ?)'
            );
            $consulta->execute([
                $idTipoComponente,
                $tipoComponente->getIdVendedor(),
                UsuarioRepositorio::limpiarEspacios($tipoComponente->getNombre()),
                $tipoComponente->getActivo() ? 1 : 0,
            ]);

            $this->atributoRepositorio->guardar($idTipoComponente, $tipoComponente->getAtributos());

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $tipoComponente->setIdTipoComponente($idTipoComponente);

        return $idTipoComponente;
    }

    /** @param TipoComponente[] $tiposComponente */
    public function insertarVarios(array $tiposComponente): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            foreach ($tiposComponente as $tipoComponente) {
                $this->insertar($tipoComponente);
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

    public function actualizar(TipoComponente $tipoComponente): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare(
                'UPDATE tbtipocomponente SET tbtipocomponentenombre = ?, tbtipocomponenteactivo = ?
                 WHERE tbtipocomponenteid = ? AND tbtipocomponentevendedorid = ?'
            );
            $consulta->execute([
                UsuarioRepositorio::limpiarEspacios($tipoComponente->getNombre()),
                $tipoComponente->getActivo() ? 1 : 0,
                $tipoComponente->getIdTipoComponente(),
                $tipoComponente->getIdVendedor(),
            ]);

            $this->atributoRepositorio->guardar((int) $tipoComponente->getIdTipoComponente(), $tipoComponente->getAtributos());

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

    public function cambiarEstado(int $idTipoComponente, int $idVendedor, bool $activo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbtipocomponente SET tbtipocomponenteactivo = ? WHERE tbtipocomponenteid = ? AND tbtipocomponentevendedorid = ?'
        );
        $consulta->execute([$activo ? 1 : 0, $idTipoComponente, $idVendedor]);
    }

    private function crearDesdeFila(array $fila): TipoComponente
    {
        $idTipoComponente = (int) $fila['tbtipocomponenteid'];

        return new TipoComponente(
            $idTipoComponente,
            (int) $fila['tbtipocomponentevendedorid'],
            (string) $fila['tbtipocomponentenombre'],
            (bool) $fila['tbtipocomponenteactivo'],
            $this->atributoRepositorio->listarPorTipo($idTipoComponente),
            (int) $fila['cantidadcomponentes']
        );
    }
}