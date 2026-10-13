<?php
/**
 * @var \Aplicacion\Modelos\Producto $producto
 * @var \Aplicacion\Modelos\Produccion[] $producciones
 * @var int $alcanzaPara
 * @var string[] $desactivados
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Detalle producto';
$paginaActual = 'productos';
$volverA = ['texto' => 'Productos', 'ruta' => '/productos'];
$scriptsExtra = ['productos.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$nombre = htmlspecialchars($producto->getNombre());
$existencia = $producto->getExistencia();
$foto = $producto->getFoto();
$urlFoto = $foto !== null
    ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto)
    : $urlBase . '/imagenes/productoPorDefecto.svg';
$ganancia = $producto->getGanancia();
$porcentaje = $producto->getPorcentajeGanancia();
$unidadesTexto = fn(int $cantidad): string => $cantidad === 1 ? '1 unidad' : $cantidad . ' unidades';
?>

<article class="fichaProducto <?= $producto->getActivo() ? '' : 'fichaProductoInactiva' ?>">
    <img src="<?= htmlspecialchars($urlFoto) ?>" alt="Foto de <?= $nombre ?>" class="fotoFichaProducto">

    <div class="datosFichaProducto">
        <h2 class="nombreFicha"><?= $nombre ?></h2>
        <?php if (!$producto->getActivo()): ?>
            <span class="etiqueta etiquetaInactivo">Desactivado</span>
        <?php endif; ?>
        <?php if ($producto->getDescripcion() !== null): ?>
            <p class="descripcionFicha"><?= htmlspecialchars($producto->getDescripcion()) ?></p>
        <?php endif; ?>

        <p class="precioFicha"><?= Numero::moneda($producto->getPrecioVenta()) ?></p>
        <p class="gananciaFicha <?= $ganancia < 0 ? 'gananciaNegativa' : '' ?>">
            <?= $ganancia >= 0 ? 'Gana ' . Numero::moneda($ganancia) : 'Pierde ' . Numero::moneda(-$ganancia) ?> por cada uno
            <?php if ($porcentaje !== null): ?>(<?= Numero::formatear($porcentaje) ?>%)<?php endif; ?>
        </p>

        <div class="existenciaFicha">
            <span class="numeroExistencia"><?= $existencia ?></span>
            <span><?= $existencia === 1 ? 'listo para vender' : 'listos para vender' ?></span>
        </div>

        <?php if ($producto->getActivo()): ?>
            <button type="button" class="boton" data-hice-mas data-id="<?= $producto->getIdProducto() ?>"
                    data-nombre="<?= $nombre ?>" data-alcanza="<?= $alcanzaPara ?>">Crear otro</button>
            <p class="textoAyuda">
                <?php if ($desactivados !== []): ?>
                    Lleva componentes desactivados: <?= htmlspecialchars(implode(', ', $desactivados)) ?>. Actívelos en el inventario para hacer más.
                <?php else: ?>
                    <?= $alcanzaPara > 0
                        ? 'Con lo que tiene en el inventario le alcanza para hacer ' . $alcanzaPara . ' más.'
                        : 'Ya no le alcanzan los componentes para hacer otro. Registre una compra.' ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</article>

<div class="columnasFicha">
    <section class="formularioSeccion">
        <h2>Lo que lleva cada uno</h2>
        <ul class="listaLleva">
            <?php foreach ($producto->getComponentes() as $linea): ?>
                <li>
                    <span>
                        <span class="nombreComponente"><?= htmlspecialchars($linea->getNombreComponente()) ?></span>
                        <span class="detalleComponente"><?= UnidadMedida::cantidad($linea->getCantidad(), $linea->getUnidad()) ?></span>
                    </span>
                    <span><?= Numero::moneda($linea->getSubtotal()) ?></span>
                </li>
            <?php endforeach; ?>
            <li>
                <span>
                    <span class="nombreComponente">Su trabajo</span>
                    <span class="detalleComponente"><?= Numero::duracion($producto->getMinutos()) ?> a <?= Numero::moneda($producto->getTarifaHora()) ?> la hora</span>
                </span>
                <span><?= Numero::moneda($producto->getCostoManoObra()) ?></span>
            </li>
            <?php foreach ($producto->getExtras() as $extra): ?>
                <li>
                    <span class="nombreComponente"><?= htmlspecialchars($extra->getConcepto()) ?></span>
                    <span><?= Numero::moneda($extra->getMonto()) ?></span>
                </li>
            <?php endforeach; ?>
            <li class="totalLleva">
                <span>Costo total</span>
                <span><?= Numero::moneda($producto->getCostoTotal()) ?></span>
            </li>
        </ul>
        <p class="textoAyuda">Con los costos que tenían los componentes cuando lo creó.</p>
    </section>

    <section class="formularioSeccion">
        <h2>Veces que lo ha hecho</h2>
        <?php if ($producciones === []): ?>
            <p class="textoAyuda">Todavía no hay registros.</p>
        <?php else: ?>
            <ul class="listaVeces">
                <?php foreach ($producciones as $produccion): ?>
                    <li class="<?= $produccion->estaAnulada() ? 'vezAnulada' : '' ?>">
                        <span>
                            <strong><?= $unidadesTexto($produccion->getCantidad()) ?></strong>
                            <span class="detalleComponente"><?= $produccion->getFecha()->format('d/m/Y') ?></span>
                        </span>
                        <?php if ($produccion->estaAnulada()): ?>
                            <span class="etiqueta etiquetaInactivo">Anulada</span>
                        <?php else: ?>
                            <form method="post" action="<?= $urlBase ?>/productos/anular-produccion" class="formularioEnLinea"
                                  data-titulo="Anular" data-boton="Anular" data-peligro
                                  data-confirmar="¿Anular <?= $produccion->getCantidad() === 1 ? 'la unidad' : 'las ' . $unidadesTexto($produccion->getCantidad()) ?> del <?= $produccion->getFecha()->format('d/m/Y') ?>? Se quitan de «<?= $nombre ?>» y los componentes vuelven al inventario.">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="id" value="<?= $produccion->getIdProduccion() ?>">
                                <button type="submit" class="boton botonSecundario botonPequeno botonPeligroSuave">Anular</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<div class="grupoBotones accionesLista">
    <a href="<?= $urlBase ?>/productos/editar?id=<?= $producto->getIdProducto() ?>" class="boton botonSecundario">Editar</a>
    <form method="post" action="<?= $urlBase ?>/productos/estado" class="formularioEnLinea"
          <?php if ($producto->getActivo()): ?>
              data-titulo="Desactivar producto" data-boton="Desactivar" data-peligro
              data-confirmar="¿Desactivar «<?= $nombre ?>»? No se borra y puede activarlo cuando quiera."
          <?php endif; ?>>
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= $producto->getIdProducto() ?>">
        <input type="hidden" name="activo" value="<?= $producto->getActivo() ? '0' : '1' ?>">
        <button type="submit" class="boton botonSecundario <?= $producto->getActivo() ? 'botonPeligroSuave' : '' ?>"><?= $producto->getActivo() ? 'Desactivar' : 'Activar' ?></button>
    </form>
</div>

<?php
$volverHiceMas = 'detalle';
require __DIR__ . '/hiceMas.php';
?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>