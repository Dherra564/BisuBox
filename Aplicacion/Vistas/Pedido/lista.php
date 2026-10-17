<?php
/**

* @var string $estado
* @var string $busqueda
* @var int|null $idCliente
* @var string|null $nombreCliente
* @var array<string, int> $conteos
* @var bool $hayPedidos
* @var int $pagina
* @var int $totalPaginas
* @var int $total
* @var int $porPagina
* @var string $urlBase
*/

use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Numero;

$titulo = 'Pedidos';
$paginaActual = 'pedidos';
require __DIR__ . '/../Plantilla/encabezado.php';

$pestanas = [
    '' => 'Todos',
    EstadoPedido::SOLICITADO => 'Por cotizar',
    EstadoPedido::COTIZADO => 'Cotizados',
    EstadoPedido::PENDIENTE => 'Por confirmar',
    EstadoPedido::CONFIRMADO => 'Confirmados',
    EstadoPedido::ENTREGADO => 'Entregados',
    EstadoPedido::CANCELADO => 'Cancelados',
];
$parametros = array_filter(['busqueda' => $busqueda, 'cliente' => $idCliente], fn($valor): bool => $valor !== '' && $valor !== null);
$enlace = fn(array $cambios): string => htmlspecialchars($urlBase . '/pedidos'
    . (($consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== '' && $valor !== null)) !== [] ? '?' . http_build_query($consulta) : ''));
?>

<?php if (!$hayPedidos): ?>
    <div class="estadoVacio">
        <p>Todavía no ha recibido pedidos. Comparta el enlace de su tienda para que sus clientes le compren.</p>
        <p><a href="<?php echo $urlBase ?>/tienda" class="boton">Ver el enlace de mi tienda</a></p>
    </div>
<?php else: ?>
    <?php if ($conteos[EstadoPedido::PENDIENTE] > 0): ?>
        <p class="introPagina">
            Tiene
            <strong><?php echo $conteos[EstadoPedido::PENDIENTE] === 1 ? '1 pedido' : $conteos[EstadoPedido::PENDIENTE] . ' pedidos' ?>
                por confirmar</strong>.
            Al confirmar uno se rebaja de la existencia lo que lleva.
        </p>
    <?php endif; ?>

    <?php if ($nombreCliente !== null): ?>
        <p class="cajaInformativa">
            Pedidos de <strong><?php echo htmlspecialchars($nombreCliente) ?></strong>.
            <a href="<?php echo $enlace(['cliente' => null]) ?>">Ver los de todos los clientes</a>
        </p>
    <?php endif; ?>

    <form method="get" action="<?php echo $urlBase ?>/pedidos" class="filtros">
        <?php if ($estado !== ''): ?><input type="hidden" name="estado" value="<?php echo $estado ?>"><?php endif; ?>
        <?php if ($idCliente !== null): ?><input type="hidden" name="cliente"
                value="<?php echo $idCliente ?>"><?php endif; ?>
        <div class="campo">
            <label for="busqueda">Buscar</label>
            <input type="search" id="busqueda" name="busqueda" maxlength="100"
                placeholder="Cliente, correo o número de pedido" value="<?php echo htmlspecialchars($busqueda) ?>">
        </div>
        <div class="filtrosBotones">
            <button type="submit" class="boton">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="<?php echo $enlace(['busqueda' => null, 'estado' => $estado]) ?>"
                    class="boton botonSecundario">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de los pedidos">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?php echo $enlace(['estado' => $codigo]) ?>"
                    class="pestana <?php echo $estado === $codigo ? 'pestanaActiva' : '' ?>" <?php echo $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?php echo $nombre ?> <span class="pestanaCantidad"><?php echo $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($pedidos === []): ?>
            <p class="estadoVacio estadoVacioPequeno">No hay pedidos con estos filtros.</p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Productos</th>
                            <th class="numero">Total</th>
                            <th>Estado</th>
                            <th><span class="soloLectores">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td data-etiqueta="Pedido">
                                    <span><strong>#<?php echo $pedido->getIdPedido() ?></strong><br>
                                        <span
                                            class="textoAyuda"><?php echo $pedido->getFecha()->format('d/m/Y H:i') ?></span></span>
                                </td>
                                <td data-etiqueta="Cliente"><?php echo htmlspecialchars($pedido->getNombreCliente()) ?></td>
                                <td data-etiqueta="Productos">
                                    <span><?php echo $pedido->esPersonalizado() ? 'Pedido personalizado' : htmlspecialchars(implode(', ', array_map(
                                        fn($detalle): string => $detalle->getCantidad() . ' × ' . $detalle->getNombreProducto(),
                                        $pedido->getDetalles()
                                    ))) ?></span>
                                </td>
                                <td data-etiqueta="Total" class="numero">
                                    <strong><?php echo $pedido->esPersonalizado() && $pedido->getTotal() <= 0 ? 'Por cotizar' : Numero::moneda($pedido->getTotal()) ?></strong>
                                </td>
                                <td data-etiqueta="Estado">
                                    <span
                                        class="etiqueta <?php echo EstadoPedido::claseEtiqueta($pedido->getEstado()) ?>"><?php echo $pedido->getEstado() ?></span>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?php echo $urlBase ?>/pedidos/detalle?id=<?php echo $pedido->getIdPedido() ?>">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $rutaPaginacion = '/pedidos';
            $parametrosPaginacion = $parametros + ['estado' => $estado];
            $nombreRegistros = 'pedidos';
            require __DIR__ . '/../Plantilla/paginacion.php';
            ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>