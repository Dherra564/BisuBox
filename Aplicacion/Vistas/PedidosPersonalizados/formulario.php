<?php
/**
 * @var \Aplicacion\Modelos\Vendedor $vendedor
 * @var \Aplicacion\Modelos\Cliente $cliente
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\MetodoPago;

$titulo = 'Pedido personalizado';
$paginaActual = 'misPedidos';
$volverA = ['texto' => 'Tienda', 'ruta' => '/tienda/' . $vendedor->getTiendaEnlace()];
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo, string $extra = ''): string => trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$esEnvio = ($datos['entrega'] ?? '') === FormaEntrega::ENVIO;
$telefono = (string) $cliente->getNumeroTelefonico();
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<div class="disenoFormulario">
    <form method="post"
        action="<?= $urlBase ?>/tienda/<?= htmlspecialchars((string) $vendedor->getTiendaEnlace()) ?>/personalizado"
        novalidate class="validarFormulario"
        data-confirmar="¿Enviar la solicitud a <?= htmlspecialchars((string) $vendedor->getTiendaNombre()) ?>? La tienda le responderá con un precio y usted decide si lo acepta."
        data-titulo="Enviar solicitud" data-boton="Enviar solicitud">
        <?= Csrf::campo() ?>

        <section class="formularioSeccion">
            <h2>¿Qué quiere que le hagan?</h2>
            <div class="<?= $claseCampo('titulo') ?>">
                <label for="titulo">Título <span class="obligatorio">*</span></label>
                <input type="text" id="titulo" name="titulo" maxlength="60" required
                    data-mensaje-requerido="Escriba qué es lo que quiere"
                    value="<?= $valor('titulo') ?>">
                <p class="textoAyuda">Por ejemplo: Pulsera con mis iniciales.</p>
                <?= $mensajeError('titulo') ?>
            </div>

            <div class="<?= $claseCampo('descripcion') ?>">
                <label for="descripcion">Descripción <span class="obligatorio">*</span></label>
                <textarea id="descripcion" name="descripcion" rows="5" minlength="10" maxlength="500" required data-contador
                    data-mensaje-requerido="Cuéntele a la tienda cómo lo quiere"
                    data-mensaje-largo="La descripción debe tener entre 10 y 500 caracteres"><?= $valor('descripcion') ?></textarea>
                <p class="textoAyuda">Colores, materiales, medidas, cantidad y cualquier detalle que ayude a la tienda a cotizarlo.</p>
                <?= $mensajeError('descripcion') ?>
            </div>

            <div class="<?= $claseCampo('fechaDeseada') ?>">
                <label for="fechaDeseada">Fecha en que lo necesita</label>
                <input type="date" id="fechaDeseada" name="fechaDeseada" min="<?= date('Y-m-d') ?>"
                    value="<?= $valor('fechaDeseada') ?>">
                <p class="textoAyuda">Opcional. La tienda le dirá si le es posible.</p>
                <?= $mensajeError('fechaDeseada') ?>
            </div>
        </section>

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
                <p class="textoAyuda">Provincia, cantón, distrito y señas.</p>
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
        </section>

        <div class="grupoBotones accionesFormulario">
            <a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars((string) $vendedor->getTiendaEnlace()) ?>" class="boton botonSecundario">Volver a la tienda</a>
            <button type="submit" class="boton">Enviar solicitud</button>
        </div>
    </form>

    <aside class="factura resumenPedido">
        <h2><?= htmlspecialchars((string) $vendedor->getTiendaNombre()) ?></h2>
        <p class="textoAyuda">Así funciona un pedido personalizado:</p>
        <ul class="lineasResumen">
            <li><span>1. Usted envía lo que quiere</span></li>
            <li><span>2. La tienda le responde con un precio</span></li>
            <li><span>3. Usted acepta o cancela</span></li>
            <li><span>4. La tienda lo elabora y se lo entrega</span></li>
        </ul>
        <p class="textoAyuda">
            La tienda le contactará al <strong><?= htmlspecialchars($telefono) ?></strong>.
            Si cambió de número, actualícelo en <a href="<?= $urlBase ?>/perfil">Mi perfil</a>.
        </p>
    </aside>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>