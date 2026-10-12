<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\CompraProveedor;
use Aplicacion\Modelos\CompraProveedorDetalle;
use Aplicacion\Modelos\Componente;
use Aplicacion\Nucleo\CostoPromedio;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use RuntimeException;
use Throwable;

class CompraProveedorRepositorio
{
    public const POR_PAGINA = 20;

    private PDO $conexion;
    private ComponenteRepositorio $componenteRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->componenteRepositorio = new ComponenteRepositorio();
    }

    /** @return CompraProveedor[] */
    public function listar(int $idVendedor, array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            'SELECT c.*, p.tbproveedornombre,
                (SELECT COUNT(*) FROM tbcompraproveedordetalle d WHERE d.tbcompraproveedordetallecompraproveedorid = c.tbcompraproveedorid) AS cantidadlineas
             FROM tbcompraproveedor c
             INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid'
            . $condiciones . '
             ORDER BY c.tbcompraproveedorfecha DESC, c.tbcompraproveedorid DESC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): CompraProveedor => $this->crearDesdeFila($fila, []), $consulta->fetchAll());
    }

    public function contar(int $idVendedor, array $filtros): int
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $filtros);
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbcompraproveedor c INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid' . $condiciones
        );
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    /** @return array{compras: int, total: float, envios: float} */
    public function resumen(int $idVendedor, array $filtros): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, ['estado' => 'activas'] + $filtros);
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(c.tbcompraproveedortotal), 0) AS total, COALESCE(SUM(c.tbcompraproveedorcostoenvio), 0) AS envios
             FROM tbcompraproveedor c INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid' . $condiciones
        );
        $consulta->execute($valores);
        $fila = $consulta->fetch();

        return ['compras' => (int) $fila['compras'], 'total' => (float) $fila['total'], 'envios' => (float) $fila['envios']];
    }

    public function contarPorVendedor(int $idVendedor): int
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbcompraproveedor WHERE tbcompraproveedorvendedorid = ?');
        $consulta->execute([$idVendedor]);

        return (int) $consulta->fetchColumn();
    }
    public function buscarPorId(int $idCompraProveedor, int $idVendedor): ?CompraProveedor
    {
        $consulta = $this->conexion->prepare(
            'SELECT c.*, p.tbproveedornombre FROM tbcompraproveedor c
             INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid
             WHERE c.tbcompraproveedorid = ? AND c.tbcompraproveedorvendedorid = ?'
        );
        $consulta->execute([$idCompraProveedor, $idVendedor]);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }

        $consulta = $this->conexion->prepare(
            'SELECT d.*, co.tbcomponentenombre, co.tbcomponenteunidad, t.tbtipocomponentenombre
             FROM tbcompraproveedordetalle d
             INNER JOIN tbcomponente co ON co.tbcomponenteid = d.tbcompraproveedordetallecomponenteid
             INNER JOIN tbtipocomponente t ON t.tbtipocomponenteid = co.tbcomponentetipocomponenteid
             WHERE d.tbcompraproveedordetallecompraproveedorid = ?
             ORDER BY d.tbcompraproveedordetalleid ASC'
        );
        $consulta->execute([$idCompraProveedor]);
        $detalles = array_map(fn(array $linea): CompraProveedorDetalle => new CompraProveedorDetalle(
            (int) $linea['tbcompraproveedordetalleid'],
            (int) $linea['tbcompraproveedordetallecompraproveedorid'],
            (int) $linea['tbcompraproveedordetallecomponenteid'],
            (float) $linea['tbcompraproveedordetallecantidad'],
            (float) $linea['tbcompraproveedordetallecostounitario'],
            (float) $linea['tbcompraproveedordetallesubtotal'],
            (float) $linea['tbcompraproveedordetallecostofinal'],
            (string) $linea['tbcomponentenombre'],
            (string) $linea['tbtipocomponentenombre'],
            (string) $linea['tbcomponenteunidad']
        ), $consulta->fetchAll());

        return $this->crearDesdeFila($fila, $detalles);
    }

    /** @param array<int, Componente> $componentesNuevos */
    public function registrar(CompraProveedor $compra, array $componentesNuevos): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $compra->getIdVendedor();

            foreach ($compra->getDetalles() as $indice => $detalle) {
                if (isset($componentesNuevos[$indice])) {
                    $detalle->setIdComponente($this->componenteRepositorio->insertar($componentesNuevos[$indice]));
                }

                $actual = $this->componenteRepositorio->bloquearExistencia((int) $detalle->getIdComponente(), $idVendedor);
                if ($actual === null) {
                    throw new RuntimeException('El componente ' . $detalle->getIdComponente() . ' no existe o es de otro vendedor');
                }
                $nuevo = CostoPromedio::sumar($actual['existencia'], $actual['costo'], $detalle->getCantidad(), $detalle->getCostoFinal());
                $this->componenteRepositorio->actualizarExistencia(
                    (int) $detalle->getIdComponente(),
                    $idVendedor,
                    $nuevo['existencia'],
                    $nuevo['costo']
                );
            }

            $idCompraProveedor = BaseDatos::generarId('tbcompraproveedor', 'tbcompraproveedorid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbcompraproveedor (tbcompraproveedorid, tbcompraproveedorvendedorid, tbcompraproveedorproveedorid, tbcompraproveedorfecha, tbcompraproveedorsubtotal,
                    tbcompraproveedorcostoenvio, tbcompraproveedortotal, tbcompraproveedorestado, tbcompraproveedorregistrofecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idCompraProveedor,
                $idVendedor,
                $compra->getIdProveedor(),
                $compra->getFecha()->format('Y-m-d'),
                $compra->getSubtotal(),
                $compra->getCostoEnvio(),
                $compra->getTotal(),
                CompraProveedor::ACTIVA,
                (new DateTime())->format('Y-m-d H:i:s'),
            ]);

            $insertarDetalle = $this->conexion->prepare(
                'INSERT INTO tbcompraproveedordetalle (tbcompraproveedordetalleid, tbcompraproveedordetallecompraproveedorid, tbcompraproveedordetallecomponenteid,
                    tbcompraproveedordetallecantidad, tbcompraproveedordetallecostounitario, tbcompraproveedordetallesubtotal, tbcompraproveedordetallecostofinal)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($compra->getDetalles() as $detalle) {
                $idDetalle = BaseDatos::generarId('tbcompraproveedordetalle', 'tbcompraproveedordetalleid');
                $insertarDetalle->execute([
                    $idDetalle,
                    $idCompraProveedor,
                    $detalle->getIdComponente(),
                    $detalle->getCantidad(),
                    $detalle->getCostoUnitario(),
                    $detalle->getSubtotal(),
                    $detalle->getCostoFinal(),
                ]);
                $detalle->setIdDetalle($idDetalle);
                $detalle->setIdCompraProveedor($idCompraProveedor);
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

        $compra->setIdCompraProveedor($idCompraProveedor);

        return $idCompraProveedor;
    }

    public function anular(CompraProveedor $compra): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $compra->getIdVendedor();

            $consulta = $this->conexion->prepare(
                'SELECT tbcompraproveedorestado FROM tbcompraproveedor WHERE tbcompraproveedorid = ? AND tbcompraproveedorvendedorid = ? FOR UPDATE'
            );
            $consulta->execute([$compra->getIdCompraProveedor(), $idVendedor]);
            if ($consulta->fetchColumn() !== CompraProveedor::ACTIVA) {
                throw new OperacionNoPermitida('Esta compra ya estaba anulada.');
            }

            $faltantes = [];
            foreach ($compra->getDetalles() as $detalle) {
                $actual = $this->componenteRepositorio->bloquearExistencia((int) $detalle->getIdComponente(), $idVendedor);
                if ($actual === null || $actual['existencia'] + 0.001 < $detalle->getCantidad()) {
                    $faltantes[] = $actual['nombre'] ?? $detalle->getNombreComponente();
                    continue;
                }
                $nuevo = CostoPromedio::restar($actual['existencia'], $actual['costo'], $detalle->getCantidad(), $detalle->getCostoFinal());
                $this->componenteRepositorio->actualizarExistencia(
                    (int) $detalle->getIdComponente(),
                    $idVendedor,
                    $nuevo['existencia'],
                    $nuevo['costo']
                );
            }
            if ($faltantes !== []) {
                throw new OperacionNoPermitida(
                    'No se puede anular: ya se usó parte de ' . implode(', ', $faltantes) . ', así que no alcanza para restarlo.'
                );
            }

            $consulta = $this->conexion->prepare('UPDATE tbcompraproveedor SET tbcompraproveedorestado = ? WHERE tbcompraproveedorid = ? AND tbcompraproveedorvendedorid = ?');
            $consulta->execute([CompraProveedor::ANULADA, $compra->getIdCompraProveedor(), $idVendedor]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $compra->setEstado(CompraProveedor::ANULADA);
    }

    /** @return string[] */
    public function componentesSinExistencia(CompraProveedor $compra): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT co.tbcomponentenombre FROM tbcompraproveedordetalle d
             INNER JOIN tbcomponente co ON co.tbcomponenteid = d.tbcompraproveedordetallecomponenteid
             WHERE d.tbcompraproveedordetallecompraproveedorid = ? AND co.tbcomponenteexistencia + 0.001 < d.tbcompraproveedordetallecantidad'
        );
        $consulta->execute([$compra->getIdCompraProveedor()]);

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    private function construirFiltros(int $idVendedor, array $filtros): array
    {
        $condiciones = ['c.tbcompraproveedorvendedorid = ?'];
        $valores = [$idVendedor];

        if (($filtros['proveedor'] ?? null) !== null) {
            $condiciones[] = 'c.tbcompraproveedorproveedorid = ?';
            $valores[] = $filtros['proveedor'];
        }

        $estado = $filtros['estado'] ?? 'activas';
        if ($estado !== 'todas') {
            $condiciones[] = 'c.tbcompraproveedorestado = ?';
            $valores[] = $estado === 'anuladas' ? CompraProveedor::ANULADA : CompraProveedor::ACTIVA;
        }

        if (($filtros['desde'] ?? null) instanceof DateTime) {
            $condiciones[] = 'c.tbcompraproveedorfecha >= ?';
            $valores[] = $filtros['desde']->format('Y-m-d');
        }
        if (($filtros['hasta'] ?? null) instanceof DateTime) {
            $condiciones[] = 'c.tbcompraproveedorfecha <= ?';
            $valores[] = $filtros['hasta']->format('Y-m-d');
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    private function crearDesdeFila(array $fila, array $detalles): CompraProveedor
    {
        return new CompraProveedor(
            (int) $fila['tbcompraproveedorid'],
            (int) $fila['tbcompraproveedorvendedorid'],
            (int) $fila['tbcompraproveedorproveedorid'],
            new DateTime($fila['tbcompraproveedorfecha']),
            (float) $fila['tbcompraproveedorsubtotal'],
            (float) $fila['tbcompraproveedorcostoenvio'],
            (float) $fila['tbcompraproveedortotal'],
            (string) $fila['tbcompraproveedorestado'],
            $fila['tbcompraproveedorregistrofecha'] !== null ? new DateTime($fila['tbcompraproveedorregistrofecha']) : null,
            $detalles,
            (string) ($fila['tbproveedornombre'] ?? ''),
            (int) ($fila['cantidadlineas'] ?? 0)
        );
    }
}