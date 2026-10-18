<?php
/**
 
 * @var \Aplicacion\Modelos\Pedido $pedido
 * @var array<int, int> $existencias
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Pedido #' . $pedido->getIdPedido();
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

$faltaExistencia = false;
foreach ($pedido->getDetalles() as $detalle) {
    if (($existencias[$detalle->getIdProducto()] ?? 0) < $detalle->getCantidad()) {
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
    <p class="cajaInformativa">Pedido entregado.</p>
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
    <?php elseif ($pedido->estaConfirmado()): ?>
        <form method="post" action="<?= $urlBase ?>/pedidos/entregar" class="formularioEnLinea"
            data-titulo="Marcar como entregado" data-boton="Ya lo entregué"
            data-confirmar="¿Ya le entregó el pedido #<?= $numero ?> a <?= htmlspecialchars($pedido->getNombreCliente()) ?>?">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $numero ?>">
            <button type="submit" class="boton">Marcar como entregado</button>
        </form>
    <?php endif; ?>
</div>

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
