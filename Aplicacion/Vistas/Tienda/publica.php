<?php

use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\TipoContacto;

$titulo = (string) $vendedor->getTiendaNombre();
$claseTarjeta = 'tarjetaRegistro';
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';

$logo = $vendedor->getTiendaLogo();
$urlLogo = $logo !== null
    ? $urlBase . '/fotos/logo?archivo=' . urlencode($logo)
    : $urlBase . '/imagenes/logoPorDefecto.svg';
$descripcion = (string) $vendedor->getTiendaDescripcion();
$contactos = $vendedor->getContactos();
?>
<section class="formularioSeccion">
    <div class="zonaFoto">
        <img src="<?= htmlspecialchars($urlLogo) ?>" alt="Logo de la tienda" class="fotoPerfilGrande">
    </div>
    <h1 class="tituloPagina"><?= htmlspecialchars($titulo) ?></h1>
    <?php if ($descripcion !== ''): ?>
        <p class="subtitulo"><?= htmlspecialchars($descripcion) ?></p>
    <?php endif; ?>
    <?php if ($contactos !== []): ?>
        <dl class="listaDatos">
            <?php foreach ($contactos as $contacto): ?>
                <dt><?= htmlspecialchars(TipoContacto::nombre($contacto->getTipo())) ?></dt>
                <dd><?= htmlspecialchars(TipoContacto::formatear($contacto->getTipo(), $contacto->getValor())) ?></dd>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>
</section>

<section class="formularioSeccion">
    <h2>Productos</h2>
    <?php if ($productos === []): ?>
        <p class="estadoVacio estadoVacioPequeno">Esta tienda todavía no tiene productos.</p>
    <?php else: ?>
        <div class="tarjetas">
            <?php foreach ($productos as $producto): ?>
                <?php
                $foto = $producto->getFoto();
                $urlFoto = $foto !== null
                    ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto)
                    : $urlBase . '/imagenes/productoPorDefecto.svg';
                ?>
                <article class="tarjeta">
                    <img src="<?= htmlspecialchars($urlFoto) ?>" alt="" class="fotoPerfilGrande">
                    <h2><?= htmlspecialchars($producto->getNombre()) ?></h2>
                    <?php if ((string) $producto->getDescripcion() !== ''): ?>
                        <p class="textoAyuda"><?= htmlspecialchars((string) $producto->getDescripcion()) ?></p>
                    <?php endif; ?>
                    <p><strong><?= Numero::moneda($producto->getPrecioVenta()) ?></strong></p>
                    <p>
                        <?php if ($producto->getExistencia() > 0): ?>
                            <span class="etiqueta etiquetaActivo">Disponible</span>
                        <?php else: ?>
                            <span class="etiqueta etiquetaInactivo">Agotado</span>
                        <?php endif; ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>