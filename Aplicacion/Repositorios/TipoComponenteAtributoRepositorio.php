<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\TipoComponenteAtributo;
use Aplicacion\Modelos\TipoComponenteAtributoOpcion;
use Configuracion\BaseDatos;
use PDO;
use Throwable;

class TipoComponenteAtributoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /** @return TipoComponenteAtributo[] */
    public function listarPorTipo(int $idTipoComponente): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT a.*,
                (SELECT COUNT(*) FROM tbcomponentevalor v
                 WHERE v.tbcomponentevalortipocomponenteatributoid = a.tbtipocomponenteatributoid
                   AND v.tbcomponentevalorvalor <> '') AS cantidadvalores
             FROM tbtipocomponenteatributo a
             WHERE a.tbtipocomponenteatributotipocomponenteid = ?
             ORDER BY a.tbtipocomponenteatributoorden ASC, a.tbtipocomponenteatributoid ASC"
        );
        $consulta->execute([$idTipoComponente]);

        return array_map(fn(array $fila): TipoComponenteAtributo => new TipoComponenteAtributo(
            (int) $fila['tbtipocomponenteatributoid'],
            (int) $fila['tbtipocomponenteatributotipocomponenteid'],
            (string) $fila['tbtipocomponenteatributonombre'],
            (string) $fila['tbtipocomponenteatributoclase'],
            $fila['tbtipocomponenteatributounidad'],
            (bool) $fila['tbtipocomponenteatributoobligatorio'],
            (int) $fila['tbtipocomponenteatributoorden'],
            (bool) $fila['tbtipocomponenteatributoactivo'],
            $this->listarOpciones((int) $fila['tbtipocomponenteatributoid']),
            (int) $fila['cantidadvalores'] > 0
        ), $consulta->fetchAll());
    }

    /** @param TipoComponenteAtributo[] $atributos */
    public function guardar(int $idTipoComponente, array $atributos): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idsActuales = $this->listarIds(
                'SELECT tbtipocomponenteatributoid FROM tbtipocomponenteatributo WHERE tbtipocomponenteatributotipocomponenteid = ?',
                $idTipoComponente
            );
            $idsGuardados = [];

            foreach (array_values($atributos) as $indice => $atributo) {
                $atributo->setOrden($indice + 1);
                $atributo->setIdTipoComponente($idTipoComponente);
                if ($atributo->getIdAtributo() !== null && in_array($atributo->getIdAtributo(), $idsActuales, true)) {
                    $this->actualizarAtributo($atributo);
                } else {
                    $this->insertarAtributo($atributo);
                }
                $idsGuardados[] = $atributo->getIdAtributo();
                $this->guardarOpciones((int) $atributo->getIdAtributo(), $atributo->getOpciones());
            }

            $orden = count($idsGuardados);
            foreach (array_diff($idsActuales, $idsGuardados) as $idAtributo) {
                $this->quitarAtributo($idAtributo, ++$orden);
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

    public function tieneValores(int $idAtributo): bool
    {
        $consulta = $this->conexion->prepare(
            "SELECT COUNT(*) FROM tbcomponentevalor WHERE tbcomponentevalortipocomponenteatributoid = ? AND tbcomponentevalorvalor <> ''"
        );
        $consulta->execute([$idAtributo]);

        return (int) $consulta->fetchColumn() > 0;
    }

    /** @return TipoComponenteAtributoOpcion[] */
    private function listarOpciones(int $idAtributo): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbtipocomponenteatributoopcion WHERE tbtipocomponenteatributoopciontipocomponenteatributoid = ?
             ORDER BY tbtipocomponenteatributoopcionid ASC'
        );
        $consulta->execute([$idAtributo]);

        return array_map(fn(array $fila): TipoComponenteAtributoOpcion => new TipoComponenteAtributoOpcion(
            (int) $fila['tbtipocomponenteatributoopcionid'],
            (int) $fila['tbtipocomponenteatributoopciontipocomponenteatributoid'],
            (string) $fila['tbtipocomponenteatributoopcionvalor'],
            (bool) $fila['tbtipocomponenteatributoopcionactivo']
        ), $consulta->fetchAll());
    }

    private function insertarAtributo(TipoComponenteAtributo $atributo): void
    {
        $idAtributo = BaseDatos::generarId('tbtipocomponenteatributo', 'tbtipocomponenteatributoid');
        $consulta = $this->conexion->prepare(
            'INSERT INTO tbtipocomponenteatributo (tbtipocomponenteatributoid, tbtipocomponenteatributotipocomponenteid,
                tbtipocomponenteatributonombre, tbtipocomponenteatributoclase, tbtipocomponenteatributounidad,
                tbtipocomponenteatributoobligatorio, tbtipocomponenteatributoorden, tbtipocomponenteatributoactivo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $idAtributo,
            $atributo->getIdTipoComponente(),
            UsuarioRepositorio::limpiarEspacios($atributo->getNombre()),
            $atributo->getClase(),
            self::textoOVacio($atributo->getUnidad()),
            $atributo->getObligatorio() ? 1 : 0,
            $atributo->getOrden(),
            $atributo->getActivo() ? 1 : 0,
        ]);
        $atributo->setIdAtributo($idAtributo);
    }

    private function actualizarAtributo(TipoComponenteAtributo $atributo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbtipocomponenteatributo SET tbtipocomponenteatributonombre = ?, tbtipocomponenteatributoclase = ?,
                tbtipocomponenteatributounidad = ?, tbtipocomponenteatributoobligatorio = ?, tbtipocomponenteatributoorden = ?,
                tbtipocomponenteatributoactivo = ?
             WHERE tbtipocomponenteatributoid = ?'
        );
        $consulta->execute([
            UsuarioRepositorio::limpiarEspacios($atributo->getNombre()),
            $atributo->getClase(),
            self::textoOVacio($atributo->getUnidad()),
            $atributo->getObligatorio() ? 1 : 0,
            $atributo->getOrden(),
            $atributo->getActivo() ? 1 : 0,
            $atributo->getIdAtributo(),
        ]);
    }

    private function quitarAtributo(int $idAtributo, int $orden): void
    {
        if ($this->tieneValores($idAtributo)) {
            $consulta = $this->conexion->prepare(
                'UPDATE tbtipocomponenteatributo SET tbtipocomponenteatributoactivo = 0, tbtipocomponenteatributoorden = ?
                 WHERE tbtipocomponenteatributoid = ?'
            );
            $consulta->execute([$orden, $idAtributo]);
            return;
        }

        $consulta = $this->conexion->prepare(
            'DELETE FROM tbtipocomponenteatributoopcion WHERE tbtipocomponenteatributoopciontipocomponenteatributoid = ?'
        );
        $consulta->execute([$idAtributo]);
        $consulta = $this->conexion->prepare('DELETE FROM tbtipocomponenteatributo WHERE tbtipocomponenteatributoid = ?');
        $consulta->execute([$idAtributo]);
    }

    /** @param TipoComponenteAtributoOpcion[] $opciones */
    private function guardarOpciones(int $idAtributo, array $opciones): void
    {
        $idsActuales = $this->listarIds(
            'SELECT tbtipocomponenteatributoopcionid FROM tbtipocomponenteatributoopcion
             WHERE tbtipocomponenteatributoopciontipocomponenteatributoid = ?',
            $idAtributo
        );
        $idsGuardados = [];

        $insertar = $this->conexion->prepare(
            'INSERT INTO tbtipocomponenteatributoopcion (tbtipocomponenteatributoopcionid,
                tbtipocomponenteatributoopciontipocomponenteatributoid, tbtipocomponenteatributoopcionvalor,
                tbtipocomponenteatributoopcionactivo)
             VALUES (?, ?, ?, ?)'
        );
        $actualizar = $this->conexion->prepare(
            'UPDATE tbtipocomponenteatributoopcion SET tbtipocomponenteatributoopcionvalor = ?, tbtipocomponenteatributoopcionactivo = ?
             WHERE tbtipocomponenteatributoopcionid = ?'
        );

        foreach ($opciones as $opcion) {
            $valor = UsuarioRepositorio::limpiarEspacios($opcion->getValor());
            if ($opcion->getIdOpcion() !== null && in_array($opcion->getIdOpcion(), $idsActuales, true)) {
                $actualizar->execute([$valor, $opcion->getActivo() ? 1 : 0, $opcion->getIdOpcion()]);
            } else {
                $idOpcion = BaseDatos::generarId('tbtipocomponenteatributoopcion', 'tbtipocomponenteatributoopcionid');
                $insertar->execute([$idOpcion, $idAtributo, $valor, $opcion->getActivo() ? 1 : 0]);
                $opcion->setIdOpcion($idOpcion);
            }
            $opcion->setIdAtributo($idAtributo);
            $idsGuardados[] = $opcion->getIdOpcion();
        }

        $usada = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbcomponentevalor WHERE tbcomponentevalortipocomponenteatributoid = ? AND tbcomponentevalorvalor = ?'
        );
        foreach (array_diff($idsActuales, $idsGuardados) as $idOpcion) {
            $usada->execute([$idAtributo, (string) $idOpcion]);
            $sql = (int) $usada->fetchColumn() > 0
                ? 'UPDATE tbtipocomponenteatributoopcion SET tbtipocomponenteatributoopcionactivo = 0 WHERE tbtipocomponenteatributoopcionid = ?'
                : 'DELETE FROM tbtipocomponenteatributoopcion WHERE tbtipocomponenteatributoopcionid = ?';
            $this->conexion->prepare($sql)->execute([$idOpcion]);
        }
    }

    /** @return int[] */
    private function listarIds(string $sql, int $id): array
    {
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([$id]);

        return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
    }

    private static function textoOVacio(?string $texto): ?string
    {
        $texto = trim((string) $texto);
        return $texto === '' ? null : $texto;
    }
}