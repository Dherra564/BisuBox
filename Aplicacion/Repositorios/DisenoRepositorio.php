<?php

namespace Aplicacion\Repositorios;

use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class DisenoRepositorio
{
    public const EN_CARRITO = 'Carrito';
    public const EN_PEDIDO = 'Pedido';
    public const QUITADO = 'Quitado';

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function crear(int $idCliente, int $idVendedor, array $diseno): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();
        try {
            $idDiseno = BaseDatos::generarId('tbdiseno', 'tbdisenoid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbdiseno (tbdisenoid, tbdisenoclienteid, tbdisenovendedorid, tbdisenotipopieza, tbdisenolargo,
                    tbdisenosimetrico, tbdisenohilocomponenteid, tbdisenohilonombre, tbdisenohilocosto, tbdisenomanoobra,
                    tbdisenoganancia, tbdisenocosto, tbdisenoprecio, tbdisenoestado, tbdisenoregistrofecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idDiseno,
                $idCliente,
                $idVendedor,
                $diseno['tipo'],
                $diseno['largo'],
                $diseno['simetrico'] ? 1 : 0,
                $diseno['idHilo'],
                $diseno['nombreHilo'],
                $diseno['costoHilo'],
                $diseno['manoObra'],
                $diseno['ganancia'],
                $diseno['costo'],
                $diseno['precio'],
                self::EN_CARRITO,
                (new DateTime())->format('Y-m-d H:i:s'),
            ]);

            $detalle = $this->conexion->prepare(
                'INSERT INTO tbdisenodetalle (tbdisenodetalleid, tbdisenodetalledisenoid, tbdisenodetallecomponenteid,
                    tbdisenodetalleposicion, tbdisenodetallenombre, tbdisenodetallefoto, tbdisenodetalletamano, tbdisenodetallecostounitario)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($diseno['detalle'] as $linea) {
                $detalle->execute([
                    BaseDatos::generarId('tbdisenodetalle', 'tbdisenodetalleid'),
                    $idDiseno,
                    $linea['idComponente'],
                    $linea['posicion'],
                    $linea['nombre'],
                    $linea['foto'],
                    $linea['tamano'],
                    $linea['costo'],
                ]);
            }

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
            return $idDiseno;
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }

    public function listarEnCarrito(int $idCliente, ?int $idVendedor = null): array
    {
        $condicion = 'tbdisenoclienteid = ? AND tbdisenoestado = ?';
        $valores = [$idCliente, self::EN_CARRITO];
        if ($idVendedor !== null) {
            $condicion .= ' AND tbdisenovendedorid = ?';
            $valores[] = $idVendedor;
        }
        $consulta = $this->conexion->prepare("SELECT * FROM tbdiseno WHERE {$condicion} ORDER BY tbdisenoid ASC");
        $consulta->execute($valores);
        $disenos = $this->conDetalle($consulta->fetchAll());

        $porTienda = [];
        foreach ($disenos as $diseno) {
            $porTienda[$diseno['idVendedor']][$diseno['id']] = $diseno;
        }
        return $idVendedor !== null ? ($porTienda[$idVendedor] ?? []) : $porTienda;
    }

    public function buscarVarios(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->conexion->prepare("SELECT * FROM tbdiseno WHERE tbdisenoid IN ({$marcas})");
        $consulta->execute($ids);

        $disenos = [];
        foreach ($this->conDetalle($consulta->fetchAll()) as $diseno) {
            $disenos[$diseno['id']] = $diseno;
        }
        return $disenos;
    }

    public function requerimientos(array $disenos): array
    {
        $componentes = [];
        $hilo = [];
        foreach ($disenos as $diseno) {
            foreach ($diseno['conteo'] as $idComponente => $cantidad) {
                $componentes[$idComponente] = ($componentes[$idComponente] ?? 0) + $cantidad;
            }
            if ($diseno['idHilo'] !== null) {
                $hilo[$diseno['idHilo']] = ($hilo[$diseno['idHilo']] ?? 0) + $diseno['largo'] / 1000;
            }
        }
        return ['componentes' => $componentes, 'hilos' => $hilo];
    }

    public function quitar(int $idCliente, int $idDiseno): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbdiseno SET tbdisenoestado = ? WHERE tbdisenoid = ? AND tbdisenoclienteid = ? AND tbdisenoestado = ?'
        );
        $consulta->execute([self::QUITADO, $idDiseno, $idCliente, self::EN_CARRITO]);
        return $consulta->rowCount() > 0;
    }

    public function marcarEnPedido(array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->conexion->prepare(
            "UPDATE tbdiseno SET tbdisenoestado = ? WHERE tbdisenoid IN ({$marcas}) AND tbdisenoestado = ?"
        );
        $consulta->execute([self::EN_PEDIDO, ...array_values($ids), self::EN_CARRITO]);
    }

    public function actualizarPrecio(int $idDiseno, float $costo, float $precio, float $manoObra, float $ganancia): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbdiseno SET tbdisenocosto = ?, tbdisenoprecio = ?, tbdisenomanoobra = ?, tbdisenoganancia = ?
             WHERE tbdisenoid = ? AND tbdisenoestado = ?'
        );
        $consulta->execute([$costo, $precio, $manoObra, $ganancia, $idDiseno, self::EN_CARRITO]);
    }

    public function fotoEnUso(string $foto): bool
    {
        $consulta = $this->conexion->prepare('SELECT 1 FROM tbdisenodetalle WHERE tbdisenodetallefoto = ? LIMIT 1');
        $consulta->execute([$foto]);
        return (bool) $consulta->fetchColumn();
    }

    private function conDetalle(array $filas): array
    {
        if ($filas === []) {
            return [];
        }
        $ids = array_map(fn(array $fila): int => (int) $fila['tbdisenoid'], $filas);
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->conexion->prepare(
            "SELECT * FROM tbdisenodetalle WHERE tbdisenodetalledisenoid IN ({$marcas})
             ORDER BY tbdisenodetalledisenoid ASC, tbdisenodetalleposicion ASC"
        );
        $consulta->execute($ids);

        $detalles = [];
        foreach ($consulta->fetchAll() as $linea) {
            $detalles[(int) $linea['tbdisenodetalledisenoid']][] = [
                'posicion' => (int) $linea['tbdisenodetalleposicion'],
                'idComponente' => (int) $linea['tbdisenodetallecomponenteid'],
                'nombre' => (string) $linea['tbdisenodetallenombre'],
                'foto' => (string) $linea['tbdisenodetallefoto'],
                'tamano' => (float) $linea['tbdisenodetalletamano'],
                'costo' => (float) $linea['tbdisenodetallecostounitario'],
            ];
        }

        return array_map(function (array $fila) use ($detalles): array {
            $id = (int) $fila['tbdisenoid'];
            $detalle = $detalles[$id] ?? [];
            $lista = array_map(fn(array $linea): int => $linea['idComponente'], $detalle);
            return [
                'id' => $id,
                'idCliente' => (int) $fila['tbdisenoclienteid'],
                'idVendedor' => (int) $fila['tbdisenovendedorid'],
                'tipo' => (string) $fila['tbdisenotipopieza'],
                'largo' => (float) $fila['tbdisenolargo'],
                'simetrico' => (bool) $fila['tbdisenosimetrico'],
                'idHilo' => $fila['tbdisenohilocomponenteid'] !== null ? (int) $fila['tbdisenohilocomponenteid'] : null,
                'nombreHilo' => (string) $fila['tbdisenohilonombre'],
                'costoHilo' => (float) $fila['tbdisenohilocosto'],
                'manoObra' => (float) $fila['tbdisenomanoobra'],
                'ganancia' => (float) $fila['tbdisenoganancia'],
                'costo' => (float) $fila['tbdisenocosto'],
                'precio' => (float) $fila['tbdisenoprecio'],
                'estado' => (string) $fila['tbdisenoestado'],
                'detalle' => $detalle,
                'lista' => $lista,
                'conteo' => array_count_values($lista),
            ];
        }, $filas);
    }
}
