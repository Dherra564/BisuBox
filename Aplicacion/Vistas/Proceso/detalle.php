<?php
/**
 * @var \Aplicacion\Modelos\Proceso $proceso
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = htmlspecialchars_decode(htmlspecialchars($proceso->getNombre()));
$paginaActual = 'procesos';
$volverA = ['texto' => 'Procesos', 'ruta' => '/procesos'];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayExtras = $proceso->getExtras() !== [];
?>

<article class="factura <?= $proceso->estaAnulado() ? 'facturaAnulada' : '' ?>">
    <header class="facturaEncabezado">
        <div>
            <span class="textoAyuda">Producto</span>
            <strong><?= htmlspecialchars($proceso->getNombre()) ?></strong>
            <?php if ($proceso->getDescripcion() !== null): ?>
                <span class="textoAyuda"><?= htmlspecialchars($proceso->getDescripcion()) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <span class="textoAyuda">Fecha</span>
            <strong><?= $proceso->getFecha()->format('d/m/Y') ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Tiempo</span>
            <strong><?= Numero::duracion($proceso->getMinutos()) ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Estado</span>
            <span
                class="etiqueta <?= $proceso->estaAnulado() ? 'etiquetaInactivo' : 'etiquetaActivo' ?>"><?= $proceso->getEstado() ?></span>
        </div>
    </header>

    <div class="tablaContenedor">
        <table class="tabla tablaFactura">
            <thead>
                <tr>
                    <th>Componente</th>
                    <th class="numero">Cantidad</th>
                    <th class="numero">Costo de cada uno</th>
                    <th class="numero">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proceso->getComponentes() as $detalle): ?>
                    <tr>
                        <td data-etiqueta="Componente">
                            <span class="nombreComponente"><?= htmlspecialchars($detalle->getNombreComponente()) ?></span>
                            <span class="detalleComponente"><?= htmlspecialchars($detalle->getNombreTipo()) ?></span>
                        </td>
                        <td data-etiqueta="Cantidad" class="numero">
                            <?= UnidadMedida::cantidad($detalle->getCantidad(), $detalle->getUnidad()) ?></td>
                        <td data-etiqueta="<?= UnidadMedida::etiquetaCosto($detalle->getUnidad()) ?>" class="numero">
                            <?= Numero::moneda($detalle->getCostoUnitario()) ?>
                            <span
                                class="detalleComponente"><?= ['Metro' => 'el metro', 'Gramo' => 'el gramo'][$detalle->getUnidad()] ?? 'cada uno' ?></span>
                        </td>
                        <td data-etiqueta="Subtotal" class="numero"><?= Numero::moneda($detalle->getSubtotal()) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($hayExtras): ?>
        <div class="tablaContenedor">
            <table class="tabla tablaFactura">
                <thead>
                    <tr>
                        <th>Costo extra</th>
                        <th class="numero">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proceso->getExtras() as $extra): ?>
                        <tr>
                            <td data-etiqueta="Costo extra"><?= htmlspecialchars($extra->getConcepto()) ?></td>
                            <td data-etiqueta="Monto" class="numero"><?= Numero::moneda($extra->getMonto()) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="totalesCompra">
        <div><span>Componentes</span><strong><?= Numero::moneda($proceso->getCostoComponentes()) ?></strong></div>
        <div><span>Mano de obra</span><strong><?= Numero::moneda($proceso->getCostoManoObra()) ?></strong></div>
        <div><span>Costos
                extra</span><strong><?= $hayExtras ? Numero::moneda($proceso->getCostoExtras()) : 'Sin costos extra' ?></strong>
        </div>
        <div class="totalCompra"><span>Precio final</span><strong><?= Numero::moneda($proceso->getPrecio()) ?></strong>
        </div>
        <p class="textoAyuda">
            Mano de obra: <?= Numero::duracion($proceso->getMinutos()) ?> a
            <?= Numero::moneda($proceso->getTarifaHora()) ?> por hora.
            Los costos de los componentes son los que tenían en el inventario al registrar el proceso.
        </p>
    </div>

    <?php if ($proceso->getFechaRegistro() !== null): ?>
        <p class="textoAyuda">Registrado en el sistema el
            <?= $proceso->getFechaRegistro()->format('d/m/Y \a \l\a\s H:i') ?>.</p>
    <?php endif; ?>
</article>

<?php if ($proceso->estaAnulado()): ?>
    <p class="cajaInformativa">Este proceso está anulado: los componentes que usó ya volvieron al inventario y no cuenta en
        los totales.</p>
<?php else: ?>
    <div class="cajaAnular">
        <p>¿Encontró un error en este proceso? Anúlelo y regístrelo de nuevo. Al anularlo, los componentes que usó vuelven
            al inventario.</p>
        <form method="post" action="<?= $urlBase ?>/procesos/anular" class="formularioEnLinea" data-titulo="Anular proceso"
            data-boton="Anular proceso" data-peligro
            data-confirmar="¿Anular el proceso «<?= htmlspecialchars($proceso->getNombre()) ?>» del <?= $proceso->getFecha()->format('d/m/Y') ?>? Los componentes que usó volverán al inventario. Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $proceso->getIdProceso() ?>">
            <button type="submit" class="boton botonPeligro">Anular proceso</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>