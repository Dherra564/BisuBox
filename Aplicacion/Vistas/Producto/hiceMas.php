<?php
/**
 
 * @var string $urlBase
 * @var string $volverHiceMas  'lista' o 'detalle'
 * @var array|null $hiceMasFallido  si el intento anterior falló: id, cantidad y error
 */

use Aplicacion\Nucleo\Csrf;
?>
<?php $hiceMasFallido = $hiceMasFallido ?? null; ?>
<dialog class="modal modalHiceMas" id="modalHiceMas" aria-labelledby="hiceMasTitulo"
    <?php if ($hiceMasFallido !== null): ?>
        data-reabrir-id="<?= (int) $hiceMasFallido['id'] ?>"
        data-reabrir-cantidad="<?= htmlspecialchars((string) $hiceMasFallido['cantidad']) ?>"
        data-reabrir-error="<?= htmlspecialchars((string) $hiceMasFallido['error']) ?>"
    <?php endif; ?>>
    <form method="post" action="<?= $urlBase ?>/productos/producir" class="modalContenido">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="" data-hice-mas-id>
        <input type="hidden" name="volver" value="<?= htmlspecialchars($volverHiceMas) ?>">

        <h2 class="modalTitulo" id="hiceMasTitulo">¿Cuántos más hizo?</h2>
        <p class="modalMensaje" data-hice-mas-nombre></p>

        <div class="selectorCantidad selectorCantidadGrande">
            <button type="button" class="botonCantidad" data-cantidad-menos aria-label="Uno menos">−</button>
            <input type="text" name="cantidad" value="1" maxlength="4" inputmode="numeric" aria-label="Cantidad"
                data-cantidad>
            <button type="button" class="botonCantidad" data-cantidad-mas aria-label="Uno más">+</button>
        </div>
        <p class="errorCampo" data-hice-mas-error hidden></p>
        <p class="textoAyuda" data-hice-mas-alcanza></p>
        <p class="textoAyuda">Se descuenta del inventario lo que lleva cada uno.</p>

        <div class="grupoBotones">
            <button type="button" class="boton botonSecundario" data-cerrar-modal>Cancelar</button>
            <button type="submit" class="boton">Guardar</button>
        </div>
    </form>
</dialog>