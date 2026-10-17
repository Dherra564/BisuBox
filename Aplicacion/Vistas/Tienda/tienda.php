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
            <p><strong>Atención:</strong> Su tienda está oculta y sus clientes no la pueden ver. Actívela en <a
                    href="<?php echo $urlBase ?>/tienda">Mi tienda</a>.</p>
        </div>
    <?php endif; ?>
    <div class="resumenDuenio">
        <a href="<?php echo $urlBase ?>/pedidos?estado=Pendiente"
            class="tarjetaResumen <?php echo $pedidosPendientes > 0 ? 'tarjetaResumenAviso' : '' ?>">
            <span>Pedidos pendientes</span>
            <strong><?php echo (int) $pedidosPendientes ?></strong>
        </a>
        <a href="<?php echo $urlBase ?>/productos" class="tarjetaResumen">
            <span>Productos agotados</span>
            <strong><?php echo (int) $agotados ?></strong>
        </a>
    </div>
<?php endif; ?>

<?php if ($busqueda === ''): ?>
    <section class="tiendaPortada">
        <div class="tiendaPortadaTexto">
            <p class="tiendaPortadaEtiqueta">Bisutería</p>
            <h1 class="tiendaPortadaTitulo"><?php echo htmlspecialchars((string) $tiendaVista->getTiendaNombre()) ?></h1>
            <?php if ((string) $tiendaVista->getTiendaDescripcion() !== ''): ?>
                <p class="tiendaPortadaDescripcion">
                    <?php echo htmlspecialchars((string) $tiendaVista->getTiendaDescripcion()) ?></p>
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
                                <a href="<?php echo htmlspecialchars($enlaceWhatsApp) ?>" target="_blank" rel="noopener">
                                    <?php echo Icono::svg($iconosContacto[$tipo] ?? 'web') ?>                 <?php echo htmlspecialchars($texto) ?>
                                </a>
                            <?php else: ?>
                                <span title="<?php echo htmlspecialchars(TipoContacto::nombre($tipo)) ?>">
                                    <?php echo Icono::svg($iconosContacto[$tipo] ?? 'web') ?>                 <?php echo htmlspecialchars($texto) ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!$esDuenio): ?>
                <p>
                    <a href="<?php echo $urlBase ?>/tienda/<?php echo htmlspecialchars((string) $tiendaVista->getTiendaEnlace()) ?>/personalizado"
                        class="boton">Pedir algo personalizado</a>
                </p>
            <?php endif; ?>
        </div>
        <?php if ($urlLogo !== null): ?>
            <img src="<?php echo htmlspecialchars($urlLogo) ?>"
                alt="Logo de <?php echo htmlspecialchars((string) $tiendaVista->getTiendaNombre()) ?>"
                class="tiendaPortadaLogo">
        <?php endif; ?>
    </section>
<?php endif; ?>

<section id="productos" class="tiendaSeccion">
    <div class="tiendaSeccionEncabezado">
        <h2><?php echo $busqueda !== '' ? 'Resultados para «' . htmlspecialchars($busqueda) . '»' : 'Nuestros productos' ?>
        </h2>
        <?php if ($busqueda !== ''): ?>
            <a
                href="<?php echo $urlBase ?>/tienda/<?php echo htmlspecialchars((string) $tiendaVista->getTiendaEnlace()) ?>#productos">Ver
                todos</a>
        <?php else: ?>
            <span
                class="textoAyuda"><?php echo count($productos) === 1 ? '1 producto' : count($productos) . ' productos' ?></span>
        <?php endif; ?>
    </div>

    <?php if ($productos === []): ?>
        <div class="estadoVacio">
            <p><?php echo $busqueda !== '' ? 'No encontramos productos con esa búsqueda.' : 'Esta tienda todavía no tiene productos.' ?>
            </p>
            <?php if ($esDuenio && $busqueda === ''): ?>
                <p><a href="<?php echo $urlBase ?>/productos/nuevo" class="boton">Agregar mi primer producto</a></p>
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
                <article class="tarjetaProducto <?php echo $hay <= 0 ? 'tarjetaProductoAgotada' : '' ?>">
                    <div class="fotoTarjetaMarco">
                        <img src="<?php echo htmlspecialchars($urlFoto) ?>" alt="" class="fotoTarjetaProducto" loading="lazy">
                        <?php if ($hay <= 0): ?><span class="sello">Agotado</span><?php elseif ($hay <= 2): ?><span
                                class="sello selloPocos"><?php echo $hay === 1 ? 'Último' : 'Últimos ' . $hay ?></span><?php endif; ?>
                    </div>
                    <div class="tarjetaProductoTexto">
                        <h3><?php echo htmlspecialchars($producto->getNombre()) ?></h3>
                        <?php if ((string) $producto->getDescripcion() !== ''): ?>
                            <p class="textoAyuda"><?php echo htmlspecialchars((string) $producto->getDescripcion()) ?></p>
                        <?php endif; ?>
                        <p class="precioProducto"><?php echo Numero::moneda($producto->getPrecioVenta()) ?></p>
                        <?php if ($yaTiene > 0): ?>
                            <p class="textoAyuda"><strong>Tiene <?php echo $yaTiene ?> en el carrito</strong></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($esDuenio): ?>
                        <p class="textoAyuda tarjetaProductoPie">
                            <?php echo $hay > 0 ? 'Quedan ' . $hay : 'Agotado' ?> ·
                            <a href="<?php echo $urlBase ?>/productos/detalle?id=<?php echo $idProducto ?>">Ver producto</a>
                        </p>
                    <?php elseif ($hay <= 0): ?>
                        <p class="textoAyuda tarjetaProductoPie">Pronto vuelve a estar disponible.</p>
                    <?php elseif ($puedeAgregar > 0): ?>
                        <form method="post" action="<?php echo $urlBase ?>/carrito/agregar" class="formularioAgregar">
                            <?php echo Csrf::campo() ?>
                            <input type="hidden" name="tienda" value="<?php echo (int) $tiendaVista->getIdVendedor() ?>">
                            <input type="hidden" name="producto" value="<?php echo $idProducto ?>">
                            <div class="selectorCantidad" data-selector-cantidad data-maximo="<?php echo $puedeAgregar ?>">
                                <button type="button" class="botonCantidad" data-menos aria-label="Uno menos">−</button>
                                <input type="text" name="cantidad" value="1" maxlength="2" inputmode="numeric"
                                    aria-label="Cantidad de <?php echo htmlspecialchars($producto->getNombre()) ?>">
                                <button type="button" class="botonCantidad" data-mas aria-label="Uno más">+</button>
                            </div>
                            <button type="submit" class="boton">Agregar</button>
                        </form>
                    <?php else: ?>
                        <p class="textoAyuda tarjetaProductoPie">Ya tiene en el carrito todo lo disponible.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>