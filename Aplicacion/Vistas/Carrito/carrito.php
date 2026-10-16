<?php
/**
 * Carrito del cliente, separado por tienda
 * @var array<int, array{vendedor: \Aplicacion\Modelos\Vendedor, lineas: array, total: float, problemas: bool}> $tiendas  solo la tienda actual
 * @var array{id: int, enlace: string}|null $tiendaActual
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;

$titulo = 'Carrito';
$paginaActual = 'carrito';
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';
?>

<?php if ($tiendas === []): ?>
    <div class="estadoVacio">
        <p>Su carrito está vacío.</p>
        <?php if ($tiendaActual !== null): ?>
            <p><a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars($tiendaActual['enlace']) ?>" class="boton">Ver productos</a></p>
        <?php else: ?>
            <p>Abra el enlace de la tienda donde quiere comprar.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($tiendas as $idVendedor => $tienda): ?>
        <?php $vendedor = $tienda['vendedor']; ?>
        <section class="factura carritoTienda">
            <header class="encabezadoSeccion">
                <h2>
                    <a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars((string) $vendedor->getTiendaEnlace()) ?>">
                        <?= htmlspecialchars((string) $vendedor->getTiendaNombre()) ?>
                    </a>
                </h2>
            </header>

            <form method="post" action="<?= $urlBase ?>/carrito/actualizar" class="formularioCarrito">
                <?= Csrf::campo() ?>
                <input type="hidden" name="tienda" value="<?= (int) $idVendedor ?>">

                <div class="tablaContenedor">
                    <table class="tabla tablaFactura">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="numero">Precio</th>
                                <th>Cantidad</th>
                                <th class="numero">Subtotal</th>
                                <th><span class="soloLectores">Quitar</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tienda['lineas'] as $linea): ?>
                                <?php
                                $producto = $linea['producto'];
                                $idProducto = (int) $producto->getIdProducto();
                                $maximo = max(1, min($producto->getExistencia(), Carrito::MAXIMO_POR_PRODUCTO));
                                ?>
                                <tr class="<?= $linea['aviso'] !== null ? 'filaConAviso' : '' ?>">
                                    <td data-etiqueta="Producto">
                                        <span>
                                            <strong><?= htmlspecialchars($producto->getNombre()) ?></strong>
                                            <?php if ($linea['aviso'] !== null): ?>
                                                <br><span class="errorCampo"><?= htmlspecialchars($linea['aviso']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td data-etiqueta="Precio" class="numero"><?= Numero::moneda($producto->getPrecioVenta()) ?></td>
                                    <td data-etiqueta="Cantidad">
                                        <div class="selectorCantidad" data-selector-cantidad data-maximo="<?= $maximo ?>" data-enviar-al-cambiar>
                                            <button type="button" class="botonCantidad" data-menos aria-label="Uno menos">−</button>
                                            <input type="text" name="cantidad[<?= $idProducto ?>]" value="<?= $linea['cantidad'] ?>"
                                                maxlength="2" inputmode="numeric" aria-label="Cantidad de <?= htmlspecialchars($producto->getNombre()) ?>">
                                            <button type="button" class="botonCantidad" data-mas aria-label="Uno más">+</button>
                                        </div>
                                    </td>
                                    <td data-etiqueta="Subtotal" class="numero"><strong><?= Numero::moneda($linea['subtotal']) ?></strong></td>
                                    <td data-etiqueta="" class="acciones">
                                        <button type="submit" name="quitar" value="<?= $idProducto ?>" class="botonEnlace">Quitar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <noscript>
                    <p><button type="submit" class="boton botonSecundario botonPequeno">Actualizar cantidades</button></p>
                </noscript>
            </form>

            <div class="totalesCompra">
                <div class="totalCompra"><span>Total</span><strong><?= Numero::moneda($tienda['total']) ?></strong></div>
            </div>

            <div class="grupoBotones accionesCarrito">
                <a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars((string) $vendedor->getTiendaEnlace()) ?>" class="boton botonSecundario">Seguir comprando</a>
                <?php if ($tienda['problemas']): ?>
                    <span class="textoAyuda">Corrija lo marcado en rojo para poder pedir.</span>
                <?php else: ?>
                    <a href="<?= $urlBase ?>/carrito/confirmar?tienda=<?= (int) $idVendedor ?>" class="boton">Hacer pedido</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>
