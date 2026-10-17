<?php
/**

 * @var string $estado
 * @var array<string, int> $conteos
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var string $urlBase
 */

use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UsuarioActual;

$titulo = UsuarioActual::esVendedor() ? 'Mis compras' : 'Mis pedidos';
$paginaActual = 'misPedidos';
require __DIR__ . '/../Plantilla/encabezado.php';

$pestanas = [
    '' => 'Todos',
    EstadoPedido::SOLICITADO => 'Solicitados',
    EstadoPedido::COTIZADO => 'Cotizados',
    EstadoPedido::PENDIENTE => 'Pendientes',
    EstadoPedido::CONFIRMADO => 'Confirmados',
    EstadoPedido::ENTREGADO => 'Entregados',
    EstadoPedido::CANCELADO => 'Cancelados',
];
?>

<?php if ($conteos[''] === 0): ?>
    <div class="estadoVacio">
        <p>Todavía no ha hecho pedidos.</p>
        <p><a href="<?= $urlBase ?>/" class="boton">Ver productos</a></p>
    </div>
<?php else: ?>
    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de los pedidos">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?= $urlBase ?>/mis-pedidos<?= $codigo !== '' ? '?estado=' . $codigo : '' ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($pedidos === []): ?>
            <p class="estadoVacio estadoVacioPequeno">No tiene pedidos en este estado.</p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Tienda</th>
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
                                    <span><strong>#<?= $pedido->getIdPedido() ?></strong><br>
                                        <span class="textoAyuda"><?= $pedido->getFecha()->format('d/m/Y H:i') ?></span></span>
                                </td>
                                <td data-etiqueta="Tienda"><?= htmlspecialchars($pedido->getNombreTienda()) ?></td>
                                <td data-etiqueta="Productos">
                                    <?= $pedido->esPersonalizado() ? 'Personalizado' : ($pedido->getUnidades() === 1 ? '1 producto' : $pedido->getUnidades() . ' productos') ?>
                                </td>
                                <td data-etiqueta="Total" class="numero">
                                    <strong><?= $pedido->esPersonalizado() && $pedido->getTotal() <= 0 ? 'Por cotizar' : Numero::moneda($pedido->getTotal()) ?></strong>
                                </td>
                                <td data-etiqueta="Estado">
                                    <span
                                        class="etiqueta <?= EstadoPedido::claseEtiqueta($pedido->getEstado()) ?>"><?= $pedido->getEstado() ?></span>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?= $urlBase ?>/mis-pedidos/detalle?id=<?= $pedido->getIdPedido() ?>">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $rutaPaginacion = '/mis-pedidos';
            $parametrosPaginacion = ['estado' => $estado];
            $nombreRegistros = 'pedidos';
            require __DIR__ . '/../Plantilla/paginacion.php';
            ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>