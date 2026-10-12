<?php
/**
 * Cuenta del precio de un producto: costo total, ganancia y precio de venta.
 * Lo usan el formulario para crear y el de editar.
 * @var array $datos
 * @var callable $claseCampo
 * @var callable $mensajeError
 * @var callable $valor
 * @var \Aplicacion\Modelos\Producto|null $productoCostos  con los costos ya guardados; null si se calculan al escribir
 */

use Aplicacion\Nucleo\DatosProducto;
use Aplicacion\Nucleo\Numero;

$productoCostos = $productoCostos ?? null;
$costoGuardado = $productoCostos?->getCostoTotal();
?>
<section class="ticketPrecio" data-precio <?= $costoGuardado !== null ? 'data-costo="' . $costoGuardado . '"' : '' ?>>
    <h2>Precio de cada uno</h2>

    <dl class="cuentaPrecio" aria-live="polite">
        <div>
            <dt>Componentes</dt>
            <dd data-total-componentes><?= Numero::moneda($productoCostos?->getCostoComponentes() ?? 0) ?></dd>
        </div>
        <div>
            <dt>Su trabajo</dt>
            <dd data-total-mano-obra><?= Numero::moneda($productoCostos?->getCostoManoObra() ?? 0) ?></dd>
        </div>
        <div>
            <dt>Otros gastos</dt>
            <dd data-total-extras><?= Numero::moneda($productoCostos?->getCostoExtras() ?? 0) ?></dd>
        </div>
        <div class="cuentaTotal">
            <dt>Costo total</dt>
            <dd data-costo-total><?= Numero::moneda($costoGuardado ?? 0) ?></dd>
        </div>
    </dl>

    <fieldset class="opcionesGanancia">
        <legend>¿Cuánto quiere ganar?</legend>
        <div class="chipsGanancia">
            <?php foreach (DatosProducto::GANANCIAS as $porcentaje): ?>
                <label class="chipGanancia">
                    <input type="radio" name="ganancia" value="<?= $porcentaje ?>" data-ganancia
                        <?= $datos['ganancia'] === (string) $porcentaje ? 'checked' : '' ?>>
                    <span><?= $porcentaje ?>%</span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="<?= $claseCampo('precioVenta', 'campoPrecioVenta') ?>">
        <label for="precioVenta">Precio de venta <span class="obligatorio">*</span></label>
        <div class="campoConUnidad campoConMoneda">
            <span class="unidadCampo">₡</span>
            <input type="text" id="precioVenta" name="precioVenta" maxlength="13" inputmode="decimal" required
                data-regla="cantidad" data-precio-venta placeholder="0"
                data-mensaje-requerido="Escriba en cuánto lo va a vender"
                data-mensaje-regla="Escriba un monto, por ejemplo 8500 o 8500,50" value="<?= $valor('precioVenta') ?>">
        </div>
        <p class="textoAyuda">Se llena solo con la ganancia que escoja; puede cambiarlo.</p>
        <?= $mensajeError('precioVenta') ?>
    </div>

    <p class="resultadoGanancia" data-resultado-ganancia hidden></p>
</section>