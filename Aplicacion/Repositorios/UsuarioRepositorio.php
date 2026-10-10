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

    public function buscarPorId(int $idUsuario): ?Usuario
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbusuario WHERE tbusuarioid = ?');
        $consulta->execute([$idUsuario]);
        $fila = $consulta->fetch();

        return $fila ? self::crearDesdeFila($fila) : null;
    }

    public function buscarPorCorreo(string $correo): ?Usuario
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbusuario WHERE tbusuariocorreo = ?');
        $consulta->execute([self::normalizarCorreo($correo)]);
        $fila = $consulta->fetch();

        return $fila ? self::crearDesdeFila($fila) : null;
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

    // Recibe el teléfono limpio: 8 dígitos
    public function existeTelefono(string $telefono, ?int $excluirIdUsuario = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbusuario WHERE tbusuariotelefono = ? AND tbusuarioid <> ?'
        );
        $consulta->execute([$telefono, $excluirIdUsuario ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    // Si ya hay una transacción abierta (registro de vendedor o cliente), la confirma quien la abrió
    public function insertar(Usuario $usuario): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idUsuario = BaseDatos::generarId('tbusuario', 'tbusuarioid');
            $contrasena = self::encriptar($usuario->getContrasena());

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbusuario (tbusuarioid, tbusuarioidentificaciontipo, tbusuarioidentificacionnumero,
                    tbusuarionombrecompleto, tbusuarioperfilimagen, tbusuariocorreo, tbusuariotelefono,
                    tbusuariocontrasena, tbusuariorol, tbusuarioregistrofecha, tbusuarioactivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idUsuario,
                $usuario->getTipoIdentificacion(),
                $usuario->getNumeroIdentificacion() !== null ? trim($usuario->getNumeroIdentificacion()) : null,
                self::limpiarEspacios((string) $usuario->getNombreCompleto()),
                $usuario->getFotoPerfil(),
                self::normalizarCorreo((string) $usuario->getCorreoUsuario()),
                $usuario->getNumeroTelefonico(),
                $contrasena,
                $usuario->getRol(),
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

    public function actualizar(Usuario $usuario): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbusuario SET tbusuarioidentificaciontipo = ?, tbusuarioidentificacionnumero = ?,
                tbusuarionombrecompleto = ?, tbusuarioperfilimagen = ?, tbusuariocorreo = ?, tbusuariotelefono = ?
             WHERE tbusuarioid = ?'
        );
        $consulta->execute([
            $usuario->getTipoIdentificacion(),
            $usuario->getNumeroIdentificacion() !== null ? trim($usuario->getNumeroIdentificacion()) : null,
            self::limpiarEspacios((string) $usuario->getNombreCompleto()),
            $usuario->getFotoPerfil(),
            self::normalizarCorreo((string) $usuario->getCorreoUsuario()),
            $usuario->getNumeroTelefonico(),
            $usuario->getIdUsuario(),
        ]);
    }

    public function cambiarContrasena(int $idUsuario, string $contrasenaNueva): bool
    {
        $consulta = $this->conexion->prepare('UPDATE tbusuario SET tbusuariocontrasena = ? WHERE tbusuarioid = ?');
        return $consulta->execute([self::encriptar($contrasenaNueva), $idUsuario]);
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
        return trim((string) preg_replace('/\s+/u', ' ', $texto));
    }

    public static function normalizarCorreo(string $correo): string
    {
        return mb_strtolower(trim($correo), 'UTF-8');
    }

    private static function crearDesdeFila(array $fila): Usuario
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
            (bool) $fila['tbusuarioactivo'],
            $fila['tbusuariorol']
        );
    }

    // Si ya viene cifrada se guarda igual
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
}