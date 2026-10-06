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

    // Cantidad de usuarios registrados (activos e inactivos)
    public function contar(): int
    {
        return (int) $this->conexion->query('SELECT COUNT(*) FROM tbusuario')->fetchColumn();
    }

    // true si el correo ya lo tiene otro usuario (activo o no). Al editar, se excluye al propio usuario.
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

    /**
     * Guarda un usuario nuevo y devuelve su id.
     * La contrasena puede venir en texto: aqui se encripta antes de guardarla.
     * Si ya hay una transaccion abierta (por ejemplo, la de VendedorRepositorio), se usa esa.
     */
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

        // El objeto queda con su id y con la contrasena ya encriptada, nunca en texto
        $usuario->setIdUsuario($idUsuario);
        $usuario->setContrasena($contrasena);

        return $idUsuario;
    }

    // Actualiza los datos personales. La contrasena y la fecha de registro no se tocan aqui.
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

    // Recibe la contrasena nueva en texto y la guarda encriptada
    public function cambiarContrasena(int $idUsuario, string $contrasenaNueva): bool
    {
        $consulta = $this->conexion->prepare('UPDATE tbusuario SET tbusuariocontrasena = ? WHERE tbusuarioid = ?');
        return $consulta->execute([self::encriptar($contrasenaNueva), $idUsuario]);
    }

    // Baja logica: nunca se borra un usuario. Al desactivarlo, tambien se cierran sus sesiones abiertas.
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

    /**
     * Para el inicio de sesion: devuelve el usuario si el correo y la contrasena son correctos, o null.
     * No dice cual de los dos fallo. Revisar getEstado() despues, para avisar si la cuenta esta desactivada.
     */
    public function verificarCredenciales(string $correo, string $contrasena): ?Usuario
    {
        $usuario = $this->buscarPorCorreo($correo);
        if ($usuario === null || $usuario->getContrasena() === null) {
            return null;
        }

        return password_verify($contrasena, $usuario->getContrasena()) ? $usuario : null;
    }

    // "  Ana   Mora " -> "Ana Mora"
    public static function limpiarEspacios(string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    // Correo siempre en minuscula y sin espacios, para comparar igual al guardar y al buscar
    public static function normalizarCorreo(string $correo): string
    {
        return mb_strtolower(trim($correo), 'UTF-8');
    }

    // Encripta solo si viene en texto; si ya es un hash, lo deja igual
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