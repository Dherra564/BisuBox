<?php
/**
 * Portada de la tienda: lo que ve cualquier persona que abre el enlace de la tienda
 * @var \Aplicacion\Modelos\Vendedor $tienda
 * @var \Aplicacion\Modelos\Producto[] $productos
 * @var string $busqueda
 * @var array<int, int> $enCarrito  lo que el cliente ya tiene en el carrito
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UsuarioActual;

$titulo = (string) $tienda->getTiendaNombre();
$paginaActual = 'tienda';
$ocultarTitulo = true;
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezadoTienda.php';

$disponibles = array_values(array_filter($productos, fn($producto): bool => $producto->getExistencia() > 0));
$agotados = array_values(array_filter($productos, fn($producto): bool => $producto->getExistencia() <= 0));
$esDuenio = UsuarioActual::esVendedor() && UsuarioActual::id() === $tienda->getIdUsuario();
?>

<?php if ($busqueda === ''): ?>
    <section class="tiendaPortada">
        <div class="tiendaPortadaTexto">
            <p class="tiendaPortadaEtiqueta">Bisutería</p>
            <h1 class="tiendaPortadaTitulo"><?= htmlspecialchars((string) $tienda->getTiendaNombre()) ?></h1>
            <?php if ((string) $tienda->getTiendaDescripcion() !== ''): ?>
                <p class="tiendaPortadaDescripcion"><?= htmlspecialchars((string) $tienda->getTiendaDescripcion()) ?></p>
            <?php endif; ?>
            <a href="#productos" class="boton">Ver productos</a>
        </div>
        <?php if ($urlLogo !== null): ?>
            <img src="<?= htmlspecialchars($urlLogo) ?>" alt="Logo de <?= htmlspecialchars((string) $tienda->getTiendaNombre()) ?>" class="tiendaPortadaLogo">
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($esDuenio): ?>
    <p class="cajaInformativa">Así ven sus clientes su tienda. Compártales este enlace para que le compren.</p>
<?php elseif (UsuarioActual::esVendedor()): ?>
    <p class="cajaInformativa">Las cuentas de vendedor no pueden hacer pedidos.</p>
<?php endif; ?>

<section id="productos" class="tiendaSeccion">
    <div class="tiendaSeccionEncabezado">
        <h2><?= $busqueda !== '' ? 'Resultados para «' . htmlspecialchars($busqueda) . '»' : 'Nuestros productos' ?></h2>
        <?php if ($busqueda !== ''): ?>
            <a href="<?= $rutaTienda ?>#productos">Ver todos</a>
        <?php endif; ?>
    </div>

    <?php if ($productos === []): ?>
        <div class="estadoVacio">
            <p><?= $busqueda !== '' ? 'No encontramos productos con esa búsqueda.' : 'Esta tienda todavía no tiene productos.' ?></p>
        </div>
    <?php else: ?>
        <div class="gridProductos">
            <?php foreach (array_merge($disponibles, $agotados) as $producto): ?>
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
                    <div class="fotoTarjetaMarco">
                        <img src="<?= htmlspecialchars($urlFoto) ?>" alt="" class="fotoTarjetaProducto" loading="lazy">
                        <?php if ($hay <= 0): ?><span class="sello">Agotado</span><?php elseif ($hay <= 2): ?><span class="sello selloPocos"><?= $hay === 1 ? 'Último' : 'Últimos ' . $hay ?></span><?php endif; ?>
                    </div>
                    <div class="tarjetaProductoTexto">
                        <h3><?= htmlspecialchars($producto->getNombre()) ?></h3>
                        <?php if ((string) $producto->getDescripcion() !== ''): ?>
                            <p class="textoAyuda"><?= htmlspecialchars((string) $producto->getDescripcion()) ?></p>
                        <?php endif; ?>
                        <p class="precioProducto"><?= Numero::moneda($producto->getPrecioVenta()) ?></p>
                        <?php if ($yaTiene > 0): ?>
                            <p class="textoAyuda"><strong>Tiene <?= $yaTiene ?> en el carrito</strong></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($hay <= 0): ?>
                        <p class="textoAyuda tarjetaProductoPie">Pronto vuelve a estar disponible.</p>
                    <?php elseif ($esClienteAqui && $puedeAgregar > 0): ?>
                        <form method="post" action="<?= $urlBase ?>/carrito/agregar" class="formularioAgregar">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="tienda" value="<?= (int) $tienda->getIdVendedor() ?>">
                            <input type="hidden" name="producto" value="<?= $idProducto ?>">
                            <div class="selectorCantidad" data-selector-cantidad data-maximo="<?= $puedeAgregar ?>">
                                <button type="button" class="botonCantidad" data-menos aria-label="Uno menos">−</button>
                                <input type="text" name="cantidad" value="1" maxlength="2" inputmode="numeric"
                                    aria-label="Cantidad de <?= htmlspecialchars($producto->getNombre()) ?>">
                                <button type="button" class="botonCantidad" data-mas aria-label="Uno más">+</button>
                            </div>
                            <button type="submit" class="boton">Agregar</button>
                        </form>
                    <?php elseif ($esClienteAqui): ?>
                        <p class="textoAyuda tarjetaProductoPie">Ya tiene en el carrito todo lo disponible.</p>
                    <?php elseif (!$haySesion): ?>
                        <div class="tarjetaProductoPie">
                            <a href="<?= $urlBase ?>/ingresar?volver=<?= $volverAqui ?>" class="boton botonSecundario botonCompleto">Ingresar para comprar</a>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pieTienda.php'; ?>
