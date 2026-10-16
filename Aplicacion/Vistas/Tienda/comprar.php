<?php
/**
 * Tienda vista por un cliente con sesión: puede agregar productos al carrito
 * @var \Aplicacion\Modelos\Vendedor $vendedor
 * @var \Aplicacion\Modelos\Producto[] $productos
 * @var array<int, int> $enCarrito  cantidad de cada producto que ya tiene en el carrito
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\TipoContacto;

$titulo = (string) $vendedor->getTiendaNombre();
$paginaActual = 'tiendaCliente';
$unidadesTienda = array_sum($enCarrito);
$botonesAccion = $unidadesTienda > 0
    ? [['texto' => 'Ver carrito (' . $unidadesTienda . ')', 'ruta' => '/carrito']]
    : [];
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$logo = $vendedor->getTiendaLogo();
$urlLogo = $logo !== null
    ? $urlBase . '/fotos/logo?archivo=' . urlencode($logo)
    : $urlBase . '/imagenes/logoPorDefecto.svg';
?>

<section class="encabezadoTienda">
    <img src="<?= htmlspecialchars($urlLogo) ?>" alt="Logo de la tienda" class="logoTienda">
    <div>
        <?php if ((string) $vendedor->getTiendaDescripcion() !== ''): ?>
            <p><?= htmlspecialchars((string) $vendedor->getTiendaDescripcion()) ?></p>
        <?php endif; ?>
        <?php if ($vendedor->getContactos() !== []): ?>
            <ul class="contactosTienda">
                <?php foreach ($vendedor->getContactos() as $contacto): ?>
                    <li>
                        <span class="textoAyuda"><?= htmlspecialchars(TipoContacto::nombre($contacto->getTipo())) ?>:</span>
                        <?= htmlspecialchars(TipoContacto::formatear($contacto->getTipo(), $contacto->getValor())) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?php if ($productos === []): ?>
    <div class="estadoVacio"><p>Esta tienda todavía no tiene productos.</p></div>
<?php else: ?>
    <div class="gridProductos">
        <?php foreach ($productos as $producto): ?>
            <?php
            $idProducto = (int) $producto->getIdProducto();
            $foto = $producto->getFoto();
            $urlFoto = $foto !== null
                ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto)
                : $urlBase . '/imagenes/productoPorDefecto.svg';
            $hay = $producto->getExistencia();
            $yaTiene = $enCarrito[$idProducto] ?? 0;
            $puedeAgregar = min($hay - $yaTiene, Carrito::MAXIMO_POR_PRODUCTO - $yaTiene);
            ?>
            <article class="tarjetaProducto <?= $hay <= 0 ? 'tarjetaProductoAgotada' : '' ?>">
                <img src="<?= htmlspecialchars($urlFoto) ?>" alt="" class="fotoTarjetaProducto">
                <div class="tarjetaProductoTexto">
                    <h2><?= htmlspecialchars($producto->getNombre()) ?></h2>
                    <?php if ((string) $producto->getDescripcion() !== ''): ?>
                        <p class="textoAyuda"><?= htmlspecialchars((string) $producto->getDescripcion()) ?></p>
                    <?php endif; ?>
                    <p class="precioProducto"><?= Numero::moneda($producto->getPrecioVenta()) ?></p>
                    <p class="textoAyuda">
                        <?php if ($hay <= 0): ?>
                            <span class="etiqueta etiquetaCancelado">Agotado</span>
                        <?php else: ?>
                            <?= $hay === 1 ? 'Queda 1' : 'Quedan ' . $hay ?>
                            <?php if ($yaTiene > 0): ?> · <strong>Tiene <?= $yaTiene ?> en el carrito</strong><?php endif; ?>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($hay > 0 && $puedeAgregar > 0): ?>
                    <form method="post" action="<?= $urlBase ?>/carrito/agregar" class="formularioAgregar">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="tienda" value="<?= (int) $vendedor->getIdVendedor() ?>">
                        <input type="hidden" name="producto" value="<?= $idProducto ?>">
                        <div class="selectorCantidad" data-selector-cantidad data-maximo="<?= $puedeAgregar ?>">
                            <button type="button" class="botonCantidad" data-menos aria-label="Uno menos">−</button>
                            <input type="text" name="cantidad" value="1" maxlength="2" inputmode="numeric"
                                aria-label="Cantidad de <?= htmlspecialchars($producto->getNombre()) ?>">
                            <button type="button" class="botonCantidad" data-mas aria-label="Uno más">+</button>
                        </div>
                        <button type="submit" class="boton">Agregar</button>
                    </form>
                <?php elseif ($hay > 0): ?>
                    <p class="textoAyuda">Ya tiene en el carrito todo lo disponible.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>
