<?php
namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Producto;
use Aplicacion\Modelos\ProductoComponente;
use Aplicacion\Modelos\ProductoExtra;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class ProductoRepositorio
{
    public const POR_PAGINA = 24;

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    
    public function buscarPorId(int $idProducto, int $idVendedor): ?Producto
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproducto WHERE tbproductoid = ? AND tbproductovendedorid = ?'
        );
        $consulta->execute([$idProducto, $idVendedor]);
        $fila = $consulta->fetch();
        if (! $fila) {
            return null;
        }

        $consulta = $this->conexion->prepare(
            'SELECT d.*, co.tbcomponentenombre, co.tbcomponenteunidad, t.tbtipocomponentenombre
             FROM tbproductocomponente d
             INNER JOIN tbcomponente co ON co.tbcomponenteid = d.tbproductocomponentecomponenteid
             INNER JOIN tbtipocomponente t ON t.tbtipocomponenteid = co.tbcomponentetipocomponenteid
             WHERE d.tbproductocomponenteproductoid = ?
             ORDER BY d.tbproductocomponenteid ASC'
        );
        $consulta->execute([$idProducto]);
        $componentes = array_map(fn(array $linea): ProductoComponente => new ProductoComponente(
            (int) $linea['tbproductocomponenteid'],
            (int) $linea['tbproductocomponenteproductoid'],
            (int) $linea['tbproductocomponentecomponenteid'],
            (float) $linea['tbproductocomponentecantidad'],
            (float) $linea['tbproductocomponentecostounitario'],
            (float) $linea['tbproductocomponentesubtotal'],
            (string) $linea['tbcomponentenombre'],
            (string) $linea['tbtipocomponentenombre'],
            (string) $linea['tbcomponenteunidad']
        ), $consulta->fetchAll());

        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproductoextra WHERE tbproductoextraproductoid = ? ORDER BY tbproductoextraid ASC'
        );
        $consulta->execute([$idProducto]);
        $extras = array_map(fn(array $extra): ProductoExtra => new ProductoExtra(
            (int) $extra['tbproductoextraid'],
            (int) $extra['tbproductoextraproductoid'],
            (string) $extra['tbproductoextraconcepto'],
            (float) $extra['tbproductoextramonto']
        ), $consulta->fetchAll());

        return $this->crearDesdeFila($fila, $componentes, $extras);
    }

    /** @return Producto[]*/
    public function listar(int $idVendedor, array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $filtros);
        $desplazamiento          = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproducto' . $condiciones . '
             ORDER BY tbproductonombre ASC, tbproductoid ASC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): Producto => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    public function contar(int $idVendedor, array $filtros = []): int
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $filtros);
        $consulta                = $this->conexion->prepare('SELECT COUNT(*) FROM tbproducto' . $condiciones);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    /** @return array{unidades: int, valor: float, agotados: int}*/
    public function resumen(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT COALESCE(SUM(tbproductoexistencia), 0) AS unidades,
                COALESCE(SUM(tbproductoexistencia * tbproductoprecioventa), 0) AS valor,
                COALESCE(SUM(tbproductoexistencia <= 0), 0) AS agotados
             FROM tbproducto WHERE tbproductovendedorid = ? AND tbproductoactivo = 1'
        );
        $consulta->execute([$idVendedor]);
        $fila = $consulta->fetch();

        return ['unidades' => (int) $fila['unidades'], 'valor' => (float) $fila['valor'], 'agotados' => (int) $fila['agotados']];
    }

    public function ultimaTarifaHora(int $idVendedor): ?float
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbproductotarifahora FROM tbproducto WHERE tbproductovendedorid = ? ORDER BY tbproductoid DESC LIMIT 1'
        );
        $consulta->execute([$idVendedor]);
        $tarifa = $consulta->fetchColumn();

        return $tarifa !== false && $tarifa !== null ? (float) $tarifa : null;
    }

    public function existeNombre(int $idVendedor, string $nombre, ?int $excluirIdProducto = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbproducto WHERE tbproductovendedorid = ? AND tbproductonombre = ? AND tbproductoid <> ?'
        );
        $consulta->execute([$idVendedor, trim($nombre), $excluirIdProducto ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(Producto $producto): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idProducto = BaseDatos::generarId('tbproducto', 'tbproductoid');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbproducto (tbproductoid, tbproductovendedorid, tbproductonombre, tbproductodescripcion, tbproductofoto,
                    tbproductominutos, tbproductotarifahora, tbproductocostocomponentes, tbproductocostomanoobra, tbproductocostoextras,
                    tbproductocostototal, tbproductoprecioventa, tbproductoexistencia, tbproductoregistrofecha, tbproductoactivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idProducto,
                $producto->getIdVendedor(),
                $producto->getNombre(),
                $producto->getDescripcion(),
                $producto->getFoto(),
                $producto->getMinutos(),
                $producto->getTarifaHora(),
                $producto->getCostoComponentes(),
                $producto->getCostoManoObra(),
                $producto->getCostoExtras(),
                $producto->getCostoTotal(),
                $producto->getPrecioVenta(),
                0,
                (new DateTime())->format('Y-m-d H:i:s'),
                1,
            ]);

            $insertarComponente = $this->conexion->prepare(
                'INSERT INTO tbproductocomponente (tbproductocomponenteid, tbproductocomponenteproductoid, tbproductocomponentecomponenteid,
                    tbproductocomponentecantidad, tbproductocomponentecostounitario, tbproductocomponentesubtotal)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($producto->getComponentes() as $linea) {
                $idLinea = BaseDatos::generarId('tbproductocomponente', 'tbproductocomponenteid');
                $insertarComponente->execute([
                    $idLinea,
                    $idProducto,
                    $linea->getIdComponente(),
                    $linea->getCantidad(),
                    $linea->getCostoUnitario(),
                    $linea->getSubtotal(),
                ]);
                $linea->setIdProductoComponente($idLinea);
                $linea->setIdProducto($idProducto);
            }

            $insertarExtra = $this->conexion->prepare(
                'INSERT INTO tbproductoextra (tbproductoextraid, tbproductoextraproductoid, tbproductoextraconcepto, tbproductoextramonto)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($producto->getExtras() as $extra) {
                $idExtra = BaseDatos::generarId('tbproductoextra', 'tbproductoextraid');
                $insertarExtra->execute([$idExtra, $idProducto, $extra->getConcepto(), $extra->getMonto()]);
                $extra->setIdProductoExtra($idExtra);
                $extra->setIdProducto($idProducto);
            }

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $producto->setIdProducto($idProducto);

        return $idProducto;
    }

    public function actualizar(Producto $producto): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproducto SET tbproductonombre = ?, tbproductodescripcion = ?, tbproductofoto = ?, tbproductoprecioventa = ?
             WHERE tbproductoid = ? AND tbproductovendedorid = ?'
        );
        $consulta->execute([
            $producto->getNombre(),
            $producto->getDescripcion(),
            $producto->getFoto(),
            $producto->getPrecioVenta(),
            $producto->getIdProducto(),
            $producto->getIdVendedor(),
        ]);
    }

    public function cambiarEstado(Producto $producto, bool $activo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproducto SET tbproductoactivo = ? WHERE tbproductoid = ? AND tbproductovendedorid = ?'
        );
        $consulta->execute([$activo ? 1 : 0, $producto->getIdProducto(), $producto->getIdVendedor()]);

        $producto->setActivo($activo);
    }

    public function bloquearExistencia(int $idProducto, int $idVendedor): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbproductoexistencia, tbproductonombre, tbproductoactivo FROM tbproducto
             WHERE tbproductoid = ? AND tbproductovendedorid = ? FOR UPDATE'
        );
        $consulta->execute([$idProducto, $idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? [
            'existencia' => (int) $fila['tbproductoexistencia'],
            'nombre'     => (string) $fila['tbproductonombre'],
            'activo'     => (bool) $fila['tbproductoactivo'],
        ] : null;
    }

    public function actualizarExistencia(int $idProducto, int $idVendedor, int $existencia): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbproducto SET tbproductoexistencia = ? WHERE tbproductoid = ? AND tbproductovendedorid = ?'
        );
        $consulta->execute([$existencia, $idProducto, $idVendedor]);
    }

    private function construirFiltros(int $idVendedor, array $filtros): array
    {
        $condiciones = ['tbproductovendedorid = ?'];
        $valores     = [$idVendedor];

        $busqueda = trim((string) ($filtros['busqueda'] ?? ''));
        if ($busqueda !== '') {
            $patron        = '%' . addcslashes($busqueda, '%_\\') . '%';
            $condiciones[] = '(tbproductonombre LIKE ? OR tbproductodescripcion LIKE ?)';
            array_push($valores, $patron, $patron);
        }

        $estado = $filtros['estado'] ?? 'todos';
        if ($estado === 'activos' || $estado === 'inactivos') {
            $condiciones[] = 'tbproductoactivo = ?';
            $valores[]     = $estado === 'activos' ? 1 : 0;
        } elseif ($estado === 'agotados') {
            $condiciones[] = 'tbproductoactivo = 1 AND tbproductoexistencia <= 0';
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    /**
     * @param ProductoComponente[] $componentes
     * @param ProductoExtra[] $extras
     */
    private function crearDesdeFila(array $fila, array $componentes = [], array $extras = []): Producto
    {
        return new Producto(
            (int) $fila['tbproductoid'],
            (int) $fila['tbproductovendedorid'],
            (string) $fila['tbproductonombre'],
            $fila['tbproductodescripcion'],
            $fila['tbproductofoto'],
            (int) $fila['tbproductominutos'],
            (float) $fila['tbproductotarifahora'],
            (float) $fila['tbproductocostocomponentes'],
            (float) $fila['tbproductocostomanoobra'],
            (float) $fila['tbproductocostoextras'],
            (float) $fila['tbproductocostototal'],
            (float) $fila['tbproductoprecioventa'],
            (int) $fila['tbproductoexistencia'],
            (bool) $fila['tbproductoactivo'],
            $fila['tbproductoregistrofecha'] !== null ? new DateTime($fila['tbproductoregistrofecha']) : null,
            $componentes,
            $extras
        );
    }

    public function listarActivos(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproducto WHERE tbproductovendedorid = ? AND tbproductoactivo = 1
         ORDER BY tbproductonombre ASC, tbproductoid ASC'
        );
        $consulta->execute([$idVendedor]);

        return array_map(fn(array $fila): Producto => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

}
