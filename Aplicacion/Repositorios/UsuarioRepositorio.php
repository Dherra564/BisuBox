<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Usuario;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\TipoIdentificacion;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;


class UsuarioRepositorio
{
    public const POR_PAGINA = 10;

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

    /**
     * @return Usuario[]
     */
    public function listar(string $busqueda, ?string $rol, ?bool $activo, int $pagina): array
    {
        [$condiciones, $valores] = $this->construirFiltros($busqueda, $rol, $activo);

        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbusuario'
            . $condiciones
            . ' ORDER BY tbusuarionombrecompleto ASC, tbusuarioid ASC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): Usuario => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    
    public function contar(string $busqueda = '', ?string $rol = null, ?bool $activo = null): int
    {
        [$condiciones, $valores] = $this->construirFiltros($busqueda, $rol, $activo);

        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbusuario' . $condiciones);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Administradores activos, para la pantalla de Ayuda.
     * @return Usuario[]
     */
    public function listarAdministradoresActivos(): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbusuario
             WHERE tbusuariorol = ? AND tbusuarioactivo = 1
             ORDER BY tbusuarionombrecompleto ASC'
        );
        $consulta->execute([Rol::ADMINISTRADOR]);

        return array_map(fn(array $fila): Usuario => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    
    public function contarAdministradoresActivos(?int $excluirIdUsuario = null): int
    {
        return $this->contar('', Rol::ADMINISTRADOR, true)
            - ($excluirIdUsuario !== null && $this->esAdministradorActivo($excluirIdUsuario) ? 1 : 0);
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

    public function existeTelefono(string $telefono, ?int $excluirIdUsuario = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbusuario WHERE tbusuariotelefono = ? AND tbusuarioid <> ?'
        );
        $consulta->execute([$telefono, $excluirIdUsuario ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(Usuario $usuario): int
    {
        BaseDatos::iniciarTransaccion();

        try {
            $idUsuario = $this->generarId();
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
                trim((string) $usuario->getNumeroIdentificacion()),
                self::limpiarEspacios((string) $usuario->getNombreCompleto()),
                $usuario->getFotoPerfil(),
                $usuario->getCorreoUsuario() !== null ? self::normalizarCorreo($usuario->getCorreoUsuario()) : null,
                $usuario->getNumeroTelefonico(),
                $contrasena,
                $usuario->getRol(),
                $usuario->getFechaRegistro()->format('Y-m-d H:i:s'),
                $usuario->getEstado() ? 1 : 0,
            ]);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }

        $usuario->setIdUsuario($idUsuario);
        $usuario->setContrasena($contrasena);

        return $idUsuario;
    }

   
    public function actualizar(Usuario $usuario, ?string $contrasenaNueva = null, bool $cerrarSesiones = false): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare(
                'UPDATE tbusuario SET tbusuarioidentificaciontipo = ?, tbusuarioidentificacionnumero = ?,
                    tbusuarionombrecompleto = ?, tbusuariotelefono = ?,
                    tbusuarioperfilimagen = ?, tbusuariocorreo = ?, tbusuariorol = ?
                 WHERE tbusuarioid = ?'
            );
            $consulta->execute([
                $usuario->getTipoIdentificacion(),
                trim((string) $usuario->getNumeroIdentificacion()),
                self::limpiarEspacios((string) $usuario->getNombreCompleto()),
                $usuario->getNumeroTelefonico(),
                $usuario->getFotoPerfil(),
                $usuario->getCorreoUsuario() !== null ? self::normalizarCorreo($usuario->getCorreoUsuario()) : null,
                $usuario->getRol(),
                $usuario->getIdUsuario(),
            ]);

            if ($contrasenaNueva !== null) {
                $this->cambiarContrasena((int) $usuario->getIdUsuario(), $contrasenaNueva);
            }
            if ($cerrarSesiones) {
                $this->cerrarSesiones((int) $usuario->getIdUsuario());
            }

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    public function cambiarContrasena(int $idUsuario, string $contrasenaNueva): bool
    {
        $consulta = $this->conexion->prepare('UPDATE tbusuario SET tbusuariocontrasena = ? WHERE tbusuarioid = ?');
        return $consulta->execute([self::encriptar($contrasenaNueva), $idUsuario]);
    }

    // Al desactivar se cierran sus sesiones abiertas, asi sale del sistema de inmediato
    public function cambiarEstado(Usuario $usuario, bool $activo): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare('UPDATE tbusuario SET tbusuarioactivo = ? WHERE tbusuarioid = ?');
            $consulta->execute([$activo ? 1 : 0, $usuario->getIdUsuario()]);

            if (!$activo) {
                $this->cerrarSesiones((int) $usuario->getIdUsuario());
            }

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }

        $usuario->setEstado($activo);
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

    private function esAdministradorActivo(int $idUsuario): bool
    {
        $usuario = $this->buscarPorId($idUsuario);
        return $usuario !== null && $usuario->getEstado() && $usuario->esAdministrador();
    }

    private function cerrarSesiones(int $idUsuario): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbsesion SET tbsesionactivo = 0, tbsesionfechacierre = ?
             WHERE tbsesionusuarioid = ? AND tbsesionactivo = 1'
        );
        $consulta->execute([(new DateTime())->format('Y-m-d H:i:s'), $idUsuario]);
    }

    private function construirFiltros(string $busqueda, ?string $rol, ?bool $activo): array
    {
        $condiciones = [];
        $valores = [];

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            $patronIdentificacion = '%' . addcslashes(TipoIdentificacion::limpiar($busqueda), '%_\\') . '%';
            $condiciones[] = '(tbusuarionombrecompleto LIKE ? OR tbusuariocorreo LIKE ? OR tbusuarioidentificacionnumero LIKE ?)';
            array_push($valores, $patron, $patron, $patronIdentificacion);
        }

        if ($rol !== null) {
            $condiciones[] = 'tbusuariorol = ?';
            $valores[] = $rol;
        }

        if ($activo !== null) {
            $condiciones[] = 'tbusuarioactivo = ?';
            $valores[] = $activo ? 1 : 0;
        }

        $sql = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        return [$sql, $valores];
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
            (bool) $fila['tbusuarioactivo'],
            $fila['tbusuariorol']
        );
    }
}