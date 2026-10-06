<?php
namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Sesion;
use Configuracion\BaseDatos;
use DateTime;

class SesionRepositorio
{
    public function registrarInicio(Sesion $sesion): int
    {
        BaseDatos::iniciarTransaccion();
        try {
            $id = BaseDatos::generarId('tbsesion', 'tbsesionid');

            $sql = 'INSERT INTO tbsesion
                        (tbsesionid, tbsesionusuarioid, tbsesionusuariotipo,
                         tbsesionfechainicio, tbsesionactivo)
                    VALUES
                        (:id, :usuarioId, :tipo, :inicio, 1)';

            BaseDatos::obtenerConexion()->prepare($sql)->execute([
                ':id'        => $id,
                ':usuarioId' => $sesion->getIdUsuario(),
                ':tipo'      => $sesion->getTipoUsuario(),
                ':inicio'    => $sesion->getFechaInicioSesion()->format('Y-m-d H:i:s'),
            ]);

            BaseDatos::confirmarTransaccion();
            $sesion->setIdSesion($id);
            return $id;
        } catch (\Throwable $error) {
            BaseDatos::revertirTransaccion();
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
            ':id'     => $sesion->getIdSesion(),
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
            ':id'     => $idSesion,
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
            ':cierre'    => (new DateTime())->format('Y-m-d H:i:s'),
            ':usuarioId' => $idUsuario,
        ]);

        return $sentencia->rowCount();
    }

    public function listarConUsuario(int $limite = 100): array
    {
        $sql = 'SELECT s.tbsesionid            AS id,
                   u.tbusuarionombrecompleto AS nombre,
                   u.tbusuariocorreo         AS correo,
                   s.tbsesionusuariotipo     AS tipo,
                   s.tbsesionfechainicio     AS inicio,
                   s.tbsesionfechacierre     AS cierre,
                   s.tbsesionactivo          AS abierta
            FROM tbsesion s
            LEFT JOIN tbusuario u ON u.tbusuarioid = s.tbsesionusuarioid
            ORDER BY s.tbsesionfechainicio DESC, s.tbsesionid DESC
            LIMIT ' . max(1, $limite);

        return BaseDatos::obtenerConexion()->query($sql)->fetchAll();
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
