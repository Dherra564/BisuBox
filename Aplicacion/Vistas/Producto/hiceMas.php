<?php
/**
 * Ventana para sumar más de un producto que ya existe ("Hice más")
 * @var string $urlBase
 * @var string $volverHiceMas  'lista' o 'detalle'
 */

use Aplicacion\Nucleo\Csrf;
?>
<dialog class="modal modalHiceMas" id="modalHiceMas" aria-labelledby="hiceMasTitulo">
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
        <p class="textoAyuda" data-hice-mas-alcanza></p>
        <p class="textoAyuda">Se descuenta del inventario lo que lleva cada uno.</p>

        <div class="grupoBotones">
            <button type="button" class="boton botonSecundario" data-cerrar-modal>Cancelar</button>
            <button type="submit" class="boton">Guardar</button>
        </div>
    </form>
</dialog>