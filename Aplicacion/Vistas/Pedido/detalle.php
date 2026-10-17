<?php
    /**
 * @var \Aplicacion\Modelos\Pedido $pedido
 * @var array<int, int> $existencias
 * @var array|null $personalizado
 * @var string $urlBase
 */

use Aplicacion\Modelos\Pedido;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\WhatsApp;

    $titulo       = 'Pedido #' . $pedido->getIdPedido();
    $paginaActual = 'pedidos';
    $volverA      = ['texto' => 'Pedidos', 'ruta' => '/pedidos'];
    require __DIR__ . '/../Plantilla/encabezado.php';

    $estado          = $pedido->getEstado();
    $esPersonalizado = $pedido->esPersonalizado();
    $porCotizar      = $esPersonalizado && in_array($estado, [EstadoPedido::SOLICITADO, EstadoPedido::COTIZADO], true);
    $puedeCancelar   = in_array($estado, [EstadoPedido::SOLICITADO, EstadoPedido::COTIZADO, EstadoPedido::PENDIENTE, EstadoPedido::CONFIRMADO], true);

    $telefono       = $pedido->getTelefonoCliente();
    $telefonoBonito = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
    $whatsApp       = WhatsApp::enlace($telefono, 'Hola ' . $pedido->getNombreCliente() . ', le escribo por su pedido'
    . ($esPersonalizado ? ' personalizado' : '') . ' #' . $pedido->getIdPedido() . '.');
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
    $numero        = $pedido->getIdPedido();
    $precioActual  = $pedido->getTotal() > 0 ? number_format($pedido->getTotal(), 2, '.', '') : '';
    $mensajeActual = $personalizado['cotizacionMensaje'] ?? '';
?>

<?php if ($estado === EstadoPedido::CANCELADO): ?>
    <p class="cajaInformativa">
        <?php echo $pedido->getCanceladoPor() === Rol::CLIENTE ? 'El cliente canceló este pedido.' : 'Usted canceló este pedido.' ?>
    </p>
<?php elseif ($estado === EstadoPedido::ENTREGADO): ?>
    <p class="cajaInformativa">Pedido entregado.</p>
<?php elseif ($esPersonalizado && $estado === EstadoPedido::SOLICITADO): ?>
    <p class="cajaInformativa">Este cliente le pidió algo personalizado. Cotícelo para que pueda decidir si lo acepta.</p>
<?php elseif ($esPersonalizado && $estado === EstadoPedido::COTIZADO): ?>
    <p class="cajaInformativa">Ya envió la cotización. Está esperando que el cliente la acepte. Mientras tanto puede cambiar el precio.</p>
<?php endif; ?>

<?php require __DIR__ . '/factura.php'; ?>

