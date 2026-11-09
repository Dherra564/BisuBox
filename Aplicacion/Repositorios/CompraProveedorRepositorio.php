<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\CompraProveedor;
use Aplicacion\Modelos\CompraProveedorDetalle;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\MovimientoInventario;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class CompraProveedorRepositorio
{
    public const POR_PAGINA = 20;
    public const NOTA_CONTADO = 'Pagada al registrar la compra';

    private const SELECCION = 'SELECT c.*, p.tbproveedornombre,
            (SELECT COUNT(*) FROM tbcompraproveedordetalle d WHERE d.tbcompraproveedordetallecompraproveedorid = c.tbcompraproveedorid) AS cantidadlineas
         FROM tbcompraproveedor c
         INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid';

    private PDO $conexion;
    private ComponenteRepositorio $componenteRepositorio;
    private InventarioRepositorio $inventario;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->componenteRepositorio = new ComponenteRepositorio();
        $this->inventario = new InventarioRepositorio();
    }

    public function listar(int $idVendedor, array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            self::SELECCION . $condiciones . '
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

    public function resumen(int $idVendedor, array $filtros): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, ['estado' => 'activas'] + $filtros);
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(c.tbcompraproveedortotal), 0) AS total,
                COALESCE(SUM(c.tbcompraproveedorcostoenvio), 0) AS envios, COALESCE(SUM(c.tbcompraproveedorsaldo), 0) AS saldo
             FROM tbcompraproveedor c INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid' . $condiciones
        );
        $consulta->execute($valores);
        $fila = $consulta->fetch();

        return [
            'compras' => (int) $fila['compras'],
            'total' => (float) $fila['total'],
            'envios' => (float) $fila['envios'],
            'saldo' => (float) $fila['saldo'],
        ];
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
            self::SELECCION . ' WHERE c.tbcompraproveedorid = ? AND c.tbcompraproveedorvendedorid = ?'
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

    public function registrar(CompraProveedor $compra, array $componentesNuevos, ?string $metodoPago): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $compra->getIdVendedor();
            $idCompraProveedor = BaseDatos::generarId('tbcompraproveedor', 'tbcompraproveedorid');
            $ahora = (new DateTime())->format('Y-m-d H:i:s');

            foreach ($compra->getDetalles() as $indice => $detalle) {
                if (isset($componentesNuevos[$indice])) {
                    $detalle->setIdComponente($this->componenteRepositorio->insertar($componentesNuevos[$indice]));
                }
            }

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbcompraproveedor (tbcompraproveedorid, tbcompraproveedorvendedorid, tbcompraproveedorproveedorid, tbcompraproveedorfecha,
                    tbcompraproveedorsubtotal, tbcompraproveedorcostoenvio, tbcompraproveedortotal, tbcompraproveedorestado,
                    tbcompraproveedorrecepcionfecha, tbcompraproveedorcondicion, tbcompraproveedorvencimiento, tbcompraproveedorsaldo,
                    tbcompraproveedorestadopago, tbcompraproveedorregistrofecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idCompraProveedor,
                $idVendedor,
                $compra->getIdProveedor(),
                $compra->getFecha()->format('Y-m-d'),
                $compra->getSubtotal(),
                $compra->getCostoEnvio(),
                $compra->getTotal(),
                $compra->getEstado(),
                $compra->estaRecibida() ? $ahora : null,
                $compra->getCondicion(),
                $compra->getVencimiento()?->format('Y-m-d'),
                $compra->getSaldo(),
                $compra->getEstadoPago(),
                $ahora,
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

            if ($compra->estaRecibida()) {
                $this->sumarAlInventario($compra, $idCompraProveedor);
            }
            if (!$compra->esCredito() && $metodoPago !== null) {
                $this->insertarAbono($idCompraProveedor, $compra->getTotal(), $metodoPago, self::NOTA_CONTADO, $ahora);
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

    public function recibir(CompraProveedor $compra): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $estado = $this->bloquearEstado($compra);
            if ($estado !== CompraProveedor::POR_RECIBIR) {
                throw new OperacionNoPermitida($estado === CompraProveedor::ANULADA
                    ? 'Esta compra está anulada.'
                    : 'Esta compra ya estaba recibida.');
            }

            $this->sumarAlInventario($compra, (int) $compra->getIdCompraProveedor());
            $this->conexion->prepare(
                'UPDATE tbcompraproveedor SET tbcompraproveedorestado = ?, tbcompraproveedorrecepcionfecha = ?
                 WHERE tbcompraproveedorid = ? AND tbcompraproveedorvendedorid = ?'
            )->execute([
                        CompraProveedor::RECIBIDA,
                        (new DateTime())->format('Y-m-d H:i:s'),
                        $compra->getIdCompraProveedor(),
                        $compra->getIdVendedor(),
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

        $compra->setEstado(CompraProveedor::RECIBIDA);
    }

    public function anular(CompraProveedor $compra): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $compra->getIdVendedor();
            $estado = $this->bloquearEstado($compra);
            if ($estado === CompraProveedor::ANULADA) {
                throw new OperacionNoPermitida('Esta compra ya estaba anulada.');
            }
            if ($compra->esCredito() && $this->totalAbonado((int) $compra->getIdCompraProveedor()) > 0) {
                throw new OperacionNoPermitida('No se puede anular: ya le hizo abonos a esta compra.');
            }

            if ($estado === CompraProveedor::RECIBIDA) {
                $faltantes = [];
                foreach ($compra->getDetalles() as $detalle) {
                    $actual = $this->inventario->bloquear(MovimientoInventario::COMPONENTE, (int) $detalle->getIdComponente(), $idVendedor);
                    if ($actual === null || $actual['existencia'] + 0.001 < $detalle->getCantidad()) {
                        $faltantes[] = $actual['nombre'] ?? $detalle->getNombreComponente();
                    }
                }
                if ($faltantes !== []) {
                    throw new OperacionNoPermitida(
                        'No se puede anular: ya se usó parte de ' . implode(', ', $faltantes) . ', así que no alcanza para restarlo.'
                    );
                }
                foreach ($compra->getDetalles() as $detalle) {
                    $this->inventario->mover(
                        $idVendedor,
                        MovimientoInventario::COMPONENTE,
                        (int) $detalle->getIdComponente(),
                        MovimientoInventario::SALIDA,
                        $detalle->getCantidad(),
                        MovimientoInventario::MOTIVO_COMPRA_ANULADA,
                        MovimientoInventario::REFERENCIA_COMPRA,
                        $compra->getIdCompraProveedor(),
                        $detalle->getCostoFinal()
                    );
                }
            }

            $this->conexion->prepare(
                'UPDATE tbcompraproveedor SET tbcompraproveedorestado = ? WHERE tbcompraproveedorid = ? AND tbcompraproveedorvendedorid = ?'
            )->execute([CompraProveedor::ANULADA, $compra->getIdCompraProveedor(), $idVendedor]);

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

    public function abonar(int $idCompraProveedor, int $idVendedor, float $monto, string $metodoPago, ?string $nota): array
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare(
                'SELECT tbcompraproveedorsaldo, tbcompraproveedorestadopago, tbcompraproveedorestado FROM tbcompraproveedor
                 WHERE tbcompraproveedorid = ? AND tbcompraproveedorvendedorid = ? FOR UPDATE'
            );
            $consulta->execute([$idCompraProveedor, $idVendedor]);
            $fila = $consulta->fetch();
            if (!$fila) {
                throw new OperacionNoPermitida('No se encontró la compra.');
            }
            if ($fila['tbcompraproveedorestado'] === CompraProveedor::ANULADA) {
                throw new OperacionNoPermitida('Esta compra está anulada.');
            }
            $saldo = (float) $fila['tbcompraproveedorsaldo'];
            if ($fila['tbcompraproveedorestadopago'] !== CompraProveedor::POR_PAGAR || $saldo <= 0) {
                throw new OperacionNoPermitida('Esta compra ya está pagada.');
            }
            if ($monto > $saldo + 0.001) {
                throw new OperacionNoPermitida(DatosVenta::mensajeSaldo($saldo));
            }

            $nuevoSaldo = max(0, round($saldo - $monto, 2));
            $this->insertarAbono($idCompraProveedor, $monto, $metodoPago, $nota, (new DateTime())->format('Y-m-d H:i:s'));
            $this->conexion->prepare(
                'UPDATE tbcompraproveedor SET tbcompraproveedorsaldo = ?, tbcompraproveedorestadopago = ? WHERE tbcompraproveedorid = ?'
            )->execute([
                        $nuevoSaldo,
                        $nuevoSaldo <= 0 ? CompraProveedor::PAGADA : CompraProveedor::POR_PAGAR,
                        $idCompraProveedor,
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

        return ['saldo' => $nuevoSaldo, 'pagada' => $nuevoSaldo <= 0];
    }

    public function abonos(int $idCompraProveedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbabonocompra WHERE tbabonocompracompraid = ? ORDER BY tbabonocomprafecha ASC, tbabonocompraid ASC'
        );
        $consulta->execute([$idCompraProveedor]);

        return array_map(fn(array $fila): array => [
            'id' => (int) $fila['tbabonocompraid'],
            'monto' => (float) $fila['tbabonocompramonto'],
            'metodoPago' => (string) $fila['tbabonocomprametodopago'],
            'nota' => $fila['tbabonocompranota'],
            'fecha' => new DateTime((string) $fila['tbabonocomprafecha']),
        ], $consulta->fetchAll());
    }

    public function componentesSinExistencia(CompraProveedor $compra): array
    {
        if (!$compra->estaRecibida()) {
            return [];
        }
        $consulta = $this->conexion->prepare(
            'SELECT co.tbcomponentenombre FROM tbcompraproveedordetalle d
             INNER JOIN tbcomponente co ON co.tbcomponenteid = d.tbcompraproveedordetallecomponenteid
             WHERE d.tbcompraproveedordetallecompraproveedorid = ? AND co.tbcomponenteexistencia + 0.001 < d.tbcompraproveedordetallecantidad'
        );
        $consulta->execute([$compra->getIdCompraProveedor()]);

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarPorPagar(int $idVendedor, array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->filtrosPorPagar($idVendedor, $filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            self::SELECCION . $condiciones . '
             ORDER BY c.tbcompraproveedorvencimiento IS NULL, c.tbcompraproveedorvencimiento ASC, c.tbcompraproveedorid ASC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): CompraProveedor => $this->crearDesdeFila($fila, []), $consulta->fetchAll());
    }

    public function contarPorPagar(int $idVendedor, array $filtros): int
    {
        [$condiciones, $valores] = $this->filtrosPorPagar($idVendedor, $filtros);
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbcompraproveedor c INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid' . $condiciones
        );
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    public function resumenPorPagar(int $idVendedor, array $filtros = []): array
    {
        [$condiciones, $valores] = $this->filtrosPorPagar($idVendedor, $filtros);
        $hoy = new DateTime('today');
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(c.tbcompraproveedorsaldo), 0) AS saldo,
                COALESCE(SUM(CASE WHEN c.tbcompraproveedorvencimiento < ? THEN c.tbcompraproveedorsaldo END), 0) AS vencido,
                COALESCE(SUM(c.tbcompraproveedorvencimiento < ?), 0) AS vencidas,
                COALESCE(SUM(CASE WHEN c.tbcompraproveedorvencimiento BETWEEN ? AND ? THEN c.tbcompraproveedorsaldo END), 0) AS semana
             FROM tbcompraproveedor c INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid' . $condiciones
        );
        $consulta->execute([
            $hoy->format('Y-m-d'),
            $hoy->format('Y-m-d'),
            $hoy->format('Y-m-d'),
            (clone $hoy)->modify('+7 days')->format('Y-m-d'),
            ...$valores,
        ]);
        $fila = $consulta->fetch();

        return [
            'compras' => (int) $fila['compras'],
            'saldo' => (float) $fila['saldo'],
            'vencido' => (float) $fila['vencido'],
            'vencidas' => (int) $fila['vencidas'],
            'semana' => (float) $fila['semana'],
        ];
    }

    public function saldosPorProveedor(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT c.tbcompraproveedorproveedorid, p.tbproveedornombre, p.tbproveedortelefono,
                COUNT(*) AS compras, SUM(c.tbcompraproveedorsaldo) AS saldo,
                SUM(c.tbcompraproveedorvencimiento < ?) AS vencidas,
                SUM(CASE WHEN c.tbcompraproveedorvencimiento < ? THEN c.tbcompraproveedorsaldo ELSE 0 END) AS vencido,
                MIN(c.tbcompraproveedorvencimiento) AS proximo
             FROM tbcompraproveedor c
             INNER JOIN tbproveedor p ON p.tbproveedorid = c.tbcompraproveedorproveedorid
             WHERE c.tbcompraproveedorvendedorid = ? AND c.tbcompraproveedorestado <> ? AND c.tbcompraproveedorestadopago = ?
                AND c.tbcompraproveedorsaldo > 0
             GROUP BY c.tbcompraproveedorproveedorid, p.tbproveedornombre, p.tbproveedortelefono
             ORDER BY saldo DESC'
        );
        $hoy = (new DateTime('today'))->format('Y-m-d');
        $consulta->execute([$hoy, $hoy, $idVendedor, CompraProveedor::ANULADA, CompraProveedor::POR_PAGAR]);

        return array_map(fn(array $fila): array => [
            'idProveedor' => (int) $fila['tbcompraproveedorproveedorid'],
            'proveedor' => (string) $fila['tbproveedornombre'],
            'telefono' => (string) $fila['tbproveedortelefono'],
            'compras' => (int) $fila['compras'],
            'saldo' => (float) $fila['saldo'],
            'vencidas' => (int) $fila['vencidas'],
            'vencido' => (float) $fila['vencido'],
            'proximo' => $fila['proximo'] !== null ? new DateTime((string) $fila['proximo']) : null,
        ], $consulta->fetchAll());
    }

    private function sumarAlInventario(CompraProveedor $compra, int $idCompraProveedor): void
    {
        foreach ($compra->getDetalles() as $detalle) {
            $this->inventario->mover(
                (int) $compra->getIdVendedor(),
                MovimientoInventario::COMPONENTE,
                (int) $detalle->getIdComponente(),
                MovimientoInventario::ENTRADA,
                $detalle->getCantidad(),
                MovimientoInventario::MOTIVO_COMPRA,
                MovimientoInventario::REFERENCIA_COMPRA,
                $idCompraProveedor,
                $detalle->getCostoFinal()
            );
        }
    }

    private function bloquearEstado(CompraProveedor $compra): string
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbcompraproveedorestado FROM tbcompraproveedor WHERE tbcompraproveedorid = ? AND tbcompraproveedorvendedorid = ? FOR UPDATE'
        );
        $consulta->execute([$compra->getIdCompraProveedor(), $compra->getIdVendedor()]);

        return (string) $consulta->fetchColumn();
    }

    private function totalAbonado(int $idCompraProveedor): float
    {
        $consulta = $this->conexion->prepare('SELECT COALESCE(SUM(tbabonocompramonto), 0) FROM tbabonocompra WHERE tbabonocompracompraid = ?');
        $consulta->execute([$idCompraProveedor]);

        return (float) $consulta->fetchColumn();
    }

    private function insertarAbono(int $idCompraProveedor, float $monto, string $metodoPago, ?string $nota, string $fecha): void
    {
        $this->conexion->prepare(
            'INSERT INTO tbabonocompra (tbabonocompraid, tbabonocompracompraid, tbabonocompramonto, tbabonocomprametodopago,
                tbabonocompranota, tbabonocomprafecha)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
                    BaseDatos::generarId('tbabonocompra', 'tbabonocompraid'),
                    $idCompraProveedor,
                    round($monto, 2),
                    $metodoPago,
                    $nota,
                    $fecha,
                ]);
    }

    private function filtrosPorPagar(int $idVendedor, array $filtros): array
    {
        $condiciones = [
            'c.tbcompraproveedorvendedorid = ?',
            'c.tbcompraproveedorestado <> ?',
            'c.tbcompraproveedorestadopago = ?',
            'c.tbcompraproveedorsaldo > 0',
        ];
        $valores = [$idVendedor, CompraProveedor::ANULADA, CompraProveedor::POR_PAGAR];

        if (($filtros['proveedor'] ?? null) !== null) {
            $condiciones[] = 'c.tbcompraproveedorproveedorid = ?';
            $valores[] = $filtros['proveedor'];
        }
        if (!empty($filtros['vencidas'])) {
            $condiciones[] = 'c.tbcompraproveedorvencimiento < ?';
            $valores[] = (new DateTime('today'))->format('Y-m-d');
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

    private function construirFiltros(int $idVendedor, array $filtros): array
    {
        $condiciones = ['c.tbcompraproveedorvendedorid = ?'];
        $valores = [$idVendedor];

        if (($filtros['proveedor'] ?? null) !== null) {
            $condiciones[] = 'c.tbcompraproveedorproveedorid = ?';
            $valores[] = $filtros['proveedor'];
        }

        $estado = $filtros['estado'] ?? 'activas';
        if ($estado === 'activas') {
            $condiciones[] = 'c.tbcompraproveedorestado <> ?';
            $valores[] = CompraProveedor::ANULADA;
        } elseif ($estado !== 'todas') {
            $condiciones[] = 'c.tbcompraproveedorestado = ?';
            $valores[] = match ($estado) {
                'porRecibir' => CompraProveedor::POR_RECIBIR,
                'recibidas' => CompraProveedor::RECIBIDA,
                default => CompraProveedor::ANULADA,
            };
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
            (int) ($fila['cantidadlineas'] ?? 0),
            $fila['tbcompraproveedorrecepcionfecha'] !== null ? new DateTime($fila['tbcompraproveedorrecepcionfecha']) : null,
            (string) ($fila['tbcompraproveedorcondicion'] ?? CompraProveedor::CONTADO),
            $fila['tbcompraproveedorvencimiento'] !== null ? new DateTime($fila['tbcompraproveedorvencimiento']) : null,
            (float) ($fila['tbcompraproveedorsaldo'] ?? 0),
            (string) ($fila['tbcompraproveedorestadopago'] ?? CompraProveedor::PAGADA)
        );
    }
}