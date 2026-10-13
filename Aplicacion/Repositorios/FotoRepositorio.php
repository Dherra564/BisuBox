<?php

namespace Aplicacion\Repositorios;

use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class FotoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function insertar(string $carpeta, string $nombre, string $tipo, string $datos): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idFoto = BaseDatos::generarId('tbfoto', 'tbfotoid');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbfoto (tbfotoid, tbfotocarpeta, tbfotonombre, tbfototipo, tbfotodatos, tbfotoregistrofecha)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $consulta->bindValue(1, $idFoto, PDO::PARAM_INT);
            $consulta->bindValue(2, $carpeta);
            $consulta->bindValue(3, $nombre);
            $consulta->bindValue(4, $tipo);
            $consulta->bindValue(5, $datos, PDO::PARAM_LOB);
            $consulta->bindValue(6, (new DateTime())->format('Y-m-d H:i:s'));
            $consulta->execute();

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

    public function buscar(string $carpeta, string $nombre): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbfototipo, tbfotodatos FROM tbfoto WHERE tbfotocarpeta = ? AND tbfotonombre = ? LIMIT 1'
        );
        $consulta->execute([$carpeta, $nombre]);
        $fila = $consulta->fetch();

        if (!$fila) {
            return null;
        }
        $datos = is_resource($fila['tbfotodatos']) ? stream_get_contents($fila['tbfotodatos']) : (string) $fila['tbfotodatos'];

        return ['tipo' => (string) $fila['tbfototipo'], 'datos' => $datos];
    }

    public function eliminar(string $carpeta, string $nombre): void
    {
        $consulta = $this->conexion->prepare('DELETE FROM tbfoto WHERE tbfotocarpeta = ? AND tbfotonombre = ?');
        $consulta->execute([$carpeta, $nombre]);
    }
}