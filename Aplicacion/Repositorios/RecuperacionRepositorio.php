<?php

namespace Aplicacion\Repositorios;

use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class RecuperacionRepositorio
{
    public const MINUTOS_VIGENCIA = 30;
    public const SEGUNDOS_ENTRE_SOLICITUDES = 60;

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public static function calcularHash(string $codigo): string
    {
        return hash('sha256', $codigo);
    }

    public function solicitudReciente(int $idUsuario): bool
    {
        $limite = (new DateTime())->modify('-' . self::SEGUNDOS_ENTRE_SOLICITUDES . ' seconds')->format('Y-m-d H:i:s');
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbrecuperacion WHERE tbrecuperacionusuarioid = ? AND tbrecuperacionregistrofecha > ?'
        );
        $consulta->execute([$idUsuario, $limite]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function crear(int $idUsuario, string $codigoHash): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $this->invalidarPendientes($idUsuario);

            $idRecuperacion = BaseDatos::generarId('tbrecuperacion', 'tbrecuperacionid');
            $ahora = new DateTime();
            $vencimiento = (clone $ahora)->modify('+' . self::MINUTOS_VIGENCIA . ' minutes');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbrecuperacion (tbrecuperacionid, tbrecuperacionusuarioid, tbrecuperacioncodigohash,
                    tbrecuperacionvencimiento, tbrecuperacionusado, tbrecuperacionregistrofecha)
                 VALUES (?, ?, ?, ?, 0, ?)'
            );
            $consulta->execute([
                $idRecuperacion,
                $idUsuario,
                $codigoHash,
                $vencimiento->format('Y-m-d H:i:s'),
                $ahora->format('Y-m-d H:i:s'),
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
    }

    public function buscarVigente(string $codigoHash): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT r.tbrecuperacionid, r.tbrecuperacionusuarioid
             FROM tbrecuperacion r
             INNER JOIN tbusuario u ON u.tbusuarioid = r.tbrecuperacionusuarioid
             WHERE r.tbrecuperacioncodigohash = ? AND r.tbrecuperacionusado = 0
               AND r.tbrecuperacionvencimiento > ? AND u.tbusuarioactivo = 1
             LIMIT 1'
        );
        $consulta->execute([$codigoHash, (new DateTime())->format('Y-m-d H:i:s')]);
        $fila = $consulta->fetch();

        return $fila ? ['id' => (int) $fila['tbrecuperacionid'], 'idUsuario' => (int) $fila['tbrecuperacionusuarioid']] : null;
    }

    public function restablecer(string $codigoHash, string $contrasenaNueva): bool
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $vigente = $this->buscarVigente($codigoHash);
            if ($vigente === null) {
                if ($transaccionPropia) {
                    BaseDatos::revertirTransaccion();
                }
                return false;
            }

            $consumir = $this->conexion->prepare(
                'UPDATE tbrecuperacion SET tbrecuperacionusado = 1
                 WHERE tbrecuperacionid = ? AND tbrecuperacionusado = 0 AND tbrecuperacionvencimiento > ?'
            );
            $consumir->execute([$vigente['id'], (new DateTime())->format('Y-m-d H:i:s')]);
            if ($consumir->rowCount() !== 1) {
                if ($transaccionPropia) {
                    BaseDatos::revertirTransaccion();
                }
                return false;
            }

            (new UsuarioRepositorio())->cambiarContrasena($vigente['idUsuario'], $contrasenaNueva);
            $this->invalidarPendientes($vigente['idUsuario']);
            (new SesionRepositorio())->cerrarTodasDeUsuario($vigente['idUsuario']);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
            return true;
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }

    private function invalidarPendientes(int $idUsuario): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbrecuperacion SET tbrecuperacionusado = 1 WHERE tbrecuperacionusuarioid = ? AND tbrecuperacionusado = 0'
        );
        $consulta->execute([$idUsuario]);
    }
}