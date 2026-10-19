<?php

use Aplicacion\Nucleo\EstadoPago;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Numero;

$titulo = 'Pedidos';
$paginaActual = 'pedidos';
require __DIR__ . '/../Plantilla/encabezado.php';

$pestanas = ['' => 'Todos'] + array_combine(EstadoPedido::todos(), ['Por entregar', 'Entregados', 'Cancelados']);
$parametros = array_filter(['busqueda' => $busqueda, 'cliente' => $idCliente, 'pago' => $soloPorVerificar ? 'verificar' : null], fn($valor): bool => $valor !== '' && $valor !== null);
$enlace = fn(array $cambios): string => htmlspecialchars($urlBase . '/pedidos'
    . (($consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== '' && $valor !== null)) !== [] ? '?' . http_build_query($consulta) : ''));
?>

<nav class="cambioVista" aria-label="Tipo de pedido">
    <a href="<?= $urlBase ?>/pedidos" class="cambioVistaActivo" aria-current="page">Pedidos</a>
    <a href="<?= $urlBase ?>/pedidos/personalizados">
        Personalizados
        <?php if ($porCotizar + $avisosPersonalizados > 0): ?>
            <span class="numeroMenu numeroMenuAviso"><?= $porCotizar + $avisosPersonalizados ?></span>
        <?php endif; ?>
    </a>
</nav>

<?php if ($soloPorVerificar): ?>
    <p class="cajaInformativa cajaAdvertencia">
        Está viendo solo los pedidos con SINPE por revisar.
        <a href="<?= $enlace(['pago' => null]) ?>">Ver todos los pedidos</a>
    </p>
<?php elseif ($pagosPorVerificar > 0): ?>
    <p class="cajaInformativa cajaAdvertencia">
        <strong><?= $pagosPorVerificar === 1 ? 'Tiene 1 SINPE por revisar.' : 'Tiene ' . $pagosPorVerificar . ' SINPE por revisar.' ?></strong>
        Ábralos para ver el comprobante y marcar si le llegó el dinero.
        <a href="<?= $enlace(['pago' => 'verificar', 'estado' => null]) ?>">Ver solo esos</a>
    </p>
<?php endif; ?>

<?php if ($avisosPersonalizados > 0): ?>
    <p class="cajaInformativa cajaAdvertencia">
        <?= $avisosPersonalizados === 1 ? 'Un cliente canceló un pedido personalizado.' : $avisosPersonalizados . ' clientes cancelaron pedidos personalizados.' ?>
        <a href="<?= $urlBase ?>/pedidos/personalizados">Ver cuáles</a>
    </p>
<?php endif; ?>

<?php if (!$hayPedidos): ?>
    <div class="estadoVacio">
        <p>Todavía no ha recibido pedidos. Comparta el enlace de su tienda para que sus clientes le compren.</p>
        <p><a href="<?= $urlBase ?>/tienda" class="boton">Ver el enlace de mi tienda</a></p>
    </div>
<?php else: ?>
    <?php if ($conteos[EstadoPedido::CONFIRMADO] > 0): ?>
        <p class="introPagina">
            Tiene
            <strong><?= $conteos[EstadoPedido::CONFIRMADO] === 1 ? '1 pedido' : $conteos[EstadoPedido::CONFIRMADO] . ' pedidos' ?>
                por entregar</strong>.
            Lo que llevan ya se rebajó de la existencia cuando el cliente compró.
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
        <?php if ($soloPorVerificar): ?><input type="hidden" name="pago" value="verificar"><?php endif; ?>
        <div class="campo">
            <label for="busqueda">Buscar</label>
            <input type="search" id="busqueda" name="busqueda" maxlength="100"
                placeholder="Cliente, correo o número de pedido" value="<?= htmlspecialchars($busqueda) ?>">
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
                                <td data-etiqueta="Total" class="numero"><strong><?= Numero::moneda($pedido->getTotal()) ?></strong>
                                </td>
                                <td data-etiqueta="Estado">
                                    <span class="estadoYPago">
                                        <span
                                            class="etiqueta <?= EstadoPedido::claseEtiqueta($pedido->getEstado()) ?>"><?= $pedido->getEstado() ?></span>
                                        <?php if ($pedido->getPago() !== null): ?>
                                            <span
                                                class="etiqueta <?= EstadoPago::claseEtiqueta($pedido->getPago()->getEstado()) ?>">SINPE
                                                <?= mb_strtolower($pedido->getPago()->getEstado(), 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($pedido->getPago() !== null): ?>
                                        <span class="etiqueta <?= EstadoPago::claseEtiqueta($pedido->getPago()->getEstado()) ?>">SINPE
                                            <?= mb_strtolower($pedido->getPago()->getEstado(), 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    </span>
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