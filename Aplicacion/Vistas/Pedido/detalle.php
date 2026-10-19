<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPago;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Detalle del pedido #' . $pedido->getIdPedido();
$paginaActual = 'pedidos';
$volverA = ['texto' => 'Pedidos', 'ruta' => '/pedidos'];
require __DIR__ . '/../Plantilla/encabezado.php';

$telefono = $pedido->getTelefonoCliente();
$telefonoBonito = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$whatsApp = WhatsApp::enlace($telefono, 'Hola ' . $pedido->getNombreCliente() . ', le escribo por su pedido #' . $pedido->getIdPedido() . '.');
$datosEncabezado = [
    ['titulo' => 'Cliente', 'valor' => $pedido->getNombreCliente()],
    ['titulo' => 'Teléfono', 'valor' => $telefonoBonito],
    ['titulo' => 'Correo', 'valor' => $pedido->getCorreoCliente()],
];

$rutaPersonalizado = '/pedidos/personalizado?id=';
$numero = $pedido->getIdPedido();
$pago = $pedido->getPago();
$activo = $pedido->estaConfirmado();
$clasesPago = [
    EstadoPago::POR_VERIFICAR => 'cajaPagoVerificar',
    EstadoPago::RECIBIDO => 'cajaPagoRecibido',
    EstadoPago::CON_PROBLEMA => 'cajaPagoProblema',
    EstadoPago::NO_LLEGO => 'cajaPagoProblema',
];
?>

<?php if ($pedido->getEstado() === EstadoPedido::CANCELADO): ?>
    <p class="cajaInformativa">
        <?= $pedido->getCanceladoPor() === Rol::CLIENTE ? 'El cliente canceló este pedido.' : 'Usted canceló este pedido.' ?>
    </p>
<?php elseif ($pedido->getEstado() === EstadoPedido::ENTREGADO): ?>
    <p class="cajaInformativa">Pedido entregado.</p>
<?php endif; ?>

<?php if ($pago !== null): ?>
    <section class="cajaPago <?= $clasesPago[$pago->getEstado()] ?? '' ?>" aria-labelledby="tituloPago">
        <div class="cajaPagoEncabezado">
            <h2 id="tituloPago">Pago por SINPE Móvil</h2>
            <span
                class="etiqueta <?= EstadoPago::claseEtiqueta($pago->getEstado()) ?>"><?= htmlspecialchars($pago->getEstado()) ?></span>
        </div>

        <div class="cajaPagoCuerpo">
            <a href="<?= $urlBase ?>/fotos/comprobante?archivo=<?= urlencode($pago->getComprobante()) ?>" target="_blank"
                rel="noopener" class="comprobantePago" title="Ver el comprobante en grande">
                <img src="<?= $urlBase ?>/fotos/comprobante?archivo=<?= urlencode($pago->getComprobante()) ?>"
                    alt="Comprobante del SINPE del pedido #<?= $numero ?>">
                <span>Toque para agrandar</span>
            </a>
            <dl class="datosPago">
                <div>
                    <dt>Monto</dt>
                    <dd class="datosPagoMonto"><?= Numero::moneda($pago->getMonto()) ?></dd>
                </div>
                <div>
                    <dt>Código en el detalle</dt>
                    <dd class="datosPagoCodigo"><?= htmlspecialchars($pago->getCodigo()) ?></dd>
                </div>
                <div>
                    <dt>Referencia</dt>
                    <dd>…<?= htmlspecialchars($pago->getReferencia()) ?></dd>
                </div>
                <div>
                    <dt>Lo envió a</dt>
                    <dd><?= htmlspecialchars($pago->getNumeroSinpeBonito()) ?><span
                            class="textoAyuda"><?= $pago->getFechaRegistro()->format('d/m/Y H:i') ?></span></dd>
                </div>
            </dl>
        </div>

        <?php if ($pago->getIntentos() > 1 && $pago->estaPorVerificar()): ?>
            <p class="cajaPagoNota">El cliente subió un comprobante nuevo (intento <?= $pago->getIntentos() ?>).</p>
        <?php endif; ?>

        <?php if ($activo && $pago->estaPorVerificar()): ?>
            <div class="cajaPagoAcciones">
                <p>Revise en su banco que llegó un SINPE de <strong><?= Numero::moneda($pago->getMonto()) ?></strong> con el
                    código <strong><?= htmlspecialchars($pago->getCodigo()) ?></strong>.</p>
                <form method="post" action="<?= $urlBase ?>/pedidos/pago/recibido" class="formularioEnLinea"
                    data-titulo="Me llegó el SINPE" data-boton="Sí, me llegó"
                    data-confirmar="¿Le llegó el SINPE de <?= Numero::moneda($pago->getMonto()) ?> con el código <?= htmlspecialchars($pago->getCodigo()) ?>?">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= $numero ?>">
                    <button type="submit" class="boton">Me llegó el SINPE</button>
                </form>
            </div>
            <form method="post" action="<?= $urlBase ?>/pedidos/pago/problema" novalidate
                class="validarFormulario formularioProblemaPago" data-titulo="No me llegó el SINPE"
                data-boton="Avisarle al cliente"
                data-confirmar="¿Avisarle al cliente que no le llegó el SINPE? Podrá subir otro comprobante.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= $numero ?>">
                <div class="campo">
                    <label for="motivoPago">¿No le llegó? Dígale al cliente qué pasó</label>
                    <textarea id="motivoPago" name="motivo" rows="2" minlength="5" maxlength="300" required data-contador
                        placeholder="Por ejemplo: no veo ningún SINPE con ese código"
                        data-mensaje-requerido="Escriba qué pasó con el pago para que el cliente sepa"
                        data-mensaje-largo="Escriba entre 5 y 300 caracteres"></textarea>
                </div>
                <button type="submit" class="boton botonSecundario botonPeligroSuave">No me llegó</button>
            </form>
        <?php elseif ($activo && $pago->tieneProblema()): ?>
            <div class="cajaPagoAcciones">
                <p>
                    Le avisó al cliente: «<?= htmlspecialchars((string) $pago->getMotivo()) ?>». Está esperando que suba otro
                    comprobante.
                    Si le llega el dinero, márquelo; si nunca llega, cancele el pedido abajo.
                </p>
                <form method="post" action="<?= $urlBase ?>/pedidos/pago/recibido" class="formularioEnLinea"
                    data-titulo="Me llegó el SINPE" data-boton="Sí, me llegó"
                    data-confirmar="¿Le llegó el SINPE de <?= Numero::moneda($pago->getMonto()) ?>?">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= $numero ?>">
                    <button type="submit" class="boton">Sí me llegó</button>
                </form>
            </div>
        <?php elseif ($pago->estaRecibido()): ?>
            <p class="cajaPagoNota">Marcó que le llegó el
                SINPE<?= $pago->getFechaRevision() !== null ? ' el ' . $pago->getFechaRevision()->format('d/m/Y H:i') : '' ?>.
            </p>
        <?php elseif ($pago->getEstado() === EstadoPago::NO_LLEGO): ?>
            <p class="cajaPagoNota">El SINPE nunca llegó y el pedido se canceló. Motivo:
                «<?= htmlspecialchars((string) $pago->getMotivo()) ?>».</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/factura.php'; ?>

