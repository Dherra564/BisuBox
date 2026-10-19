<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPago;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\TipoContacto;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Detalle del pedido #' . $pedido->getIdPedido();
$paginaActual = 'misPedidos';
$volverA = ['texto' => UsuarioActual::esVendedor() ? 'Mis compras' : 'Mis pedidos', 'ruta' => '/mis-pedidos'];
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$enlaceTienda = '<a href="' . $urlBase . '/tienda/' . htmlspecialchars($pedido->getEnlaceTienda()) . '" class="facturaProveedor">'
    . htmlspecialchars($pedido->getNombreTienda()) . '</a>';
$datosEncabezado = [['titulo' => 'Tienda', 'valor' => $enlaceTienda, 'html' => true]];
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';

$whatsApp = null;
foreach ($contactos as $contacto) {
    if ($contacto->getTipo() === TipoContacto::WHATSAPP) {
        $whatsApp = WhatsApp::enlace($contacto->getValor(), 'Hola, le escribo por mi pedido #' . $pedido->getIdPedido() . ' en BisuBox.');
        break;
    }
}

$pago = $pedido->getPago();
$activo = $pedido->estaConfirmado();
$comprobantePendiente = $datos['comprobantePendiente'] ?? null;
$clasesPago = [
    EstadoPago::POR_VERIFICAR => 'cajaPagoVerificar',
    EstadoPago::RECIBIDO => 'cajaPagoRecibido',
    EstadoPago::CON_PROBLEMA => 'cajaPagoProblema',
    EstadoPago::NO_LLEGO => 'cajaPagoProblema',
];
$explicaciones = [
    EstadoPedido::CONFIRMADO => $pago !== null
        ? 'Su compra está confirmada. La tienda la prepara y le avisa por aquí cuando revise su SINPE.'
        : 'Su compra está confirmada y la tienda la está preparando. Coordine con ella el pago y la entrega.',
    EstadoPedido::ENTREGADO => 'Este pedido ya fue entregado.',
    EstadoPedido::CANCELADO => $pedido->getCanceladoPor() === Rol::CLIENTE ? 'Usted canceló este pedido.' : 'La tienda canceló este pedido.',
];
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<p class="cajaInformativa"><?= htmlspecialchars($explicaciones[$pedido->getEstado()] ?? '') ?></p>

