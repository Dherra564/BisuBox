<?php
/**
 
 * @var \Aplicacion\Modelos\Pedido[] $pedidos
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

$pestanas = ['' => 'Todos'] + array_combine(EstadoPedido::todos(), ['Por confirmar', 'Confirmados', 'Entregados', 'Cancelados']);
$parametros = array_filter(['busqueda' => $busqueda, 'cliente' => $idCliente], fn($valor): bool => $valor !== '' && $valor !== null);
$enlace = fn(array $cambios): string => htmlspecialchars($urlBase . '/pedidos'
    . (($consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== '' && $valor !== null)) !== [] ? '?' . http_build_query($consulta) : ''));
?>

<?php if (!$hayPedidos): ?>
    <div class="estadoVacio">
        <p>Todavía no ha recibido pedidos. Comparta el enlace de su tienda para que sus clientes le compren.</p>
        <p><a href="<?= $urlBase ?>/tienda" class="boton">Ver el enlace de mi tienda</a></p>
    </div>
<?php else: ?>
    <?php if ($conteos[EstadoPedido::PENDIENTE] > 0): ?>
        <p class="introPagina">
            Tiene <strong><?= $conteos[EstadoPedido::PENDIENTE] === 1 ? '1 pedido' : $conteos[EstadoPedido::PENDIENTE] . ' pedidos' ?> por confirmar</strong>.
            Al confirmar uno se rebaja de la existencia lo que lleva.
        </p>
    <?php endif; ?>

    <?php if ($nombreCliente !== null): ?>
        <p class="cajaInformativa">
            Pedidos de <strong><?= htmlspecialchars($nombreCliente) ?></strong>.
            <a href="<?= $enlace(['cliente' => null]) ?>">Ver los de todos los clientes</a>
        </p>
    <?php endif; ?>

    <form method="get" action="<?= $urlBase ?>/pedidos" class="filtros">
        <?php if ($estado !== ''): ?><input type="hidden" name="estado" value="<?= $estado ?>"><?php endif; ?>
        <?php if ($idCliente !== null): ?><input type="hidden" name="cliente" value="<?= $idCliente ?>"><?php endif; ?>
        <div class="campo">
            <label for="busqueda">Buscar</label>
            <input type="search" id="busqueda" name="busqueda" maxlength="100" placeholder="Cliente, correo o número de pedido"
                value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <div class="filtrosBotones">
            <button type="submit" class="boton">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="<?= $enlace(['busqueda' => null, 'estado' => $estado]) ?>" class="boton botonSecundario">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de los pedidos">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?= $enlace(['estado' => $codigo]) ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
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
                                    <span><strong>#<?= $pedido->getIdPedido() ?></strong><br>
                                        <span class="textoAyuda"><?= $pedido->getFecha()->format('d/m/Y H:i') ?></span></span>
                                </td>
                                <td data-etiqueta="Cliente"><?= htmlspecialchars($pedido->getNombreCliente()) ?></td>
                                <td data-etiqueta="Productos">
                                    <span><?= htmlspecialchars(implode(', ', array_map(
                                        fn($detalle): string => $detalle->getCantidad() . ' × ' . $detalle->getNombreProducto(),
                                        $pedido->getDetalles()
                                    ))) ?></span>
                                </td>
                                <td data-etiqueta="Total" class="numero"><strong><?= Numero::moneda($pedido->getTotal()) ?></strong></td>
                                <td data-etiqueta="Estado">
                                    <span class="etiqueta <?= EstadoPedido::claseEtiqueta($pedido->getEstado()) ?>"><?= $pedido->getEstado() ?></span>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?= $urlBase ?>/pedidos/detalle?id=<?= $pedido->getIdPedido() ?>">Ver</a>
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
