<?php
/**
 
 * @var \Aplicacion\Modelos\Pedido $pedido
 * @var \Aplicacion\Modelos\VendedorContacto[] $contactos
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\TipoContacto;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Pedido #' . $pedido->getIdPedido();
$paginaActual = 'misPedidos';
$volverA = ['texto' => 'Mis pedidos', 'ruta' => '/mis-pedidos'];
require __DIR__ . '/../Plantilla/encabezado.php';

$enlaceTienda = '<a href="' . $urlBase . '/tienda/' . htmlspecialchars($pedido->getEnlaceTienda()) . '" class="facturaProveedor">'
    . htmlspecialchars($pedido->getNombreTienda()) . '</a>';
$datosEncabezado = [['titulo' => 'Tienda', 'valor' => $enlaceTienda, 'html' => true]];

$whatsApp = null;
foreach ($contactos as $contacto) {
    if ($contacto->getTipo() === TipoContacto::WHATSAPP) {
        $whatsApp = WhatsApp::enlace($contacto->getValor(), 'Hola, le escribo por mi pedido #' . $pedido->getIdPedido() . ' en BisuBox.');
        break;
    }
}

$explicaciones = [
    EstadoPedido::PENDIENTE => 'La tienda todavía no lo ha confirmado. Mientras tanto puede cancelarlo.',
    EstadoPedido::CONFIRMADO => 'La tienda confirmó su pedido y lo está preparando. Coordine con ella el pago y la entrega.',
    EstadoPedido::ENTREGADO => 'Este pedido ya fue entregado.',
    EstadoPedido::CANCELADO => $pedido->getCanceladoPor() === Rol::CLIENTE ? 'Usted canceló este pedido.' : 'La tienda canceló este pedido.',
];
?>

<p class="cajaInformativa"><?= htmlspecialchars($explicaciones[$pedido->getEstado()] ?? '') ?></p>

<?php require __DIR__ . '/../Pedido/factura.php'; ?>

<div class="grupoBotones">
    <?php if ($whatsApp !== null): ?>
        <a href="<?= htmlspecialchars($whatsApp) ?>" class="boton botonSecundario" target="_blank" rel="noopener">Escribirle a la tienda por WhatsApp</a>
    <?php endif; ?>
</div>

<?php if ($pedido->estaPendiente()): ?>
    <div class="cajaAnular">
        <p>¿Ya no lo quiere? Puede cancelarlo mientras la tienda no lo haya confirmado.</p>
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
