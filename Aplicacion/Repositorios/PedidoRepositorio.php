<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Modelos\PedidoDetalle;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Rol;
use Configuracion\BaseDatos;
use DateTime;
use PDO;
use Throwable;


class PedidoRepositorio
{
    public const POR_PAGINA = 10;

    private const CONSULTA_BASE =
        'SELECT p.*, v.tbvendedortiendanombre, v.tbvendedortiendaenlace,
            u.tbusuarionombrecompleto, u.tbusuariotelefono, u.tbusuariocorreo
         FROM tbpedido p
         INNER JOIN tbvendedor v ON v.tbvendedorid = p.tbpedidovendedorid
         INNER JOIN tbcliente c ON c.tbclienteid = p.tbpedidoclienteid
         INNER JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid';

    private PDO $conexion;
    private ProductoRepositorio $productoRepositorio;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
        $this->productoRepositorio = new ProductoRepositorio();
    }

    public function crear(Pedido $pedido): int
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $idPedido = BaseDatos::generarId('tbpedido', 'tbpedidoid');

            $consulta = $this->conexion->prepare(
                'INSERT INTO tbpedido (tbpedidoid, tbpedidovendedorid, tbpedidoclienteid, tbpedidofecha, tbpedidoentrega,
                    tbpedidodireccion, tbpedidometodopago, tbpedidonota, tbpedidototal, tbpedidoestado,
                    tbpedidocanceladopor, tbpedidoactualizacionfecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)'
            );
            $consulta->execute([
                $idPedido,
                $pedido->getIdVendedor(),
                $pedido->getIdCliente(),
                $pedido->getFecha()->format('Y-m-d H:i:s'),
                $pedido->getEntrega(),
                $pedido->getDireccion(),
                $pedido->getMetodoPago(),
                $pedido->getNota(),
                $pedido->getTotal(),
                $pedido->getEstado(),
            ]);

            $insertarDetalle = $this->conexion->prepare(
                'INSERT INTO tbpedidodetalle (tbpedidodetalleid, tbpedidodetallepedidoid, tbpedidodetalleproductoid,
                    tbpedidodetallenombre, tbpedidodetallecantidad, tbpedidodetalleprecio, tbpedidodetallesubtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($pedido->getDetalles() as $detalle) {
                $idDetalle = BaseDatos::generarId('tbpedidodetalle', 'tbpedidodetalleid');
                $insertarDetalle->execute([
                    $idDetalle,
                    $idPedido,
                    $detalle->getIdProducto(),
                    $detalle->getNombreProducto(),
                    $detalle->getCantidad(),
                    $detalle->getPrecio(),
                    $detalle->getSubtotal(),
                ]);
                $detalle->setIdPedidoDetalle($idDetalle);
                $detalle->setIdPedido($idPedido);
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

        $pedido->setIdPedido($idPedido);

        return $idPedido;
    }

    public function buscarParaVendedor(int $idPedido, int $idVendedor): ?Pedido
    {
        return $this->buscar('p.tbpedidoid = ? AND p.tbpedidovendedorid = ?', [$idPedido, $idVendedor]);
    }

    public function buscarParaCliente(int $idPedido, int $idCliente): ?Pedido
    {
        return $this->buscar('p.tbpedidoid = ? AND p.tbpedidoclienteid = ?', [$idPedido, $idCliente]);
    }

    /**
     * @return Pedido[]
     */
    public function listar(array $filtros, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->construirFiltros($filtros);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            self::CONSULTA_BASE . $condiciones
            . ' ORDER BY p.tbpedidofecha DESC, p.tbpedidoid DESC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);
        $pedidos = array_map(fn(array $fila): Pedido => $this->crearDesdeFila($fila), $consulta->fetchAll());

        $this->cargarDetalles($pedidos);

        return $pedidos;
    }

    public function contar(array $filtros): int
    {
        [$condiciones, $valores] = $this->construirFiltros($filtros);

        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM tbpedido p
             INNER JOIN tbcliente c ON c.tbclienteid = p.tbpedidoclienteid
             INNER JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid' . $condiciones
        );
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    
    public function confirmar(Pedido $pedido): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $this->exigirEstado($pedido, [EstadoPedido::PENDIENTE], 'Este pedido ya no está pendiente.');

            $faltantes = [];
            $nuevasExistencias = [];
            foreach ($pedido->getDetalles() as $detalle) {
                $producto = $this->productoRepositorio->bloquearExistencia($detalle->getIdProducto(), $pedido->getIdVendedor());
                $hay = $producto['existencia'] ?? 0;
                $yaApartado = $nuevasExistencias[$detalle->getIdProducto()] ?? $hay;

                if ($producto === null || $yaApartado < $detalle->getCantidad()) {
                    $faltantes[] = '«' . $detalle->getNombreProducto() . '» (tiene ' . max(0, $yaApartado)
                        . ', el pedido lleva ' . $detalle->getCantidad() . ')';
                    continue;
                }
                $nuevasExistencias[$detalle->getIdProducto()] = $yaApartado - $detalle->getCantidad();
            }

            if ($faltantes !== []) {
                throw new OperacionNoPermitida(
                    'No hay suficiente existencia de ' . implode(', ', $faltantes)
                    . '. Registre lo que hizo en Productos con «Hice más» o cancele el pedido.'
                );
            }

            foreach ($nuevasExistencias as $idProducto => $existencia) {
                $this->productoRepositorio->actualizarExistencia($idProducto, $pedido->getIdVendedor(), $existencia);
            }
            $this->guardarEstado($pedido, EstadoPedido::CONFIRMADO, null);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    
    public function cancelar(Pedido $pedido, string $canceladoPor): void
    {
        $permitidos = $canceladoPor === Rol::CLIENTE
            ? [EstadoPedido::PENDIENTE]
            : [EstadoPedido::PENDIENTE, EstadoPedido::CONFIRMADO];
        $mensaje = $canceladoPor === Rol::CLIENTE
            ? 'Solo puede cancelar pedidos que la tienda todavía no ha confirmado. Escríbale a la tienda.'
            : 'Este pedido ya no se puede cancelar.';

        BaseDatos::iniciarTransaccion();

        try {
            $estadoAnterior = $this->exigirEstado($pedido, $permitidos, $mensaje);

            if ($estadoAnterior === EstadoPedido::CONFIRMADO) {
                foreach ($pedido->getDetalles() as $detalle) {
                    $producto = $this->productoRepositorio->bloquearExistencia($detalle->getIdProducto(), $pedido->getIdVendedor());
                    if ($producto !== null) {
                        $this->productoRepositorio->actualizarExistencia(
                            $detalle->getIdProducto(),
                            $pedido->getIdVendedor(),
                            $producto['existencia'] + $detalle->getCantidad()
                        );
                    }
                }
            }
            $this->guardarEstado($pedido, EstadoPedido::CANCELADO, $canceladoPor);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    public function entregar(Pedido $pedido): void
    {
        BaseDatos::iniciarTransaccion();

        try {
            $this->exigirEstado($pedido, [EstadoPedido::CONFIRMADO], 'Solo se pueden entregar pedidos confirmados.');
            $this->guardarEstado($pedido, EstadoPedido::ENTREGADO, null);

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $error) {
            BaseDatos::revertirTransaccion();
            throw $error;
        }
    }

    /**
     
     * @return array<int, array{idCliente: int, nombre: string, telefono: string, correo: string, pedidos: int, entregados: int, totalComprado: float, ultimoPedido: DateTime}>
     */
    public function listarClientes(int $idVendedor, string $busqueda, int $pagina = 1): array
    {
        [$condiciones, $valores] = $this->filtrosClientes($idVendedor, $busqueda);
        $desplazamiento = (max(1, $pagina) - 1) * self::POR_PAGINA;

        $consulta = $this->conexion->prepare(
            "SELECT c.tbclienteid, u.tbusuarionombrecompleto, u.tbusuariotelefono, u.tbusuariocorreo,
                COUNT(*) AS pedidos,
                SUM(p.tbpedidoestado = 'Entregado') AS entregados,
                COALESCE(SUM(CASE WHEN p.tbpedidoestado = 'Entregado' THEN p.tbpedidototal END), 0) AS totalcomprado,
                MAX(p.tbpedidofecha) AS ultimopedido
             FROM tbpedido p
             INNER JOIN tbcliente c ON c.tbclienteid = p.tbpedidoclienteid
             INNER JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid" . $condiciones . '
             GROUP BY c.tbclienteid, u.tbusuarionombrecompleto, u.tbusuariotelefono, u.tbusuariocorreo
             ORDER BY ultimopedido DESC, c.tbclienteid DESC
             LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $desplazamiento
        );
        $consulta->execute($valores);

        return array_map(fn(array $fila): array => [
            'idCliente' => (int) $fila['tbclienteid'],
            'nombre' => (string) $fila['tbusuarionombrecompleto'],
            'telefono' => (string) $fila['tbusuariotelefono'],
            'correo' => (string) $fila['tbusuariocorreo'],
            'pedidos' => (int) $fila['pedidos'],
            'entregados' => (int) $fila['entregados'],
            'totalComprado' => (float) $fila['totalcomprado'],
            'ultimoPedido' => new DateTime($fila['ultimopedido']),
        ], $consulta->fetchAll());
    }

    public function contarClientes(int $idVendedor, string $busqueda = ''): int
    {
        [$condiciones, $valores] = $this->filtrosClientes($idVendedor, $busqueda);

        $consulta = $this->conexion->prepare(
            'SELECT COUNT(DISTINCT p.tbpedidoclienteid) FROM tbpedido p
             INNER JOIN tbcliente c ON c.tbclienteid = p.tbpedidoclienteid
             INNER JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid' . $condiciones
        );
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    
    public function nombreClienteDeVendedor(int $idCliente, int $idVendedor): ?string
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.tbusuarionombrecompleto FROM tbpedido p
             INNER JOIN tbcliente c ON c.tbclienteid = p.tbpedidoclienteid
             INNER JOIN tbusuario u ON u.tbusuarioid = c.tbclienteusuarioid
             WHERE p.tbpedidoclienteid = ? AND p.tbpedidovendedorid = ? LIMIT 1'
        );
        $consulta->execute([$idCliente, $idVendedor]);
        $nombre = $consulta->fetchColumn();

        return $nombre === false ? null : (string) $nombre;
    }

    private function buscar(string $condicion, array $valores): ?Pedido
    {
        $consulta = $this->conexion->prepare(self::CONSULTA_BASE . ' WHERE ' . $condicion);
        $consulta->execute($valores);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }

        $pedido = $this->crearDesdeFila($fila);
        $this->cargarDetalles([$pedido]);

        return $pedido;
    }

    /** @param Pedido[] $pedidos */
    private function cargarDetalles(array $pedidos): void
    {
        if ($pedidos === []) {
            return;
        }
        $ids = array_map(fn(Pedido $pedido): int => (int) $pedido->getIdPedido(), $pedidos);
        $marcas = implode(', ', array_fill(0, count($ids), '?'));

        $consulta = $this->conexion->prepare(
            "SELECT * FROM tbpedidodetalle WHERE tbpedidodetallepedidoid IN ({$marcas}) ORDER BY tbpedidodetalleid ASC"
        );
        $consulta->execute($ids);

        $porPedido = [];
        foreach ($consulta->fetchAll() as $fila) {
            $porPedido[(int) $fila['tbpedidodetallepedidoid']][] = new PedidoDetalle(
                (int) $fila['tbpedidodetalleid'],
                (int) $fila['tbpedidodetallepedidoid'],
                (int) $fila['tbpedidodetalleproductoid'],
                (string) $fila['tbpedidodetallenombre'],
                (int) $fila['tbpedidodetallecantidad'],
                (float) $fila['tbpedidodetalleprecio'],
                (float) $fila['tbpedidodetallesubtotal']
            );
        }

        foreach ($pedidos as $pedido) {
            $pedido->setDetalles($porPedido[(int) $pedido->getIdPedido()] ?? []);
        }
    }


    private function exigirEstado(Pedido $pedido, array $permitidos, string $mensaje): string
    {
        $consulta = $this->conexion->prepare('SELECT tbpedidoestado FROM tbpedido WHERE tbpedidoid = ? FOR UPDATE');
        $consulta->execute([$pedido->getIdPedido()]);
        $estado = (string) $consulta->fetchColumn();

        if (!in_array($estado, $permitidos, true)) {
            throw new OperacionNoPermitida($mensaje);
        }

        return $estado;
    }

    private function guardarEstado(Pedido $pedido, string $estado, ?string $canceladoPor): void
    {
        $pedido->setEstado($estado, $canceladoPor);

        $consulta = $this->conexion->prepare(
            'UPDATE tbpedido SET tbpedidoestado = ?, tbpedidocanceladopor = ?, tbpedidoactualizacionfecha = ?
             WHERE tbpedidoid = ?'
        );
        $consulta->execute([
            $estado,
            $canceladoPor,
            $pedido->getFechaActualizacion()->format('Y-m-d H:i:s'),
            $pedido->getIdPedido(),
        ]);
    }

    private function construirFiltros(array $filtros): array
    {
        $condiciones = [];
        $valores = [];

        if (isset($filtros['idVendedor'])) {
            $condiciones[] = 'p.tbpedidovendedorid = ?';
            $valores[] = (int) $filtros['idVendedor'];
        }
        if (isset($filtros['idCliente'])) {
            $condiciones[] = 'p.tbpedidoclienteid = ?';
            $valores[] = (int) $filtros['idCliente'];
        }
        if ($condiciones === []) {
            
            $condiciones[] = '1 = 0';
        }

        $estado = $filtros['estado'] ?? null;
        if (EstadoPedido::existe($estado)) {
            $condiciones[] = 'p.tbpedidoestado = ?';
            $valores[] = $estado;
        }

        $busqueda = trim((string) ($filtros['busqueda'] ?? ''));
        if ($busqueda !== '') {
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            $buscarEn = ['u.tbusuarionombrecompleto LIKE ?', 'u.tbusuariocorreo LIKE ?'];
            array_push($valores, $patron, $patron);
            if (ctype_digit($busqueda)) {
                $buscarEn[] = 'p.tbpedidoid = ?';
                $valores[] = (int) $busqueda;
            }
            $condiciones[] = '(' . implode(' OR ', $buscarEn) . ')';
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    private function filtrosClientes(int $idVendedor, string $busqueda): array
    {
        $condiciones = ['p.tbpedidovendedorid = ?'];
        $valores = [$idVendedor];

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            $condiciones[] = '(u.tbusuarionombrecompleto LIKE ? OR u.tbusuariocorreo LIKE ? OR u.tbusuariotelefono LIKE ?)';
            array_push($valores, $patron, $patron, $patron);
        }

        return [' WHERE ' . implode(' AND ', $condiciones), $valores];
    }

    private function crearDesdeFila(array $fila): Pedido
    {
        $pedido = new Pedido(
            (int) $fila['tbpedidoid'],
            (int) $fila['tbpedidovendedorid'],
            (int) $fila['tbpedidoclienteid'],
            new DateTime($fila['tbpedidofecha']),
            (string) $fila['tbpedidoentrega'],
            $fila['tbpedidodireccion'],
            (string) $fila['tbpedidometodopago'],
            $fila['tbpedidonota'],
            (float) $fila['tbpedidototal'],
            (string) $fila['tbpedidoestado'],
            $fila['tbpedidocanceladopor'],
            $fila['tbpedidoactualizacionfecha'] !== null ? new DateTime($fila['tbpedidoactualizacionfecha']) : null
        );
        $pedido->setDatosTienda((string) $fila['tbvendedortiendanombre'], (string) $fila['tbvendedortiendaenlace']);
        $pedido->setDatosCliente(
            (string) $fila['tbusuarionombrecompleto'],
            (string) $fila['tbusuariotelefono'],
            (string) $fila['tbusuariocorreo']
        );

        return $pedido;
    }
}
