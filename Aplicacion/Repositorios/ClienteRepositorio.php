<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Cliente;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class ClienteRepositorio
{
    private PDO $conexion;
    private UsuarioRepositorio $usuarioRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->usuarioRepositorio = new UsuarioRepositorio();
    }

    public function buscarPorIdUsuario(int $idUsuario): ?Cliente
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.*, c.* FROM tbcliente c
             INNER JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid
             WHERE c.tbclienteusuarioid = ?'
        );
        $consulta->execute([$idUsuario]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function insertar(Cliente $cliente): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idUsuario = $this->usuarioRepositorio->insertar($cliente);
            $idCliente = BaseDatos::generarId('tbcliente', 'tbclienteid');

            $consulta = $this->conexion->prepare('INSERT INTO tbcliente (tbclienteid, tbclienteusuarioid) VALUES (?, ?)');
            $consulta->execute([$idCliente, $idUsuario]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $cliente->setIdCliente($idCliente);

        return $idCliente;
    }

    private function crearDesdeFila(array $fila): Cliente
    {
        return new Cliente(
            (int) $fila['tbusuarioid'],
            $fila['tbusuarionombrecompleto'],
            $fila['tbusuarioperfilimagen'],
            $fila['tbusuariocorreo'],
            $fila['tbusuariotelefono'],
            $fila['tbusuariocontrasena'],
            $fila['tbusuarioregistrofecha'] !== null ? new DateTime($fila['tbusuarioregistrofecha']) : null,
            (bool) $fila['tbusuarioactivo'],
            (int) $fila['tbclienteid']
        );
    }
}