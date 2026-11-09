<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Modelos\PedidoDetalle;
use Aplicacion\Modelos\PedidoPago;
use Aplicacion\Nucleo\DatosCotizacion;
use Aplicacion\Nucleo\EstadoCotizacion;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;

class CotizacionRepositorio
{
    public const POR_PAGINA = 10;

    private const SELECCION = 'SELECT c.*, v.tbvendedortiendanombre, v.tbvendedortiendaenlace,
            u.tbusuarionombrecompleto, u.tbusuariotelefono, u.tbusuariocorreo
        FROM tbcotizacion c
        INNER JOIN tbvendedor v ON v.tbvendedorid = c.tbcotizacionvendedorid
        INNER JOIN tbcliente cl ON cl.tbclienteid = c.tbcotizacionclienteid
        INNER JOIN tbusuario u ON u.tbusuarioid = cl.tbclienteusuarioid';

    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    public function crear(int $idCliente, int $idVendedor, ?string $foto, string $descripcion): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $id = BaseDatos::generarId('tbcotizacion', 'tbcotizacionid');
            $consulta = $this->conexion->prepare(
                'INSERT INTO tbcotizacion (tbcotizacionid, tbcotizacionclienteid, tbcotizacionvendedorid, tbcotizacionfoto,
                    tbcotizaciondescripcion, tbcotizacionrespuesta, tbcotizacionmanoobra, tbcotizacionprecio,
                    tbcotizacionestado, tbcotizacionregistrofecha, tbcotizacionrespuestafecha)
                 VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL, ?, ?, NULL)'
            );
            $consulta->execute([
                $id,
                $idCliente,
                $idVendedor,
                $foto,
                $descripcion,
                EstadoCotizacion::ENVIADA,
                (new DateTime())->format('Y-m-d H:i:s'),
            ]);

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
            return $id;
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }

    public function editarSolicitud(int $id, int $idCliente, string $descripcion, ?string $fotoNueva): ?string
    {
        BaseDatos::iniciarTransaccion();

        try {
            $fila = $this->bloquear($id);
            if (
                $fila === null
                || (int) $fila['tbcotizacionclienteid'] !== $idCliente
                || $fila['tbcotizacionestado'] !== EstadoCotizacion::ENVIADA
            ) {
                throw new OperacionNoPermitida(DatosCotizacion::MENSAJE_NO_EDITABLE);
            }

            $consulta = $this->conexion->prepare(
                'UPDATE tbcotizacion SET tbcotizaciondescripcion = ?, tbcotizacionfoto = COALESCE(?, tbcotizacionfoto)
                 WHERE tbcotizacionid = ?'
            );
            $consulta->execute([$descripcion, $fotoNueva, $id]);

            BaseDatos::confirmarTransaccion();
            return $fotoNueva !== null ? $fila['tbcotizacionfoto'] : null;
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    public function cancelar(int $id, int $idCliente): bool
    {
        return $this->cambiarEstado($id, 'tbcotizacionclienteid', $idCliente, EstadoCotizacion::ENVIADA, EstadoCotizacion::CANCELADA);
    }

    public function rechazar(int $id, int $idCliente): bool
    {
        return $this->cambiarEstado($id, 'tbcotizacionclienteid', $idCliente, EstadoCotizacion::RESPONDIDA, EstadoCotizacion::RECHAZADA);
    }

    public function responder(int $id, int $idVendedor, array $respuesta, string $mensaje): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $fila = $this->bloquear($id);
            if (
                $fila === null
                || (int) $fila['tbcotizacionvendedorid'] !== $idVendedor
                || !in_array($fila['tbcotizacionestado'], [EstadoCotizacion::ENVIADA, EstadoCotizacion::RESPONDIDA], true)
            ) {
                throw new OperacionNoPermitida(DatosCotizacion::MENSAJE_NO_RESPONDER);
            }

            $this->conexion->prepare(
                'UPDATE tbcotizaciondetalle SET tbcotizaciondetalleactivo = 0
                 WHERE tbcotizaciondetallecotizacionid = ? AND tbcotizaciondetalleactivo = 1'
            )->execute([$id]);

            $insertar = $this->conexion->prepare(
                'INSERT INTO tbcotizaciondetalle (tbcotizaciondetalleid, tbcotizaciondetallecotizacionid,
                    tbcotizaciondetallecomponenteid, tbcotizaciondetallecantidad, tbcotizaciondetallecostounitario,
                    tbcotizaciondetallesubtotal, tbcotizaciondetalleactivo)
                 VALUES (?, ?, ?, ?, ?, ?, 1)'
            );
            foreach ($respuesta['lineas'] as $linea) {
                $insertar->execute([
                    BaseDatos::generarId('tbcotizaciondetalle', 'tbcotizaciondetalleid'),
                    $id,
                    $linea['idComponente'],
                    $linea['cantidad'],
                    $linea['costoUnitario'],
                    $linea['subtotal'],
                ]);
            }

            $this->conexion->prepare(
                'UPDATE tbcotizacion SET tbcotizacionrespuesta = ?, tbcotizacionmanoobra = ?, tbcotizacionprecio = ?,
                    tbcotizacionestado = ?, tbcotizacionrespuestafecha = ?
                 WHERE tbcotizacionid = ?'
            )->execute([
                        $mensaje !== '' ? $mensaje : null,
                        $respuesta['manoObra'],
                        $respuesta['precio'],
                        EstadoCotizacion::RESPONDIDA,
                        (new DateTime())->format('Y-m-d H:i:s'),
                        $id,
                    ]);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    public function aceptar(int $id, int $idCliente, array $entrega, float $precioVisto, ?PedidoPago $pago): int
    {
        BaseDatos::iniciarTransaccion();

        try {
            $fila = $this->bloquear($id);
            if (
                $fila === null
                || (int) $fila['tbcotizacionclienteid'] !== $idCliente
                || $fila['tbcotizacionestado'] !== EstadoCotizacion::RESPONDIDA
            ) {
                throw new OperacionNoPermitida(DatosCotizacion::MENSAJE_NO_DECIDIR);
            }

            $precio = (float) $fila['tbcotizacionprecio'];
            if (abs($precio - $precioVisto) > 0.001) {
                throw new OperacionNoPermitida(DatosCotizacion::MENSAJE_PRECIO_CAMBIO);
            }

            $detalle = new PedidoDetalle(null, null, null, 'Pieza por cotización #' . $id, 1, $precio, $precio, idCotizacion: $id);
            $pedido = new Pedido(
                null,
                (int) $fila['tbcotizacionvendedorid'],
                $idCliente,
                new DateTime(),
                $entrega['entrega'],
                $entrega['direccion'],
                $entrega['metodoPago'],
                $entrega['nota'],
                $precio,
                EstadoPedido::CONFIRMADO,
                null,
                null,
                [$detalle]
            );
            $idPedido = (new PedidoRepositorio())->crear($pedido, $pago);

            $this->conexion->prepare(
                'UPDATE tbcotizacion SET tbcotizacionestado = ?, tbcotizacionpedidoid = ? WHERE tbcotizacionid = ?'
            )->execute([EstadoCotizacion::ACEPTADA, $idPedido, $id]);

            BaseDatos::confirmarTransaccion();
            return $idPedido;
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    public function buscarParaCliente(int $id, int $idCliente): ?array
    {
        return $this->buscar('c.tbcotizacionid = ? AND c.tbcotizacionclienteid = ?', [$id, $idCliente]);
    }

    public function buscarParaVendedor(int $id, int $idVendedor): ?array
    {
        return $this->buscar('c.tbcotizacionid = ? AND c.tbcotizacionvendedorid = ?', [$id, $idVendedor]);
    }

    public function listar(array $filtros, int $pagina = 1): array
    {
        [$donde, $valores] = $this->construirFiltros($filtros);
        $desde = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            self::SELECCION . $donde . ' ORDER BY c.tbcotizacionid DESC LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desde
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): array => $this->desdeFila($fila), $consulta->fetchAll());
    }

    public function contar(array $filtros): int
    {
        [$donde, $valores] = $this->construirFiltros($filtros);
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM tbcotizacion c' . $donde);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    public function conteosPorEstado(array $filtros): array
    {
        unset($filtros['estado']);
        [$donde, $valores] = $this->construirFiltros($filtros);
        $consulta = $this->conexion->prepare(
            'SELECT tbcotizacionestado AS estado, COUNT(*) AS total FROM tbcotizacion c' . $donde . ' GROUP BY tbcotizacionestado'
        );
        $consulta->execute($valores);

        $conteos = ['' => 0];
        foreach (EstadoCotizacion::todos() as $estado) {
            $conteos[$estado] = 0;
        }
        foreach ($consulta->fetchAll() as $fila) {
            $conteos[$fila['estado']] = (int) $fila['total'];
            $conteos[''] += (int) $fila['total'];
        }

        return $conteos;
    }

    public function contarPorResponder(int $idVendedor): int
    {
        return $this->contar(['idVendedor' => $idVendedor, 'estado' => EstadoCotizacion::ENVIADA]);
    }

    public function componentesDe(array $idsCotizacion): array
    {
        if ($idsCotizacion === []) {
            return [];
        }
        $marcas = implode(', ', array_fill(0, count($idsCotizacion), '?'));
        $consulta = $this->conexion->prepare(
            "SELECT tbcotizaciondetallecomponenteid AS componente, SUM(tbcotizaciondetallecantidad) AS cantidad
             FROM tbcotizaciondetalle
             WHERE tbcotizaciondetallecotizacionid IN ({$marcas}) AND tbcotizaciondetalleactivo = 1
             GROUP BY tbcotizaciondetallecomponenteid"
        );
        $consulta->execute(array_values($idsCotizacion));

        $necesarios = [];
        foreach ($consulta->fetchAll() as $fila) {
            $necesarios[(int) $fila['componente']] = (float) $fila['cantidad'];
        }

        return $necesarios;
    }

    public function buscarPorFoto(string $nombre): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT cl.tbclienteusuarioid AS usuariocliente, v.tbvendedorusuarioid AS usuariovendedor
             FROM tbcotizacion c
             INNER JOIN tbcliente cl ON cl.tbclienteid = c.tbcotizacionclienteid
             INNER JOIN tbvendedor v ON v.tbvendedorid = c.tbcotizacionvendedorid
             WHERE c.tbcotizacionfoto = ? LIMIT 1'
        );
        $consulta->execute([$nombre]);
        $fila = $consulta->fetch();

        return $fila ? [
            'idUsuarioCliente' => (int) $fila['usuariocliente'],
            'idUsuarioVendedor' => (int) $fila['usuariovendedor'],
        ] : null;
    }

    private function bloquear(int $id): ?array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM tbcotizacion WHERE tbcotizacionid = ? FOR UPDATE');
        $consulta->execute([$id]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    private function cambiarEstado(int $id, string $columnaDuenio, int $idDuenio, string $desde, string $hacia): bool
    {
        $consulta = $this->conexion->prepare(
            "UPDATE tbcotizacion SET tbcotizacionestado = ? WHERE tbcotizacionid = ? AND {$columnaDuenio} = ? AND tbcotizacionestado = ?"
        );
        $consulta->execute([$hacia, $id, $idDuenio, $desde]);

        return $consulta->rowCount() === 1;
    }

    private function buscar(string $condicion, array $valores): ?array
    {
        $consulta = $this->conexion->prepare(self::SELECCION . ' WHERE ' . $condicion);
        $consulta->execute($valores);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }
        $cotizacion = $this->desdeFila($fila);
        $cotizacion['detalles'] = $this->detalles($cotizacion['id']);
        $cotizacion['idPedido'] = $fila['tbcotizacionpedidoid'] !== null ? (int) $fila['tbcotizacionpedidoid'] : null;

        return $cotizacion;
    }

    private function detalles(int $idCotizacion): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT d.*, comp.tbcomponentenombre, comp.tbcomponenteunidad
             FROM tbcotizaciondetalle d
             LEFT JOIN tbcomponente comp ON comp.tbcomponenteid = d.tbcotizaciondetallecomponenteid
             WHERE d.tbcotizaciondetallecotizacionid = ? AND d.tbcotizaciondetalleactivo = 1
             ORDER BY d.tbcotizaciondetalleid ASC'
        );
        $consulta->execute([$idCotizacion]);

        return array_map(fn(array $fila): array => [
            'idComponente' => (int) $fila['tbcotizaciondetallecomponenteid'],
            'nombre' => (string) $fila['tbcomponentenombre'],
            'unidad' => $fila['tbcomponenteunidad'],
            'cantidad' => (float) $fila['tbcotizaciondetallecantidad'],
            'costoUnitario' => (float) $fila['tbcotizaciondetallecostounitario'],
            'subtotal' => (float) $fila['tbcotizaciondetallesubtotal'],
        ], $consulta->fetchAll());
    }

    private function construirFiltros(array $filtros): array
    {
        $condiciones = [];
        $valores = [];

        if (isset($filtros['idCliente'])) {
            $condiciones[] = 'c.tbcotizacionclienteid = ?';
            $valores[] = (int) $filtros['idCliente'];
        }
        if (isset($filtros['idVendedor'])) {
            $condiciones[] = 'c.tbcotizacionvendedorid = ?';
            $valores[] = (int) $filtros['idVendedor'];
        }
        if (isset($filtros['estado']) && EstadoCotizacion::existe($filtros['estado'])) {
            $condiciones[] = 'c.tbcotizacionestado = ?';
            $valores[] = $filtros['estado'];
        }

        return [$condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    private function desdeFila(array $fila): array
    {
        return [
            'id' => (int) $fila['tbcotizacionid'],
            'idCliente' => (int) $fila['tbcotizacionclienteid'],
            'idVendedor' => (int) $fila['tbcotizacionvendedorid'],
            'foto' => $fila['tbcotizacionfoto'],
            'descripcion' => (string) $fila['tbcotizaciondescripcion'],
            'respuesta' => $fila['tbcotizacionrespuesta'],
            'manoObra' => $fila['tbcotizacionmanoobra'] !== null ? (float) $fila['tbcotizacionmanoobra'] : null,
            'precio' => $fila['tbcotizacionprecio'] !== null ? (float) $fila['tbcotizacionprecio'] : null,
            'estado' => (string) $fila['tbcotizacionestado'],
            'fecha' => new DateTime($fila['tbcotizacionregistrofecha']),
            'fechaRespuesta' => $fila['tbcotizacionrespuestafecha'] !== null ? new DateTime($fila['tbcotizacionrespuestafecha']) : null,
            'nombreTienda' => (string) $fila['tbvendedortiendanombre'],
            'enlaceTienda' => (string) $fila['tbvendedortiendaenlace'],
            'nombreCliente' => (string) $fila['tbusuarionombrecompleto'],
            'telefonoCliente' => (string) $fila['tbusuariotelefono'],
            'correoCliente' => (string) $fila['tbusuariocorreo'],
        ];
    }
}