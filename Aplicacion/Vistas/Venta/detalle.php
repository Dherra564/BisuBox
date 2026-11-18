<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Venta #' . $venta['id'];
$paginaActual = 'ventas';
$volverA = ['texto' => 'Ventas', 'ruta' => '/ventas'];
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$porCobrar = $venta['estadoPago'] === DatosVenta::ESTADO_POR_COBRAR && $venta['saldo'] > 0;
$vencida = $porCobrar && $venta['vencimiento'] !== null && $venta['vencimiento'] < new DateTime('today');
$whatsApp = $porCobrar ? WhatsApp::enlace($venta['telefono'], DatosVenta::recordatorio($venta, $tienda)) : null;
$porcentajePagado = $venta['total'] > 0 ? min(100, max(0, round($venta['pagado'] / $venta['total'] * 100))) : 0;
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<section class="encabezadoVenta">
    <div class="facturaEncabezado">
        <div>
            <span class="textoAyuda">Cliente</span>
            <a href="<?= $urlBase ?>/ventas?cliente=<?= $venta['idCliente'] ?>" class="facturaProveedor">
                <?= htmlspecialchars($venta['cliente'] !== '' ? $venta['cliente'] : 'Cliente') ?>
            </a>
        </div>
        <div>
            <span class="textoAyuda">Fecha de la venta</span>
            <strong>
                <?= $venta['fecha']->format('d/m/Y') ?>
            </strong>
        </div>
        <div>
            <span class="textoAyuda">Pedido</span>
            <a href="<?= $urlBase ?>/pedidos/detalle?id=<?= $venta['idPedido'] ?>" class="facturaProveedor">#<?= $venta['idPedido'] ?>
            </a>
        </div>
        <div>
            <span class="textoAyuda">Tipo</span>
            <strong>
                <?= htmlspecialchars($venta['tipo']) ?>
            </strong>
        </div>
        <div>
            <span class="textoAyuda">Estado</span>
            <span
                class="etiqueta <?= DatosVenta::claseEtiqueta($venta['estadoPago'], $venta['vencimiento'], $venta['saldo']) ?>">
                <?= $vencida ? 'Vencida' : htmlspecialchars($venta['estadoPago']) ?>
            </span>
        </div>
    </div>
    <?php if ($whatsApp !== null): ?>
        <a href="<?= htmlspecialchars($whatsApp) ?>" class="boton" target="_blank" rel="noopener">Recordarle por WhatsApp</a>
    <?php endif; ?>
</section>

<section class="cuentasVenta" aria-label="Pagos de la venta">
    <div>
        <span class="textoAyuda">Total de la venta</span>
        <strong><?= Numero::moneda($venta['total']) ?></strong>
    </div>
    <div>
        <span class="textoAyuda">Pagado</span>
        <strong><?= Numero::moneda($venta['pagado']) ?></strong>
    </div>
    <div class="saldoVenta">
        <span class="textoAyuda">Saldo</span>
        <strong><?= Numero::moneda($venta['saldo']) ?></strong>
    </div>
    <div class="avanceVenta">
        <div class="barraAvance" aria-hidden="true"><span style="width: <?= $porcentajePagado ?>%"></span></div>
        <?php if ($porCobrar && $venta['vencimiento'] !== null): ?>
            <p class="<?= $vencida ? 'errorCampo' : 'textoAyuda' ?>">
                <?= $vencida ? 'Venció el ' : 'Fecha límite para pagar: ' ?>
                <?= $venta['vencimiento']->format('d/m/Y') ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<?php if ($porCobrar): ?>
    <section class="formularioSeccion abonoVenta">
        <h2>Registrar un abono</h2>
        <form method="post" action="<?= $urlBase ?>/ventas/abonar" novalidate class="validarFormulario"
            data-titulo="Registrar abono" data-boton="Guardar abono"
            data-confirmar="¿Guardar el abono de <?= htmlspecialchars($venta['cliente'] !== '' ? $venta['cliente'] : 'el cliente') ?> a la venta #<?= $venta['id'] ?>?">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $venta['id'] ?>">
            <div class="<?= $claseCampo('monto') ?>">
                <label for="monto">Monto que le pagó <span class="obligatorio">*</span></label>
                <input type="text" id="monto" name="monto" inputmode="decimal" maxlength="14" required data-regla="abono"
                    data-saldo="<?= htmlspecialchars((string) $venta['saldo']) ?>"
                    data-mensaje-saldo="<?= htmlspecialchars(DatosVenta::mensajeSaldo($venta['saldo'])) ?>"
                    data-mensaje-requerido="Escriba cuánto le pagó"
                    value="<?= htmlspecialchars((string) $datos['monto']) ?>">
                <p class="textoAyuda">Debe
                    <?= Numero::moneda($venta['saldo']) ?>. Puede abonar una parte o todo.
                </p>
                <?= $mensajeError('monto') ?>
            </div>

            <div class="<?= $claseCampo('metodoPago') ?>">
                <label for="metodoPago">¿Cómo le pagó? <span class="obligatorio">*</span></label>
                <select id="metodoPago" name="metodoPago" required
                    data-mensaje-requerido="<?= DatosVenta::MENSAJE_METODO ?>">
                    <option value="">Escoja una opción</option>
                    <?php foreach (MetodoPago::todos() as $codigo => $nombre): ?>
                        <option value="<?= $codigo ?>" <?= $datos['metodoPago'] === $codigo ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </option>
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

            <button type="submit" class="boton">Guardar abono</button>
        </form>
    </section>
<?php endif; ?>

<section class="factura abonosVenta">
    <h2>Abonos</h2>
    <?php if ($abonos === []): ?>
        <p class="textoAyuda">Todavía no ha registrado abonos para esta venta.</p>
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
                            <td data-etiqueta="Fecha">
                                <?= $abono['fecha']->format('d/m/Y H:i') ?>
                            </td>
                            <td data-etiqueta="Cómo pagó">
                                <?= htmlspecialchars(MetodoPago::nombre($abono['metodoPago'])) ?>
                            </td>
                            <td data-etiqueta="Nota">
                                <?= htmlspecialchars((string) ($abono['nota'] ?? '')) ?>
                            </td>
                            <td data-etiqueta="Monto" class="numero"><strong>
                                    <?= Numero::moneda($abono['monto']) ?>
                                </strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>