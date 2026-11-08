<?php

use Aplicacion\Nucleo\EstadoCotizacion;
use Aplicacion\Nucleo\Numero;

$titulo = 'Mis cotizaciones';
$paginaActual = 'misCotizaciones';
require __DIR__ . '/../Plantilla/encabezado.php';

$pestanas = ['' => 'Todas'] + array_combine(
    EstadoCotizacion::todos(),
    ['Enviadas', 'Respondidas', 'Aceptadas', 'Rechazadas', 'Canceladas']
);
?>

<?php if ($conteos[''] === 0): ?>
    <div class="estadoVacio">
        <p>Todavía no ha pedido cotizaciones.</p>
        <p>Entre a la tienda que le guste y use «Cotizar por foto».</p>
        <p><a href="<?= $urlBase ?>/" class="boton">Ir al inicio</a></p>
    </div>
<?php else: ?>
    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de las cotizaciones">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?= $urlBase ?>/mis-cotizaciones<?= $codigo !== '' ? '?estado=' . $codigo : '' ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($cotizaciones === []): ?>
            <p class="estadoVacio estadoVacioPequeno">No tiene cotizaciones en este estado.</p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Solicitud</th>
                            <th>Tienda</th>
                            <th class="numero">Precio</th>
                            <th>Estado</th>
                            <th><span class="soloLectores">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones as $cotizacion): ?>
                            <tr>
                                <td data-etiqueta="Solicitud">
                                    <span><strong>#<?= $cotizacion['id'] ?></strong><br>
                                        <span class="textoAyuda"><?= $cotizacion['fecha']->format('d/m/Y H:i') ?></span></span>
                                </td>
                                <td data-etiqueta="Tienda"><?= htmlspecialchars($cotizacion['nombreTienda']) ?></td>
                                <td data-etiqueta="Precio" class="numero">
                                    <?= $cotizacion['precio'] !== null ? '<strong>' . Numero::moneda($cotizacion['precio']) . '</strong>' : '<span class="textoAyuda">Sin responder</span>' ?>
                                </td>
                                <td data-etiqueta="Estado">
                                    <span class="etiqueta <?= EstadoCotizacion::claseEtiqueta($cotizacion['estado']) ?>"><?= $cotizacion['estado'] ?></span>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?= $urlBase ?>/mis-cotizaciones/detalle?id=<?= $cotizacion['id'] ?>">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $rutaPaginacion = '/mis-cotizaciones';
            $parametrosPaginacion = ['estado' => $estado];
            $nombreRegistros = 'cotizaciones';
            require __DIR__ . '/../Plantilla/paginacion.php';
            ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>