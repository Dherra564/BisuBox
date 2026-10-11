<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Componente;
use Aplicacion\Nucleo\ClaseDato;
use Configuracion\BaseDatos;
use PDO;
use Throwable;

class ComponenteRepositorio
{
    private PDO $conexion;

    private const CONDICIONES_EXISTENCIA = [
        'agotado' => 'c.tbcomponenteexistencia <= 0',
        'bajo' => 'c.tbcomponenteexistencia > 0 AND c.tbcomponenteexistenciaminima > 0
                   AND c.tbcomponenteexistencia <= c.tbcomponenteexistenciaminima',
        'suficiente' => 'c.tbcomponenteexistencia > 0 AND (c.tbcomponenteexistenciaminima <= 0
                         OR c.tbcomponenteexistencia > c.tbcomponenteexistenciaminima)',
    ];

    private const ORDENES = [
        'nombre' => 'c.tbcomponentenombre ASC',
        'existencia' => 'CASE WHEN c.tbcomponenteexistencia <= 0 THEN 0
                              WHEN c.tbcomponenteexistenciaminima > 0 AND c.tbcomponenteexistencia <= c.tbcomponenteexistenciaminima THEN 1
                              ELSE 2 END ASC, c.tbcomponentenombre ASC',
        'valor' => '(c.tbcomponenteexistencia * c.tbcomponentecostounitario) DESC, c.tbcomponentenombre ASC',
        'recientes' => 'c.tbcomponenteid DESC',
    ];

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /** @return Componente[] */
    public function listar(int $idVendedor, array $filtros): array
    {
        $condiciones = ['c.tbcomponentevendedorid = ?'];
        $parametros = [$idVendedor];

        if ($filtros['buscar'] !== '') {
            $condiciones[] = "(c.tbcomponentenombre LIKE ? OR t.tbtipocomponentenombre LIKE ?
                OR EXISTS (SELECT 1 FROM tbcomponentevalor vb
                           INNER JOIN tbtipocomponenteatributo ab ON ab.tbtipocomponenteatributoid = vb.tbcomponentevalortipocomponenteatributoid
                           WHERE vb.tbcomponentevalorcomponenteid = c.tbcomponenteid
                             AND ab.tbtipocomponenteatributoclase IN ('" . ClaseDato::TEXTO . "', '" . ClaseDato::NUMERO . "')
                             AND vb.tbcomponentevalorvalor LIKE ?))";
            $patron = '%' . addcslashes($filtros['buscar'], '%_\\') . '%';
            array_push($parametros, $patron, $patron, $patron);
        }

        if ($filtros['tipo'] !== null) {
            $condiciones[] = 'c.tbcomponentetipocomponenteid = ?';
            $parametros[] = $filtros['tipo'];
        }

        if (isset(self::CONDICIONES_EXISTENCIA[$filtros['existencia']])) {
            $condiciones[] = '(' . self::CONDICIONES_EXISTENCIA[$filtros['existencia']] . ')';
        }

        if ($filtros['estado'] !== 'todos') {
            $condiciones[] = 'c.tbcomponenteactivo = ?';
            $parametros[] = $filtros['estado'] === 'inactivos' ? 0 : 1;
        }

        foreach ($filtros['datos'] as $idAtributo => $dato) {
            if ($dato['clase'] === ClaseDato::SINO && $dato['valor'] === '0') {
                $condiciones[] = "NOT EXISTS (SELECT 1 FROM tbcomponentevalor vf WHERE vf.tbcomponentevalorcomponenteid = c.tbcomponenteid
                                  AND vf.tbcomponentevalortipocomponenteatributoid = ? AND vf.tbcomponentevalorvalor = '1')";
                $parametros[] = $idAtributo;
                continue;
            }
            $condiciones[] = 'EXISTS (SELECT 1 FROM tbcomponentevalor vf WHERE vf.tbcomponentevalorcomponenteid = c.tbcomponenteid
                              AND vf.tbcomponentevalortipocomponenteatributoid = ? AND vf.tbcomponentevalorvalor = ?)';
            array_push($parametros, $idAtributo, $dato['valor']);
        }

        $consulta = $this->conexion->prepare(
            'SELECT c.*, t.tbtipocomponentenombre
             FROM tbcomponente c
             INNER JOIN tbtipocomponente t ON t.tbtipocomponenteid = c.tbcomponentetipocomponenteid
             WHERE ' . implode(' AND ', $condiciones) . '
             ORDER BY ' . (self::ORDENES[$filtros['orden']] ?? self::ORDENES['nombre'])
        );
        $consulta->execute($parametros);
        $filas = $consulta->fetchAll();

        $valores = $this->listarValores(array_map(fn(array $fila): int => (int) $fila['tbcomponenteid'], $filas));

        return array_map(
            fn(array $fila): Componente => $this->crearDesdeFila($fila, $valores[(int) $fila['tbcomponenteid']] ?? []),
            $filas
        );
    }

    /** @return array{total: int, valor: float, bajos: int, agotados: int} */
    public function resumen(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) AS total,
                COALESCE(SUM(c.tbcomponenteexistencia * c.tbcomponentecostounitario), 0) AS valor,
                COALESCE(SUM(CASE WHEN ' . self::CONDICIONES_EXISTENCIA['bajo'] . ' THEN 1 ELSE 0 END), 0) AS bajos,
                COALESCE(SUM(CASE WHEN ' . self::CONDICIONES_EXISTENCIA['agotado'] . ' THEN 1 ELSE 0 END), 0) AS agotados
             FROM tbcomponente c
             WHERE c.tbcomponentevendedorid = ? AND c.tbcomponenteactivo = 1'
        );
        $consulta->execute([$idVendedor]);
        $fila = $consulta->fetch();

        return [
            'total' => (int) $fila['total'],
            'valor' => (float) $fila['valor'],
            'bajos' => (int) $fila['bajos'],
            'agotados' => (int) $fila['agotados'],
        ];
    }

    public function contarPorVendedor(int $idVendedor): int
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbcomponente WHERE tbcomponentevendedorid = ?');
        $consulta->execute([$idVendedor]);

        return (int) $consulta->fetchColumn();
    }

    public function buscarPorId(int $idComponente, int $idVendedor): ?Componente
    {
        $consulta = $this->conexion->prepare(
            'SELECT c.*, t.tbtipocomponentenombre
             FROM tbcomponente c
             INNER JOIN tbtipocomponente t ON t.tbtipocomponenteid = c.tbcomponentetipocomponenteid
             WHERE c.tbcomponenteid = ? AND c.tbcomponentevendedorid = ?'
        );
        $consulta->execute([$idComponente, $idVendedor]);
        $fila = $consulta->fetch();

        return $fila ? $this->crearDesdeFila($fila, $this->listarValores([$idComponente])[$idComponente] ?? []) : null;
    }

    public function existeNombre(int $idVendedor, string $nombre, ?int $excluirIdComponente = null): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbcomponente
             WHERE tbcomponentevendedorid = ? AND tbcomponentenombre = ? AND tbcomponenteid <> ?'
        );
        $consulta->execute([$idVendedor, UsuarioRepositorio::limpiarEspacios($nombre), $excluirIdComponente ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function insertar(Componente $componente): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idComponente = BaseDatos::generarId('tbcomponente', 'tbcomponenteid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbcomponente (tbcomponenteid, tbcomponentevendedorid, tbcomponentetipocomponenteid, tbcomponentenombre,
                    tbcomponenteunidad, tbcomponenteexistencia, tbcomponentecostounitario, tbcomponenteexistenciaminima, tbcomponenteactivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $idComponente,
                $componente->getIdVendedor(),
                $componente->getIdTipoComponente(),
                UsuarioRepositorio::limpiarEspacios($componente->getNombre()),
                $componente->getUnidad(),
                $componente->getExistencia(),
                $componente->getCostoUnitario(),
                $componente->getExistenciaMinima(),
                $componente->getActivo() ? 1 : 0,
            ]);

            $this->guardarValores($idComponente, $componente->getValores(), array_keys($componente->getValores()));

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }

        $componente->setIdComponente($idComponente);

        return $idComponente;
    }

    /** @param int[] $idsAtributos */
    public function actualizar(Componente $componente, array $idsAtributos): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare(
                'UPDATE tbcomponente SET tbcomponentenombre = ?, tbcomponenteunidad = ?, tbcomponenteexistenciaminima = ?
                 WHERE tbcomponenteid = ? AND tbcomponentevendedorid = ?'
            );
            $consulta->execute([
                UsuarioRepositorio::limpiarEspacios($componente->getNombre()),
                $componente->getUnidad(),
                $componente->getExistenciaMinima(),
                $componente->getIdComponente(),
                $componente->getIdVendedor(),
            ]);

            $this->guardarValores((int) $componente->getIdComponente(), $componente->getValores(), $idsAtributos);

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

    public function cambiarEstado(int $idComponente, int $idVendedor, bool $activo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbcomponente SET tbcomponenteactivo = ? WHERE tbcomponenteid = ? AND tbcomponentevendedorid = ?'
        );
        $consulta->execute([$activo ? 1 : 0, $idComponente, $idVendedor]);
    }

    // Reemplaza los valores de esos datos; los vacíos no se guardan
    /** @param array<int, string> $valores  @param int[] $idsAtributos */
    private function guardarValores(int $idComponente, array $valores, array $idsAtributos): void
    {
        if ($idsAtributos !== []) {
            $marcas = implode(', ', array_fill(0, count($idsAtributos), '?'));
            $consulta = $this->conexion->prepare(
                "DELETE FROM tbcomponentevalor WHERE tbcomponentevalorcomponenteid = ?
                 AND tbcomponentevalortipocomponenteatributoid IN ({$marcas})"
            );
            $consulta->execute(array_merge([$idComponente], array_values($idsAtributos)));
        }

        $insertar = $this->conexion->prepare(
            'INSERT INTO tbcomponentevalor (tbcomponentevalorid, tbcomponentevalorcomponenteid,
                tbcomponentevalortipocomponenteatributoid, tbcomponentevalorvalor)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($valores as $idAtributo => $valor) {
            if ($valor === '' || !in_array($idAtributo, $idsAtributos, true)) {
                continue;
            }
            $insertar->execute([BaseDatos::generarId('tbcomponentevalor', 'tbcomponentevalorid'), $idComponente, $idAtributo, $valor]);
        }
    }

    /** @param int[] $idsComponente  @return array<int, array<int, string>> */
    private function listarValores(array $idsComponente): array
    {
        if ($idsComponente === []) {
            return [];
        }

        $marcas = implode(', ', array_fill(0, count($idsComponente), '?'));
        $consulta = $this->conexion->prepare(
            "SELECT tbcomponentevalorcomponenteid, tbcomponentevalortipocomponenteatributoid, tbcomponentevalorvalor
             FROM tbcomponentevalor WHERE tbcomponentevalorcomponenteid IN ({$marcas})"
        );
        $consulta->execute($idsComponente);

        $valores = [];
        foreach ($consulta->fetchAll() as $fila) {
            $valores[(int) $fila['tbcomponentevalorcomponenteid']][(int) $fila['tbcomponentevalortipocomponenteatributoid']]
                = (string) $fila['tbcomponentevalorvalor'];
        }

        return $valores;
    }

    private function crearDesdeFila(array $fila, array $valores): Componente
    {
        return new Componente(
            (int) $fila['tbcomponenteid'],
            (int) $fila['tbcomponentevendedorid'],
            (int) $fila['tbcomponentetipocomponenteid'],
            (string) $fila['tbcomponentenombre'],
            (string) $fila['tbcomponenteunidad'],
            (float) $fila['tbcomponenteexistencia'],
            (float) $fila['tbcomponentecostounitario'],
            (float) $fila['tbcomponenteexistenciaminima'],
            (bool) $fila['tbcomponenteactivo'],
            $valores,
            (string) ($fila['tbtipocomponentenombre'] ?? '')
        );
    }
}