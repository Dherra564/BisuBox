<?php

use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\Icono;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\WhatsApp;

$idProveedor = (int) $proveedor->getIdProveedor();
$titulo = (string) $proveedor->getNombre();
$paginaActual = 'proveedores';
$volverA = ['texto' => 'Proveedores', 'ruta' => '/proveedores'];
$botonesAccion = [
    ['texto' => 'Editar', 'ruta' => '/proveedores/editar?id=' . $idProveedor, 'secundario' => true],
    ['texto' => '+ Registrar compra', 'ruta' => '/compras-proveedor/nueva?proveedor=' . $idProveedor],
];
require __DIR__ . '/../Plantilla/encabezado.php';

$telefono = (string) $proveedor->getTelefono();
$whatsApp = WhatsApp::enlace($telefono, 'Hola, le escribo de mi tienda en BisuBox.');
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$partes = array_slice(preg_split('/\s+/u', trim($titulo), -1, PREG_SPLIT_NO_EMPTY), 0, 2);
$iniciales = mb_strtoupper(implode('', array_map(fn(string $parte): string => mb_substr($parte, 0, 1, 'UTF-8'), $partes)), 'UTF-8');
?>

<div class="disenoProveedor">
    <aside class="tarjetaArticulo tarjetaProveedor">
        <div class="articuloEncabezado">
            <span class="inicialesCliente" aria-hidden="true"><?= htmlspecialchars($iniciales) ?></span>
            <span>
                <strong><?= htmlspecialchars($titulo) ?></strong>
                <?php if ($proveedor->getEstado()): ?>
                    <span class="etiqueta etiquetaActivo">Activo</span>
                <?php else: ?>
                    <span class="etiqueta etiquetaInactivo">Inactivo</span>
                <?php endif; ?>
            </span>
        </div>
        <?php if (!$proveedor->getEstado()): ?>
            <p class="textoAyuda">No aparece al registrar compras nuevas.</p>
        <?php endif; ?>
        <div class="datoArticulo">
            <span class="textoAyuda">Teléfono</span>
            <?php if ($whatsApp !== null): ?>
                <a href="<?= htmlspecialchars($whatsApp) ?>" target="_blank" rel="noopener" class="telefonoCliente">
                    <span class="botonIconoWhatsApp" aria-hidden="true"><?= Icono::svg('whatsapp') ?></span>
                    <?= htmlspecialchars($telefono) ?>
                </a>
            <?php else: ?>
                <strong><?= htmlspecialchars($telefono) ?></strong>
            <?php endif; ?>
        </div>
        <div class="datoArticulo">
            <span class="textoAyuda">Correo</span>
            <strong><?= htmlspecialchars((string) $proveedor->getCorreo()) ?></strong>
        </div>
        <div class="datoArticulo">
            <span class="textoAyuda">Líneas que vende</span>
            <?php if ($lineas === []): ?>
                <span class="textoAyuda">Sin líneas asignadas</span>
            <?php else: ?>
                <span class="lineasProveedor">
                    <?php foreach ($lineas as $linea): ?>
                        <span class="chipLinea"><?= htmlspecialchars($linea->getNombre()) ?></span>
                    <?php endforeach; ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="datoArticulo">
            <span class="textoAyuda">Registrado el</span>
            <strong><?= $proveedor->getFechaRegistro()->format('d/m/Y') ?></strong>
        </div>
    </aside>

    <div class="columnaProveedor">
        <div class="resumenInventario resumenProveedorTarjetas">
            <div class="tarjeta tarjetaResumen">
                <span class="tarjetaResumenTitulo">Total comprado</span>
                <span class="tarjetaNumero"><?= Numero::moneda($resumen['total']) ?></span>
            </div>
            <div class="tarjeta tarjetaResumen">
                <span class="tarjetaResumenTitulo">Compras</span>
                <span class="tarjetaNumero"><?= $resumen['compras'] ?></span>
            </div>
            <div class="tarjeta tarjetaResumen">
                <span class="tarjetaResumenTitulo">Envíos</span>
                <span class="tarjetaNumero"><?= Numero::moneda($resumen['envios']) ?></span>
            </div>
            <div class="tarjeta tarjetaResumen <?= $resumen['saldo'] > 0 ? 'tarjetaPorAcabarse' : '' ?>">
                <span class="tarjetaResumenTitulo">Le debe</span>
                <span class="tarjetaNumero"><?= Numero::moneda($resumen['saldo']) ?></span>
                <?php if ($resumen['saldo'] > 0): ?>
                    <a href="<?= $urlBase ?>/compras-por-pagar?proveedor=<?= $idProveedor ?>" class="enlaceVerVenta">Ver qué
                        debe →</a>
                <?php endif; ?>
            </div>
        </div>

        <section class="formularioSeccion seccionHistorial comprasProveedor">
            <h2>Compras a este proveedor</h2>

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
                                    <td data-etiqueta="Componentes">
                                        <?= $compra->getCantidadLineas() === 1 ? '1 componente' : $compra->getCantidadLineas() . ' componentes' ?>
                                    </td>
                                    <td data-etiqueta="Total" class="numero">
                                        <strong><?= Numero::moneda($compra->getTotal()) ?></strong></td>
                                    <td data-etiqueta="Estado">
                                        <span
                                            class="etiqueta <?= DatosCompraProveedor::claseEstado($compra) ?>"><?= htmlspecialchars($compra->getEstado()) ?></span>
                                        <?php if ($compra->debe()): ?>
                                            <span
                                                class="etiqueta <?= DatosCompraProveedor::clasePago($compra) ?>"><?= DatosCompraProveedor::textoPago($compra) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-etiqueta="" class="acciones">
                                        <a href="<?= $urlBase ?>/compras-proveedor/detalle?id=<?= $compra->getIdCompraProveedor() ?>"
                                            class="boton botonPequeno">Ver</a>
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
    </div>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>