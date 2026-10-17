<?php
    /**
 * 
 * @var \Aplicacion\Modelos\VendedorContacto[] $contactos
 * @var array|null $personalizado
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\TipoContacto;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\WhatsApp;
use Aplicacion\Nucleo\Numero;

    $titulo       = 'Pedido #' . $pedido->getIdPedido();
    $paginaActual = 'misPedidos';
    $volverA      = ['texto' => UsuarioActual::esVendedor() ? 'Mis compras' : 'Mis pedidos', 'ruta' => '/mis-pedidos'];
    require __DIR__ . '/../Plantilla/encabezado.php';

    $estado          = $pedido->getEstado();
    $esPersonalizado = $pedido->esPersonalizado();
    $puedeCancelar   = in_array($estado, [EstadoPedido::SOLICITADO, EstadoPedido::COTIZADO, EstadoPedido::PENDIENTE], true);

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
    EstadoPedido::SOLICITADO => 'La tienda todavía no ha cotizado su solicitud. Cuando lo haga, verá el precio aquí y podrá aceptarlo.',
    EstadoPedido::COTIZADO   => 'La tienda le envió una cotización. Si está de acuerdo, acéptela para que empiecen a trabajar en su pedido.',
    EstadoPedido::PENDIENTE  => $esPersonalizado
        ? 'Aceptó la cotización. La tienda todavía no ha confirmado que empezará a elaborarlo. Mientras tanto puede cancelarlo.'
        : 'La tienda todavía no lo ha confirmado. Mientras tanto puede cancelarlo.',
    EstadoPedido::CONFIRMADO => $esPersonalizado
        ? 'La tienda confirmó su pedido y lo está elaborando. Coordine con ella el pago y la entrega.'
        : 'La tienda confirmó su pedido y lo está preparando. Coordine con ella el pago y la entrega.',
    EstadoPedido::ENTREGADO  => 'Este pedido ya fue entregado.',
    EstadoPedido::CANCELADO  => $pedido->getCanceladoPor() === Rol::CLIENTE ? 'Usted canceló este pedido.' : 'La tienda canceló este pedido.',
    ];
?>

<p class="cajaInformativa"><?php echo htmlspecialchars($explicaciones[$estado] ?? '') ?></p>

<?php require __DIR__ . '/../Pedido/factura.php'; ?>

<div class="grupoBotones">
    <?php if ($whatsApp !== null): ?>
        <a href="<?php echo htmlspecialchars($whatsApp) ?>" class="boton botonSecundario" target="_blank" rel="noopener">Escribirle a la tienda por WhatsApp</a>
    <?php endif; ?>

    <?php if ($esPersonalizado && $estado === EstadoPedido::COTIZADO): ?>
        <form method="post" action="<?php echo $urlBase ?>/mis-pedidos/aceptar" class="formularioEnLinea"
            data-titulo="Aceptar cotización" data-boton="Aceptar cotización"
            data-confirmar="¿Aceptar la cotización de <?php echo htmlspecialchars($pedido->getNombreTienda()) ?> por <?php echo Numero::moneda($pedido->getTotal()) ?>?">
            <?php echo Csrf::campo() ?>
            <input type="hidden" name="id" value="<?php echo $pedido->getIdPedido() ?>">
            <input type="hidden" name="precio" value="<?php echo number_format($pedido->getTotal(), 2, '.', '') ?>">
            <button type="submit" class="boton">Aceptar cotización</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($puedeCancelar): ?>
    <div class="cajaAnular">
        <p>
            <?php echo $estado === EstadoPedido::PENDIENTE
    ? '¿Ya no lo quiere? Puede cancelarlo mientras la tienda no lo haya confirmado.'
    : '¿Ya no lo quiere? Puede cancelar la solicitud cuando quiera.' ?>
        </p>
        <form method="post" action="<?php echo $urlBase ?>/mis-pedidos/cancelar" class="formularioEnLinea"
            data-titulo="Cancelar pedido" data-boton="Cancelar pedido" data-peligro
            data-confirmar="¿Cancelar el pedido #<?php echo $pedido->getIdPedido() ?> a <?php echo htmlspecialchars($pedido->getNombreTienda()) ?>? Esto no se puede deshacer.">
            <?php echo Csrf::campo() ?>
            <input type="hidden" name="id" value="<?php echo $pedido->getIdPedido() ?>">
            <button type="submit" class="boton botonPeligro">Cancelar pedido</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>