<?php

namespace Aplicacion\Repositorios;

use Configuracion\BaseDatos;
use PDO;
use Throwable;

// Carrito de cada cliente guardado en la base de datos.
// Una fila por producto: cliente, tienda, producto y cantidad
class CarritoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /** @return array<int, array<int, int>> [idVendedor => [idProducto => cantidad]] */
    public function listar(int $idCliente): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbcarritovendedorid, tbcarritoproductoid, tbcarritocantidad FROM tbcarrito
             WHERE tbcarritoclienteid = ?
             ORDER BY tbcarritoagregadofecha ASC, tbcarritoid ASC'
        );
        $consulta->execute([$idCliente]);

        $carrito = [];
        foreach ($consulta->fetchAll() as $fila) {
            $carrito[(int) $fila['tbcarritovendedorid']][(int) $fila['tbcarritoproductoid']] = (int) $fila['tbcarritocantidad'];
        }
        return $carrito;
    }

    // Tienda de lo último que el cliente agregó al carrito; si no tiene nada, la de su último pedido
    public function ultimaTienda(int $idCliente): ?int
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbcarritovendedorid FROM tbcarrito WHERE tbcarritoclienteid = ?
             ORDER BY tbcarritoagregadofecha DESC, tbcarritoid DESC LIMIT 1'
        );
        $consulta->execute([$idCliente]);
        $idVendedor = $consulta->fetchColumn();

        if ($idVendedor === false) {
            $consulta = $this->conexion->prepare(
                'SELECT tbpedidovendedorid FROM tbpedido WHERE tbpedidoclienteid = ?
                 ORDER BY tbpedidofecha DESC, tbpedidoid DESC LIMIT 1'
            );
            $consulta->execute([$idCliente]);
            $idVendedor = $consulta->fetchColumn();
        }

        return $idVendedor === false ? null : (int) $idVendedor;
    }

    // Deja la cantidad exacta de un producto; con 0 o menos lo quita del carrito
    public function guardarCantidad(int $idCliente, int $idVendedor, int $idProducto, int $cantidad): void
    {
        if ($cantidad <= 0) {
            $consulta = $this->conexion->prepare(
                'DELETE FROM tbcarrito WHERE tbcarritoclienteid = ? AND tbcarritoproductoid = ?'
            );
            $consulta->execute([$idCliente, $idProducto]);
            return;
        }

        $transaccionPropia = BaseDatos::iniciarTransaccion();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT tbcarritoid FROM tbcarrito WHERE tbcarritoclienteid = ? AND tbcarritoproductoid = ? FOR UPDATE'
            );
            $consulta->execute([$idCliente, $idProducto]);
            $idCarrito = $consulta->fetchColumn();

            if ($idCarrito !== false) {
                $consulta = $this->conexion->prepare('UPDATE tbcarrito SET tbcarritocantidad = ?, tbcarritoagregadofecha = NOW() WHERE tbcarritoid = ?');
                $consulta->execute([$cantidad, (int) $idCarrito]);
            } else {
                $consulta = $this->conexion->prepare(
                    'INSERT INTO tbcarrito (tbcarritoid, tbcarritoclienteid, tbcarritovendedorid, tbcarritoproductoid,
                        tbcarritocantidad, tbcarritoagregadofecha)
                     VALUES (?, ?, ?, ?, ?, NOW())'
                );
                $consulta->execute([
                    BaseDatos::generarId('tbcarrito', 'tbcarritoid'),
                    $idCliente,
                    $idVendedor,
                    $idProducto,
                    $cantidad,
                ]);
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
    }

    public function vaciarTienda(int $idCliente, int $idVendedor): void
    {
        $consulta = $this->conexion->prepare(
            'DELETE FROM tbcarrito WHERE tbcarritoclienteid = ? AND tbcarritovendedorid = ?'
        );
        $consulta->execute([$idCliente, $idVendedor]);
    }
}
