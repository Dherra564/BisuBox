<?php

use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;

$disenos = $disenos ?? [];
$rutaPersonalizado = $rutaPersonalizado ?? '/mis-pedidos/personalizado?id=';
$rutaCotizacion = $rutaCotizacion ?? '/mis-cotizaciones/detalle?id=';
$cancelado = $pedido->getEstado() === EstadoPedido::CANCELADO;
?>
<article class="factura <?= $cancelado ? 'facturaAnulada' : '' ?>">
    <h2 class="facturaTitulo">Productos</h2>

    <div class="tablaContenedor">
        <table class="tabla tablaFactura">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="numero">Cantidad</th>
                    <th class="numero">Precio</th>
                    <th class="numero">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedido->getDetalles() as $detalle): ?>
                    <tr>
                        <td data-etiqueta="Producto">
                            <span>
                                <?= htmlspecialchars($detalle->getNombreProducto()) ?>
                                <?php if ($detalle->esPersonalizado()): ?>
                                    <br><a href="<?= $urlBase . $rutaPersonalizado . $detalle->getIdPersonalizado() ?>"
                                        class="textoAyuda">Ver las piezas que escogió</a>
                                <?php endif; ?>
                                <?php if ($detalle->esCotizacion()): ?>
                                    <br><a href="<?= $urlBase . $rutaCotizacion . $detalle->getIdCotizacion() ?>"
                                        class="textoAyuda">Ver la cotización</a>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td data-etiqueta="Cantidad" class="numero"><?= $detalle->getCantidad() ?></td>
                        <td data-etiqueta="Precio" class="numero"><?= Numero::moneda($detalle->getPrecio()) ?></td>
                        <td data-etiqueta="Subtotal" class="numero">
                            <strong><?= Numero::moneda($detalle->getSubtotal()) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($disenos !== []): ?>
        <div class="disenosCarrito">
            <h3>Piezas personalizadas</h3>
            <?php foreach ($pedido->getDetalles() as $detalle): ?>
                <?php if ($detalle->esDiseno() && isset($disenos[$detalle->getIdDiseno()])): ?>
                    <?php
                    $disenoLinea = $disenos[$detalle->getIdDiseno()];
                    $avisoDiseno = null;
                    $precioDiseno = $detalle->getSubtotal();
                    $quitarDiseno = false;
                    require __DIR__ . '/../Plantilla/lineaDiseno.php';
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="facturaPie">
        <dl class="listaDatos datosEntrega">
            <dt>Entrega</dt>
            <dd><?= htmlspecialchars(FormaEntrega::nombre($pedido->getEntrega())) ?></dd>
            <?php if ($pedido->getDireccion() !== null): ?>
                <dt>Dirección</dt>
                <dd><?= nl2br(htmlspecialchars($pedido->getDireccion())) ?></dd>
            <?php endif; ?>
            <dt>Pago</dt>
            <dd><?= htmlspecialchars(MetodoPago::nombre($pedido->getMetodoPago())) ?></dd>
            <?php if ($pedido->getNota() !== null): ?>
                <dt>Nota</dt>
                <dd><?= nl2br(htmlspecialchars($pedido->getNota())) ?></dd>
            <?php endif; ?>
        </dl>

        <div class="totalesCompra">
            <div class="totalCompra"><span>Total</span><strong><?= Numero::moneda($pedido->getTotal()) ?></strong></div>
        </div>
    </div>
</article>