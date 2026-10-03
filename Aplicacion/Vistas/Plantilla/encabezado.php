<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\UsuarioActual;
use Configuracion\Configuracion;

    $urlBase      = rtrim((string) Configuracion::obtener('appUrl', ''), '/');
    $titulo       = $titulo ?? 'BisuBox';
    $paginaActual = $paginaActual ?? '';
    $botonAccion  = $botonAccion ?? null;
    $mensajes     = $mensajes ?? [];

    // Opciones del modulo de usuarios. Pendiente (Damian): mostrar solo las que permite el rol.
    $opcionesMenu = [
    'inicio'     => ['texto' => 'Inicio', 'ruta' => '/'],
    'vendedores' => ['texto' => 'Vendedores', 'ruta' => '/vendedores'],
    'sesiones'   => ['texto' => 'Historial de sesiones', 'ruta' => '/sesiones'],
    'perfil'     => ['texto' => 'Mi perfil', 'ruta' => '/perfil'],
    ];

    // Modulos que todavia no existen: se muestran en gris, sin enlace
    $proximasFases = ['Inventario', 'Proveedores', 'Compras', 'Producción', 'Pedidos', 'Ventas y caja', 'Gastos', 'Reportes'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($titulo) ?> | BisuBox</title>
    <!-- Sin icono en la pestaña. "data:," evita que el navegador pida favicon.ico y salga un error 404 -->
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo $urlBase ?>/css/estilos.css">
</head>
<body>
    <div class="aplicacion">
        <nav class="menuLateral" id="menuLateral" aria-label="Menú principal">
            <a href="<?php echo $urlBase ?>/" class="logo">BisuBox</a>
            <ul>
                <?php foreach ($opcionesMenu as $clave => $opcion): ?>
                    <li>
                        <a href="<?php echo $urlBase . $opcion['ruta'] ?>"
                           class="<?php echo $clave === $paginaActual ? 'activo' : '' ?>"
                           <?php echo $clave === $paginaActual ? 'aria-current="page"' : '' ?>>
                            <?php echo htmlspecialchars($opcion['texto']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="menuSeccion">Próximas fases</p>
            <ul class="menuProximas">
                <?php foreach ($proximasFases as $modulo): ?>
                    <li><span><?php echo htmlspecialchars($modulo) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="zonaPrincipal">
            <header class="barraSuperior">
                <button type="button" class="botonMenu" id="botonMenu" aria-label="Abrir menú" aria-expanded="false">☰</button>
                <span class="nombreNegocio">Negocio de bisutería</span>
                <a href="<?php echo $urlBase ?>/" class="logoCelular">BisuBox</a>
                <div class="usuarioActual">
                    <?php if (UsuarioActual::haySesion()): ?>
                        <a href="<?php echo $urlBase ?>/perfil" class="nombreUsuario"><?php echo htmlspecialchars((string) UsuarioActual::nombre()) ?></a>
                        <span class="etiqueta etiquetaRol"><?php echo htmlspecialchars((string) UsuarioActual::tipo()) ?></span>
                        <form method="post" action="<?php echo $urlBase ?>/salir" class="formularioSalir">
                        <?php echo Csrf::campo() ?>
                        <button type="submit" class="boton botonSecundario botonPequeno">Cerrar sesión</button>
                        </form>
                        <a href="<?php echo $urlBase ?>/salir" class="boton botonSecundario botonPequeno">Cerrar sesión</a>
                    <?php else: ?>
                        <span class="etiqueta etiquetaInactivo">Sin sesión</span>
                    <?php endif; ?>
                </div>
            </header>

            <main class="contenido">
                <div class="encabezadoPagina">
                    <h1 class="tituloPagina"><?php echo htmlspecialchars($titulo) ?></h1>
                    <?php if ($botonAccion !== null): ?>
                        <a href="<?php echo $urlBase . $botonAccion['ruta'] ?>" class="boton"><?php echo htmlspecialchars($botonAccion['texto']) ?></a>
                    <?php endif; ?>
                </div>
                <?php require __DIR__ . '/mensajes.php'; ?>