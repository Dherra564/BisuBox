<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Proceso;
use Aplicacion\Modelos\ProcesoComponente;
use Aplicacion\Modelos\ProcesoExtra;
use Aplicacion\Nucleo\CostoPromedio;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use RuntimeException;
use Throwable;

class ProcesoRepositorio
{
    public const POR_PAGINA = 20;

    private PDO $conexion;
    private ComponenteRepositorio $componenteRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->componenteRepositorio = new ComponenteRepositorio();
    }

    /** @return Proceso[] */
    public function listar(int $idVendedor, array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            'SELECT p.*,
                (SELECT COUNT(*) FROM tbprocesocomponente c WHERE c.tbprocesocomponenteprocesoid = p.tbprocesoid) AS cantidadcomponentes
             FROM tbproceso p' . $condiciones . '
             ORDER BY p.tbprocesofecha DESC, p.tbprocesoid DESC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): Proceso => $this->crearDesdeFila($fila, [], []), $consulta->fetchAll());
    }

    public function contar(int $idVendedor, array $filtros): int
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, $filtros);
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbproceso p' . $condiciones);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    /** @return array{procesos: int, valor: float, minutos: int} */
    public function resumen(int $idVendedor, array $filtros): array
    {
        [$condiciones, $valores] = $this->construirFiltros($idVendedor, ['estado' => 'registrados'] + $filtros);
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) AS procesos, COALESCE(SUM(p.tbprocesoprecio), 0) AS valor, COALESCE(SUM(p.tbprocesominutos), 0) AS minutos
             FROM tbproceso p' . $condiciones
        );
        $consulta->execute($valores);
        $fila = $consulta->fetch();

        return ['procesos' => (int) $fila['procesos'], 'valor' => (float) $fila['valor'], 'minutos' => (int) $fila['minutos']];
    }

    public function contarPorVendedor(int $idVendedor): int
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbproceso WHERE tbprocesovendedorid = ?');
        $consulta->execute([$idVendedor]);

        return (int) $consulta->fetchColumn();
    }

    public function ultimaTarifaHora(int $idVendedor): ?float
    {
        $consulta = $this->conexion->prepare(
            'SELECT tbprocesotarifahora FROM tbproceso WHERE tbprocesovendedorid = ? ORDER BY tbprocesoid DESC LIMIT 1'
        );
        $consulta->execute([$idVendedor]);
        $tarifa = $consulta->fetchColumn();

        return $tarifa !== false && $tarifa !== null ? (float) $tarifa : null;
    }

    public function buscarPorId(int $idProceso, int $idVendedor): ?Proceso
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbproceso WHERE tbprocesoid = ? AND tbprocesovendedorid = ?');
        $consulta->execute([$idProceso, $idVendedor]);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }

        $consulta = $this->conexion->prepare(
            'SELECT d.*, co.tbcomponentenombre, co.tbcomponenteunidad, t.tbtipocomponentenombre
             FROM tbprocesocomponente d
             INNER JOIN tbcomponente co ON co.tbcomponenteid = d.tbprocesocomponentecomponenteid
             INNER JOIN tbtipocomponente t ON t.tbtipocomponenteid = co.tbcomponentetipocomponenteid
             WHERE d.tbprocesocomponenteprocesoid = ?
             ORDER BY d.tbprocesocomponenteid ASC'
        );
        $consulta->execute([$idProceso]);
        $componentes = array_map(fn(array $linea): ProcesoComponente => new ProcesoComponente(
            (int) $linea['tbprocesocomponenteid'],
            (int) $linea['tbprocesocomponenteprocesoid'],
            (int) $linea['tbprocesocomponentecomponenteid'],
            (float) $linea['tbprocesocomponentecantidad'],
            (float) $linea['tbprocesocomponentecostounitario'],
            (float) $linea['tbprocesocomponentesubtotal'],
            (string) $linea['tbcomponentenombre'],
            (string) $linea['tbtipocomponentenombre'],
            (string) $linea['tbcomponenteunidad']
        ), $consulta->fetchAll());

        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbprocesoextra WHERE tbprocesoextraprocesoid = ? ORDER BY tbprocesoextraid ASC'
        );
        $consulta->execute([$idProceso]);
        $extras = array_map(fn(array $extra): ProcesoExtra => new ProcesoExtra(
            (int) $extra['tbprocesoextraid'],
            (int) $extra['tbprocesoextraprocesoid'],
            (string) $extra['tbprocesoextraconcepto'],
            (float) $extra['tbprocesoextramonto']
        ), $consulta->fetchAll());

        return $this->crearDesdeFila($fila, $componentes, $extras);
    }

    public function registrar(Proceso $proceso): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $proceso->getIdVendedor();

            $faltantes = [];
            foreach ($proceso->getComponentes() as $detalle) {
                $actual = $this->componenteRepositorio->bloquearExistencia($detalle->getIdComponente(), $idVendedor);
                if ($actual === null) {
                    throw new RuntimeException('El componente ' . $detalle->getIdComponente() . ' no existe o es de otro vendedor');
                }
                if ($actual['existencia'] + 0.001 < $detalle->getCantidad()) {
                    $faltantes[] = $actual['nombre'] . ' (tiene ' . Numero::formatear($actual['existencia']) . ')';
                    continue;
                }
                if (abs($actual['costo'] - $detalle->getCostoUnitario()) > 0.00005) {
                    throw new OperacionNoPermitida(
                        'El costo de ' . $actual['nombre'] . ' cambió mientras registraba el proceso. Revise el resumen e intente de nuevo.'
                    );
                }

                $this->componenteRepositorio->actualizarExistencia(
                    $detalle->getIdComponente(),
                    $idVendedor,
                    round($actual['existencia'] - $detalle->getCantidad(), 2),
                    $actual['costo']
                );
            }
            if ($faltantes !== []) {
                throw new OperacionNoPermitida('No alcanza el inventario para: ' . implode(', ', $faltantes) . '.');
            }

            $idProceso = BaseDatos::generarId('tbproceso', 'tbprocesoid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbproceso (tbprocesoid, tbprocesovendedorid, tbprocesonombre, tbprocesodescripcion, tbprocesofecha,
                    tbprocesominutos, tbprocesotarifahora, tbprocesocostocomponentes, tbprocesocostomanoobra, tbprocesocostoextras,
                    tbprocesoprecio, tbprocesoestado, tbprocesoregistrofecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idProceso,
                $idVendedor,
                $proceso->getNombre(),
                $proceso->getDescripcion(),
                $proceso->getFecha()->format('Y-m-d'),
                $proceso->getMinutos(),
                $proceso->getTarifaHora(),
                $proceso->getCostoComponentes(),
                $proceso->getCostoManoObra(),
                $proceso->getCostoExtras(),
                $proceso->getPrecio(),
                Proceso::REGISTRADO,
                (new DateTime())->format('Y-m-d H:i:s'),
            ]);

            $insertarComponente = $this->conexion->prepare(
                'INSERT INTO tbprocesocomponente (tbprocesocomponenteid, tbprocesocomponenteprocesoid, tbprocesocomponentecomponenteid,
                    tbprocesocomponentecantidad, tbprocesocomponentecostounitario, tbprocesocomponentesubtotal)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($proceso->getComponentes() as $detalle) {
                $idDetalle = BaseDatos::generarId('tbprocesocomponente', 'tbprocesocomponenteid');
                $insertarComponente->execute([
                    $idDetalle,
                    $idProceso,
                    $detalle->getIdComponente(),
                    $detalle->getCantidad(),
                    $detalle->getCostoUnitario(),
                    $detalle->getSubtotal(),
                ]);
                $detalle->setIdProcesoComponente($idDetalle);
                $detalle->setIdProceso($idProceso);
            }

            $insertarExtra = $this->conexion->prepare(
                'INSERT INTO tbprocesoextra (tbprocesoextraid, tbprocesoextraprocesoid, tbprocesoextraconcepto, tbprocesoextramonto)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($proceso->getExtras() as $extra) {
                $idExtra = BaseDatos::generarId('tbprocesoextra', 'tbprocesoextraid');
                $insertarExtra->execute([$idExtra, $idProceso, $extra->getConcepto(), $extra->getMonto()]);
                $extra->setIdProcesoExtra($idExtra);
                $extra->setIdProceso($idProceso);
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

        $proceso->setIdProceso($idProceso);

        return $idProceso;
    }

    public function anular(Proceso $proceso): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idVendedor = (int) $proceso->getIdVendedor();

            $consulta = $this->conexion->prepare(
                'SELECT tbprocesoestado FROM tbproceso WHERE tbprocesoid = ? AND tbprocesovendedorid = ? FOR UPDATE'
            );
            $consulta->execute([$proceso->getIdProceso(), $idVendedor]);
            if ($consulta->fetchColumn() !== Proceso::REGISTRADO) {
                throw new OperacionNoPermitida('Este proceso ya estaba anulado.');
            }

            foreach ($proceso->getComponentes() as $detalle) {
                $actual = $this->componenteRepositorio->bloquearExistencia($detalle->getIdComponente(), $idVendedor);
                if ($actual === null) {
                    throw new RuntimeException('El componente ' . $detalle->getIdComponente() . ' no existe o es de otro vendedor');
                }
                $nuevo = CostoPromedio::sumar($actual['existencia'], $actual['costo'], $detalle->getCantidad(), $detalle->getCostoUnitario());
                $this->componenteRepositorio->actualizarExistencia(
                    $detalle->getIdComponente(),
                    $idVendedor,
                    $nuevo['existencia'],
                    $nuevo['costo']
                );
            }

            $consulta = $this->conexion->prepare(
                'UPDATE tbproceso SET tbprocesoestado = ? WHERE tbprocesoid = ? AND tbprocesovendedorid = ?'
            );
            $consulta->execute([Proceso::ANULADO, $proceso->getIdProceso(), $idVendedor]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $proceso->setEstado(Proceso::ANULADO);
    }

    private function construirFiltros(int $idVendedor, array $filtros): array
    {
        $condiciones = ['p.tbprocesovendedorid = ?'];
        $valores = [$idVendedor];

        $busqueda = trim((string) ($filtros['busqueda'] ?? ''));
        if ($busqueda !== '') {
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            $condiciones[] = '(p.tbprocesonombre LIKE ? OR p.tbprocesodescripcion LIKE ?)';
            array_push($valores, $patron, $patron);
        }

        $estado = $filtros['estado'] ?? 'todos';
        if ($estado !== 'todos') {
            $condiciones[] = 'p.tbprocesoestado = ?';
            $valores[] = $estado === 'anulados' ? Proceso::ANULADO : Proceso::REGISTRADO;
        }

        if (($filtros['desde'] ?? null) instanceof DateTime) {
            $condiciones[] = 'p.tbprocesofecha >= ?';
            $valores[] = $filtros['desde']->format('Y-m-d');
        }
        if (($filtros['hasta'] ?? null) instanceof DateTime) {
            $condiciones[] = 'p.tbprocesofecha <= ?';
            $valores[] = $filtros['hasta']->format('Y-m-d');
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    /**
     * @param ProcesoComponente[] $componentes
     * @param ProcesoExtra[] $extras
     */
    private function crearDesdeFila(array $fila, array $componentes, array $extras): Proceso
    {
        return new Proceso(
            (int) $fila['tbprocesoid'],
            (int) $fila['tbprocesovendedorid'],
            (string) $fila['tbprocesonombre'],
            $fila['tbprocesodescripcion'],
            new DateTime($fila['tbprocesofecha']),
            (int) $fila['tbprocesominutos'],
            (float) $fila['tbprocesotarifahora'],
            (float) $fila['tbprocesocostocomponentes'],
            (float) $fila['tbprocesocostomanoobra'],
            (float) $fila['tbprocesocostoextras'],
            (float) $fila['tbprocesoprecio'],
            (string) $fila['tbprocesoestado'],
            $fila['tbprocesoregistrofecha'] !== null ? new DateTime($fila['tbprocesoregistrofecha']) : null,
            $componentes,
            $extras,
            (int) ($fila['cantidadcomponentes'] ?? 0)
        );
    }
}