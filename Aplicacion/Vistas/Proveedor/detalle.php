<?php
/**
 * @var \Aplicacion\Modelos\Proveedor $proveedor
 * @var \Aplicacion\Modelos\CompraProveedor[] $compras
 * @var int $totalCompras
 * @var array $resumen
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Numero;

$titulo = 'Detalle del proveedor';
$paginaActual = 'proveedores';
$volverA = ['texto' => 'Proveedores', 'ruta' => '/proveedores'];
require __DIR__ . '/../Plantilla/encabezado.php';

$telefono = (string) $proveedor->getTelefono();
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$idProveedor = (int) $proveedor->getIdProveedor();
?>

<section class="formularioSeccion">
    <h2><?= htmlspecialchars((string) $proveedor->getNombre()) ?></h2>
    <dl class="listaDatos">
        <dt>Teléfono</dt>
        <dd><?= htmlspecialchars($telefono) ?></dd>

        <dt>Correo</dt>
        <dd><?= htmlspecialchars((string) $proveedor->getCorreo()) ?></dd>

        <dt>Registrado el</dt>
        <dd><?= $proveedor->getFechaRegistro()->format('d/m/Y') ?></dd>

        <dt>Estado</dt>
        <dd>
            <?php if ($proveedor->getEstado()): ?>
                <span class="etiqueta etiquetaActivo">Activo</span>
            <?php else: ?>
                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                <p class="textoAyuda">No aparece al registrar compras nuevas.</p>
            <?php endif; ?>
        </dd>
    </dl>
</section>

<section class="formularioSeccion comprasProveedor">
    <h2>Compras a este proveedor</h2>

    <div class="resumenProveedor">
        <div>
            <span class="textoAyuda">Total comprado</span>
            <strong><?= Numero::moneda($resumen['total']) ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Compras</span>
            <strong><?= $resumen['compras'] ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Envíos</span>
            <strong><?= Numero::moneda($resumen['envios']) ?></strong>
        </div>
    </div>

    <?php if ($compras === []): ?>
        <p class="estadoVacio estadoVacioPequeno">Todavía no hay compras a este proveedor.</p>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla tablaCompras">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Componentes</th>
                        <th class="numero">Total</th>
                        <th>Estado</th>
                        <th><span class="soloLectores">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $compra): ?>
                        <tr class="<?= $compra->estaAnulada() ? 'filaInactiva' : '' ?>">
                            <td data-etiqueta="Fecha"><?= $compra->getFecha()->format('d/m/Y') ?></td>
                            <td data-etiqueta="Componentes"><?= $compra->getCantidadLineas() === 1 ? '1 componente' : $compra->getCantidadLineas() . ' componentes' ?></td>
                            <td data-etiqueta="Total" class="numero"><strong><?= Numero::moneda($compra->getTotal()) ?></strong></td>
                            <td data-etiqueta="Estado">
                                <span class="etiqueta <?= $compra->estaAnulada() ? 'etiquetaInactivo' : 'etiquetaActivo' ?>"><?= $compra->getEstado() ?></span>
                            </td>
                            <td class="acciones">
                                <a href="<?= $urlBase ?>/compras-proveedor/detalle?id=<?= $compra->getIdCompraProveedor() ?>" class="boton botonSecundario botonPequeno">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalCompras > count($compras)): ?>
            <p class="textoAyuda">
                Se muestran las <?= count($compras) ?> más recientes de <?= $totalCompras ?>.
                <a href="<?= $urlBase ?>/compras-proveedor?proveedor=<?= $idProveedor ?>">Ver todas</a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>