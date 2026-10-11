<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;
use Configuracion\Configuracion;

$urlBase = rtrim((string) Configuracion::obtener('appUrl', ''), '/');
$titulo = $titulo ?? 'BisuBox';
$paginaActual = $paginaActual ?? '';
$botonAccion = $botonAccion ?? null;
$botonesAccion = $botonesAccion ?? ($botonAccion !== null ? [$botonAccion] : []);
$volverA = $volverA ?? null;
$mensajes = $mensajes ?? [];

$opcionesMenu = [
    'inicio' => ['texto' => 'Inicio', 'ruta' => '/', 'permiso' => 'panel.ver'],
    'perfil' => ['texto' => 'Mi perfil', 'ruta' => '/perfil', 'permiso' => 'perfil.ver'],
    'tienda' => ['texto' => 'Mi tienda', 'ruta' => '/tienda', 'permiso' => 'tienda.gestionar'],
    'inventario' => ['texto' => 'Inventario', 'ruta' => '/inventario', 'permiso' => 'inventario.gestionar'],
    'sesiones' => ['texto' => 'Historial de sesiones', 'ruta' => '/sesiones', 'permiso' => 'sesiones.ver'],
];
$opcionesMenu = array_filter($opcionesMenu, fn(array $opcion): bool => Permiso::puede($opcion['permiso']));

$proximasFases = UsuarioActual::esVendedor()
    ? ['Proveedores', 'Compras', 'Productos', 'Producción', 'Pedidos']
    : ['Tiendas', 'Carrito', 'Mis pedidos'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?> | BisuBox</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= Configuracion::recurso('css/estilos.css') ?>">
</head>

<body>
    <div class="aplicacion">
        <nav class="menuLateral" id="menuLateral" aria-label="Menú principal">
            <a href="<?= $urlBase ?>/" class="logo">BisuBox</a>
            <ul>
                <?php foreach ($opcionesMenu as $clave => $opcion): ?>
                    <li>
                        <a href="<?= $urlBase . $opcion['ruta'] ?>" class="<?= $clave === $paginaActual ? 'activo' : '' ?>"
                            <?= $clave === $paginaActual ? 'aria-current="page"' : '' ?>>
                            <?= htmlspecialchars($opcion['texto']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="menuSeccion">Próximas fases</p>
            <ul class="menuProximas">
                <?php foreach ($proximasFases as $modulo): ?>
                    <li><span><?= htmlspecialchars($modulo) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="zonaPrincipal">
            <header class="barraSuperior">
                <button type="button" class="botonMenu" id="botonMenu" aria-label="Abrir menú"
                    aria-expanded="false">☰</button>
                <span class="nombreNegocio">Negocio de bisutería</span>
                <a href="<?= $urlBase ?>/" class="logoCelular">BisuBox</a>
                <div class="usuarioActual">
                    <?php if (UsuarioActual::haySesion()): ?>
                        <img src="<?= $urlBase ?>/fotos/perfil?archivo=<?= urlencode((string) UsuarioActual::foto()) ?>"
                            alt="" class="fotoUsuario">
                        <a href="<?= $urlBase ?>/perfil"
                            class="nombreUsuario"><?= htmlspecialchars((string) UsuarioActual::nombre()) ?></a>
                        <span class="etiqueta etiquetaRol"><?= htmlspecialchars(Rol::nombre(UsuarioActual::rol())) ?></span>
                        <form method="post" action="<?= $urlBase ?>/salir" class="formularioSalir"
                            data-confirmar="¿Desea cerrar sesión?" data-titulo="Cerrar sesión" data-boton="Cerrar sesión">
                            <?= Csrf::campo() ?>
                            <button type="submit" class="boton botonSecundario botonPequeno">Cerrar sesión</button>
                        </form>
                    <?php else: ?>
                        <span class="etiqueta etiquetaInactivo">Sin sesión</span>
                    <?php endif; ?>
                </div>
            </header>

            <main class="contenido">
                <?php if ($volverA !== null): ?>
                    <a href="<?= $urlBase . $volverA['ruta'] ?>" class="enlaceVolver">← <?= htmlspecialchars($volverA['texto']) ?></a>
                <?php endif; ?>
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
                <?php require __DIR__ . '/mensajes.php'; ?>