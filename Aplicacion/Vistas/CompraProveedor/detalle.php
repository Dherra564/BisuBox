<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Compra del ' . $compra->getFecha()->format('d/m/Y');
$paginaActual = 'proveedores';
$volverA = $compra->debe()
    ? ['texto' => 'Compras por pagar', 'ruta' => '/compras-por-pagar']
    : ['texto' => 'Compras a proveedores', 'ruta' => '/compras-proveedor'];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayEnvio = $compra->getCostoEnvio() > 0;
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<article class="factura <?= $compra->estaAnulada() ? 'facturaAnulada' : '' ?>">
    <header class="facturaEncabezado">
        <div>
            <span class="textoAyuda">Proveedor</span>
            <a href="<?= $urlBase ?>/proveedores/detalle?id=<?= $compra->getIdProveedor() ?>" class="facturaProveedor">
                <?= htmlspecialchars($compra->getNombreProveedor()) ?>
            </a>
        </div>
        <div>
            <span class="textoAyuda">Fecha de la compra</span>
            <strong><?= $compra->getFecha()->format('d/m/Y') ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Estado</span>
            <span
                class="etiqueta <?= DatosCompraProveedor::claseEstado($compra) ?>"><?= htmlspecialchars($compra->getEstado()) ?></span>
        </div>
        <div>
            <span class="textoAyuda">Pago</span>
            <span>
                <strong><?= htmlspecialchars($compra->getCondicion()) ?></strong>
                <?php if (!$compra->estaAnulada()): ?>
                    <span
                        class="etiqueta <?= DatosCompraProveedor::clasePago($compra) ?>"><?= DatosCompraProveedor::textoPago($compra) ?></span>
                <?php endif; ?>
            </span>
        </div>
    </header>

    <div class="tablaContenedor">
        <table class="tabla tablaFactura">
            <thead>
                <tr>
                    <th>Componente</th>
                    <th class="numero">Cantidad</th>
                    <th class="numero">Precio total</th>
                    <th class="numero">Costo de cada uno</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compra->getDetalles() as $detalle): ?>
                    <tr>
                        <td data-etiqueta="Componente">
                            <span class="nombreComponente"><?= htmlspecialchars($detalle->getNombreComponente()) ?></span>
                            <span class="detalleComponente"><?= htmlspecialchars($detalle->getNombreTipo()) ?></span>
                        </td>
                        <td data-etiqueta="Cantidad" class="numero">
                            <?= UnidadMedida::cantidad($detalle->getCantidad(), $detalle->getUnidad()) ?></td>
                        <td data-etiqueta="Precio total" class="numero">
                            <strong><?= Numero::moneda($detalle->getSubtotal()) ?></strong></td>
                        <td data-etiqueta="<?= UnidadMedida::etiquetaCosto($detalle->getUnidad()) ?>" class="numero">
                            <?= Numero::moneda($detalle->getCostoUnitario()) ?>
                            <span
                                class="detalleComponente"><?= ['Metro' => 'el metro', 'Gramo' => 'el gramo'][$detalle->getUnidad()] ?? 'cada uno' ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="totalesCompra">
        <div><span>Subtotal</span><strong><?= Numero::moneda($compra->getSubtotal()) ?></strong></div>
        <div>
            <span>Envío</span><strong><?= $hayEnvio ? Numero::moneda($compra->getCostoEnvio()) : 'Sin envío' ?></strong>
        </div>
        <div class="totalCompra"><span>Total</span><strong><?= Numero::moneda($compra->getTotal()) ?></strong></div>
        <?php if ($compra->esCredito() && !$compra->estaAnulada()): ?>
            <div><span>Pagado</span><strong><?= Numero::moneda($compra->getPagado()) ?></strong></div>
            <div class="totalCompra"><span>Saldo</span><strong><?= Numero::moneda($compra->getSaldo()) ?></strong></div>
        <?php endif; ?>
    </div>

    <?php if ($compra->debe() && $compra->getVencimiento() !== null): ?>
        <p class="<?= $compra->estaVencida() ? 'errorCampo' : 'textoAyuda' ?>">
            <?= $compra->estaVencida() ? 'Venció el ' : 'Fecha límite para pagar: ' ?>    <?= $compra->getVencimiento()->format('d/m/Y') ?>
        </p>
    <?php endif; ?>

    <?php if ($compra->getFechaRegistro() !== null): ?>
        <p class="textoAyuda">
            Registrada en el sistema el <?= $compra->getFechaRegistro()->format('d/m/Y \a \l\a\s H:i') ?>.
            <?php if ($compra->getFechaRecepcion() !== null): ?>
                Recibida el <?= $compra->getFechaRecepcion()->format('d/m/Y \a \l\a\s H:i') ?>.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <h2>Pagos</h2>
    <?php if ($abonos === []): ?>
        <p class="textoAyuda">Todavía no ha registrado pagos para esta compra.</p>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla tablaFactura">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cómo pagó</th>
                        <th>Nota</th>
                        <th class="numero">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($abonos as $abono): ?>
                        <tr>
                            <td data-etiqueta="Fecha"><?= $abono['fecha']->format('d/m/Y H:i') ?></td>
                            <td data-etiqueta="Cómo pagó"><?= htmlspecialchars(MetodoPago::nombre($abono['metodoPago'])) ?></td>
                            <td data-etiqueta="Nota"><?= htmlspecialchars((string) ($abono['nota'] ?? '')) ?></td>
                            <td data-etiqueta="Monto" class="numero"><strong><?= Numero::moneda($abono['monto']) ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</article>

