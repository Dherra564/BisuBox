<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;

$titulo = 'Comprar pieza personalizada';
$paginaActual = 'misPedidos';
$volverA = ['texto' => 'Pedido personalizado #' . $personalizado->getIdPersonalizado(), 'ruta' => '/mis-pedidos/personalizado?id=' . $personalizado->getIdPersonalizado()];
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo, string $extra = ''): string => trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$esEnvio = ($datos['entrega'] ?? '') === FormaEntrega::ENVIO;
$telefono = $cliente !== null ? (string) $cliente->getNumeroTelefonico() : '';
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<div class="disenoFormulario">
    <form method="post" action="<?= $urlBase ?>/mis-pedidos/personalizado/comprar" novalidate class="validarFormulario"
        data-confirmar="¿Comprar su pieza personalizada a <?= htmlspecialchars($personalizado->getNombreTienda()) ?> por <?= Numero::moneda((float) $personalizado->getTotal()) ?>?"
        data-titulo="Comprar" data-boton="Comprar">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= (int) $personalizado->getIdPersonalizado() ?>">

        <section class="formularioSeccion">
            <h2>¿Cómo lo quiere recibir?</h2>
            <div class="<?= $claseCampo('entrega') ?>">
                <span class="etiquetaCampo" id="etiquetaEntrega">Entrega <span class="obligatorio">*</span></span>
                <div class="opcionesUnidad" role="radiogroup" aria-labelledby="etiquetaEntrega">
                    <?php foreach (FormaEntrega::todas() as $codigo => $nombre): ?>
                        <label class="opcionUnidad">
                            <input type="radio" name="entrega" value="<?= $codigo ?>" data-entrega required
                                data-mensaje-requerido="Escoja cómo quiere recibir el pedido"
                                <?= ($datos['entrega'] ?? '') === $codigo ? 'checked' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= $mensajeError('entrega') ?>
            </div>

            <div class="<?= $claseCampo('direccion') ?>" data-campo-direccion <?= $esEnvio ? '' : 'hidden' ?>>
                <label for="direccion">Dirección de entrega <span class="obligatorio">*</span></label>
                <textarea id="direccion" name="direccion" rows="3" minlength="10" maxlength="250" data-contador
                    data-regla="direccion" <?= $esEnvio ? 'required' : '' ?>
                    data-mensaje-requerido="Escriba la dirección de entrega"
                    data-mensaje-largo="La dirección debe tener entre 10 y 250 caracteres"><?= $valor('direccion') ?></textarea>
                <p class="textoAyuda">Provincia, cantón, distrito y señas. Por ejemplo: Heredia, San Pablo, 100 m norte de la iglesia, casa blanca.</p>
                <?= $mensajeError('direccion') ?>
            </div>
        </section>

        <section class="formularioSeccion">
            <h2>¿Cómo va a pagar?</h2>
            <div class="<?= $claseCampo('metodoPago') ?>">
                <label for="metodoPago">Método de pago <span class="obligatorio">*</span></label>
                <select id="metodoPago" name="metodoPago" required data-mensaje-requerido="Escoja cómo va a pagar">
                    <option value="">Escoja una opción</option>
                    <?php foreach (MetodoPago::todos() as $codigo => $nombre): ?>
                        <option value="<?= $codigo ?>" <?= ($datos['metodoPago'] ?? '') === $codigo ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="textoAyuda">El pago se coordina directamente con la tienda.</p>
                <?= $mensajeError('metodoPago') ?>
            </div>

            <div class="<?= $claseCampo('nota') ?>">
                <label for="nota">Nota para la tienda</label>
                <textarea id="nota" name="nota" rows="2" maxlength="300" data-contador
                    data-mensaje-largo="La nota puede tener como máximo 300 caracteres"><?= $valor('nota') ?></textarea>
                <p class="textoAyuda">Opcional. Por ejemplo, a qué hora le queda bien recibirlo.</p>
                <?= $mensajeError('nota') ?>
            </div>
        </section>

        <div class="grupoBotones accionesFormulario">
            <a href="<?= $urlBase ?>/mis-pedidos/personalizado?id=<?= (int) $personalizado->getIdPersonalizado() ?>" class="boton botonSecundario">Volver</a>
            <button type="submit" class="boton">Comprar</button>
        </div>
    </form>

    <aside class="factura resumenPedido">
        <h2><?= htmlspecialchars($personalizado->getNombreTienda()) ?></h2>
        <ul class="lineasResumen">
            <li>
                <span><?= $personalizado->getPiezas() ?> × <?= htmlspecialchars($personalizado->getNombre()) ?>, personalizado</span>
                <strong><?= Numero::moneda((float) $personalizado->getTotal()) ?></strong>
            </li>
        </ul>
        <div class="totalesCompra">
            <div class="totalCompra"><span>Total</span><strong><?= Numero::moneda((float) $personalizado->getTotal()) ?></strong></div>
        </div>
        <?php if ($telefono !== ''): ?>
            <p class="textoAyuda">
                La tienda le contactará al <strong><?= htmlspecialchars($telefono) ?></strong>.
                Si cambió de número, actualícelo en <a href="<?= $urlBase ?>/perfil">Mi perfil</a>.
            </p>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>