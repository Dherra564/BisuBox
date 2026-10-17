<?php

use Aplicacion\Nucleo\Navegacion;
use Aplicacion\Nucleo\Numero;
use Configuracion\Configuracion;

$urlBase = Configuracion::urlBase();
$mensajes = $mensajes ?? [];
$navegacion = Navegacion::armar(null, '');
$tiendaAcceso = $navegacion['tienda'];
$logoAcceso = $tiendaAcceso !== null ? $tiendaAcceso->getTiendaLogo() : null;
$nombreAcceso = $tiendaAcceso !== null ? (string) $tiendaAcceso->getTiendaNombre() : 'BisuBox';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?> | <?= htmlspecialchars($nombreAcceso) ?></title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= Configuracion::recurso('css/estilos.css') ?>">
</head>

<body>
    <div class="paginaAcceso paginaAccesoDividida">
        <aside class="accesoMarca">
            <?php if ($tiendaAcceso !== null): ?>
                <a href="<?= $urlBase . $navegacion['rutaTienda'] ?>" class="accesoVolver">← Volver a la tienda</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <div class="accesoMarcaTexto">
                <?php if ($logoAcceso !== null): ?>
                    <img src="<?= $urlBase ?>/fotos/logo?archivo=<?= urlencode($logoAcceso) ?>" alt="" class="accesoLogo">
                <?php endif; ?>
                <p class="accesoNombre"><?= htmlspecialchars($nombreAcceso) ?></p>
                <p class="accesoDescripcion">
                    <?= htmlspecialchars($tiendaAcceso !== null && (string) $tiendaAcceso->getTiendaDescripcion() !== ''
                        ? (string) $tiendaAcceso->getTiendaDescripcion()
                        : 'Bisutería hecha a mano, directo de quien la crea.') ?>
                </p>
                <?php if ($navegacion['unidadesCarrito'] > 0): ?>
                    <div class="accesoCarrito">
                        <span>Su carrito: <?= (int) $navegacion['unidadesCarrito'] ?>
                            <?= $navegacion['unidadesCarrito'] === 1 ? 'producto' : 'productos' ?></span>
                        <strong><?= Numero::moneda($navegacion['totalCarrito']) ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <span class="accesoHecho"><?= $tiendaAcceso !== null ? 'Hecho con BisuBox' : '' ?></span>
        </aside>

        <main class="accesoFormulario">
            <div class="tarjetaAcceso">