<?php if ($compra->debe()): ?>
    <section class="formularioSeccion">
        <h2>Registrar un abono</h2>
        <form method="post" action="<?= $urlBase ?>/compras-proveedor/abonar" novalidate class="validarFormulario"
            data-titulo="Registrar abono" data-boton="Guardar abono"
            data-confirmar="¿Guardar el abono a <?= htmlspecialchars($compra->getNombreProveedor()) ?> por la compra del <?= $compra->getFecha()->format('d/m/Y') ?>?">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $compra->getIdCompraProveedor() ?>">

            <div class="<?= $claseCampo('monto') ?>">
                <label for="monto">Monto que le pagó <span class="obligatorio">*</span></label>
                <input type="text" id="monto" name="monto" inputmode="decimal" maxlength="14" required data-regla="abono"
                    data-saldo="<?= htmlspecialchars((string) $compra->getSaldo()) ?>"
                    data-mensaje-saldo="<?= htmlspecialchars(DatosVenta::mensajeSaldo($compra->getSaldo())) ?>"
                    data-mensaje-requerido="Escriba cuánto le pagó"
                    value="<?= htmlspecialchars((string) $datos['monto']) ?>">
                <p class="textoAyuda">Debe <?= Numero::moneda($compra->getSaldo()) ?>. Puede abonar una parte o todo.</p>
                <?= $mensajeError('monto') ?>
            </div>

            <div class="<?= $claseCampo('metodoPago') ?>">
                <label for="metodoPago">¿Cómo le pagó? <span class="obligatorio">*</span></label>
                <select id="metodoPago" name="metodoPago" required
                    data-mensaje-requerido="<?= DatosVenta::MENSAJE_METODO ?>">
                    <option value="">Escoja una opción</option>
                    <?php foreach (MetodoPago::todos() as $codigo => $nombre): ?>
                        <option value="<?= $codigo ?>" <?= $datos['metodoPago'] === $codigo ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $mensajeError('metodoPago') ?>
            </div>

            <div class="<?= $claseCampo('nota') ?>">
                <label for="nota">Nota</label>
                <input type="text" id="nota" name="nota" maxlength="300"
                    data-mensaje-largo="La nota puede tener como máximo 300 caracteres"
                    value="<?= htmlspecialchars((string) $datos['nota']) ?>">
                <p class="textoAyuda">Opcional. Por ejemplo, el número de comprobante.</p>
                <?= $mensajeError('nota') ?>
            </div>

            <div class="grupoBotones">
                <button type="submit" class="boton">Guardar abono</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if ($compra->estaPorRecibir()): ?>
    <div class="cajaPaso">
        <p><strong>Esta compra todavía no ha llegado.</strong> Cuando la reciba, márquela para sumar los componentes al
            inventario.</p>
        <form method="post" action="<?= $urlBase ?>/compras-proveedor/recibir" class="formularioEnLinea"
            data-titulo="Recibir compra" data-boton="Ya la recibí"
            data-confirmar="¿Ya le llegó la compra de <?= htmlspecialchars($compra->getNombreProveedor()) ?>? Se sumarán al inventario los componentes que trae.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $compra->getIdCompraProveedor() ?>">
            <button type="submit" class="boton">Marcar como recibida</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($compra->estaAnulada()): ?>
    <p class="cajaInformativa">
        Esta compra está
        anulada<?= $compra->getFechaRecepcion() !== null ? ': lo que entró con ella ya se restó del inventario' : '' ?>
        y no cuenta en los totales.
    </p>
<?php elseif ($compra->esCredito() && $compra->getPagado() > 0): ?>
    <div class="cajaAnular">
        <p><strong>No se puede anular:</strong> ya le hizo abonos a esta compra.</p>
    </div>
<?php elseif ($sinExistencia !== []): ?>
    <div class="cajaAnular">
        <p>
            <strong>No se puede anular:</strong> ya se usó parte de <?= htmlspecialchars(implode(', ', $sinExistencia)) ?>,
            así que en el inventario no queda lo suficiente para restarlo.
        </p>
    </div>
<?php else: ?>
    <div class="cajaAnular">
        <p>
            ¿Encontró un error en esta compra? Anúlela y regístrela de nuevo.
            <?= $compra->estaRecibida() ? 'Al anularla se resta del inventario lo que entró con ella.' : '' ?>
        </p>
        <form method="post" action="<?= $urlBase ?>/compras-proveedor/anular" class="formularioEnLinea"
            data-titulo="Anular compra" data-boton="Anular compra" data-peligro
            data-confirmar="¿Anular la compra del <?= $compra->getFecha()->format('d/m/Y') ?> a <?= htmlspecialchars($compra->getNombreProveedor()) ?>?<?= $compra->estaRecibida() ? ' Se restará del inventario lo que entró con ella.' : '' ?> Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $compra->getIdCompraProveedor() ?>">
            <button type="submit" class="boton botonPeligro">Anular compra</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>