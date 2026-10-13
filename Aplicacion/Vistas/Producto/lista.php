<?php
/**
 * @var \Aplicacion\Modelos\Producto[] $productos
 * @var array $resumen
 * @var string $busqueda
 * @var string $estado
 * @var array $estados
 * @var array $conteos
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var bool $hayProductos
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Numero;

$titulo = 'Productos';
$paginaActual = 'productos';
$scriptsExtra = ['productos.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$parametros = array_filter(['busqueda' => $busqueda, 'estado' => $estado !== 'activos' ? $estado : null]);
$enlace = function (array $cambios) use ($urlBase, $parametros): string {
    $consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== null && $valor !== '');
    return htmlspecialchars($urlBase . '/productos' . ($consulta !== [] ? '?' . http_build_query($consulta) : ''));
};
$urlFoto = fn(?string $foto): string => htmlspecialchars($foto !== null
    ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto)
    : $urlBase . '/imagenes/productoPorDefecto.svg');
?>

<?php if (!$hayProductos): ?>
    <div class="estadoVacio">
        <p>Todavía no tiene productos. Cuando haga uno, regístrelo aquí: anota lo que lleva, se descuenta del inventario y
            el sistema le ayuda a ponerle precio.</p>
        <a href="<?= $urlBase ?>/productos/nuevo" class="boton">Registrar mi primer producto</a>
    </div>
<?php else: ?>
    <p class="resumenProductos">
        <span><strong><?= $resumen['unidades'] ?></strong> <?= $resumen['unidades'] === 1 ? 'listo' : 'listos' ?> para
            vender</span>
        <span>Valen <strong><?= Numero::moneda($resumen['valor']) ?></strong></span>
        <?php if ($resumen['agotados'] > 0): ?>
            <a href="<?= $enlace(['estado' => 'agotados', 'pagina' => null]) ?>" class="resumenAgotados">
                <?= $resumen['agotados'] === 1 ? '1 agotado' : $resumen['agotados'] . ' agotados' ?>
            </a>
        <?php endif; ?>
    </p>

    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de los productos">
            <?php foreach ($estados as $codigo => $nombre): ?>
                <a href="<?= $enlace(['estado' => $codigo === 'activos' ? null : $codigo, 'pagina' => null]) ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="get" action="<?= $urlBase ?>/productos" class="filtrosCompras">
            <?php if ($estado !== 'activos'): ?>
                <input type="hidden" name="estado" value="<?= $estado ?>">
            <?php endif; ?>
            <div class="filaFiltros">
                <div class="campo campoProveedor">
                    <label for="busqueda">Buscar</label>
                    <input type="search" id="busqueda" name="busqueda" maxlength="100" placeholder="Nombre o descripción"
                        value="<?= htmlspecialchars($busqueda) ?>">
                </div>
            </div>
            <?php if ($busqueda !== ''): ?>
                <div class="fraseFiltros">
                    <a href="<?= $enlace(['busqueda' => null, 'pagina' => null]) ?>" class="chipFiltro"
                        aria-label="Quitar la búsqueda <?= htmlspecialchars($busqueda) ?>">«<?= htmlspecialchars($busqueda) ?>»
                        <span aria-hidden="true">✕</span></a>
                </div>
            <?php endif; ?>
        </form>

        <?php if ($productos === []): ?>
            <p class="sinResultados">No hay productos con esos filtros.</p>
        <?php else: ?>
            <ul class="gridProductos">
                <?php foreach ($productos as $producto): ?>
                    <?php
                    $nombre = htmlspecialchars($producto->getNombre());
                    $existencia = $producto->getExistencia();
                    ?>
                    <li class="tarjetaProducto <?= $producto->getActivo() ? '' : 'tarjetaProductoInactiva' ?>">
                        <a href="<?= $urlBase ?>/productos/detalle?id=<?= $producto->getIdProducto() ?>"
                            class="enlaceTarjetaProducto">
                            <span class="fotoTarjetaProducto">
                                <img src="<?= $urlFoto($producto->getFoto()) ?>" alt="" loading="lazy">
                                <?php if (!$producto->getActivo()): ?>
                                    <span class="etiqueta etiquetaInactivo">Desactivado</span>
                                <?php elseif ($existencia <= 0): ?>
                                    <span class="etiqueta etiquetaAgotado">Agotado</span>
                                <?php else: ?>
                                    <span
                                        class="etiqueta etiquetaActivo"><?= $existencia === 1 ? '1 listo' : $existencia . ' listos' ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="nombreTarjetaProducto"><?= $nombre ?></span>
                            <span class="precioTarjetaProducto"><?= Numero::moneda($producto->getPrecioVenta()) ?></span>
                            <span class="gananciaTarjetaProducto <?= $producto->getGanancia() < 0 ? 'gananciaNegativa' : '' ?>">
                                <?= $producto->getGanancia() >= 0
                                    ? 'Gana ' . Numero::moneda($producto->getGanancia()) . ' por cada uno'
                                    : 'Pierde ' . Numero::moneda(-$producto->getGanancia()) . ' por cada uno' ?>
                            </span>
                        </a>
                        <?php if ($producto->getActivo()): ?>
                            <button type="button" class="boton botonSecundario botonPequeno botonHiceMas" data-hice-mas
                                data-id="<?= $producto->getIdProducto() ?>" data-nombre="<?= $nombre ?>">Crear otro</button>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($totalPaginas > 1): ?>
        <?php
        $rutaPaginacion = '/productos';
        $parametrosPaginacion = $parametros;
        $nombreRegistros = 'productos';
        require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
    <?php endif; ?>

    <div class="grupoBotones accionesLista">
        <a href="<?= $urlBase ?>/productos/nuevo" class="boton">Nuevo producto</a>
    </div>

    <?php
    $volverHiceMas = 'lista';
    require __DIR__ . '/hiceMas.php';
?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>