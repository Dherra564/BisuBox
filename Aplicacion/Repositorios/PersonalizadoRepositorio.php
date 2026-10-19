<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Modelos\PedidoPago;
use Aplicacion\Modelos\Personalizado;
use Aplicacion\Modelos\PersonalizadoComponente;
use Aplicacion\Modelos\PersonalizadoExtra;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Rol;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class PersonalizadoRepositorio
{
    public const POR_PAGINA = 10;

    private const CONSULTA_BASE =
        'SELECT p.*, v.tbvendedortiendanombre, v.tbvendedortiendaenlace,
            u.tbusuarionombrecompleto, u.tbusuariotelefono, u.tbusuariocorreo, pr.tbproductofoto
         FROM tbpersonalizado p
         INNER JOIN tbvendedor v ON v.tbvendedorid = p.tbpersonalizadovendedorid
         INNER JOIN tbcliente c ON c.tbclienteid = p.tbpersonalizadoclienteid
         INNER JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid
         LEFT JOIN tbproducto pr ON pr.tbproductoid = p.tbpersonalizadoproductoid';

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function crear(Personalizado $personalizado): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idPersonalizado = BaseDatos::generarId('tbpersonalizado', 'tbpersonalizadoid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbpersonalizado (tbpersonalizadoid, tbpersonalizadovendedorid, tbpersonalizadoclienteid,
                    tbpersonalizadoproductoid, tbpersonalizadonombre, tbpersonalizadopiezas, tbpersonalizadonotacliente,
                    tbpersonalizadoestado, tbpersonalizadominutos, tbpersonalizadotarifahora, tbpersonalizadocostocomponentes,
                    tbpersonalizadocostomanoobra, tbpersonalizadocostoextras, tbpersonalizadocostototal, tbpersonalizadoganancia,
                    tbpersonalizadoprecio, tbpersonalizadototal, tbpersonalizadonotatienda, tbpersonalizadocanceladopor,
                    tbpersonalizadoavisovisto, tbpersonalizadopedidoid, tbpersonalizadoregistrofecha,
                    tbpersonalizadocotizadofecha, tbpersonalizadorespuestafecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NULL, NULL, NULL, NULL, NULL, 1, NULL, ?, NULL, NULL)'
            );
            $consulta->execute([
                $idPersonalizado,
                $personalizado->getIdVendedor(),
                $personalizado->getIdCliente(),
                $personalizado->getIdProducto(),
                $personalizado->getNombre(),
                $personalizado->getPiezas(),
                $personalizado->getNotaCliente(),
                $personalizado->getEstado(),
                $personalizado->getMinutos(),
                $personalizado->getTarifaHora(),
                $personalizado->getCostoComponentes(),
                $personalizado->getCostoManoObra(),
                $personalizado->getFechaRegistro()->format('Y-m-d H:i:s'),
            ]);

            $insertar = $this->conexion->prepare(
                'INSERT INTO tbpersonalizadocomponente (tbpersonalizadocomponenteid, tbpersonalizadocomponentepersonalizadoid,
                    tbpersonalizadocomponentecomponenteid, tbpersonalizadocomponenteoriginalid,
                    tbpersonalizadocomponentecantidad, tbpersonalizadocomponentecostounitario)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($personalizado->getComponentes() as $linea) {
                $idLinea = BaseDatos::generarId('tbpersonalizadocomponente', 'tbpersonalizadocomponenteid');
                $insertar->execute([
                    $idLinea,
                    $idPersonalizado,
                    $linea->getIdComponente(),
                    $linea->getIdComponenteOriginal(),
                    $linea->getCantidad(),
                    $linea->getCostoUnitario(),
                ]);
                $linea->setIdPersonalizadoComponente($idLinea);
                $linea->setIdPersonalizado($idPersonalizado);
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

        $personalizado->setIdPersonalizado($idPersonalizado);

        return $idPersonalizado;
    }

    public function buscarParaCliente(int $idPersonalizado, int $idCliente): ?Personalizado
    {
        return $this->buscar('p.tbpersonalizadoid = ? AND p.tbpersonalizadoclienteid = ?', [$idPersonalizado, $idCliente]);
    }

    public function buscarParaVendedor(int $idPersonalizado, int $idVendedor): ?Personalizado
    {
        return $this->buscar('p.tbpersonalizadoid = ? AND p.tbpersonalizadovendedorid = ?', [$idPersonalizado, $idVendedor]);
    }

    public function listar(array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->construirFiltros($filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            self::CONSULTA_BASE . $condiciones
            . ' ORDER BY p.tbpersonalizadoregistrofecha DESC, p.tbpersonalizadoid DESC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);
        $personalizados = array_map(fn(array $fila): Personalizado => $this->crearDesdeFila($fila), $consulta->fetchAll());
        $this->cargarComponentes($personalizados);

        return $personalizados;
    }

    public function contar(array $filtros): int
    {
        [$condiciones, $valores] = $this->construirFiltros($filtros);
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbpersonalizado p' . $condiciones);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    public function avisosSinVer(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            self::CONSULTA_BASE . ' WHERE p.tbpersonalizadovendedorid = ? AND p.tbpersonalizadoavisovisto = 0
             ORDER BY p.tbpersonalizadorespuestafecha DESC'
        );
        $consulta->execute([$idVendedor]);

        return array_map(fn(array $fila): Personalizado => $this->crearDesdeFila($fila), $consulta->fetchAll());
    }

    public function contarAvisosSinVer(int $idVendedor): int
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbpersonalizado WHERE tbpersonalizadovendedorid = ? AND tbpersonalizadoavisovisto = 0'
        );
        $consulta->execute([$idVendedor]);

        return (int) $consulta->fetchColumn();
    }

    public function marcarAvisosVistos(int $idVendedor): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbpersonalizado SET tbpersonalizadoavisovisto = 1 WHERE tbpersonalizadovendedorid = ? AND tbpersonalizadoavisovisto = 0'
        );
        $consulta->execute([$idVendedor]);
    }

    public function cotizar(Personalizado $personalizado): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $this->exigirEstado($personalizado, [EstadoPersonalizado::EN_REVISION], 'Este pedido ya fue cotizado o cancelado.');

            $consulta = $this->conexion->prepare(
                'UPDATE tbpersonalizado SET tbpersonalizadoestado = ?, tbpersonalizadocostocomponentes = ?,
                    tbpersonalizadocostoextras = ?, tbpersonalizadocostototal = ?, tbpersonalizadoganancia = ?,
                    tbpersonalizadoprecio = ?, tbpersonalizadototal = ?, tbpersonalizadonotatienda = ?,
                    tbpersonalizadocotizadofecha = ?
                 WHERE tbpersonalizadoid = ?'
            );
            $consulta->execute([
                EstadoPersonalizado::COTIZADO,
                $personalizado->getCostoComponentes(),
                $personalizado->getCostoExtras(),
                $personalizado->getCostoTotal(),
                $personalizado->getGanancia(),
                $personalizado->getPrecio(),
                $personalizado->getTotal(),
                $personalizado->getNotaTienda(),
                $personalizado->getFechaCotizado()?->format('Y-m-d H:i:s'),
                $personalizado->getIdPersonalizado(),
            ]);

            $actualizarLinea = $this->conexion->prepare(
                'UPDATE tbpersonalizadocomponente SET tbpersonalizadocomponentecostounitario = ? WHERE tbpersonalizadocomponenteid = ?'
            );
            foreach ($personalizado->getComponentes() as $linea) {
                $actualizarLinea->execute([$linea->getCostoUnitario(), $linea->getIdPersonalizadoComponente()]);
            }

            $this->conexion->prepare('DELETE FROM tbpersonalizadoextra WHERE tbpersonalizadoextrapersonalizadoid = ?')
                ->execute([$personalizado->getIdPersonalizado()]);
            $insertarExtra = $this->conexion->prepare(
                'INSERT INTO tbpersonalizadoextra (tbpersonalizadoextraid, tbpersonalizadoextrapersonalizadoid,
                    tbpersonalizadoextraconcepto, tbpersonalizadoextramonto)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($personalizado->getExtras() as $extra) {
                $insertarExtra->execute([
                    BaseDatos::generarId('tbpersonalizadoextra', 'tbpersonalizadoextraid'),
                    $personalizado->getIdPersonalizado(),
                    $extra->getConcepto(),
                    $extra->getMonto(),
                ]);
            }

            $personalizado->setEstado(EstadoPersonalizado::COTIZADO);
            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    public function cancelar(Personalizado $personalizado, string $canceladoPor, ?string $notaTienda = null): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $this->exigirEstado(
                $personalizado,
                [EstadoPersonalizado::EN_REVISION, EstadoPersonalizado::COTIZADO],
                'Este pedido ya no se puede cancelar.'
            );

            $personalizado->setEstado(EstadoPersonalizado::CANCELADO, $canceladoPor);
            if ($canceladoPor === Rol::VENDEDOR) {
                $personalizado->setNotaTienda($notaTienda);
            }

            $consulta = $this->conexion->prepare(
                'UPDATE tbpersonalizado SET tbpersonalizadoestado = ?, tbpersonalizadocanceladopor = ?,
                    tbpersonalizadoavisovisto = ?, tbpersonalizadonotatienda = ?, tbpersonalizadorespuestafecha = ?
                 WHERE tbpersonalizadoid = ?'
            );
            $consulta->execute([
                EstadoPersonalizado::CANCELADO,
                $canceladoPor,
                $canceladoPor === Rol::CLIENTE ? 0 : 1,
                $personalizado->getNotaTienda(),
                $personalizado->getFechaRespuesta()?->format('Y-m-d H:i:s'),
                $personalizado->getIdPersonalizado(),
            ]);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    public function comprar(Personalizado $personalizado, Pedido $pedido, ?PedidoPago $pago = null): int
    {
        BaseDatos::iniciarTransaccion();

        try {
            $this->exigirEstado($personalizado, [EstadoPersonalizado::COTIZADO], 'Este pedido ya no está esperando su respuesta.');

            $idPedido = (new PedidoRepositorio())->crear($pedido, $pago);
            $personalizado->setEstado(EstadoPersonalizado::COMPRADO);
            $personalizado->setIdPedido($idPedido);

            $consulta = $this->conexion->prepare(
                'UPDATE tbpersonalizado SET tbpersonalizadoestado = ?, tbpersonalizadopedidoid = ?, tbpersonalizadorespuestafecha = ?
                 WHERE tbpersonalizadoid = ?'
            );
            $consulta->execute([
                EstadoPersonalizado::COMPRADO,
                $idPedido,
                $personalizado->getFechaRespuesta()?->format('Y-m-d H:i:s'),
                $personalizado->getIdPersonalizado(),
            ]);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }

        return $idPedido;
    }

    public function cancelarPorPedido(int $idPersonalizado, string $canceladoPor): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE tbpersonalizado SET tbpersonalizadoestado = ?, tbpersonalizadocanceladopor = ?,
                tbpersonalizadoavisovisto = 1, tbpersonalizadorespuestafecha = ?
             WHERE tbpersonalizadoid = ? AND tbpersonalizadoestado = ?'
        );
        $consulta->execute([
            EstadoPersonalizado::CANCELADO,
            $canceladoPor,
            (new DateTime())->format('Y-m-d H:i:s'),
            $idPersonalizado,
            EstadoPersonalizado::COMPRADO,
        ]);
    }

    public function necesidades(int $idPersonalizado): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT pc.tbpersonalizadocomponentecomponenteid AS componente, c.tbcomponentenombre AS nombre,
                SUM(pc.tbpersonalizadocomponentecantidad * p.tbpersonalizadopiezas) AS cantidad
             FROM tbpersonalizadocomponente pc
             INNER JOIN tbpersonalizado p ON p.tbpersonalizadoid = pc.tbpersonalizadocomponentepersonalizadoid
             LEFT JOIN tbcomponente c ON c.tbcomponenteid = pc.tbpersonalizadocomponentecomponenteid
             WHERE pc.tbpersonalizadocomponentepersonalizadoid = ?
             GROUP BY pc.tbpersonalizadocomponentecomponenteid, c.tbcomponentenombre'
        );
        $consulta->execute([$idPersonalizado]);

        $necesidades = [];
        foreach ($consulta->fetchAll() as $fila) {
            $necesidades[(int) $fila['componente']] = [
                'nombre' => (string) ($fila['nombre'] ?? 'Un componente que ya no existe'),
                'cantidad' => (float) $fila['cantidad'],
            ];
        }
        return $necesidades;
    }

    private function buscar(string $condicion, array $valores): ?Personalizado
    {
        $consulta = $this->conexion->prepare(self::CONSULTA_BASE . ' WHERE ' . $condicion);
        $consulta->execute($valores);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }

        $personalizado = $this->crearDesdeFila($fila);
        $this->cargarComponentes([$personalizado]);

        return $personalizado;
    }

    private function cargarComponentes(array $personalizados): void
    {
        if ($personalizados === []) {
            return;
        }
        $ids = array_map(fn(Personalizado $personalizado): int => (int) $personalizado->getIdPersonalizado(), $personalizados);
        $marcas = implode(', ', array_fill(0, count($ids), '?'));

        $consulta = $this->conexion->prepare(
            "SELECT pc.*, c.tbcomponentenombre, c.tbcomponenteunidad, c.tbcomponentefoto, c.tbcomponenteexistencia,
                c.tbcomponentecostounitario, c.tbcomponenteactivo, t.tbtipocomponentenombre,
                o.tbcomponentenombre AS nombreoriginal, o.tbcomponentefoto AS fotooriginal
             FROM tbpersonalizadocomponente pc
             LEFT JOIN tbcomponente c ON c.tbcomponenteid = pc.tbpersonalizadocomponentecomponenteid
             LEFT JOIN tbtipocomponente t ON t.tbtipocomponenteid = c.tbcomponentetipocomponenteid
             LEFT JOIN tbcomponente o ON o.tbcomponenteid = pc.tbpersonalizadocomponenteoriginalid
             WHERE pc.tbpersonalizadocomponentepersonalizadoid IN ({$marcas})
             ORDER BY pc.tbpersonalizadocomponenteid ASC"
        );
        $consulta->execute($ids);

        $porPersonalizado = [];
        foreach ($consulta->fetchAll() as $fila) {
            $linea = new PersonalizadoComponente(
                (int) $fila['tbpersonalizadocomponenteid'],
                (int) $fila['tbpersonalizadocomponentepersonalizadoid'],
                (int) $fila['tbpersonalizadocomponentecomponenteid'],
                (int) $fila['tbpersonalizadocomponenteoriginalid'],
                (float) $fila['tbpersonalizadocomponentecantidad'],
                (float) $fila['tbpersonalizadocomponentecostounitario']
            );
            $activo = (bool) ($fila['tbcomponenteactivo'] ?? false);
            $linea->setDatosComponente(
                (string) ($fila['tbcomponentenombre'] ?? 'Componente eliminado'),
                (string) ($fila['nombreoriginal'] ?? 'Componente eliminado'),
                (string) ($fila['tbtipocomponentenombre'] ?? ''),
                (string) ($fila['tbcomponenteunidad'] ?? ''),
                $fila['tbcomponentefoto'] ?? null,
                $fila['fotooriginal'] ?? null,
                $activo ? (float) $fila['tbcomponenteexistencia'] : 0.0,
                (float) ($fila['tbcomponentecostounitario'] ?? 0)
            );
            $porPersonalizado[(int) $fila['tbpersonalizadocomponentepersonalizadoid']][] = $linea;
        }

        $consulta = $this->conexion->prepare(
            "SELECT * FROM tbpersonalizadoextra WHERE tbpersonalizadoextrapersonalizadoid IN ({$marcas}) ORDER BY tbpersonalizadoextraid ASC"
        );
        $consulta->execute($ids);
        $extras = [];
        foreach ($consulta->fetchAll() as $fila) {
            $extras[(int) $fila['tbpersonalizadoextrapersonalizadoid']][] = new PersonalizadoExtra(
                (int) $fila['tbpersonalizadoextraid'],
                (int) $fila['tbpersonalizadoextrapersonalizadoid'],
                (string) $fila['tbpersonalizadoextraconcepto'],
                (float) $fila['tbpersonalizadoextramonto']
            );
        }

        foreach ($personalizados as $personalizado) {
            $id = (int) $personalizado->getIdPersonalizado();
            $personalizado->setComponentes($porPersonalizado[$id] ?? []);
            $personalizado->setGuardado(
                $personalizado->getCostoExtras(),
                $personalizado->getCostoTotal(),
                $personalizado->getGanancia(),
                $personalizado->getPrecio(),
                $personalizado->getTotal(),
                $personalizado->getNotaTienda(),
                $personalizado->getCanceladoPor(),
                $personalizado->getAvisoVisto(),
                $personalizado->getIdPedido(),
                $personalizado->getFechaCotizado(),
                $personalizado->getFechaRespuesta(),
                $extras[$id] ?? []
            );
        }
    }

    private function exigirEstado(Personalizado $personalizado, array $permitidos, string $mensaje): string
    {
        $consulta = $this->conexion->prepare('SELECT tbpersonalizadoestado FROM tbpersonalizado WHERE tbpersonalizadoid = ? FOR UPDATE');
        $consulta->execute([$personalizado->getIdPersonalizado()]);
        $estado = (string) $consulta->fetchColumn();

        if (!in_array($estado, $permitidos, true)) {
            throw new OperacionNoPermitida($mensaje);
        }

        return $estado;
    }

    private function construirFiltros(array $filtros): array
    {
        $condiciones = [];
        $valores = [];

        if (isset($filtros['idVendedor'])) {
            $condiciones[] = 'p.tbpersonalizadovendedorid = ?';
            $valores[] = (int) $filtros['idVendedor'];
        }
        if (isset($filtros['idCliente'])) {
            $condiciones[] = 'p.tbpersonalizadoclienteid = ?';
            $valores[] = (int) $filtros['idCliente'];
        }
        if ($condiciones === []) {
            $condiciones[] = '1 = 0';
        }

        $estado = $filtros['estado'] ?? null;
        if (EstadoPersonalizado::existe($estado)) {
            $condiciones[] = 'p.tbpersonalizadoestado = ?';
            $valores[] = $estado;
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    private function crearDesdeFila(array $fila): Personalizado
    {
        $personalizado = new Personalizado(
            (int) $fila['tbpersonalizadoid'],
            (int) $fila['tbpersonalizadovendedorid'],
            (int) $fila['tbpersonalizadoclienteid'],
            (int) $fila['tbpersonalizadoproductoid'],
            (string) $fila['tbpersonalizadonombre'],
            (int) $fila['tbpersonalizadopiezas'],
            $fila['tbpersonalizadonotacliente'],
            (string) $fila['tbpersonalizadoestado'],
            (int) $fila['tbpersonalizadominutos'],
            (float) $fila['tbpersonalizadotarifahora'],
            (float) $fila['tbpersonalizadocostocomponentes'],
            (float) $fila['tbpersonalizadocostomanoobra'],
            new DateTime($fila['tbpersonalizadoregistrofecha'])
        );
        $personalizado->setGuardado(
            (float) $fila['tbpersonalizadocostoextras'],
            (float) $fila['tbpersonalizadocostototal'],
            $fila['tbpersonalizadoganancia'] !== null ? (int) $fila['tbpersonalizadoganancia'] : null,
            $fila['tbpersonalizadoprecio'] !== null ? (float) $fila['tbpersonalizadoprecio'] : null,
            $fila['tbpersonalizadototal'] !== null ? (float) $fila['tbpersonalizadototal'] : null,
            $fila['tbpersonalizadonotatienda'],
            $fila['tbpersonalizadocanceladopor'],
            (bool) $fila['tbpersonalizadoavisovisto'],
            $fila['tbpersonalizadopedidoid'] !== null ? (int) $fila['tbpersonalizadopedidoid'] : null,
            $fila['tbpersonalizadocotizadofecha'] !== null ? new DateTime($fila['tbpersonalizadocotizadofecha']) : null,
            $fila['tbpersonalizadorespuestafecha'] !== null ? new DateTime($fila['tbpersonalizadorespuestafecha']) : null,
            []
        );
        $personalizado->setDatosTienda((string) $fila['tbvendedortiendanombre'], (string) $fila['tbvendedortiendaenlace']);
        $personalizado->setDatosCliente(
            (string) $fila['tbusuarionombrecompleto'],
            (string) $fila['tbusuariotelefono'],
            (string) $fila['tbusuariocorreo']
        );
        $personalizado->setFotoProducto($fila['tbproductofoto'] ?? null);

        return $personalizado;
    }
}