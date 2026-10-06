<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\TipoIdentificacion;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

/**
 * Cada vendedor ocupa una fila en tbusuario y otra en tbvendedor. Se leen juntas
 * y se guardan juntas, en una sola transacción.
 */
class VendedorRepositorio
{
    public const POR_PAGINA = 10;

    private PDO $conexion;
    private UsuarioRepositorio $usuarioRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->usuarioRepositorio = new UsuarioRepositorio();
    }

    private function generarId(): int
    {
        return BaseDatos::generarId('tbvendedor', 'tbvendedorid');
    }

    public function buscarPorId(int $idVendedor): ?Vendedor
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.*, v.tbvendedorid, v.tbvendedorregistrofecha, v.tbvendedoractivo
             FROM tbvendedor v
             INNER JOIN tbusuario u ON u.tbusuarioid = v.tbusuarioid
             WHERE v.tbvendedorid = ?'
        );
        $consulta->execute([$idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    // Devuelve el vendedor de ese usuario, o null si el usuario no es vendedor (lo usa el inicio de sesión)
    public function buscarPorIdUsuario(int $idUsuario): ?Vendedor
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.*, v.tbvendedorid, v.tbvendedorregistrofecha, v.tbvendedoractivo
             FROM tbvendedor v
             INNER JOIN tbusuario u ON u.tbusuarioid = v.tbusuarioid
             WHERE v.tbusuarioid = ?'
        );
        $consulta->execute([$idUsuario]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    /**
     * Una página de vendedores ordenada por nombre.
     * $busqueda filtra por nombre, correo o identificación; $activo en null trae todos.
     */
    public function listar(string $busqueda, ?bool $activo, int $pagina): array
    {
        [$condiciones, $valores] = $this->construirFiltros($busqueda, $activo);

        // LIMIT y OFFSET van escritos en el SQL porque son enteros calculados aquí, nunca texto del usuario
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;
        $consulta = $this->conexion->prepare(
            'SELECT u.*, v.tbvendedorid, v.tbvendedorregistrofecha, v.tbvendedoractivo
             FROM tbvendedor v
             INNER JOIN tbusuario u ON u.tbusuarioid = v.tbusuarioid'
            . $condiciones
            . ' ORDER BY u.tbusuarionombrecompleto ASC, v.tbvendedorid ASC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): Vendedor => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    // Cantidad de vendedores con los mismos filtros de listar(), para la paginación
    public function contar(string $busqueda, ?bool $activo): int
    {
        [$condiciones, $valores] = $this->construirFiltros($busqueda, $activo);

        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*)
             FROM tbvendedor v
             INNER JOIN tbusuario u ON u.tbusuarioid = v.tbusuarioid'
            . $condiciones
        );
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Guarda tbusuario y tbvendedor en una sola transacción y devuelve el id del vendedor.
     * Si cualquiera de los dos falla, no se guarda ninguno.
     */
    public function insertar(Vendedor $vendedor): int
    {
        BaseDatos::iniciarTransaccion();

        try {
            $idUsuario = $this->usuarioRepositorio->insertar($vendedor);
            $idVendedor = $this->generarId();

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbvendedor (tbvendedorid, tbusuarioid, tbvendedorregistrofecha, tbvendedoractivo)
                 VALUES (?, ?, ?, ?)'
            );
            $consulta->execute([
                $idVendedor,
                $idUsuario,
                $vendedor->getRegistroFechaVendedor()->format('Y-m-d H:i:s'),
                $vendedor->getEstadoVendedor() ? 1 : 0,
            ]);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }

        $vendedor->setIdVendedor($idVendedor);

        return $idVendedor;
    }

    /**
     * Actualiza los datos personales (tbusuario, incluido el teléfono).
     * Si llega $contrasenaNueva (en texto), también se restablece la contraseña, en la misma transacción.
     */
    public function actualizar(Vendedor $vendedor, ?string $contrasenaNueva = null): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $this->usuarioRepositorio->actualizar($vendedor);

            if ($contrasenaNueva !== null) {
                $this->usuarioRepositorio->cambiarContrasena($vendedor->getIdUsuario(), $contrasenaNueva);
            }

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    // Activa o desactiva tbusuario y tbvendedor juntos. Al desactivar se cierran sus sesiones abiertas.
    public function cambiarEstado(Vendedor $vendedor, bool $activo): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $this->usuarioRepositorio->cambiarEstado($vendedor->getIdUsuario(), $activo);

            $consulta = $this->conexion->prepare(
                'UPDATE tbvendedor SET tbvendedoractivo = ? WHERE tbvendedorid = ?'
            );
            $consulta->execute([$activo ? 1 : 0, $vendedor->getIdVendedor()]);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }

        $vendedor->setEstado($activo);
        $vendedor->setEstadoVendedor($activo);
    }

    // Arma el WHERE de listar() y contar(), para que las dos usen exactamente los mismos filtros
    private function construirFiltros(string $busqueda, ?bool $activo): array
    {
        $condiciones = [];
        $valores = [];

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            // % y _ se escapan para que se busquen como texto y no como comodines
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            // La identificación se guarda sin guiones: "1-0234" también encuentra 102340567
            $patronIdentificacion = '%' . addcslashes(TipoIdentificacion::limpiar($busqueda), '%_\\') . '%';
            $condiciones[] = '(u.tbusuarionombrecompleto LIKE ? OR u.tbusuariocorreo LIKE ? OR u.tbusuarioidentificacionnumero LIKE ?)';
            array_push($valores, $patron, $patron, $patronIdentificacion);
        }

        if ($activo !== null) {
            $condiciones[] = 'v.tbvendedoractivo = ?';
            $valores[] = $activo ? 1 : 0;
        }

        $sql = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        return [$sql, $valores];
    }

    // Convierte una fila de tbusuario + tbvendedor en un objeto Vendedor
    private function crearDesdeFila(array $fila): Vendedor
    {
        return new Vendedor(
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
            (int) $fila['tbvendedorid'],
            $fila['tbvendedorregistrofecha'] !== null ? new DateTime($fila['tbvendedorregistrofecha']) : null,
            (bool) $fila['tbvendedoractivo']
        );
    }
}