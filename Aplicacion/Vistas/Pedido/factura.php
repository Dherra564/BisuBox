<?php

use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;

$rutaPersonalizado = $rutaPersonalizado ?? '/mis-pedidos/personalizado?id=';
$cancelado = $pedido->getEstado() === EstadoPedido::CANCELADO;
?>
<article class="factura <?= $cancelado ? 'facturaAnulada' : '' ?>">
    <header class="facturaEncabezado">
        <?php foreach ($datosEncabezado as $dato): ?>
            <div>
                <span class="textoAyuda"><?= htmlspecialchars($dato['titulo']) ?></span>
                <strong><?= !empty($dato['html']) ? $dato['valor'] : htmlspecialchars($dato['valor']) ?></strong>
            </div>
        <?php endforeach; ?>
        <div>
            <span class="textoAyuda">Fecha</span>
            <strong><?= $pedido->getFecha()->format('d/m/Y H:i') ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Estado</span>
            <span class="etiqueta <?= EstadoPedido::claseEtiqueta($pedido->getEstado()) ?>"><?= $pedido->getEstado() ?></span>
        </div>
    </header>

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
                                    <br><a href="<?= $urlBase . $rutaPersonalizado . $detalle->getIdPersonalizado() ?>" class="textoAyuda">Ver las piezas que escogió</a>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td data-etiqueta="Cantidad" class="numero"><?= $detalle->getCantidad() ?></td>
                        <td data-etiqueta="Precio" class="numero"><?= Numero::moneda($detalle->getPrecio()) ?></td>
                        <td data-etiqueta="Subtotal" class="numero"><strong><?= Numero::moneda($detalle->getSubtotal()) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="totalesCompra">
        <div class="totalCompra"><span>Total</span><strong><?= Numero::moneda($pedido->getTotal()) ?></strong></div>
    </div>

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
</article>