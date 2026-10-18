<?php

use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Personalice su pieza';
$paginaActual = 'inicio';
$volverA = ['texto' => 'Volver a la tienda', 'ruta' => '/tienda/' . $tiendaVista->getTiendaEnlace()];
$scriptsExtra = ['pedido.js', 'personalizar.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$fotoComponente = fn(?string $foto): string => $foto !== null
    ? $urlBase . '/fotos/componente?archivo=' . urlencode($foto)
    : $urlBase . '/imagenes/productoPorDefecto.svg';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo" data-error-servidor>' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';

$piezas = Carrito::leerCantidad((string) $datos['piezas']) ?? 1;
$total = 0.0;
$cambios = [];
$primeraConError = null;
foreach ($lineas as $id => $linea) {
    $elegido = $datos['elegidos'][$id] !== null ? ($linea['opciones'][$datos['elegidos'][$id]] ?? null) : null;
    $lineas[$id]['elegido'] = $elegido;
    $lineas[$id]['mostrado'] = $elegido ?? $linea['original'];
    $lineas[$id]['cambiado'] = $elegido !== null && (int) $elegido->getIdComponente() !== $linea['idOriginal'];
    if ($elegido !== null) {
        $total += $elegido->getCostoUnitario() * $linea['cantidad'] * $piezas;
    }
    if ($lineas[$id]['cambiado']) {
        $cambios[] = $linea['original']->getNombre() . ' → ' . $elegido->getNombre();
    }
    if ($primeraConError === null && (isset($errores['componente.' . $id]) || $elegido === null)) {
        $primeraConError = $id;
    }
}
$fotoProducto = $producto->getFoto();
?>

<p class="introPagina">
    Toque una pieza y escoja otra del mismo tipo mirando las fotos. La cantidad de cada pieza es la misma del original.
    La tienda revisa su pedido y le manda el precio final.
</p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise lo que está marcado en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/personalizar" class="formularioPersonalizar" novalidate data-personalizar
    data-primera="<?= $primeraConError ?? array_key_first($lineas) ?>">
    <?= Csrf::campo() ?>
    <input type="hidden" name="tienda" value="<?= (int) $tiendaVista->getIdVendedor() ?>">
    <input type="hidden" name="producto" value="<?= (int) $producto->getIdProducto() ?>">

    <div class="personalizarArriba">
        <section class="tarjetaBase" aria-label="Producto">
            <div class="fotoBase">
                <?php if ($fotoProducto !== null): ?>
                    <img src="<?= $urlBase ?>/fotos/producto?archivo=<?= urlencode($fotoProducto) ?>" alt="">
                <?php else: ?>
                    <img src="<?= $urlBase ?>/imagenes/productoPorDefecto.svg" alt="" class="fotoBaseVacia">
                <?php endif; ?>
            </div>
            <div class="textoBase">
                <span class="etiquetaBase">Basado en</span>
                <strong class="nombreBase"><?= htmlspecialchars($producto->getNombre()) ?></strong>
                <div class="campo <?= isset($errores['piezas']) ? 'campoConError' : '' ?>">
                    <label for="piezas">¿Cuántas piezas quiere?</label>
                    <div class="selectorCantidad" data-selector-cantidad data-maximo="<?= Carrito::MAXIMO_POR_PRODUCTO ?>">
                        <button type="button" class="botonCantidad" data-menos aria-label="Una pieza menos">−</button>
                        <input type="text" id="piezas" name="piezas" value="<?= htmlspecialchars((string) $datos['piezas']) ?>"
                            maxlength="2" inputmode="numeric" data-piezas>
                        <button type="button" class="botonCantidad" data-mas aria-label="Una pieza más">+</button>
                    </div>
                    <?= $mensajeError('piezas') ?>
                </div>
            </div>
        </section>

        <section class="piezasPersonalizar" aria-labelledby="tituloPiezas">
            <div class="encabezadoSeccion">
                <h2 id="tituloPiezas">1. Toque la pieza que quiere cambiar</h2>
                <span class="textoAyuda" data-texto-cambios><?= count($cambios) === 0 ? 'Sin cambios' : (count($cambios) === 1 ? '1 cambio' : count($cambios) . ' cambios') ?></span>
            </div>
            <div class="gridPiezas">
                <?php foreach ($lineas as $id => $linea): ?>
                    <?php $conError = isset($errores['componente.' . $id]); ?>
                    <button type="button" class="tarjetaPieza <?= $conError ? 'tarjetaPiezaError' : '' ?>" data-pieza="<?= $id ?>"
                        aria-controls="galeria-<?= $id ?>" aria-pressed="false">
                        <span class="fotoPieza">
                            <img src="<?= htmlspecialchars($fotoComponente($linea['mostrado']->getFoto())) ?>" alt="" data-pieza-foto>
                            <span class="selloPieza selloCambiado" data-pieza-cambiado <?= $linea['cambiado'] ? '' : 'hidden' ?>>Cambiado</span>
                            <span class="selloPieza selloFalta" data-pieza-falta <?= $conError ? '' : 'hidden' ?>>Revisar</span>
                            <span class="cantidadPieza"><?= htmlspecialchars(UnidadMedida::cantidad($linea['cantidad'], $linea['unidad'])) ?></span>
                        </span>
                        <span class="textoPieza">
                            <span class="tipoPieza"><?= htmlspecialchars($linea['tipo']) ?></span>
                            <span class="nombrePieza" data-pieza-nombre><?= htmlspecialchars($linea['mostrado']->getNombre()) ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <?php foreach ($lineas as $id => $linea): ?>
        <?php $tipoMinuscula = mb_strtolower($linea['tipo'], 'UTF-8'); ?>
        <section class="galeriaPieza" id="galeria-<?= $id ?>" data-galeria="<?= $id ?>"
            data-cantidad="<?= $linea['cantidad'] ?>" data-original="<?= $linea['idOriginal'] ?>"
            data-nombre-original="<?= htmlspecialchars($linea['original']->getNombre()) ?>"
            role="radiogroup" aria-labelledby="tituloGaleria-<?= $id ?>">
            <div class="galeriaEncabezado">
                <div>
                    <h2 id="tituloGaleria-<?= $id ?>">2. Escoja su <?= htmlspecialchars($tipoMinuscula) ?></h2>
                    <p class="textoAyuda">
                        Solo se muestran los de tipo <?= htmlspecialchars($tipoMinuscula) ?>.
                        Antes llevaba: <?= htmlspecialchars($linea['original']->getNombre()) ?>.
                    </p>
                </div>
                <div class="galeriaAcciones">
                    <?php if ($linea['originalDisponible']): ?>
                        <button type="button" class="botonEnlace" data-volver-original <?= $linea['cambiado'] ? '' : 'hidden' ?>>Volver al original</button>
                    <?php endif; ?>
                    <span class="chipLleva">Lleva <?= htmlspecialchars(UnidadMedida::cantidad($linea['cantidad'], $linea['unidad'])) ?> por pieza</span>
                </div>
            </div>

            <?= $mensajeError('componente.' . $id) ?>
            <p class="errorCampo" data-error-galeria hidden></p>

            <?php if ($linea['opciones'] === []): ?>
                <p class="estadoVacio estadoVacioPequeno">La tienda no tiene <?= htmlspecialchars($tipoMinuscula) ?> disponible en este momento.</p>
            <?php else: ?>
                <div class="gridOpciones">
                    <?php foreach ($linea['opciones'] as $opcion): ?>
                        <?php
                        $idOpcion = (int) $opcion->getIdComponente();
                        $agotada = $opcion->getExistencia() <= 0;
                        $urlOpcion = $fotoComponente($opcion->getFoto());
                        ?>
                        <label class="opcionComponente <?= $agotada ? 'opcionAgotada' : '' ?>">
                            <input type="radio" name="componente[<?= $id ?>]" value="<?= $idOpcion ?>"
                                data-costo="<?= $opcion->getCostoUnitario() ?>" data-existencia="<?= $opcion->getExistencia() ?>"
                                data-nombre="<?= htmlspecialchars($opcion->getNombre()) ?>" data-foto="<?= htmlspecialchars($urlOpcion) ?>"
                                <?= $datos['elegidos'][$id] === $idOpcion && !$agotada ? 'checked' : '' ?> <?= $agotada ? 'disabled' : '' ?>>
                            <span class="fotoOpcion">
                                <img src="<?= htmlspecialchars($urlOpcion) ?>" alt="" loading="lazy">
                                <span class="marcaElegida" aria-hidden="true">✓</span>
                                <?php if ($agotada): ?><span class="tapaAgotado"><span>Agotado</span></span><?php endif; ?>
                            </span>
                            <span class="textoOpcion">
                                <strong><?= htmlspecialchars($opcion->getNombre()) ?></strong>
                                <span class="<?= $agotada ? 'errorCampo' : 'textoAyuda' ?>">
                                    <?= $agotada ? 'Sin existencias' : 'Quedan ' . htmlspecialchars(UnidadMedida::cantidad($opcion->getExistencia(), $opcion->getUnidad())) ?>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <section class="resumenPersonalizado" aria-labelledby="tituloResumen">
        <div class="resumenPersonalizadoPedido">
            <h2 id="tituloResumen">3. Su pedido</h2>
            <p><strong data-resumen-piezas><?= $piezas ?></strong> <span data-resumen-palabra><?= $piezas === 1 ? 'pieza' : 'piezas' ?></span>
                de <?= htmlspecialchars($producto->getNombre()) ?></p>
            <p class="textoAyuda" data-sin-cambios <?= $cambios === [] ? '' : 'hidden' ?>>Todavía igual al original.</p>
            <ul class="listaCambios" data-lista-cambios>
                <?php foreach ($cambios as $cambio): ?>
                    <li><?= htmlspecialchars($cambio) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="campo <?= isset($errores['nota']) ? 'campoConError' : '' ?>">
            <label for="nota">Algo más para la tienda</label>
            <textarea id="nota" name="nota" rows="4" maxlength="300" data-contador
                placeholder="Por ejemplo: que la cadena sea un poco más corta"><?= htmlspecialchars((string) $datos['nota']) ?></textarea>
            <p class="textoAyuda">Opcional.</p>
            <?= $mensajeError('nota') ?>
        </div>

        <div class="resumenPersonalizadoTotal">
            <span class="textoAyuda"><strong>Total de componentes</strong></span>
            <strong class="totalComponentes" data-total><?= Numero::moneda(round($total, 2)) ?></strong>
            <p class="textoAyuda">Es solo lo de los materiales. La tienda le suma la mano de obra y le manda el precio final.</p>
            <p class="alertaInventario" data-alerta-inventario role="alert" hidden>Hay piezas que no alcanzan o que falta escoger. Revise lo marcado.</p>
            <button type="submit" class="boton botonRegistrar" data-enviar>Enviar a cotizar</button>
        </div>
    </section>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>