<div class="grupoBotones accionesPedido">
    <?php if ($whatsApp !== null): ?>
        <a href="<?= htmlspecialchars($whatsApp) ?>" class="boton" target="_blank" rel="noopener">Escribirle por
            WhatsApp</a>
    <?php endif; ?>
    <a href="<?= $urlBase ?>/pedidos?cliente=<?= $pedido->getIdCliente() ?>" class="boton">Otros pedidos de este
        cliente</a>
</div>

<?php if ($activo): ?>
    <section class="formularioSeccion entregaPedido" aria-labelledby="tituloEntrega">
        <h2 id="tituloEntrega">Entregar el pedido</h2>
        <?php if ($pedido->pagoPendiente()): ?>
            <p class="textoAyuda">Primero confirme que le llegó el SINPE. Después podrá marcarlo como entregado.</p>
        <?php else: ?>
            <form method="post" action="<?= $urlBase ?>/pedidos/entregar" novalidate class="validarFormulario formularioEntrega"
                data-titulo="Marcar como entregado" data-boton="Ya lo entregué"
                data-confirmar="¿Ya le entregó el pedido #<?= $numero ?> a <?= htmlspecialchars($pedido->getNombreCliente()) ?>?">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= $numero ?>">
                <?php if ($pedido->getRetiroCodigo() !== null): ?>
                    <div class="campo">
                        <label for="codigoRetiro">Código de retiro que le dice el cliente</label>
                        <input type="text" id="codigoRetiro" name="codigoRetiro" maxlength="6" autocomplete="off"
                            class="campoCodigoRetiro" required data-regla="codigoRetiro" placeholder="Ej. H4TQ9R"
                            data-mensaje-requerido="Escriba el código de retiro que le da el cliente">
                    </div>
                <?php endif; ?>
                <button type="submit" class="boton">Marcar como entregado</button>
            </form>
            <p class="textoAyuda">
                <?= $pedido->getRetiroCodigo() !== null
                    ? 'El cliente ve su código en el detalle de su pedido. Sin ese código no se marca como entregado.'
                    : 'En los envíos se marca como entregado sin código.' ?>
            </p>
        <?php endif; ?>
    </section>

    <div class="cajaAnular">
        <p>¿No se va a entregar? Al cancelarlo, los productos vuelven a la existencia.</p>
        <form method="post" action="<?= $urlBase ?>/pedidos/cancelar" class="formularioEnLinea"
            data-titulo="Cancelar pedido" data-boton="Cancelar pedido" data-peligro
            data-confirmar="¿Cancelar el pedido #<?= $numero ?> de <?= htmlspecialchars($pedido->getNombreCliente()) ?>? Los productos volverán a la existencia. Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $numero ?>">
            <button type="submit" class="boton botonPeligro">Cancelar pedido</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>