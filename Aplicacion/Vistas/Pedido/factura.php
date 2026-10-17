<?php
/**
 * @var \Aplicacion\Modelos\Pedido $pedido
 * @var array $datosEncabezado  [['titulo' => ..., 'valor' => ..., 'html' => bool]]
 * @var array<int, int> $existencias  (opcional) existencia actual de cada producto
 * @var array|null $personalizado  (opcional) datos del pedido personalizado
 */

use Aplicacion\Modelos\Pedido;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;

$existencias = $existencias ?? [];
$personalizado = $personalizado ?? null;
$cancelado = $pedido->getEstado() === EstadoPedido::CANCELADO;
$esPersonalizado = $pedido->esPersonalizado();
$sinPrecio = $esPersonalizado && $pedido->getTotal() <= 0;
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

    <?php if ($esPersonalizado): ?>
        <dl class="listaDatos datosEntrega">
            <dt>Tipo</dt>
            <dd>Pedido personalizado</dd>
            <?php if ($personalizado !== null): ?>
                <dt>Lo que pidió</dt>
                <dd><?= htmlspecialchars($personalizado['titulo']) ?></dd>
                <dt>Descripción</dt>
                <dd><?= nl2br(htmlspecialchars($personalizado['descripcion'])) ?></dd>
                <?php if ($personalizado['fechaDeseada'] !== null): ?>
                    <dt>Fecha deseada</dt>
                    <dd><?= $personalizado['fechaDeseada']->format('d/m/Y') ?></dd>
                <?php endif; ?>
            <?php endif; ?>
        </dl>
    <?php else: ?>
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
                        <?php $hay = $existencias[$detalle->getIdProducto()] ?? null; ?>
                        <tr>
                            <td data-etiqueta="Producto">
                                <span>
                                    <?= htmlspecialchars($detalle->getNombreProducto()) ?>
                                    <?php if ($pedido->estaPendiente() && $existencias !== []): ?>
                                        <br><span class="<?= $hay !== null && $hay >= $detalle->getCantidad() ? 'textoAyuda' : 'errorCampo' ?>">
                                            <?= $hay === null ? 'Este producto ya no existe' : 'Tiene ' . $hay . ' en existencia' ?>
                                        </span>
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
    <?php endif; ?>

    <div class="totalesCompra">
        <div class="totalCompra">
            <span>Total</span>
            <strong><?= $sinPrecio ? 'Por cotizar' : Numero::moneda($pedido->getTotal()) ?></strong>
        </div>
    </div>

    <dl class="listaDatos datosEntrega">
        <?php if ($esPersonalizado && $personalizado !== null && $personalizado['cotizacionMensaje'] !== null): ?>
            <dt>Mensaje de la tienda</dt>
            <dd><?= nl2br(htmlspecialchars($personalizado['cotizacionMensaje'])) ?></dd>
        <?php endif; ?>
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