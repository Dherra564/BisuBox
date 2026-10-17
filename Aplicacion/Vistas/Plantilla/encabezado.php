<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Icono;
use Aplicacion\Nucleo\Navegacion;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;
use Configuracion\Configuracion;

$urlBase = Configuracion::urlBase();
$titulo = $titulo ?? 'BisuBox';
$paginaActual = $paginaActual ?? '';
$botonAccion = $botonAccion ?? null;
$botonesAccion = $botonesAccion ?? ($botonAccion !== null ? [$botonAccion] : []);
$volverA = $volverA ?? null;
$mensajes = $mensajes ?? [];
$busqueda = $busqueda ?? '';
$ocultarTitulo = $ocultarTitulo ?? false;

$navegacion = Navegacion::armar($tiendaVista ?? null, $paginaActual);
$tiendaSitio = $navegacion['tienda'];
$nombreSitio = $tiendaSitio !== null ? (string) $tiendaSitio->getTiendaNombre() : 'BisuBox';
$rutaMarca = $urlBase . ($navegacion['rutaTienda'] ?? '/');
$haySesion = UsuarioActual::haySesion();
$nombreCorto = $haySesion ? explode(' ', trim((string) UsuarioActual::nombre()))[0] : '';
$rolTexto = $haySesion ? ($navegacion['esDuenio'] ? 'Dueño de esta tienda' : Rol::nombre(UsuarioActual::rol())) : '';
$volverAqui = urlencode($navegacion['rutaTienda'] ?? '/');
$enlaceCompartir = null;
if ($navegacion['esDuenio']) {
    $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $enlaceCompartir = $esquema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $urlBase . $navegacion['rutaTienda'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo === $nombreSitio ? $nombreSitio : $titulo . ' | ' . $nombreSitio) ?></title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= Configuracion::recurso('css/estilos.css') ?>">
    <script>
        try {
            if (localStorage.getItem('bisuboxMenuCerrado') === 'si' && window.matchMedia('(min-width: 901px)').matches) {
                document.documentElement.classList.add('menuCerrado');
            }
        } catch (error) {
        }
    </script>
</head>

<body class="sitio">
    <header class="barraSitio">
        <div class="barraSitioIzquierda">
            <button type="button" class="botonMenuSitio" id="botonMenu" aria-controls="menuSitio" aria-expanded="true"
                aria-label="Cerrar menú" title="Cerrar menú"><?= Icono::svg('menu') ?></button>
            <?php if ($tiendaSitio !== null): ?>
                <form method="get" action="<?= $rutaMarca ?>" class="tiendaBuscador" role="search">
                    <label for="buscarProducto" class="soloLectores">Buscar productos</label>
                    <input type="search" id="buscarProducto" name="buscar" maxlength="60" placeholder="Buscar productos"
                        value="<?= htmlspecialchars($busqueda) ?>">
                    <button type="submit" aria-label="Buscar"><?= Icono::svg('buscar') ?></button>
                </form>
            <?php endif; ?>
        </div>

        <a href="<?= $rutaMarca ?>" class="tiendaMarca">
            <span class="tiendaMarcaNombre"><?= htmlspecialchars($nombreSitio) ?></span>
        </a>

        <nav class="tiendaIconos" aria-label="Su cuenta">
            <div class="menuCuenta">
                <button type="button" class="tiendaIcono" id="botonCuenta" aria-expanded="false"
                    aria-controls="panelCuenta" aria-label="<?= $haySesion ? 'Mi cuenta' : 'Ingresar o crear cuenta' ?>"
                    title="<?= $haySesion ? 'Mi cuenta' : 'Ingresar o crear cuenta' ?>">
                    <?= Icono::svg('perfil') ?>
                    <?php if ($haySesion): ?><span
                            class="nombreCuenta"><?= htmlspecialchars($nombreCorto) ?></span><?php endif; ?>
                </button>
                <div class="panelCuenta" id="panelCuenta" hidden>
                    <?php if ($haySesion): ?>
                        <div class="panelCuentaUsuario">
                            <strong><?= htmlspecialchars((string) UsuarioActual::nombre()) ?></strong>
                            <span><?= htmlspecialchars($rolTexto) ?></span>
                        </div>
                        <a href="<?= $urlBase ?>/perfil"><?= Icono::svg('perfil') ?> Mi perfil</a>
                        <form method="post" action="<?= $urlBase ?>/salir" data-confirmar="¿Desea cerrar sesión?"
                            data-titulo="Cerrar sesión" data-boton="Cerrar sesión">
                            <?= Csrf::campo() ?>
                            <button type="submit" class="opcionSalir"><?= Icono::svg('salir') ?> Cerrar sesión</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= $urlBase ?>/ingresar?volver=<?= $volverAqui ?>"
                            class="opcionPrincipal"><?= Icono::svg('ingresar') ?> Ingresar</a>
                        <a href="<?= $urlBase ?>/registro?volver=<?= $volverAqui ?>"><?= Icono::svg('perfil') ?> Crear
                            cuenta</a>
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?= $urlBase . $navegacion['rutaPedidos'] ?>"
                class="tiendaIcono <?= in_array($paginaActual, ['misPedidos', 'pedidos'], true) ? 'tiendaIconoActivo' : '' ?>"
                aria-label="<?= $navegacion['textoPedidos'] ?>"
                title="<?= $navegacion['textoPedidos'] ?>"><?= Icono::svg('bolsa') ?></a>
            <?php if ($navegacion['puedeComprar']): ?>
                <a href="<?= $urlBase ?>/carrito"
                    class="tiendaIcono tiendaCarrito <?= $paginaActual === 'carrito' ? 'tiendaIconoActivo' : '' ?>"
                    aria-label="Carrito, <?= (int) $navegacion['unidadesCarrito'] ?> productos" title="Carrito">
                    <?= Icono::svg('carrito') ?>
                    <?php if ($navegacion['unidadesCarrito'] > 0): ?><span
                            class="tiendaCarritoNumero"><?= (int) $navegacion['unidadesCarrito'] ?></span><?php endif; ?>
                    <span class="tiendaCarritoTotal"><?= Numero::moneda($navegacion['totalCarrito']) ?></span>
                </a>
            <?php endif; ?>
            <?php if ($enlaceCompartir !== null): ?>
                <button type="button" class="botonCopiarEnlace" data-copiar="<?= htmlspecialchars($enlaceCompartir) ?>"
                    title="Copiar el enlace de su tienda"><?= Icono::svg('enlace') ?> <span>Copiar enlace</span></button>
            <?php endif; ?>
        </nav>
    </header>

    <div class="cuerpoSitio">
        <nav class="menuSitio" id="menuSitio" aria-label="Menú principal">
            <ul>
                <?php foreach ($navegacion['menu'] as $opcion): ?>
                    <?php if (isset($opcion['separador'])): ?>
                        <li class="menuSeparador" aria-hidden="true"><span><?= htmlspecialchars($opcion['separador']) ?></span>
                        </li>
                        <?php continue; ?>
                    <?php endif; ?>
                    <li>
                        <a href="<?= $urlBase . htmlspecialchars($opcion['ruta']) ?>"
                            title="<?= htmlspecialchars($opcion['texto']) ?>"
                            class="<?= $opcion['activo'] ? 'activo' : '' ?> <?= $opcion['clave'] === 'emprender' ? 'opcionEmprender' : '' ?>"
                            <?= $opcion['activo'] ? 'aria-current="page"' : '' ?>>
                            <?= Icono::svg($opcion['icono']) ?>
                            <span class="textoMenu"><?= htmlspecialchars($opcion['texto']) ?></span>
                            <?php if ($opcion['numero'] > 0): ?>
                                <span class="numeroMenu <?= $opcion['clave'] === 'pedidos' ? 'numeroMenuAviso' : '' ?>"
                                    aria-label="<?= (int) $opcion['numero'] ?> <?= $opcion['clave'] === 'carrito' ? 'en el carrito' : 'por confirmar' ?>"><?= (int) $opcion['numero'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (UsuarioActual::esCliente() && $paginaActual !== 'emprender'): ?>
                <div class="tarjetaMenu">
                    <strong>¿Le gustaría comenzar a emprender?</strong>
                    <span>Abra su tienda con los datos de su cuenta.</span>
                    <a href="<?= $urlBase ?>/emprender" class="boton botonPequeno">Quiero mi tienda</a>
                </div>
            <?php elseif (!$haySesion): ?>
                <div class="tarjetaMenu">
                    <strong>¿Ya tiene cuenta?</strong>
                    <a href="<?= $urlBase ?>/ingresar?volver=<?= $volverAqui ?>" class="boton botonPequeno">Ingresar</a>
                    <a href="<?= $urlBase ?>/registro?volver=<?= $volverAqui ?>">Crear cuenta</a>
                </div>
            <?php endif; ?>
        </nav>
        <div class="veloMenu" id="veloMenu" hidden></div>

        <main class="contenido">
            <?php if ($volverA !== null): ?>
                <a href="<?= $urlBase . $volverA['ruta'] ?>" class="enlaceVolver">←
                    <?= htmlspecialchars($volverA['texto']) ?></a>
            <?php endif; ?>
            <?php if (!$ocultarTitulo): ?>
                <div class="encabezadoPagina">
                    <h1 class="tituloPagina"><?= htmlspecialchars($titulo) ?></h1>
                    <?php if ($botonesAccion !== []): ?>
                        <div class="grupoBotones">
                            <?php foreach ($botonesAccion as $boton): ?>
                                <a href="<?= $urlBase . $boton['ruta'] ?>"
                                    class="boton <?= !empty($boton['secundario']) ? 'botonSecundario' : '' ?>"><?= htmlspecialchars($boton['texto']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php require __DIR__ . '/mensajes.php'; ?>