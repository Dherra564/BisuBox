<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Arme su pieza';
$paginaActual = 'inicio';
$volverA = ['ruta' => '/tienda/' . $tienda->getTiendaEnlace(), 'texto' => 'Volver a la tienda'];
$scriptsExtra = ['algoritmosVista.js', 'vistaDiseno.js', 'personalizador.js'];
require __DIR__ . '/../Plantilla/encabezado.php';
?>

<p class="introPagina">Toque las piezas para ensartarlas y vea cómo queda. Con <strong>Simétrico</strong>, la primera pieza queda al centro y las demás se repiten a los dos lados.</p>

<?php if ($esDuenio): ?>
    <p class="cajaInformativa">Así ven sus clientes el personalizador. Desde su propia tienda no puede agregar al carrito.</p>
<?php endif; ?>

<div class="personalizador" id="personalizador">
    <div class="vistaPersonalizador">
        <canvas id="lienzoDiseno" width="560" height="560" role="img" aria-label="Vista previa de su pieza"></canvas>
        <div class="ordenPiezas">
            <h2 class="tituloOrden">Orden de las piezas</h2>
            <ol id="ordenPiezas" class="listaOrden" aria-live="polite"></ol>
            <p class="textoAyuda" id="ordenVacio">Todavía no hay piezas. Toque una pieza de la lista para empezar.</p>
        </div>
    </div>

    <div class="panelPersonalizador">
        <fieldset class="campo">
            <legend class="etiquetaCampo">Tipo de pieza</legend>
            <div class="opcionesTipoPieza" id="tiposPieza">
                <?php foreach ($datosPagina['tipos'] as $clave => $tipo): ?>
                    <label class="chipTipoPieza">
                        <input type="radio" name="tipoPieza" value="<?= htmlspecialchars($clave) ?>" <?= $clave === 'pulsera' ? 'checked' : '' ?>>
                        <?= htmlspecialchars($tipo['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="campo">
            <label for="largoPieza">Largo: <strong id="largoTexto"></strong></label>
            <input type="range" id="largoPieza" step="<?= (int) $datosPagina['paso'] ?>">
        </div>

        <div class="campo campoCasilla">
            <input type="checkbox" id="simetricoPieza" checked>
            <label for="simetricoPieza">Simétrico
                <span class="textoAyuda">La primera pieza queda al centro.</span></label>
        </div>

        <div class="grupoBotones botonesDiseno">
            <button type="button" class="boton botonSecundario botonPequeno" id="deshacerPieza">Deshacer</button>
            <button type="button" class="boton botonSecundario botonPequeno" id="patronPieza">Repetir patrón</button>
            <button type="button" class="boton botonSecundario botonPequeno" id="limpiarPieza">Limpiar</button>
        </div>

        <p class="avisoDiseno" id="avisoDiseno" role="status" aria-live="polite"></p>

        <div class="datoDiseno">
            <span class="textoAyuda">Hilo usado</span>
            <strong id="hiloUsado"></strong>
            <div class="barraHilo"><div id="barraHilo"></div></div>
            <span class="textoAyuda" id="faltaHilo"></span>
            <button type="button" class="boton botonSecundario botonPequeno" id="ajustarLargo" hidden></button>
        </div>

        <div class="datoDiseno">
            <span class="textoAyuda">Precio</span>
            <strong class="precioDiseno" id="precioDiseno">₡0</strong>
            <span class="textoAyuda">Incluye piezas, hilo y mano de obra.</span>
        </div>

        <div class="datoDiseno">
            <span class="textoAyuda">Lleva</span>
            <ul class="materialesDiseno" id="materialesDiseno"></ul>
        </div>

        <?php if (!$esDuenio): ?>
            <form method="post" action="<?= $urlBase ?>/personalizador/agregar" id="formularioDiseno">
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
</div>

<section class="catalogoPiezas">
    <h2>Piezas</h2>
    <div class="listaCatalogoPiezas" id="catalogoPiezas"></div>
</section>

<script type="application/json" id="datosPersonalizador"><?= json_encode($datosPagina + ['nombreHilo' => $nombreHilo], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>
