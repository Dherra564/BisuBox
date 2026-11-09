<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Líneas de producto';
$paginaActual = 'productos';
$volverA = ['texto' => 'Productos', 'ruta' => '/productos'];
$botonAccion = $lineas !== [] ? ['texto' => 'Nueva línea', 'ruta' => '/lineas/nueva'] : null;
require __DIR__ . '/../Plantilla/encabezado.php';

$cuantos = fn(int $cantidad, string $uno, string $varios): string => $cantidad === 1 ? "1 {$uno}" : "{$cantidad} {$varios}";
usort($lineas, fn($a, $b): int => (int) $b->getActivo() <=> (int) $a->getActivo());
?>

<?php if ($lineas === []): ?>
    <div class="estadoVacio">
        <p>Una línea agrupa lo que vende un proveedor y lo que hace su tienda, por ejemplo Perlas, Cadenas o Pulseras.
            Sus clientes podrán filtrar la tienda por línea.</p>
        <a href="<?= $urlBase ?>/lineas/nueva" class="boton">Crear la primera línea</a>
    </div>
<?php else: ?>
    <p class="cajaInformativa">
        Asigne cada línea a los proveedores que la venden y a sus productos. Al registrar una compra, se le sugieren
        primero los proveedores de la línea que escoja.
    </p>

    <div class="tarjetas tarjetasTipo">
        <?php foreach ($lineas as $linea): ?>
            <article class="tarjeta tarjetaTipo <?= $linea->getActivo() ? '' : 'tarjetaInactiva' ?>">
                <div class="tarjetaTipoEncabezado">
                    <h2><?= htmlspecialchars($linea->getNombre()) ?></h2>
                    <?php if ($linea->getActivo()): ?>
                        <span class="etiqueta etiquetaActivo">Activa</span>
                    <?php else: ?>
                        <span class="etiqueta etiquetaInactivo">Desactivada</span>
                    <?php endif; ?>
                </div>
                <?php if ($linea->getDescripcion() !== null): ?>
                    <p><?= htmlspecialchars($linea->getDescripcion()) ?></p>
                <?php endif; ?>
                <p class="textoAyuda">
                    <a href="<?= $urlBase ?>/proveedores?linea=<?= $linea->getIdLinea() ?>"><?= $cuantos($linea->getCantidadProveedores(), 'proveedor', 'proveedores') ?></a>
                    · <?= $cuantos($linea->getCantidadProductos(), 'producto', 'productos') ?>
                </p>

                <div class="grupoBotones tarjetaAccion">
                    <a href="<?= $urlBase ?>/lineas/editar?id=<?= $linea->getIdLinea() ?>" class="boton botonSecundario botonPequeno">Editar</a>
                    <form method="post" action="<?= $urlBase ?>/lineas/estado" class="formularioEnLinea"
                        <?php if ($linea->getActivo()): ?>
                            data-titulo="Desactivar línea" data-boton="Desactivar"
                            data-confirmar="¿Desactivar la línea «<?= htmlspecialchars($linea->getNombre()) ?>»? Sus productos siguen a la venta, pero la línea ya no sale en los filtros de la tienda."
                        <?php endif; ?>>
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= $linea->getIdLinea() ?>">
                        <input type="hidden" name="activo" value="<?= $linea->getActivo() ? '0' : '1' ?>">
                        <button type="submit" class="boton botonSecundario botonPequeno <?= $linea->getActivo() ? 'botonPeligroSuave' : '' ?>">
                            <?= $linea->getActivo() ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>