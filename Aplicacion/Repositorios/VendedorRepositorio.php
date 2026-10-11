<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Vendedor;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class VendedorRepositorio
{
    private PDO $conexion;
    private UsuarioRepositorio $usuarioRepositorio;
    private VendedorContactoRepositorio $contactoRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->usuarioRepositorio = new UsuarioRepositorio();
        $this->contactoRepositorio = new VendedorContactoRepositorio();
    }

    public function buscarPorIdUsuario(int $idUsuario): ?Vendedor
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.*, v.* FROM tbvendedor v
             INNER JOIN tbusuario u ON u.tbusuarioid = v.tbvendedorusuarioid
             WHERE v.tbvendedorusuarioid = ?'
        );
        $consulta->execute([$idUsuario]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function buscarPorEnlace(string $enlace): ?Vendedor
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.*, v.* FROM tbvendedor v
             INNER JOIN tbusuario u ON u.tbusuarioid = v.tbvendedorusuarioid
             WHERE v.tbvendedortiendaenlace = ?'
        );
        $consulta->execute([self::normalizarEnlace($enlace)]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila) : null;
    }

    public function existeEnlace(string $enlace, ?int $excluirIdVendedor = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbvendedor WHERE tbvendedortiendaenlace = ? AND tbvendedorid <> ?'
        );
        $consulta->execute([self::normalizarEnlace($enlace), $excluirIdVendedor ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(Vendedor $vendedor): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idUsuario = $this->usuarioRepositorio->insertar($vendedor);
            $idVendedor = BaseDatos::generarId('tbvendedor', 'tbvendedorid');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbvendedor (tbvendedorid, tbvendedorusuarioid, tbvendedortiendanombre, tbvendedortiendaenlace,
                    tbvendedortiendadescripcion, tbvendedortiendalogo, tbvendedoractivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idVendedor,
                $idUsuario,
                UsuarioRepositorio::limpiarEspacios((string) $vendedor->getTiendaNombre()),
                self::normalizarEnlace((string) $vendedor->getTiendaEnlace()),
                self::descripcionOVacio($vendedor->getTiendaDescripcion()),
                $vendedor->getTiendaLogo(),
                $vendedor->getTiendaActiva() ? 1 : 0,
            ]);

            $this->contactoRepositorio->reemplazar($idVendedor, $vendedor->getContactos());

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $vendedor->setIdVendedor($idVendedor);

        return $idVendedor;
    }

    public function actualizarTienda(Vendedor $vendedor): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare(
                'UPDATE tbvendedor SET tbvendedortiendanombre = ?, tbvendedortiendaenlace = ?,
                    tbvendedortiendadescripcion = ?, tbvendedortiendalogo = ?, tbvendedoractivo = ?
                 WHERE tbvendedorid = ?'
            );
            $consulta->execute([
                UsuarioRepositorio::limpiarEspacios((string) $vendedor->getTiendaNombre()),
                self::normalizarEnlace((string) $vendedor->getTiendaEnlace()),
                self::descripcionOVacio($vendedor->getTiendaDescripcion()),
                $vendedor->getTiendaLogo(),
                $vendedor->getTiendaActiva() ? 1 : 0,
                $vendedor->getIdVendedor(),
            ]);

            $this->contactoRepositorio->reemplazar((int) $vendedor->getIdVendedor(), $vendedor->getContactos());

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

    public static function normalizarEnlace(string $enlace): string
    {
        return mb_strtolower(trim($enlace), 'UTF-8');
    }

    public function generarEnlace(string $nombreTienda): string
    {
        $base = self::convertirEnEnlace($nombreTienda);
        $enlace = $base;
        $numero = 2;
        while ($this->existeEnlace($enlace)) {
            $enlace = $base . '-' . $numero;
            $numero++;
        }
        return $enlace;
    }

    
    public static function convertirEnEnlace(string $nombreTienda): string
    {
        $texto = strtr(mb_strtolower(trim($nombreTienda), 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ç' => 'c',
            '&' => ' y ', "'" => '',
        ]);
        $texto = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
        $texto = rtrim(substr($texto, 0, 50), '-');

        return strlen($texto) >= 3 ? $texto : trim('tienda-' . $texto, '-');
    }
    
    private static function descripcionOVacio(?string $descripcion): ?string
    {
        $descripcion = trim((string) $descripcion);
        return $descripcion === '' ? null : $descripcion;
    }

    private function crearDesdeFila(array $fila): Vendedor
    {
        $idVendedor = (int) $fila['tbvendedorid'];

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
            $idVendedor,
            $fila['tbvendedortiendanombre'],
            $fila['tbvendedortiendaenlace'],
            $fila['tbvendedortiendadescripcion'],
            $fila['tbvendedortiendalogo'],
            (bool) $fila['tbvendedoractivo'],
            $this->contactoRepositorio->listarPorVendedor($idVendedor)
        );
    }
}