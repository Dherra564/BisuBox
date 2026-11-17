<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Arme su pieza';
$paginaActual = 'inicio';
$volverA = ['ruta' => '/tienda/' . $tienda->getTiendaEnlace(), 'texto' => 'Volver a la tienda'];
$scriptsExtra = ['algoritmosVista.js', 'vistaDiseno.js', 'personalizador.js'];
require __DIR__ . '/../Plantilla/encabezado.php';
?>

<p class="introPagina">Toque las piezas para ensartarlas y vea cómo queda.</p>

<?php if ($esDuenio): ?>
    <p class="cajaInformativa">Así ven sus clientes el personalizador. Desde su propia tienda no puede agregar al carrito.
    </p>
<?php endif; ?>

<div class="personalizador armePieza" id="personalizador">
    <section class="vistaPersonalizador">
        <canvas id="lienzoDiseno" width="560" height="560" role="img" aria-label="Vista previa de su pieza"></canvas>
        <div class="ordenPiezas">
            <div class="encabezadoOrden">
                <h2 class="tituloOrden">Orden de las piezas</h2>
                <div class="botonesDiseno">
                    <button type="button" class="enlaceAccion" id="deshacerPieza">Deshacer</button>
                    <button type="button" class="enlaceAccion" id="patronPieza">Repetir patrón</button>
                    <button type="button" class="enlaceAccion enlaceAccionPeligro" id="limpiarPieza">Limpiar</button>
                </div>
            </div>
            <ol id="ordenPiezas" class="listaOrden" aria-live="polite"></ol>
            <p class="textoAyuda" id="ordenVacio">Todavía no hay piezas. Toque una pieza de la lista para empezar.</p>
        </div>
    </section>

    <div class="columnaArme">
        <section class="pasoArme">
            <h2>1. Elija la pieza y el largo</h2>
            <fieldset class="campo">
                <legend class="soloLectores">Tipo de pieza</legend>
                <div class="opcionesTipoPieza" id="tiposPieza">
                    <?php foreach ($datosPagina['tipos'] as $clave => $tipo): ?>
                        <label class="chipTipoPieza">
                            <input type="radio" name="tipoPieza" value="<?= htmlspecialchars($clave) ?>"
                                <?= $clave === 'pulsera' ? 'checked' : '' ?>>
                            <?= htmlspecialchars($tipo['nombre']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="campo campoLargoPieza">
                <label for="largoPieza">Largo: <strong id="largoTexto"></strong></label>
                <input type="range" id="largoPieza" step="<?= (int) $datosPagina['paso'] ?>">
            </div>

            <div class="campo campoCasilla campoInterruptor">
                <input type="checkbox" id="simetricoPieza" checked>
                <label for="simetricoPieza">Simétrico
                    <span class="textoAyuda">La primera pieza queda al centro y las demás se repiten a los dos
                        lados.</span></label>
            </div>
        </section>

        <section class="pasoArme catalogoPiezas">
            <h2>2. Toque las piezas para ensartarlas</h2>
            <div class="listaCatalogoPiezas" id="catalogoPiezas"></div>
        </section>

        <section class="resumenArme">
            <p class="avisoDiseno" id="avisoDiseno" role="status" aria-live="polite"></p>

            <div class="datoDiseno datoHilo">
                <div class="filaDatoDiseno">
                    <span class="etiquetaResumen">Hilo usado</span>
                    <strong id="hiloUsado"></strong>
                </div>
                <div class="barraHilo">
                    <div id="barraHilo"></div>
                </div>
                <div class="filaDatoDiseno">
                    <span class="textoAyuda" id="faltaHilo"></span>
                    <button type="button" class="enlaceAccion" id="ajustarLargo" hidden></button>
                </div>
            </div>

            <div class="datoDiseno datoLleva">
                <span class="etiquetaResumen">Lleva</span>
                <ul class="materialesDiseno" id="materialesDiseno"></ul>
            </div>

            <div class="compraArme">
                <div class="datoDiseno datoPrecio">
                    <span class="etiquetaResumen">Precio <span class="textoAyuda">(piezas, hilo y mano de
                            obra)</span></span>
                    <strong class="precioDiseno" id="precioDiseno">₡0</strong>
                </div>

                <?php if (!$esDuenio): ?>
                    <form method="post" action="<?= $urlBase ?>/personalizador/agregar" id="formularioDiseno"
                        class="formularioArme">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="tienda" value="<?= (int) $datosPagina['idTienda'] ?>">
                        <input type="hidden" name="tipo" id="campoTipo">
                        <input type="hidden" name="largo" id="campoLargo">
                        <input type="hidden" name="simetrico" id="campoSimetrico">
                        <div id="campoPiezas"></div>
                        <button type="submit" class="boton botonCompleto">Agregar al carrito</button>
                        <?php if (!$datosPagina['haySesion']): ?>
                            <p class="textoAyuda">Le pediremos ingresar o crear su cuenta; su diseño no se pierde.</p>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script type="application/json"
    id="datosPersonalizador"><?= json_encode($datosPagina + ['nombreHilo' => $nombreHilo], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>