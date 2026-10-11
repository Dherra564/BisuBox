<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Proveedor;
use Aplicacion\Nucleo\TipoIdentificacion;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;


class ProveedorRepositorio
{
    public const POR_PAGINA = 10;

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function buscarPorId(int $idProveedor, int $idVendedor): ?Proveedor
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproveedor WHERE tbproveedorid = ? AND tbproveedorvendedorid = ?'
        );
        $consulta->execute([$idProveedor, $idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    /** @return Proveedor[] */
    public function listar(int $idVendedor, string $busqueda, ?bool $activo, int $pagina): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $busqueda, $activo);

        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproveedor' . $condiciones
            . ' ORDER BY tbproveedornombre ASC, tbproveedorid ASC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): Proveedor => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    public function contar(int $idVendedor, string $busqueda = '', ?bool $activo = null): int
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $busqueda, $activo);

        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbproveedor' . $condiciones);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    
    public function existeIdentificacion(int $idVendedor, string $numero, ?int $excluirIdProveedor = null): bool
    {
        return $this->existe($idVendedor, 'tbproveedoridentificacionnumero', $numero, $excluirIdProveedor);
    }

    public function existeNombre(int $idVendedor, string $nombre, ?int $excluirIdProveedor = null): bool
    {
        return $this->existe($idVendedor, 'tbproveedornombre', $nombre, $excluirIdProveedor);
    }

    public function existeTelefono(int $idVendedor, string $telefono, ?int $excluirIdProveedor = null): bool
    {
        return $this->existe($idVendedor, 'tbproveedortelefono', $telefono, $excluirIdProveedor);
    }

    public function existeCorreo(int $idVendedor, string $correo, ?int $excluirIdProveedor = null): bool
    {
        return $this->existe($idVendedor, 'tbproveedorcorreo', $correo, $excluirIdProveedor);
    }

    public function insertar(Proveedor $proveedor): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idProveedor = BaseDatos::generarId('tbproveedor', 'tbproveedorid');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbproveedor (tbproveedorid, tbproveedorvendedorid, tbproveedoridentificaciontipo,
                    tbproveedoridentificacionnumero, tbproveedornombre, tbproveedortelefono, tbproveedorcorreo,
                    tbproveedorregistrofecha, tbproveedoractivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idProveedor,
                $proveedor->getIdVendedor(),
                $proveedor->getTipoIdentificacion(),
                $proveedor->getNumeroIdentificacion(),
                UsuarioRepositorio::limpiarEspacios((string) $proveedor->getNombre()),
                $proveedor->getTelefono(),
                $proveedor->getCorreo(),
                $proveedor->getFechaRegistro()->format('Y-m-d H:i:s'),
                $proveedor->getEstado() ? 1 : 0,
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

        $proveedor->setIdProveedor($idProveedor);

        return $idProveedor;
    }

    public function actualizar(Proveedor $proveedor): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproveedor SET tbproveedoridentificaciontipo = ?, tbproveedoridentificacionnumero = ?,
                tbproveedornombre = ?, tbproveedortelefono = ?, tbproveedorcorreo = ?
             WHERE tbproveedorid = ? AND tbproveedorvendedorid = ?'
        );
        $consulta->execute([
            $proveedor->getTipoIdentificacion(),
            $proveedor->getNumeroIdentificacion(),
            UsuarioRepositorio::limpiarEspacios((string) $proveedor->getNombre()),
            $proveedor->getTelefono(),
            $proveedor->getCorreo(),
            $proveedor->getIdProveedor(),
            $proveedor->getIdVendedor(),
        ]);
    }

    public function cambiarEstado(Proveedor $proveedor, bool $activo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproveedor SET tbproveedoractivo = ? WHERE tbproveedorid = ? AND tbproveedorvendedorid = ?'
        );
        $consulta->execute([$activo ? 1 : 0, $proveedor->getIdProveedor(), $proveedor->getIdVendedor()]);

        $proveedor->setEstado($activo);
    }

    private function existe(int $idVendedor, string $columna, string $valor, ?int $excluirIdProveedor): bool
    {
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*) FROM tbproveedor
             WHERE tbproveedorvendedorid = ? AND {$columna} = ? AND tbproveedorid <> ?"
        );
        $consulta->execute([$idVendedor, trim($valor), $excluirIdProveedor ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    private function construirFiltros(int $idVendedor, string $busqueda, ?bool $activo): array
    {
        $condiciones = ['tbproveedorvendedorid = ?'];
        $valores = [$idVendedor];

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            $patronNumeros = '%' . addcslashes(TipoIdentificacion::limpiar($busqueda), '%_\\') . '%';
            $condiciones[] = '(tbproveedornombre LIKE ? OR tbproveedorcorreo LIKE ?
                OR tbproveedoridentificacionnumero LIKE ? OR tbproveedortelefono LIKE ?)';
            array_push($valores, $patron, $patron, $patronNumeros, $patronNumeros);
        }

        if ($activo !== null) {
            $condiciones[] = 'tbproveedoractivo = ?';
            $valores[] = $activo ? 1 : 0;
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    private function crearDesdeFila(array $fila): Proveedor
    {
        return new Proveedor(
            (int) $fila['tbproveedorid'],
            (int) $fila['tbproveedorvendedorid'],
            $fila['tbproveedoridentificaciontipo'],
            $fila['tbproveedoridentificacionnumero'],
            $fila['tbproveedornombre'],
            $fila['tbproveedortelefono'],
            $fila['tbproveedorcorreo'],
            $fila['tbproveedorregistrofecha'] !== null ? new DateTime($fila['tbproveedorregistrofecha']) : null,
            (bool) $fila['tbproveedoractivo']
        );
    }
}