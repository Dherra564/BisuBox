<?php

namespace Configuracion;

use Configuracion\Configuracion;
use DateTime;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;


class BaseDatos
{
    private static ?PDO $conexion = null;


    public static function obtenerConexion(): PDO
    {
        if (self::$conexion === null) {
            $servidor = Configuracion::obtener('bdServidor');
            $puerto = Configuracion::obtener('bdPuerto');
            $nombre = Configuracion::obtener('bdNombre');
            $dsn = "mysql:host={$servidor};port={$puerto};dbname={$nombre};charset=utf8mb4";

            try {
                self::$conexion = new PDO(
                    $dsn,
                    Configuracion::obtener('bdUsuario'),
                    Configuracion::obtener('bdContrasena', ''),
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );


                $diferenciaHoraria = (new DateTime())->format('P');
                self::$conexion->exec("SET time_zone = '{$diferenciaHoraria}'");
            } catch (PDOException $error) {

                error_log('Error de conexion: ' . $error->getMessage());
                throw new RuntimeException('No se pudo conectar a la base de datos.');
            }
        }

        return self::$conexion;
    }

    public static function iniciarTransaccion(): bool
    {
        $conexion = self::obtenerConexion();
        if ($conexion->inTransaction()) {
            return false;
        }
        $conexion->beginTransaction();
        return true;
    }

    public static function confirmarTransaccion(): void
    {
        $conexion = self::obtenerConexion();
        if ($conexion->inTransaction()) {
            $conexion->commit();
        }
    }

    public static function revertirTransaccion(): void
    {
        $conexion = self::obtenerConexion();
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
    }


    public static function generarId(string $tabla, string $columna): int
    {

        if (!preg_match('/^[a-z]+$/', $tabla) || !preg_match('/^[a-z]+$/', $columna)) {
            throw new InvalidArgumentException('Nombre de tabla o columna no válido.');
        }

        $conexion = self::obtenerConexion();
        if (!$conexion->inTransaction()) {
            throw new RuntimeException('generarId debe llamarse dentro de una transacción.');
        }

        $sql = "SELECT COALESCE(MAX({$columna}), 0) + 1 AS siguiente FROM {$tabla} FOR UPDATE";
        $fila = $conexion->query($sql)->fetch();

        return (int) $fila['siguiente'];
    }
}