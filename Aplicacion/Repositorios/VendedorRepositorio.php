<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\TipoIdentificacion;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

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

    public function listar(string $busqueda, ?bool $activo, int $pagina): array
    {
        [$condiciones, $valores] = $this->construirFiltros($busqueda, $activo);

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

    private function construirFiltros(string $busqueda, ?bool $activo): array
    {
        $condiciones = [];
        $valores = [];

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
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