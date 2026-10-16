<?php
/**
 * @var array<int, array{vendedor: \Aplicacion\Modelos\Vendedor, disponibles: int}> $tiendas
 * @var string $busqueda
 * @var string $urlBase
 */

$titulo = 'Tiendas';
$paginaActual = 'tiendas';
require __DIR__ . '/../Plantilla/encabezado.php';
?>

<p class="introPagina">Entre a una tienda, agregue al carrito lo que le guste y envíe su pedido.</p>

<form method="get" action="<?= $urlBase ?>/tiendas" class="filtros">
    <div class="campo">
        <label for="busqueda">Buscar tienda</label>
        <input type="search" id="busqueda" name="busqueda" maxlength="100" placeholder="Nombre o descripción"
            value="<?= htmlspecialchars($busqueda) ?>">
    </div>
    <div class="filtrosBotones">
        <button type="submit" class="boton">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a href="<?= $urlBase ?>/tiendas" class="boton botonSecundario">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($tiendas === []): ?>
    <div class="estadoVacio">
        <p><?= $busqueda !== '' ? 'No se encontraron tiendas con esa búsqueda.' : 'Todavía no hay tiendas abiertas.' ?></p>
    </div>
<?php else: ?>
    <div class="gridTiendas">
        <?php foreach ($tiendas as $fila): ?>
            <?php
            $vendedor = $fila['vendedor'];
            $logo = $vendedor->getTiendaLogo();
            $urlLogo = $logo !== null
                ? $urlBase . '/fotos/logo?archivo=' . urlencode($logo)
                : $urlBase . '/imagenes/logoPorDefecto.svg';
            ?>
            <a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars((string) $vendedor->getTiendaEnlace()) ?>" class="tarjeta tarjetaTienda">
                <img src="<?= htmlspecialchars($urlLogo) ?>" alt="" class="logoTienda">
                <span class="tarjetaTiendaTexto">
                    <strong><?= htmlspecialchars((string) $vendedor->getTiendaNombre()) ?></strong>
                    <?php if ((string) $vendedor->getTiendaDescripcion() !== ''): ?>
                        <span class="textoAyuda"><?= htmlspecialchars((string) $vendedor->getTiendaDescripcion()) ?></span>
                    <?php endif; ?>
                    <span class="etiqueta <?= $fila['disponibles'] > 0 ? 'etiquetaActivo' : 'etiquetaCancelado' ?>">
                        <?= $fila['disponibles'] === 0
                            ? 'Sin productos disponibles'
                            : ($fila['disponibles'] === 1 ? '1 producto disponible' : $fila['disponibles'] . ' productos disponibles') ?>
                    </span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>
