<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Produccion;
use Aplicacion\Modelos\ProduccionComponente;
use Aplicacion\Modelos\Producto;
use Aplicacion\Nucleo\CostoPromedio;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use RuntimeException;
use Throwable;

class ProduccionRepositorio
{
    private PDO $conexion;
    private ComponenteRepositorio $componenteRepositorio;
    private ProductoRepositorio $productoRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->componenteRepositorio = new ComponenteRepositorio();
        $this->productoRepositorio = new ProductoRepositorio();
    }

    // Crear un producto es registrar su primera producción: o se guarda todo o nada
    public function crearProducto(Producto $producto, int $cantidad): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idProducto = $this->productoRepositorio->insertar($producto);
            $this->producir($producto, $cantidad);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            $producto->setIdProducto(null);
            throw $error;
        }

        return $idProducto;
    }

    // Descuenta lo que lleva cada uno por la cantidad que hizo y suma la cantidad al producto
    public function producir(Producto $producto, int $cantidad): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $producto->getIdVendedor();
            $idProducto = (int) $producto->getIdProducto();

            $actualProducto = $this->productoRepositorio->bloquearExistencia($idProducto, $idVendedor);
            if ($actualProducto === null) {
                throw new RuntimeException('El producto ' . $idProducto . ' no existe o es de otro vendedor');
            }
            if (!$actualProducto['activo']) {
                throw new OperacionNoPermitida('El producto ' . $actualProducto['nombre'] . ' está desactivado. Actívelo para hacer más.');
            }

            $usados = [];
            $faltantes = [];
            foreach ($producto->getComponentes() as $linea) {
                $necesita = round($linea->getCantidad() * $cantidad, 2);
                $actual = $this->componenteRepositorio->bloquearExistencia($linea->getIdComponente(), $idVendedor);
                if ($actual === null) {
                    throw new RuntimeException('El componente ' . $linea->getIdComponente() . ' no existe o es de otro vendedor');
                }
                if ($actual['existencia'] + 0.001 < $necesita) {
                    $faltantes[] = $actual['nombre'] . ' (necesita ' . Numero::formatear($necesita)
                        . ' y tiene ' . Numero::formatear($actual['existencia']) . ')';
                    continue;
                }

                $this->componenteRepositorio->actualizarExistencia(
                    $linea->getIdComponente(),
                    $idVendedor,
                    round($actual['existencia'] - $necesita, 2),
                    $actual['costo']
                );
                $usados[] = new ProduccionComponente(null, null, $linea->getIdComponente(), $necesita, $actual['costo']);
            }
            if ($faltantes !== []) {
                throw new OperacionNoPermitida('No alcanza el inventario para: ' . implode(', ', $faltantes) . '.');
            }

            $this->productoRepositorio->actualizarExistencia($idProducto, $idVendedor, $actualProducto['existencia'] + $cantidad);

            $idProduccion = BaseDatos::generarId('tbproduccion', 'tbproduccionid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbproduccion (tbproduccionid, tbproduccionvendedorid, tbproduccionproductoid, tbproduccioncantidad,
                    tbproduccionfecha, tbproduccionestado, tbproduccionregistrofecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ahora = new DateTime();
            $consulta->execute([
                $idProduccion,
                $idVendedor,
                $idProducto,
                $cantidad,
                $ahora->format('Y-m-d'),
                Produccion::ACTIVA,
                $ahora->format('Y-m-d H:i:s'),
            ]);

            $insertar = $this->conexion->prepare(
                'INSERT INTO tbproduccioncomponente (tbproduccioncomponenteid, tbproduccioncomponenteproduccionid,
                    tbproduccioncomponentecomponenteid, tbproduccioncomponentecantidad, tbproduccioncomponentecostounitario)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($usados as $usado) {
                $idUsado = BaseDatos::generarId('tbproduccioncomponente', 'tbproduccioncomponenteid');
                $insertar->execute([$idUsado, $idProduccion, $usado->getIdComponente(), $usado->getCantidad(), $usado->getCostoUnitario()]);
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

        return $idProduccion;
    }

    /** @return Produccion[] de un producto, las más recientes primero */
    public function listarPorProducto(int $idProducto, int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproduccion WHERE tbproduccionproductoid = ? AND tbproduccionvendedorid = ?
             ORDER BY tbproduccionid DESC'
        );
        $consulta->execute([$idProducto, $idVendedor]);

        return array_map(fn(array $fila): Produccion => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    public function buscarPorId(int $idProduccion, int $idVendedor): ?Produccion
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbproduccion WHERE tbproduccionid = ? AND tbproduccionvendedorid = ?');
        $consulta->execute([$idProduccion, $idVendedor]);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }

        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbproduccioncomponente WHERE tbproduccioncomponenteproduccionid = ? ORDER BY tbproduccioncomponenteid ASC'
        );
        $consulta->execute([$idProduccion]);
        $componentes = array_map(fn(array $linea): ProduccionComponente => new ProduccionComponente(
            (int) $linea['tbproduccioncomponenteid'],
            (int) $linea['tbproduccioncomponenteproduccionid'],
            (int) $linea['tbproduccioncomponentecomponenteid'],
            (float) $linea['tbproduccioncomponentecantidad'],
            (float) $linea['tbproduccioncomponentecostounitario']
        ), $consulta->fetchAll());

        return $this->crearDesdeFila($fila, $componentes);
    }

    // Quita del producto los que se hicieron y devuelve los componentes al inventario
    public function anular(Produccion $produccion): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $produccion->getIdVendedor();

            $consulta = $this->conexion->prepare(
                'SELECT tbproduccionestado FROM tbproduccion WHERE tbproduccionid = ? AND tbproduccionvendedorid = ? FOR UPDATE'
            );
            $consulta->execute([$produccion->getIdProduccion(), $idVendedor]);
            if ($consulta->fetchColumn() !== Produccion::ACTIVA) {
                throw new OperacionNoPermitida('Esta producción ya estaba anulada.');
            }

            $producto = $this->productoRepositorio->bloquearExistencia($produccion->getIdProducto(), $idVendedor);
            if ($producto === null) {
                throw new RuntimeException('El producto ' . $produccion->getIdProducto() . ' no existe o es de otro vendedor');
            }
            if ($producto['existencia'] < $produccion->getCantidad()) {
                throw new OperacionNoPermitida(
                    'No se puede anular: esa vez hizo ' . $produccion->getCantidad() . ' y ahora solo le quedan ' . $producto['existencia'] . '.'
                );
            }
            $this->productoRepositorio->actualizarExistencia(
                $produccion->getIdProducto(),
                $idVendedor,
                $producto['existencia'] - $produccion->getCantidad()
            );

            foreach ($produccion->getComponentes() as $usado) {
                $actual = $this->componenteRepositorio->bloquearExistencia($usado->getIdComponente(), $idVendedor);
                if ($actual === null) {
                    throw new RuntimeException('El componente ' . $usado->getIdComponente() . ' no existe o es de otro vendedor');
                }
                $nuevo = CostoPromedio::sumar($actual['existencia'], $actual['costo'], $usado->getCantidad(), $usado->getCostoUnitario());
                $this->componenteRepositorio->actualizarExistencia($usado->getIdComponente(), $idVendedor, $nuevo['existencia'], $nuevo['costo']);
            }

            $consulta = $this->conexion->prepare(
                'UPDATE tbproduccion SET tbproduccionestado = ? WHERE tbproduccionid = ? AND tbproduccionvendedorid = ?'
            );
            $consulta->execute([Produccion::ANULADA, $produccion->getIdProduccion(), $idVendedor]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $produccion->setEstado(Produccion::ANULADA);
    }

    /** @param ProduccionComponente[] $componentes */
    private function crearDesdeFila(array $fila, array $componentes = []): Produccion
    {
        return new Produccion(
            (int) $fila['tbproduccionid'],
            (int) $fila['tbproduccionvendedorid'],
            (int) $fila['tbproduccionproductoid'],
            (int) $fila['tbproduccioncantidad'],
            new DateTime($fila['tbproduccionfecha']),
            (string) $fila['tbproduccionestado'],
            $fila['tbproduccionregistrofecha'] !== null ? new DateTime($fila['tbproduccionregistrofecha']) : null,
            $componentes
        );
    }
}