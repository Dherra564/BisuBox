<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Usuario;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class UsuarioRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    private function generarId(): int
    {
        return BaseDatos::generarId('tbusuario', 'tbusuarioid');
    }

    public function buscarPorId(int $idUsuario): ?Usuario
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbusuario WHERE tbusuarioid = ?');
        $consulta->execute([$idUsuario]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function buscarPorCorreo(string $correo): ?Usuario
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbusuario WHERE tbusuariocorreo = ?');
        $consulta->execute([self::normalizarCorreo($correo)]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function contar(): int
    {
        return (int) $this->conexion->query('SELECT COUNT(*) FROM tbusuario')->fetchColumn();
    }

    public function existeCorreo(string $correo, ?int $excluirIdUsuario = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbusuario WHERE tbusuariocorreo = ? AND tbusuarioid <> ?'
        );
        $consulta->execute([self::normalizarCorreo($correo), $excluirIdUsuario ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function existeIdentificacion(string $identificacion, ?int $excluirIdUsuario = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbusuario WHERE tbusuarioidentificacionnumero = ? AND tbusuarioid <> ?'
        );
        $consulta->execute([trim($identificacion), $excluirIdUsuario ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(Usuario $usuario): int
    {
        $transaccionPropia = !$this->conexion->inTransaction();
        if ($transaccionPropia) {
            BaseDatos::iniciarTransaccion();
        }

        try {
            $idUsuario = $this->generarId();
            $contrasena = self::encriptar($usuario->getContrasena());

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbusuario (tbusuarioid, tbusuarioidentificaciontipo, tbusuarioidentificacionnumero,
                    tbusuarionombrecompleto, tbusuarioperfilimagen, tbusuariocorreo, tbusuariotelefono,
                    tbusuariocontrasena, tbusuarioregistrofecha, tbusuarioactivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idUsuario,
                $usuario->getTipoIdentificacion(),
                trim((string) $usuario->getNumeroIdentificacion()),
                self::limpiarEspacios((string) $usuario->getNombreCompleto()),
                $usuario->getFotoPerfil(),
                $usuario->getCorreoUsuario() !== null ? self::normalizarCorreo($usuario->getCorreoUsuario()) : null,
                $usuario->getNumeroTelefonico(),
                $contrasena,
                $usuario->getFechaRegistro()->format('Y-m-d H:i:s'),
                $usuario->getEstado() ? 1 : 0,
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

        $usuario->setIdUsuario($idUsuario);
        $usuario->setContrasena($contrasena);

        return $idUsuario;
    }

    public function actualizar(Usuario $usuario): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbusuario SET tbusuarioidentificaciontipo = ?, tbusuarioidentificacionnumero = ?,
                tbusuarionombrecompleto = ?, tbusuariotelefono = ?,
                tbusuarioperfilimagen = ?, tbusuariocorreo = ?
             WHERE tbusuarioid = ?'
        );

        return $consulta->execute([
            $usuario->getTipoIdentificacion(),
            trim((string) $usuario->getNumeroIdentificacion()),
            self::limpiarEspacios((string) $usuario->getNombreCompleto()),
            $usuario->getNumeroTelefonico(),
            $usuario->getFotoPerfil(),
            $usuario->getCorreoUsuario() !== null ? self::normalizarCorreo($usuario->getCorreoUsuario()) : null,
            $usuario->getIdUsuario(),
        ]);
    }

    public function cambiarContrasena(int $idUsuario, string $contrasenaNueva): bool
    {
        $consulta = $this->conexion->prepare('UPDATE tbusuario SET tbusuariocontrasena = ? WHERE tbusuarioid = ?');
        return $consulta->execute([self::encriptar($contrasenaNueva), $idUsuario]);
    }

    public function cambiarEstado(int $idUsuario, bool $activo): bool
    {
        $consulta = $this->conexion->prepare('UPDATE tbusuario SET tbusuarioactivo = ? WHERE tbusuarioid = ?');
        $resultado = $consulta->execute([$activo ? 1 : 0, $idUsuario]);

        if (!$activo) {
            $cierre = $this->conexion->prepare(
                'UPDATE tbsesion SET tbsesionactivo = 0, tbsesionfechacierre = NOW()
                 WHERE tbsesionusuarioid = ? AND tbsesionactivo = 1'
            );
            $cierre->execute([$idUsuario]);
        }

        return $resultado;
    }

    public function verificarCredenciales(string $correo, string $contrasena): ?Usuario
    {
        $usuario = $this->buscarPorCorreo($correo);
        if ($usuario === null || $usuario->getContrasena() === null) {
            return null;
        }

        return password_verify($contrasena, $usuario->getContrasena()) ? $usuario : null;
    }

    public static function limpiarEspacios(string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    public static function normalizarCorreo(string $correo): string
    {
        return mb_strtolower(trim($correo), 'UTF-8');
    }

    private static function encriptar(?string $contrasena): ?string
    {
        if ($contrasena === null || $contrasena === '') {
            return null;
        }
        if (password_get_info($contrasena)['algo'] !== null) {
            return $contrasena;
        }
        return password_hash($contrasena, PASSWORD_DEFAULT);
    }

    private function crearDesdeFila(array $fila): Usuario
    {
        return new Usuario(
            (int) $fila['tbusuarioid'],
            $fila['tbusuarioidentificaciontipo'],
            $fila['tbusuarioidentificacionnumero'],
            $fila['tbusuarionombrecompleto'],
            $fila['tbusuarioperfilimagen'],
            $fila['tbusuariocorreo'],
            $fila['tbusuariotelefono'],
            $fila['tbusuariocontrasena'],
            $fila['tbusuarioregistrofecha'] !== null ? new DateTime($fila['tbusuarioregistrofecha']) : null,
            (bool) $fila['tbusuarioactivo']
        );
    }
}