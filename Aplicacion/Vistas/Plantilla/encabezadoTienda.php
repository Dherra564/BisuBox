<?php
/**
 * Encabezado de la página de una tienda, como la ve el cliente: logo, buscador, cuenta y carrito.
 * Lo usan la tienda, el ingreso y registro de sus clientes, y todas las páginas del cliente
 * (carrito, mis pedidos, mi cuenta).
 *
 * @var \Aplicacion\Modelos\Vendedor|null $tienda  si no viene, se usa la tienda donde el cliente está comprando
 * @var string|null $titulo
 * @var bool|null $ocultarTitulo  la portada de la tienda no muestra título
 * @var string|null $busqueda
 * @var string|null $paginaActual
 */

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\TipoContacto;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\WhatsApp;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;

$urlBase = Configuracion::urlBase();
$usaDisenoTienda = true;
$mensajes = $mensajes ?? [];
$paginaActual = $paginaActual ?? '';
$busqueda = $busqueda ?? '';
$volverA = $volverA ?? null;
$botonesAccion = $botonesAccion ?? (isset($botonAccion) ? [$botonAccion] : []);

if (!isset($tienda)) {
    $tiendaActual = Carrito::tiendaActual();
    $tienda = $tiendaActual !== null ? (new VendedorRepositorio())->buscarPorId($tiendaActual['id']) : null;
}
// Se guarda aparte porque algunas páginas usan $tienda para otras cosas
$tiendaPagina = $tienda;
$nombreTienda = (string) $tiendaPagina->getTiendaNombre();
$titulo = $titulo ?? $nombreTienda;
$rutaTienda = $urlBase . '/tienda/' . $tiendaPagina->getTiendaEnlace();
$logo = $tiendaPagina->getTiendaLogo();
$urlLogo = $logo !== null ? $urlBase . '/fotos/logo?archivo=' . urlencode($logo) : null;

// ¿Quién está viendo? Un cliente (puede comprar), alguien sin sesión o un vendedor
$esClienteAqui = UsuarioActual::esCliente();
$haySesion = UsuarioActual::haySesion();

// Total del carrito para el ícono de arriba
$unidadesCarrito = 0;
$totalCarrito = 0.0;
if ($esClienteAqui) {
    $enCarrito = Carrito::deTienda((int) $tiendaPagina->getIdVendedor());
    if ($enCarrito !== []) {
        foreach ((new ProductoRepositorio())->buscarVarios((int) $tiendaPagina->getIdVendedor(), array_keys($enCarrito)) as $idProducto => $producto) {
            $unidadesCarrito += $enCarrito[$idProducto];
            $totalCarrito += $enCarrito[$idProducto] * $producto->getPrecioVenta();
        }
    }
}

$whatsAppTienda = null;
foreach ($tiendaPagina->getContactos() as $contacto) {
    if ($contacto->getTipo() === TipoContacto::WHATSAPP) {
        $whatsAppTienda = $contacto;
        break;
    }
}
$volverAqui = urlencode('/tienda/' . $tiendaPagina->getTiendaEnlace());
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo === $nombreTienda ? $nombreTienda : $titulo . ' | ' . $nombreTienda) ?></title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= Configuracion::recurso('css/estilos.css') ?>">
</head>

