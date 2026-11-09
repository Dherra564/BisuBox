<?php

use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;

$titulo = 'Pedidos';
$paginaActual = 'pedidos';
require __DIR__ . '/../Plantilla/encabezado.php';

$pestanas = [
    EstadoPersonalizado::EN_REVISION => 'Por cotizar',
    EstadoPersonalizado::COTIZADO => 'Esperando respuesta',
    EstadoPersonalizado::COMPRADO => 'Comprados',
    EstadoPersonalizado::CANCELADO => 'Cancelados',
    '' => 'Todos',
];
$enlacePestana = fn(string $codigo): string => $urlBase . '/pedidos/personalizados?estado=' . urlencode($codigo === '' ? 'todos' : $codigo);
?>

<nav class="cambioVista" aria-label="Tipo de pedido">
    <a href="<?= $urlBase ?>/pedidos">Pedidos</a>
    <a href="<?= $urlBase ?>/pedidos/personalizados" class="cambioVistaActivo" aria-current="page">
        Personalizados
        <?php if ($conteos[EstadoPersonalizado::EN_REVISION] > 0): ?>
            <span class="numeroMenu numeroMenuAviso"><?= $conteos[EstadoPersonalizado::EN_REVISION] ?></span>
        <?php endif; ?>
    </a>
</nav>

<?php foreach ($avisos as $aviso): ?>
    <div class="alerta alertaAdvertencia" role="status">
        <span class="alertaIcono" aria-hidden="true">!</span>
        <p>
            <strong><?= htmlspecialchars($aviso->getNombreCliente()) ?> <?= $aviso->getPrecio() !== null ? 'no aceptó la cotización' : 'canceló su pedido' ?></strong>
            del pedido personalizado #<?= $aviso->getIdPersonalizado() ?> (<?= htmlspecialchars($aviso->getNombre()) ?><?= $aviso->getTotal() !== null ? ', ' . Numero::moneda((float) $aviso->getTotal()) : '' ?>).
            Quedó cancelado.
        </p>
        <button type="button" class="alertaCerrar" aria-label="Cerrar">×</button>
    </div>
<?php endforeach; ?>

<?php if ($conteos[''] === 0): ?>
    <div class="estadoVacio">
        <p>Todavía no le han pedido piezas personalizadas. Sus clientes pueden hacerlo con el botón «Personalizar» de cada producto en su tienda.</p>
    </div>
<?php else: ?>
    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de los pedidos personalizados">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?= htmlspecialchars($enlacePestana($codigo)) ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($personalizados === []): ?>
            <p class="estadoVacio estadoVacioPequeno">No hay pedidos personalizados en este estado.</p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Basado en</th>
                            <th>Qué cambió</th>
                            <th class="numero">Piezas</th>
                            <th><?= $estado === EstadoPersonalizado::EN_REVISION ? 'Inventario' : 'Estado' ?></th>
                            <th><span class="soloLectores">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personalizados as $personalizado): ?>
                            <?php
                            $cambios = array_map(fn($linea): string => $linea->getNombreOriginal() . ' → ' . $linea->getNombre(), $personalizado->getCambios());
                            $foto = $personalizado->getFotoProducto();
                            ?>
                            <tr>
                                <td data-etiqueta="Pedido">
                                    <span><strong>#<?= $personalizado->getIdPersonalizado() ?></strong><br>
                                        <span class="textoAyuda"><?= $personalizado->getFechaRegistro()->format('d/m/Y H:i') ?></span></span>
                                </td>
                                <td data-etiqueta="Cliente"><?= htmlspecialchars($personalizado->getNombreCliente()) ?></td>
                                <td data-etiqueta="Basado en">
                                    <span class="productoConFoto">
                                        <img src="<?= $foto !== null ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto) : $urlBase . '/imagenes/productoPorDefecto.svg' ?>" alt="" class="miniaturaComponente" loading="lazy">
                                        <?= htmlspecialchars($personalizado->getNombre()) ?>
                                    </span>
                                </td>
                                <td data-etiqueta="Qué cambió">
                                    <span><?= $cambios === [] ? 'Nada, igual al original' : nl2br(htmlspecialchars(implode("\n", $cambios))) ?></span>
                                </td>
                                <td data-etiqueta="Piezas" class="numero"><?= $personalizado->getPiezas() ?></td>
                                <td data-etiqueta="<?= $estado === EstadoPersonalizado::EN_REVISION ? 'Inventario' : 'Estado' ?>">
                                    <?php if ($personalizado->estaEnRevision()): ?>
                                        <span class="etiqueta <?= $personalizado->alcanzaInventario() ? 'etiquetaActivo' : 'etiquetaAgotado' ?>">
                                            <?= $personalizado->alcanzaInventario() ? 'Alcanza' : 'Ya no alcanza' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="etiqueta <?= EstadoPersonalizado::claseEtiqueta($personalizado->getEstado()) ?>"><?= $personalizado->getEstado() ?></span>
                                        <?php if ($personalizado->getEstado() === EstadoPersonalizado::CANCELADO): ?>
                                            <br><span class="textoAyuda"><?= $personalizado->getCanceladoPor() === Rol::CLIENTE ? 'Por el cliente' : 'Por usted' ?></span>
                                        <?php elseif ($personalizado->getTotal() !== null): ?>
                                            <br><span class="textoAyuda"><?= Numero::moneda((float) $personalizado->getTotal()) ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?= $urlBase ?>/pedidos/personalizado?id=<?= $personalizado->getIdPersonalizado() ?>"
                                        class="boton botonPequeno <?= $personalizado->estaEnRevision() ? '' : 'botonSecundario' ?>">
                                        <?= $personalizado->estaEnRevision() ? 'Cotizar' : 'Ver' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $rutaPaginacion = '/pedidos/personalizados';
            $parametrosPaginacion = ['estado' => $estado === '' ? 'todos' : $estado];
            $nombreRegistros = 'pedidos personalizados';
            require __DIR__ . '/../Plantilla/paginacion.php';
            ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>