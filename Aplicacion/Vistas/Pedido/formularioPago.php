<?php

use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;

$ayudasMetodo = [
    MetodoPago::SINPE => 'Paga ahora y sube el comprobante',
    MetodoPago::TRANSFERENCIA => 'Se coordina con la tienda',
    MetodoPago::EFECTIVO => 'Paga al recibirlo',
];
$metodoElegido = (string) ($datos['metodoPago'] ?? '');
$esSinpe = $metodoElegido === MetodoPago::SINPE;
$comprobantePendiente = $datos['comprobantePendiente'] ?? null;
?>
<section class="formularioSeccion">
    <h2>¿Cómo va a pagar?</h2>
    <div class="<?= $claseCampo('metodoPago') ?>">
        <span class="etiquetaCampo" id="etiquetaMetodoPago">Método de pago <span class="obligatorio">*</span></span>
        <div class="opcionesPago" role="radiogroup" aria-labelledby="etiquetaMetodoPago">
            <?php foreach ($metodos as $codigo => $nombre): ?>
                <label class="opcionPago">
                    <input type="radio" name="metodoPago" value="<?= htmlspecialchars($codigo) ?>" data-metodo-pago required
                        data-mensaje-requerido="Escoja cómo va a pagar" <?= $metodoElegido === $codigo ? 'checked' : '' ?>>
                    <strong><?= htmlspecialchars($nombre) ?></strong>
                    <span><?= htmlspecialchars($ayudasMetodo[$codigo] ?? '') ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= $mensajeError('metodoPago') ?>
    </div>

    <?php if ($codigoPago !== null): ?>
        <div class="pasosSinpe" data-pago-sinpe <?= $esSinpe ? '' : 'hidden' ?>>
            <input type="hidden" name="codigoPago" value="<?= htmlspecialchars($codigoPago) ?>">

            <div class="pasoSinpe">
                <span class="pasoSinpeNumero" aria-hidden="true">1</span>
                <div class="pasoSinpeCuerpo">
                    <strong class="pasoSinpeTitulo">Haga el SINPE desde la app de su banco</strong>
                    <div class="datosSinpe">
                        <div class="datoSinpe">
                            <span class="textoAyuda">Número</span>
                            <strong class="datoSinpeValor"><?= htmlspecialchars($tiendaPago->getSinpeBonito()) ?></strong>
                            <span class="textoAyuda">A nombre de
                                <?= htmlspecialchars((string) $tiendaPago->getNombreCompleto()) ?></span>
                        </div>
                        <div class="datoSinpe">
                            <span class="textoAyuda">Monto exacto</span>
                            <strong class="datoSinpeValor datoSinpeMonto"><?= Numero::moneda($total) ?></strong>
                        </div>
                        <div class="datoSinpe datoSinpeCodigo">
                            <span class="textoAyuda">Escriba en el <strong>detalle</strong> del SINPE</span>
                            <div class="codigoSinpeFila">
                                <strong class="codigoSinpe"><?= htmlspecialchars($codigoPago) ?></strong>
                                <button type="button" class="boton botonSecundario botonPequeno"
                                    data-copiar-codigo="<?= htmlspecialchars($codigoPago) ?>">Copiar</button>
                            </div>
                        </div>
                    </div>
                    <?= $mensajeError('codigoPago') ?>
                </div>
            </div>

            <div class="pasoSinpe">
                <span class="pasoSinpeNumero" aria-hidden="true">2</span>
                <div class="pasoSinpeCuerpo <?= $claseCampo('comprobante') ?>">
                    <label for="comprobante" class="pasoSinpeTitulo">Suba la foto del comprobante</label>
                    <?php if ($comprobantePendiente !== null): ?>
                        <input type="hidden" name="comprobantePendiente" value="<?= htmlspecialchars($comprobantePendiente) ?>">
                    <?php endif; ?>
                    <label for="comprobante" class="zonaComprobante">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 16V4" />
                            <path d="M7 9l5-5 5 5" />
                            <path d="M4 20h16" />
                        </svg>
                        <strong
                            data-nombre-comprobante><?= $comprobantePendiente !== null ? 'Ya subió su comprobante. Toque para cambiarlo' : 'Elegir foto' ?></strong>
                        <span class="textoAyuda">JPG o PNG, máximo 5 MB</span>
                    </label>
                    <input type="file" id="comprobante" name="comprobante" accept="image/jpeg,image/png"
                        class="campoArchivo" data-comprobante <?= $comprobantePendiente === null ? 'required' : '' ?>
                        data-mensaje-requerido="Suba la foto del comprobante del SINPE">
                    <?= $mensajeError('comprobante') ?>
                </div>
            </div>

            <div class="pasoSinpe">
                <span class="pasoSinpeNumero" aria-hidden="true">3</span>
                <div class="pasoSinpeCuerpo <?= $claseCampo('referencia') ?>">
                    <label for="referencia" class="pasoSinpeTitulo">Últimos 4 dígitos de la referencia</label>
                    <input type="text" id="referencia" name="referencia" maxlength="4" inputmode="numeric"
                        placeholder="0000" class="campoReferencia" required data-regla="referencia"
                        data-mensaje-requerido="Escriba los últimos 4 dígitos de la referencia del SINPE"
                        value="<?= htmlspecialchars((string) ($datos['referencia'] ?? '')) ?>">
                    <p class="textoAyuda">Salen en el comprobante, al final del número de referencia.</p>
                    <?= $mensajeError('referencia') ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?= $claseCampo('nota') ?>">
        <label for="nota">Nota para la tienda</label>
        <textarea id="nota" name="nota" rows="2" maxlength="300" data-contador
            data-mensaje-largo="La nota puede tener como máximo 300 caracteres"><?= htmlspecialchars((string) ($datos['nota'] ?? '')) ?></textarea>
        <p class="textoAyuda">Opcional. Por ejemplo, a qué hora le queda bien recibirlo.</p>
        <?= $mensajeError('nota') ?>
    </div>
</section>