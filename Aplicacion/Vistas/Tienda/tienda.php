<?php

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Icono;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\TipoContacto;
use Aplicacion\Nucleo\WhatsApp;

$titulo = (string) $tiendaVista->getTiendaNombre();
$paginaActual = 'inicio';
$ocultarTitulo = true;
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$disponibles = array_values(array_filter($productos, fn($producto): bool => $producto->getExistencia() > 0));
$sinExistencia = array_values(array_filter($productos, fn($producto): bool => $producto->getExistencia() <= 0));
$logo = $tiendaVista->getTiendaLogo();
$urlLogo = $logo !== null ? $urlBase . '/fotos/logo?archivo=' . urlencode($logo) : null;
$iconosContacto = [
    TipoContacto::WHATSAPP => 'whatsapp',
    TipoContacto::INSTAGRAM => 'instagram',
    TipoContacto::FACEBOOK => 'facebook',
    TipoContacto::TIKTOK => 'tiktok',
];
?>

<?php if ($esDuenio): ?>
    <?php if (!$tiendaVista->getTiendaActiva()): ?>
        <div class="alerta alertaAdvertencia" role="status">
            <span class="alertaIcono" aria-hidden="true">⚠</span>
            <p><strong>Atención:</strong> Su tienda está oculta y sus clientes no la pueden ver. Actívela en <a href="<?= $urlBase ?>/tienda">Mi tienda</a>.</p>
        </div>
    <?php endif; ?>
    <div class="resumenDuenio">
            <a href="<?= $urlBase ?>/pedidos?estado=Confirmado" class="tarjetaResumen <?= $pedidosPorEntregar > 0 ? 'tarjetaResumenAviso' : '' ?>">
            <span>Pedidos por entregar</span>
            <strong><?= (int) $pedidosPorEntregar ?></strong>
        </a>
        <a href="<?= $urlBase ?>/productos" class="tarjetaResumen">
            <span>Productos agotados</span>
            <strong><?= (int) $agotados ?></strong>
        </a>
    </div>
<?php endif; ?>

<?php if ($busqueda === ''): ?>
    <section class="tiendaPortada">
        <div class="tiendaPortadaTexto">
            <p class="tiendaPortadaEtiqueta">Bisutería</p>
            <h1 class="tiendaPortadaTitulo"><?= htmlspecialchars((string) $tiendaVista->getTiendaNombre()) ?></h1>
            <?php if ((string) $tiendaVista->getTiendaDescripcion() !== ''): ?>
                <p class="tiendaPortadaDescripcion"><?= htmlspecialchars((string) $tiendaVista->getTiendaDescripcion()) ?></p>
            <?php endif; ?>
            <?php if ($tiendaVista->getContactos() !== []): ?>
                <ul class="tiendaPortadaContactos">
                    <?php foreach ($tiendaVista->getContactos() as $contacto): ?>
                        <?php
                        $tipo = $contacto->getTipo();
                        $texto = TipoContacto::formatear($tipo, $contacto->getValor());
                        $enlaceWhatsApp = $tipo === TipoContacto::WHATSAPP ? WhatsApp::enlace($contacto->getValor()) : null;
                        ?>
                        <li>
                            <?php if ($enlaceWhatsApp !== null): ?>
                                <a href="<?= htmlspecialchars($enlaceWhatsApp) ?>" target="_blank" rel="noopener">
                                    <?= Icono::svg($iconosContacto[$tipo] ?? 'web') ?> <?= htmlspecialchars($texto) ?>
                                </a>
                            <?php else: ?>
                                <span title="<?= htmlspecialchars(TipoContacto::nombre($tipo)) ?>">
                                    <?= Icono::svg($iconosContacto[$tipo] ?? 'web') ?> <?= htmlspecialchars($texto) ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php if ($urlLogo !== null): ?>
            <img src="<?= htmlspecialchars($urlLogo) ?>" alt="Logo de <?= htmlspecialchars((string) $tiendaVista->getTiendaNombre()) ?>" class="tiendaPortadaLogo">
        <?php endif; ?>
    </section>
<?php endif; ?>

<section id="productos" class="tiendaSeccion">
    <div class="tiendaSeccionEncabezado">
        <h2><?= $busqueda !== '' ? 'Resultados para «' . htmlspecialchars($busqueda) . '»' : 'Nuestros productos' ?></h2>
        <?php if ($busqueda !== ''): ?>
            <a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars((string) $tiendaVista->getTiendaEnlace()) ?>#productos">Ver todos</a>
        <?php else: ?>
            <span class="textoAyuda"><?= count($productos) === 1 ? '1 producto' : count($productos) . ' productos' ?></span>
        <?php endif; ?>
    </div>

    <?php if ($productos === []): ?>
        <div class="estadoVacio">
            <p><?= $busqueda !== '' ? 'No encontramos productos con esa búsqueda.' : 'Esta tienda todavía no tiene productos.' ?></p>
            <?php if ($esDuenio && $busqueda === ''): ?>
                <p><a href="<?= $urlBase ?>/productos/nuevo" class="boton">Agregar mi primer producto</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="gridProductos">
            <?php foreach (array_merge($disponibles, $sinExistencia) as $producto): ?>
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

                    <?php if ($esDuenio): ?>
                        <p class="textoAyuda tarjetaProductoPie">
                            <?= $hay > 0 ? 'Quedan ' . $hay : 'Agotado' ?> ·
                            <a href="<?= $urlBase ?>/productos/detalle?id=<?= $idProducto ?>">Ver producto</a>
                        </p>
                    <?php elseif ($hay <= 0): ?>
                        <p class="textoAyuda tarjetaProductoPie">Pronto vuelve a estar disponible.</p>
                    <?php elseif ($puedeAgregar > 0): ?>
                        <form method="post" action="<?= $urlBase ?>/carrito/agregar" class="formularioAgregar">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="tienda" value="<?= (int) $tiendaVista->getIdVendedor() ?>">
                            <input type="hidden" name="producto" value="<?= $idProducto ?>">
                            <div class="selectorCantidad" data-selector-cantidad data-maximo="<?= $puedeAgregar ?>">
                                <button type="button" class="botonCantidad" data-menos aria-label="Uno menos">−</button>
                                <input type="text" name="cantidad" value="1" maxlength="2" inputmode="numeric"
                                    aria-label="Cantidad de <?= htmlspecialchars($producto->getNombre()) ?>">
                                <button type="button" class="botonCantidad" data-mas aria-label="Uno más">+</button>
                            </div>
                            <button type="submit" class="boton">Agregar al carrito</button>
                        </form>
                    <?php else: ?>
                        <p class="textoAyuda tarjetaProductoPie">Ya tiene en el carrito todo lo disponible.</p>
                    <?php endif; ?>
                    <?php if (!$esDuenio): ?>
                        <a href="<?= $urlBase ?>/personalizar?tienda=<?= (int) $tiendaVista->getIdVendedor() ?>&amp;producto=<?= $idProducto ?>" class="enlacePersonalizar">Personalizar</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>