<?php

use Aplicacion\Nucleo\EstadoCotizacion;
use Aplicacion\Nucleo\Numero;

$titulo = 'Cotizaciones';
$paginaActual = 'cotizaciones';
require __DIR__ . '/../Plantilla/encabezado.php';

$pestanas = ['' => 'Todas'] + array_combine(
    EstadoCotizacion::todos(),
    ['Por responder', 'Respondidas', 'Aceptadas', 'Rechazadas', 'Canceladas']
);
$porResponder = $conteos[EstadoCotizacion::ENVIADA];
?>

<?php if ($conteos[''] === 0): ?>
    <div class="estadoVacio">
        <p>Todavía no ha recibido solicitudes de cotización. Sus clientes las envían desde su tienda con «Cotizar por foto».</p>
        <p><a href="<?= $urlBase ?>/tienda" class="boton">Ver el enlace de mi tienda</a></p>
    </div>
<?php else: ?>
    <?php if ($porResponder > 0): ?>
        <p class="introPagina">
            Tiene <strong><?= $porResponder === 1 ? '1 solicitud' : $porResponder . ' solicitudes' ?> por responder</strong>.
        </p>
    <?php endif; ?>

    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de las cotizaciones">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?= $urlBase ?>/cotizaciones<?= $codigo !== '' ? '?estado=' . $codigo : '' ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($cotizaciones === []): ?>
            <p class="estadoVacio estadoVacioPequeno">No hay cotizaciones en este estado.</p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Solicitud</th>
                            <th>Cliente</th>
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
                                <td data-etiqueta="Cliente"><?= htmlspecialchars($cotizacion['nombreCliente']) ?></td>
                                <td data-etiqueta="Precio" class="numero">
                                    <?= $cotizacion['precio'] !== null ? '<strong>' . Numero::moneda($cotizacion['precio']) . '</strong>' : '<span class="textoAyuda">Sin responder</span>' ?>
                                </td>
                                <td data-etiqueta="Estado">
                                    <span class="etiqueta <?= EstadoCotizacion::claseEtiqueta($cotizacion['estado']) ?>"><?= $cotizacion['estado'] ?></span>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?= $urlBase ?>/cotizaciones/detalle?id=<?= $cotizacion['id'] ?>">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $rutaPaginacion = '/cotizaciones';
            $parametrosPaginacion = ['estado' => $estado];
            $nombreRegistros = 'cotizaciones';
            require __DIR__ . '/../Plantilla/paginacion.php';
            ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>