<?php
/**
 
 * @var \Aplicacion\Modelos\Pedido $pedido
 * @var array<int, int> $existencias
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Pedido #' . $pedido->getIdPedido();
$paginaActual = 'pedidos';
$volverA = ['texto' => 'Pedidos', 'ruta' => '/pedidos'];
$scriptsExtra = ['algoritmosVista.js', 'vistaDiseno.js', 'entregaPedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$telefono = $pedido->getTelefonoCliente();
$telefonoBonito = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$whatsApp = WhatsApp::enlace($telefono, 'Hola ' . $pedido->getNombreCliente() . ', le escribo por su pedido #' . $pedido->getIdPedido() . '.');
$datosEncabezado = [
    ['titulo' => 'Cliente', 'valor' => $pedido->getNombreCliente()],
    ['titulo' => 'Teléfono', 'valor' => $telefonoBonito],
    ['titulo' => 'Correo', 'valor' => $pedido->getCorreoCliente()],
];

$faltaExistencia = false;
foreach ($pedido->getDetalles() as $detalle) {
    if (!$detalle->esPersonalizado() && ($existencias[$detalle->getIdProducto()] ?? 0) < $detalle->getCantidad()) {
        $faltaExistencia = true;
    }
}
$numero = $pedido->getIdPedido();
?>

<?php if ($pedido->getEstado() === EstadoPedido::CANCELADO): ?>
    <p class="cajaInformativa">
        <?= $pedido->getCanceladoPor() === Rol::CLIENTE ? 'El cliente canceló este pedido.' : 'Usted canceló este pedido.' ?>
    </p>
<?php elseif ($pedido->getEstado() === EstadoPedido::ENTREGADO): ?>
    <?php if ($venta === null): ?>
        <p class="cajaInformativa">Pedido entregado.</p>
    <?php else: ?>
        <div class="cajaInformativa resumenVenta">
            <p><strong>Pedido entregado.</strong> Venta #<?= $venta['id'] ?> · <?= htmlspecialchars($venta['tipo']) ?></p>
            <p>
                <span class="etiqueta <?= DatosVenta::claseEtiqueta($venta['estadoPago'], $venta['vencimiento'], $venta['saldo']) ?>"><?= htmlspecialchars($venta['estadoPago']) ?></span>
                <?php if ($venta['saldo'] > 0): ?>
                    Falta cobrar <strong><?= Numero::moneda($venta['saldo']) ?></strong>
                    <?= $venta['vencimiento'] !== null ? 'antes del ' . $venta['vencimiento']->format('d/m/Y') : '' ?>.
                <?php endif; ?>
                <a href="<?= $urlBase ?>/ventas">Ver ventas</a>
            </p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/factura.php'; ?>

<div class="grupoBotones accionesPedido">
    <?php if ($whatsApp !== null): ?>
        <a href="<?= htmlspecialchars($whatsApp) ?>" class="boton botonSecundario" target="_blank" rel="noopener">Escribirle por WhatsApp</a>
    <?php endif; ?>
    <a href="<?= $urlBase ?>/pedidos?cliente=<?= $pedido->getIdCliente() ?>" class="boton botonSecundario">Otros pedidos de este cliente</a>

    <?php if ($pedido->estaPendiente()): ?>
        <form method="post" action="<?= $urlBase ?>/pedidos/confirmar" class="formularioEnLinea"
            data-titulo="Confirmar pedido" data-boton="Confirmar"
            data-confirmar="¿Confirmar el pedido #<?= $numero ?>? Se rebajará de la existencia lo que lleva.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $numero ?>">
            <button type="submit" class="boton">Confirmar pedido</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($pedido->estaConfirmado()): ?>
    <?php $debe = $pagoElegido === DatosVenta::PAGO_PENDIENTE; ?>
    <section class="formularioSeccion seccionEntrega">
        <h2>Entregar pedido</h2>
        <form method="post" action="<?= $urlBase ?>/pedidos/entregar" novalidate class="validarFormulario" id="formularioEntrega"
            data-titulo="Marcar como entregado" data-boton="Ya lo entregué"
            data-confirmar="¿Ya le entregó el pedido #<?= $numero ?> a <?= htmlspecialchars($pedido->getNombreCliente()) ?>? Se registrará la venta.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $numero ?>">
            <fieldset class="campo">
                <legend class="etiquetaCampo">¿Cómo quedó el pago de <?= Numero::moneda($pedido->getTotal()) ?>?</legend>
                <div class="opcionesPago">
                    <label class="opcionPago">
                        <input type="radio" name="pago" value="<?= DatosVenta::PAGO_COMPLETO ?>" <?= $debe ? '' : 'checked' ?>>
                        <span><strong>Pagó todo</strong><small>La venta queda pagada.</small></span>
                    </label>
                    <label class="opcionPago">
                        <input type="radio" name="pago" value="<?= DatosVenta::PAGO_PENDIENTE ?>" <?= $debe ? 'checked' : '' ?>>
                        <span><strong>Queda debiendo</strong><small>La venta queda por cobrar.</small></span>
                    </label>
                </div>
            </fieldset>
            <div class="campo campoVencimiento" id="campoVencimiento" <?= $debe ? '' : 'hidden' ?>>
                <label for="vencimiento">Fecha límite para pagar <span class="obligatorio">*</span></label>
                <input type="date" id="vencimiento" name="vencimiento" required data-regla="fechaLimite"
                    data-mensaje-requerido="Elija la fecha límite para pagar"
                    min="<?= date('Y-m-d') ?>" max="<?= DatosVenta::vencimientoMaximo() ?>"
                    value="<?= htmlspecialchars($vencimientoElegido) ?>">
                <p class="textoAyuda">Puede ser de hoy a 6 meses. Cuando le pague, márquela como pagada en Ventas.</p>
            </div>
            <button type="submit" class="boton">Marcar como entregado</button>
        </form>
    </section>
<?php endif; ?>

<?php if ($pedido->estaPendiente() && $faltanComponentes !== []): ?>
    <div class="cajaInformativa cajaAdvertencia">
        <p>No tiene suficientes componentes para las piezas personalizadas. Registre una
            <a href="<?= $urlBase ?>/compras-proveedor/nueva">compra</a> antes de confirmar, o cancele el pedido.</p>
        <ul>
            <?php foreach ($faltanComponentes as $faltante): ?>
                <li><?= htmlspecialchars($faltante) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($pedido->estaPendiente() && $faltaExistencia): ?>
    <p class="cajaInformativa cajaAdvertencia">
        No tiene suficiente existencia de algún producto (marcado en rojo). Registre lo que hizo en
        <a href="<?= $urlBase ?>/productos">Productos</a> con «Hice más» antes de confirmar, o cancele el pedido.
    </p>
<?php endif; ?>

<?php if ($pedido->estaPendiente() || $pedido->estaConfirmado()): ?>
    <div class="cajaAnular">
        <p>
            <?= $pedido->estaConfirmado()
                ? '¿No se va a entregar? Al cancelarlo, los productos vuelven a la existencia.'
                : '¿No puede atender este pedido? Cancélelo y avísele al cliente.' ?>
        </p>
        <form method="post" action="<?= $urlBase ?>/pedidos/cancelar" class="formularioEnLinea"
            data-titulo="Cancelar pedido" data-boton="Cancelar pedido" data-peligro
            data-confirmar="¿Cancelar el pedido #<?= $numero ?> de <?= htmlspecialchars($pedido->getNombreCliente()) ?>?<?= $pedido->estaConfirmado() ? ' Los productos volverán a la existencia.' : '' ?> Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $numero ?>">
            <button type="submit" class="boton botonPeligro">Cancelar pedido</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>