<div class="grupoBotones accionesPedido">
    <?php if ($whatsApp !== null): ?>
        <a href="<?php echo htmlspecialchars($whatsApp) ?>" class="boton botonSecundario" target="_blank" rel="noopener">Escribirle por WhatsApp</a>
    <?php endif; ?>
    <a href="<?php echo $urlBase ?>/pedidos?cliente=<?php echo $pedido->getIdCliente() ?>" class="boton botonSecundario">Otros pedidos de este cliente</a>

    <?php if ($pedido->estaPendiente()): ?>
        <form method="post" action="<?php echo $urlBase ?>/pedidos/confirmar" class="formularioEnLinea"
            data-titulo="Confirmar pedido" data-boton="Confirmar"
            data-confirmar="<?php echo $esPersonalizado
    ? '¿Confirmar el pedido #' . $numero . '? Significa que va a empezar a elaborarlo.'
    : '¿Confirmar el pedido #' . $numero . '? Se rebajará de la existencia lo que lleva.' ?>">
            <?php echo Csrf::campo() ?>
            <input type="hidden" name="id" value="<?php echo $numero ?>">
            <button type="submit" class="boton">Confirmar pedido</button>
        </form>
    <?php elseif ($pedido->estaConfirmado()): ?>
        <form method="post" action="<?php echo $urlBase ?>/pedidos/entregar" class="formularioEnLinea"
            data-titulo="Marcar como entregado" data-boton="Ya lo entregué"
            data-confirmar="¿Ya le entregó el pedido #<?php echo $numero ?> a <?php echo htmlspecialchars($pedido->getNombreCliente()) ?>?">
            <?php echo Csrf::campo() ?>
            <input type="hidden" name="id" value="<?php echo $numero ?>">
            <button type="submit" class="boton">Marcar como entregado</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($porCotizar): ?>
    <section class="formularioSeccion">
        <h2><?php echo $estado === EstadoPedido::COTIZADO ? 'Cambiar la cotización' : 'Cotizar este pedido' ?></h2>
        <form method="post" action="<?php echo $urlBase ?>/pedidos/cotizar" novalidate class="validarFormulario"
            data-titulo="Enviar cotización" data-boton="Enviar cotización"
            data-confirmar="¿Enviar la cotización del pedido #<?php echo $numero ?> a <?php echo htmlspecialchars($pedido->getNombreCliente()) ?>?">
            <?php echo Csrf::campo() ?>
            <input type="hidden" name="id" value="<?php echo $numero ?>">

            <div class="campo">
                <label for="precio">Precio total <span class="obligatorio">*</span></label>
                <input type="text" id="precio" name="precio" inputmode="decimal" maxlength="12" required
                    data-mensaje-requerido="Escriba el precio"
                    value="<?php echo htmlspecialchars($precioActual) ?>">
                <p class="textoAyuda">Lo que le va a cobrar por todo el trabajo, con materiales. Por ejemplo: 8500 o 8500.50.</p>
            </div>

            <div class="campo">
                <label for="mensaje">Mensaje para el cliente</label>
                <textarea id="mensaje" name="mensaje" rows="3" maxlength="300" data-contador
                    data-mensaje-largo="El mensaje puede tener como máximo 300 caracteres"><?php echo htmlspecialchars((string) $mensajeActual) ?></textarea>
                <p class="textoAyuda">Opcional. Por ejemplo, cuánto tarda o qué materiales usaría.</p>
            </div>

            <div class="grupoBotones accionesFormulario">
                <button type="submit" class="boton">Enviar cotización</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if ($pedido->estaPendiente() && $faltaExistencia): ?>
    <p class="cajaInformativa cajaAdvertencia">
        No tiene suficiente existencia de algún producto (marcado en rojo). Registre lo que hizo en
        <a href="<?php echo $urlBase ?>/productos">Productos</a> con «Hice más» antes de confirmar, o cancele el pedido.
    </p>
<?php endif; ?>

<?php if ($puedeCancelar): ?>
    <?php $devuelveProductos = $pedido->estaConfirmado() && ! $esPersonalizado; ?>
    <div class="cajaAnular">
        <p>
            <?php echo $devuelveProductos
    ? '¿No se va a entregar? Al cancelarlo, los productos vuelven a la existencia.'
    : '¿No puede atender este pedido? Cancélelo y avísele al cliente.' ?>
        </p>
        <form method="post" action="<?php echo $urlBase ?>/pedidos/cancelar" class="formularioEnLinea"
            data-titulo="Cancelar pedido" data-boton="Cancelar pedido" data-peligro
            data-confirmar="¿Cancelar el pedido #<?php echo $numero ?> de <?php echo htmlspecialchars($pedido->getNombreCliente()) ?>?<?php echo $devuelveProductos ? ' Los productos volverán a la existencia.' : '' ?> Esto no se puede deshacer.">
            <?php echo Csrf::campo() ?>
            <input type="hidden" name="id" value="<?php echo $numero ?>">
            <button type="submit" class="boton botonPeligro">Cancelar pedido</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>