<?php if ($pago !== null || ($activo && $pedido->getRetiroCodigo() !== null)): ?>
    <div class="cajasPedidoCliente">
        <?php if ($pago !== null): ?>
            <section class="cajaPago <?= $clasesPago[$pago->getEstado()] ?? '' ?>" aria-labelledby="tituloPago">
                <div class="cajaPagoEncabezado">
                    <h2 id="tituloPago">Pago por SINPE Móvil</h2>
                    <span
                        class="etiqueta <?= EstadoPago::claseEtiqueta($pago->getEstado()) ?>"><?= htmlspecialchars($pago->getEstado()) ?></span>
                </div>

                <?php if ($pago->estaPorVerificar()): ?>
                    <p>La tienda está revisando que le llegó su SINPE de <strong><?= Numero::moneda($pago->getMonto()) ?></strong>.
                    </p>
                <?php elseif ($pago->estaRecibido()): ?>
                    <p>La tienda confirmó que le llegó su SINPE de <strong><?= Numero::moneda($pago->getMonto()) ?></strong>.</p>
                <?php elseif ($pago->tieneProblema()): ?>
                    <p><strong>La tienda no encuentra su SINPE.</strong> Le dice:
                        «<?= htmlspecialchars((string) $pago->getMotivo()) ?>»</p>
                    <p>Revise el comprobante y súbalo otra vez, o escríbale a la tienda.</p>
                <?php else: ?>
                    <p>La tienda no recibió su SINPE y canceló el pedido. Si usted sí lo hizo, escríbale con su comprobante.</p>
                <?php endif; ?>

                <div class="datosPagoCliente">
                    <span>Código: <strong><?= htmlspecialchars($pago->getCodigo()) ?></strong></span>
                    <span>Referencia: <strong>…<?= htmlspecialchars($pago->getReferencia()) ?></strong></span>
                    <a href="<?= $urlBase ?>/fotos/comprobante?archivo=<?= urlencode($pago->getComprobante()) ?>"
                        target="_blank" rel="noopener">Ver comprobante</a>
                </div>

                <?php if ($activo && $pago->tieneProblema()): ?>
                    <form method="post" action="<?= $urlBase ?>/mis-pedidos/pago" enctype="multipart/form-data" novalidate
                        class="validarFormulario formularioReenviarPago" data-titulo="Enviar comprobante" data-boton="Enviar"
                        data-confirmar="¿Enviarle este comprobante a la tienda para que lo revise otra vez?">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= $pedido->getIdPedido() ?>">
                        <div class="<?= $claseCampo('comprobante') ?>">
                            <label for="comprobante">Foto del comprobante</label>
                            <?php if ($comprobantePendiente !== null): ?>
                                <input type="hidden" name="comprobantePendiente" value="<?= htmlspecialchars($comprobantePendiente) ?>">
                            <?php endif; ?>
                            <label for="comprobante" class="zonaComprobante zonaComprobantePequena">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 16V4" />
                                    <path d="M7 9l5-5 5 5" />
                                    <path d="M4 20h16" />
                                </svg>
                                <strong
                                    data-nombre-comprobante><?= $comprobantePendiente !== null ? 'Ya subió su comprobante. Toque para cambiarlo' : 'Elegir foto' ?></strong>
                            </label>
                            <input type="file" id="comprobante" name="comprobante" accept="image/jpeg,image/png"
                                class="campoArchivo" data-comprobante <?= $comprobantePendiente === null ? 'required' : '' ?>
                                data-mensaje-requerido="Suba la foto del comprobante del SINPE">
                            <?= $mensajeError('comprobante') ?>
                        </div>
                        <div class="<?= $claseCampo('referencia') ?>">
                            <label for="referencia">Últimos 4 dígitos de la referencia</label>
                            <input type="text" id="referencia" name="referencia" maxlength="4" inputmode="numeric"
                                placeholder="0000" class="campoReferencia" required data-regla="referencia"
                                data-mensaje-requerido="Escriba los últimos 4 dígitos de la referencia del SINPE"
                                value="<?= htmlspecialchars((string) ($datos['referencia'] ?? '')) ?>">
                            <?= $mensajeError('referencia') ?>
                        </div>
                        <button type="submit" class="boton">Enviar comprobante otra vez</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($activo && $pedido->getRetiroCodigo() !== null): ?>
            <section class="cajaCodigoRetiro" aria-labelledby="tituloRetiro">
                <h2 id="tituloRetiro">Su código de retiro</h2>
                <strong class="codigoRetiro"><?= htmlspecialchars($pedido->getRetiroCodigo()) ?></strong>
                <p class="textoAyuda">Dígaselo a la tienda cuando recoja su pedido. Sin este código no se lo pueden entregar.
                </p>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Pedido/factura.php'; ?>

<div class="grupoBotones">
    <?php if ($whatsApp !== null): ?>
        <a href="<?= htmlspecialchars($whatsApp) ?>" class="boton" target="_blank" rel="noopener">Escribirle a la tienda por
            WhatsApp</a>
    <?php endif; ?>
</div>

<?php if ($activo): ?>
    <div class="cajaAnular">
        <p>
            ¿Ya no lo quiere? Puede cancelarlo mientras no se lo hayan entregado.
            <?= $pago !== null ? 'La devolución del SINPE la coordina con la tienda.' : '' ?>
        </p>
        <form method="post" action="<?= $urlBase ?>/mis-pedidos/cancelar" class="formularioEnLinea"
            data-titulo="Cancelar pedido" data-boton="Cancelar pedido" data-peligro
            data-confirmar="¿Cancelar el pedido #<?= $pedido->getIdPedido() ?> a <?= htmlspecialchars($pedido->getNombreTienda()) ?>? Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $pedido->getIdPedido() ?>">
            <button type="submit" class="boton botonPeligro">Cancelar pedido</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>