<?php
/**
 * @var \Aplicacion\Modelos\CompraProveedor $compra
 * @var string[] $sinExistencia
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Compra del ' . $compra->getFecha()->format('d/m/Y');
$paginaActual = 'proveedores';
$volverA = ['texto' => 'Compras a proveedores', 'ruta' => '/compras-proveedor'];
$botonesAccion = [
    ['texto' => 'Ver inventario', 'ruta' => '/inventario', 'secundario' => true],
    ['texto' => 'Registrar otra compra', 'ruta' => '/compras-proveedor/nueva'],
];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayEnvio = $compra->getCostoEnvio() > 0;
?>

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
            <span class="etiqueta <?= $compra->estaAnulada() ? 'etiquetaInactivo' : 'etiquetaActivo' ?>"><?= $compra->getEstado() ?></span>
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
                    <?php if ($hayEnvio): ?>
                        <th class="numero">Con envío</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compra->getDetalles() as $detalle): ?>
                    <tr>
                        <td data-etiqueta="Componente">
                            <span class="nombreComponente"><?= htmlspecialchars($detalle->getNombreComponente()) ?></span>
                            <span class="detalleComponente"><?= htmlspecialchars($detalle->getNombreTipo()) ?></span>
                        </td>
                        <td data-etiqueta="Cantidad" class="numero"><?= UnidadMedida::cantidad($detalle->getCantidad(), $detalle->getUnidad()) ?></td>
                        <td data-etiqueta="Precio total" class="numero"><strong><?= Numero::moneda($detalle->getSubtotal()) ?></strong></td>
                        <td data-etiqueta="<?= UnidadMedida::etiquetaCosto($detalle->getUnidad()) ?>" class="numero">
                            <?= Numero::moneda($detalle->getCostoUnitario()) ?>
                            <span class="detalleComponente"><?= ['Metro' => 'el metro', 'Gramo' => 'el gramo'][$detalle->getUnidad()] ?? 'cada uno' ?></span>
                        </td>
                        <?php if ($hayEnvio): ?>
                            <td data-etiqueta="Con envío" class="numero"><?= Numero::moneda($detalle->getCostoFinal()) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="totalesCompra">
        <div><span>Subtotal</span><strong><?= Numero::moneda($compra->getSubtotal()) ?></strong></div>
        <div><span>Envío</span><strong><?= $hayEnvio ? Numero::moneda($compra->getCostoEnvio()) : 'Sin envío' ?></strong></div>
        <div class="totalCompra"><span>Total</span><strong><?= Numero::moneda($compra->getTotal()) ?></strong></div>
        <?php if ($hayEnvio): ?>
            <p class="textoAyuda">El costo de cada uno es el precio total entre la cantidad. "Con envío" le suma su parte del envío; ese es el que entró al costo promedio del inventario.</p>
        <?php endif; ?>
    </div>

    <?php if ($compra->getFechaRegistro() !== null): ?>
        <p class="textoAyuda">Registrada en el sistema el <?= $compra->getFechaRegistro()->format('d/m/Y \a \l\a\s H:i') ?>.</p>
    <?php endif; ?>
</article>

<?php if ($compra->estaAnulada()): ?>
    <p class="cajaInformativa">Esta compra está anulada: lo que entró con ella ya se restó del inventario y no cuenta en los totales.</p>
<?php elseif ($sinExistencia !== []): ?>
    <div class="cajaAnular">
        <p>
            <strong>No se puede anular:</strong> ya se usó parte de <?= htmlspecialchars(implode(', ', $sinExistencia)) ?>,
            así que en el inventario no queda lo suficiente para restarlo.
        </p>
    </div>
<?php else: ?>
    <div class="cajaAnular">
        <p>¿Encontró un error en esta compra? Anúlela y regístrela de nuevo. Al anularla se resta del inventario lo que entró con ella.</p>
        <form method="post" action="<?= $urlBase ?>/compras-proveedor/anular" class="formularioEnLinea"
              data-titulo="Anular compra" data-boton="Anular compra" data-peligro
              data-confirmar="¿Anular la compra del <?= $compra->getFecha()->format('d/m/Y') ?> a <?= htmlspecialchars($compra->getNombreProveedor()) ?>? Se restará del inventario lo que entró con ella. Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $compra->getIdCompraProveedor() ?>">
            <button type="submit" class="boton botonPeligro">Anular compra</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>