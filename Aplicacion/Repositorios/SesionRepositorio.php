<?php
namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Sesion;
use Configuracion\BaseDatos;
use DateTime;

class SesionRepositorio
{
    public function registrarInicio(Sesion $sesion): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();
        try {
            $id = BaseDatos::generarId('tbsesion', 'tbsesionid');

            $sql = 'INSERT INTO tbsesion (tbsesionid, tbsesionusuarioid, tbsesionusuariotipo, tbsesionfechainicio, tbsesionactivo)
                    VALUES (:id, :usuarioId, :tipo, :inicio, 1)';

            BaseDatos::obtenerConexion()->prepare($sql)->execute([
                ':id' => $id,
                ':usuarioId' => $sesion->getIdUsuario(),
                ':tipo' => $sesion->getTipoUsuario(),
                ':inicio' => $sesion->getFechaInicioSesion()->format('Y-m-d H:i:s'),
            ]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
            $sesion->setIdSesion($id);
            return $id;
        } catch (\Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }

    public function registrarCierre(Sesion $sesion): void
    {

        if ($sesion->estaActiva()) {
            $sesion->cerrar();
        }

        $sql = 'UPDATE tbsesion
                SET tbsesionfechacierre = :cierre,
                    tbsesionactivo = 0
                WHERE tbsesionid = :id';

        BaseDatos::obtenerConexion()->prepare($sql)->execute([
            ':cierre' => $sesion->getFechaCierreSesion()->format('Y-m-d H:i:s'),
            ':id' => $sesion->getIdSesion(),
        ]);
    }

    public function cerrarPorId(int $idSesion): void
    {
        $sql = 'UPDATE tbsesion
                SET tbsesionfechacierre = :cierre,
                    tbsesionactivo = 0
                WHERE tbsesionid = :id
                  AND tbsesionactivo = 1';

        BaseDatos::obtenerConexion()->prepare($sql)->execute([
            ':cierre' => (new DateTime())->format('Y-m-d H:i:s'),
            ':id' => $idSesion,
        ]);
    }

    public function cerrarTodasDeUsuario(int $idUsuario): int
    {
        $sql = 'UPDATE tbsesion
                SET tbsesionfechacierre = :cierre,
                    tbsesionactivo = 0
                WHERE tbsesionusuarioid = :usuarioId
                  AND tbsesionactivo = 1';

        $sentencia = BaseDatos::obtenerConexion()->prepare($sql);
        $sentencia->execute([
            ':cierre' => (new DateTime())->format('Y-m-d H:i:s'),
            ':usuarioId' => $idUsuario,
        ]);

        return $sentencia->rowCount();
    }

    public function listarPorUsuario(int $idUsuario, int $limite = 100): array
    {
        $sentencia = BaseDatos::obtenerConexion()->prepare(
            'SELECT tbsesionid AS id,
                    tbsesionusuariotipo AS tipo,
                    tbsesionfechainicio AS inicio,
                    tbsesionfechacierre AS cierre,
                    tbsesionactivo AS abierta
             FROM tbsesion
             WHERE tbsesionusuarioid = :usuarioId
             ORDER BY tbsesionfechainicio DESC, tbsesionid DESC
             LIMIT ' . max(1, $limite)
        );
        $sentencia->execute([':usuarioId' => $idUsuario]);

        return $sentencia->fetchAll();
    }

    public function estaAbierta(int $idSesion): bool
    {
        $sentencia = BaseDatos::obtenerConexion()->prepare(
            'SELECT tbsesionactivo FROM tbsesion WHERE tbsesionid = :id'
        );
        $sentencia->execute([':id' => $idSesion]);

        return (int) $sentencia->fetchColumn() === 1;
    }
}
