<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\SuperAdmin;
use Configuracion\BaseDatos;
use DateTime;
use PDO;


class SuperAdminRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function buscarPorIdUsuario(int $idUsuario): ?SuperAdmin
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.*, s.tbsuperadminid, s.tbsuperadminactivo
             FROM tbsuperadmin s
             INNER JOIN tbusuario u ON u.tbusuarioid = s.tbusuarioid
             WHERE s.tbusuarioid = ?'
        );
        $consulta->execute([$idUsuario]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    /**
     * @return SuperAdmin[]
     */
    public function listarActivos(): array
    {
        $consulta = $this->conexion->query(
            'SELECT u.*, s.tbsuperadminid, s.tbsuperadminactivo
             FROM tbsuperadmin s
             INNER JOIN tbusuario u ON u.tbusuarioid = s.tbusuarioid
             WHERE s.tbsuperadminactivo = 1 AND u.tbusuarioactivo = 1
             ORDER BY u.tbusuarionombrecompleto ASC'
        );

        return array_map(fn(array $fila): SuperAdmin => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    private function crearDesdeFila(array $fila): SuperAdmin
    {
        return new SuperAdmin(
            (int) $fila['tbusuarioid'],
            $fila['tbusuarioidentificaciontipo'],
            $fila['tbusuarioidentificacionnumero'],
            $fila['tbusuarionombrecompleto'],
            $fila['tbusuarioperfilimagen'],
            $fila['tbusuariocorreo'],
            $fila['tbusuariotelefono'],
            $fila['tbusuariocontrasena'],
            $fila['tbusuarioregistrofecha'] !== null ? new DateTime($fila['tbusuarioregistrofecha']) : null,
            (bool) $fila['tbusuarioactivo'],
            (int) $fila['tbsuperadminid'],
            (bool) $fila['tbsuperadminactivo']
        );
    }
}