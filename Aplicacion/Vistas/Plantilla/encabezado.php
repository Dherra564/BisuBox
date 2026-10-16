<?php

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;

// El cliente ve sus páginas (carrito, pedidos, perfil) con el diseño de la tienda donde está comprando
if (UsuarioActual::esCliente() && Carrito::tiendaActual() !== null) {
    require __DIR__ . '/encabezadoTienda.php';
    return;
}

$urlBase = Configuracion::urlBase();
$titulo = $titulo ?? 'BisuBox';
$paginaActual = $paginaActual ?? '';
$botonAccion = $botonAccion ?? null;
$botonesAccion = $botonesAccion ?? ($botonAccion !== null ? [$botonAccion] : []);
$volverA = $volverA ?? null;
$mensajes = $mensajes ?? [];

// Números que se muestran junto al menú: unidades en el carrito (cliente) y pedidos por confirmar (vendedor)
$numeroMenu = [];
if (UsuarioActual::esCliente()) {
    $numeroMenu['carrito'] = Carrito::unidades();
} elseif (UsuarioActual::esVendedor()) {
    try {
        $vendedorMenu = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedorMenu !== null) {
            $numeroMenu['pedidos'] = (new PedidoRepositorio())->contar([
                'idVendedor' => (int) $vendedorMenu->getIdVendedor(),
                'estado' => EstadoPedido::PENDIENTE,
            ]);
        }
    } catch (Throwable $error) {
        error_log('No se pudieron contar los pedidos pendientes: ' . $error->getMessage());
    }
}

$opcionesMenu = [
    'inicio' => ['texto' => 'Inicio', 'ruta' => '/', 'permiso' => 'panel.ver'],
    'carrito' => ['texto' => 'Carrito', 'ruta' => '/carrito', 'permiso' => 'carrito.usar'],
    'misPedidos' => ['texto' => 'Mis pedidos', 'ruta' => '/mis-pedidos', 'permiso' => 'misPedidos.ver'],
    'pedidos' => ['texto' => 'Pedidos', 'ruta' => '/pedidos', 'permiso' => 'pedidos.gestionar'],
    'clientes' => ['texto' => 'Clientes', 'ruta' => '/clientes', 'permiso' => 'clientes.ver'],
    'productos' => ['texto' => 'Productos', 'ruta' => '/productos', 'permiso' => 'productos.gestionar'],
    'inventario' => ['texto' => 'Inventario', 'ruta' => '/inventario', 'permiso' => 'inventario.gestionar'],
    'proveedores' => ['texto' => 'Proveedores', 'ruta' => '/proveedores', 'permiso' => 'proveedores.gestionar'],
    'tienda' => ['texto' => 'Mi tienda', 'ruta' => '/tienda', 'permiso' => 'tienda.gestionar'],
    'perfil' => ['texto' => 'Mi perfil', 'ruta' => '/perfil', 'permiso' => 'perfil.ver'],
    'sesiones' => ['texto' => 'Historial de sesiones', 'ruta' => '/sesiones', 'permiso' => 'sesiones.ver'],
];
// El cliente ve un enlace a la tienda donde está comprando
$tiendaCliente = UsuarioActual::esCliente() ? Carrito::tiendaActual() : null;
if ($tiendaCliente !== null) {
    $opcionesMenu = ['inicio' => $opcionesMenu['inicio'], 'tiendaCliente' => ['texto' => 'Tienda', 'ruta' => '/tienda/' . $tiendaCliente['enlace'], 'permiso' => 'carrito.usar']] + $opcionesMenu;
    unset($opcionesMenu['inicio']);
}
$opcionesMenu = array_filter($opcionesMenu, fn(array $opcion): bool => Permiso::puede($opcion['permiso']));
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
                            <?php if (!empty($numeroMenu[$clave])): ?>
                                <span class="numeroMenu" aria-label="<?= (int) $numeroMenu[$clave] ?> <?= $clave === 'carrito' ? 'en el carrito' : 'por confirmar' ?>"><?= (int) $numeroMenu[$clave] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
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