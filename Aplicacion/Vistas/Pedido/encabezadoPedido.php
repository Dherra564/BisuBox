<?php

use Aplicacion\Nucleo\EstadoPedido;

$botonesEncabezado = $botonesEncabezado ?? [];
?>
<section class="encabezadoPedido">
    <div class="facturaEncabezado">
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
            <span
                class="etiqueta <?= EstadoPedido::claseEtiqueta($pedido->getEstado()) ?>"><?= $pedido->getEstado() ?></span>
        </div>
    </div>
    <?php if ($botonesEncabezado !== []): ?>
        <div class="grupoBotones">
            <?php foreach ($botonesEncabezado as $boton): ?>
                <a href="<?= htmlspecialchars($boton['ruta']) ?>"
                    class="boton <?= !empty($boton['secundario']) ? 'botonSecundario' : '' ?>" <?= !empty($boton['externo']) ? 'target="_blank" rel="noopener"' : '' ?>><?= htmlspecialchars($boton['texto']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>