<body class="cuerpoTienda">
    <div class="tiendaBarraSuperior">
        <div class="tiendaContenedor tiendaBarraFila">
            <ul class="tiendaContactosBarra">
                <?php foreach ($tiendaPagina->getContactos() as $contacto): ?>
                    <li><?= htmlspecialchars(TipoContacto::nombre($contacto->getTipo())) ?>: <?= htmlspecialchars(TipoContacto::formatear($contacto->getTipo(), $contacto->getValor())) ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="tiendaSesion">
                <?php if ($esClienteAqui): ?>
                    <span>Hola, <?= htmlspecialchars(explode(' ', (string) UsuarioActual::nombre())[0]) ?></span>
                    <form method="post" action="<?= $urlBase ?>/salir" class="formularioEnLinea"
                        data-confirmar="¿Desea cerrar sesión?" data-titulo="Cerrar sesión" data-boton="Cerrar sesión">
                        <?= Csrf::campo() ?>
                        <button type="submit" class="botonEnlace">Cerrar sesión</button>
                    </form>
                <?php elseif (!$haySesion): ?>
                    <a href="<?= $urlBase ?>/ingresar?volver=<?= $volverAqui ?>">Ingresar</a>
                    <a href="<?= $urlBase ?>/registro/cliente?volver=<?= $volverAqui ?>">Crear cuenta</a>
                <?php else: ?>
                    <a href="<?= $urlBase ?>/">Ir a mi panel</a>
                    <form method="post" action="<?= $urlBase ?>/salir" class="formularioEnLinea"
                        data-confirmar="¿Desea cerrar sesión?" data-titulo="Cerrar sesión" data-boton="Cerrar sesión">
                        <?= Csrf::campo() ?>
                        <button type="submit" class="botonEnlace">Cerrar sesión</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <header class="tiendaEncabezado">
        <div class="tiendaContenedor tiendaEncabezadoFila">
            <form method="get" action="<?= $rutaTienda ?>" class="tiendaBuscador" role="search">
                <label for="buscarProducto" class="soloLectores">Buscar productos</label>
                <input type="search" id="buscarProducto" name="buscar" maxlength="60" placeholder="Buscar productos"
                    value="<?= htmlspecialchars($busqueda) ?>">
                <button type="submit" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
                </button>
            </form>

            <a href="<?= $rutaTienda ?>" class="tiendaMarca">
                <?php if ($urlLogo !== null): ?>
                    <img src="<?= htmlspecialchars($urlLogo) ?>" alt="" class="tiendaMarcaLogo">
                <?php endif; ?>
                <span class="tiendaMarcaNombre"><?= htmlspecialchars($nombreTienda) ?></span>
            </a>

            <nav class="tiendaIconos" aria-label="Su cuenta">
                <a href="<?= $esClienteAqui ? $urlBase . '/perfil' : $urlBase . '/ingresar?volver=' . $volverAqui ?>" class="tiendaIcono <?= $paginaActual === 'perfil' ? 'tiendaIconoActivo' : '' ?>"
                    aria-label="<?= $esClienteAqui ? 'Mi cuenta' : 'Ingresar' ?>" title="<?= $esClienteAqui ? 'Mi cuenta' : 'Ingresar' ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
                </a>
                <a href="<?= $esClienteAqui ? $urlBase . '/mis-pedidos' : $urlBase . '/ingresar?volver=' . urlencode('/mis-pedidos') ?>"
                    class="tiendaIcono <?= $paginaActual === 'misPedidos' ? 'tiendaIconoActivo' : '' ?>" aria-label="Mis pedidos" title="Mis pedidos">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                </a>
                <a href="<?= $esClienteAqui ? $urlBase . '/carrito' : $urlBase . '/ingresar?volver=' . $volverAqui ?>"
                    class="tiendaIcono tiendaCarrito <?= $paginaActual === 'carrito' ? 'tiendaIconoActivo' : '' ?>" aria-label="Carrito, <?= $unidadesCarrito ?> productos">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 11h10.2L20 8H6.2"/><circle cx="9" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/></svg>
                    <?php if ($unidadesCarrito > 0): ?><span class="tiendaCarritoNumero"><?= $unidadesCarrito ?></span><?php endif; ?>
                    <span class="tiendaCarritoTotal"><?= Numero::moneda($totalCarrito) ?></span>
                </a>
            </nav>
        </div>

        <nav class="tiendaMenu" aria-label="Secciones de la tienda">
            <div class="tiendaContenedor">
                <a href="<?= $rutaTienda ?>" class="<?= $paginaActual === 'tienda' ? 'tiendaMenuActivo' : '' ?>">Productos</a>
                <?php if ($esClienteAqui): ?>
                    <a href="<?= $urlBase ?>/carrito" class="<?= $paginaActual === 'carrito' ? 'tiendaMenuActivo' : '' ?>">Carrito</a>
                    <a href="<?= $urlBase ?>/mis-pedidos" class="<?= $paginaActual === 'misPedidos' ? 'tiendaMenuActivo' : '' ?>">Mis pedidos</a>
                    <a href="<?= $urlBase ?>/perfil" class="<?= $paginaActual === 'perfil' ? 'tiendaMenuActivo' : '' ?>">Mi cuenta</a>
                <?php endif; ?>
                <a href="#contacto">Contacto</a>
            </div>
        </nav>
    </header>

    <main class="tiendaContenido tiendaContenedor">
        <?php if ($volverA !== null): ?>
            <a href="<?= $urlBase . $volverA['ruta'] ?>" class="enlaceVolver">← <?= htmlspecialchars($volverA['texto']) ?></a>
        <?php endif; ?>
        <?php if (empty($ocultarTitulo)): ?>
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
