<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Fotos de piezas';
$paginaActual = 'personalizador';
$volverA = ['ruta' => '/personalizador', 'texto' => 'Personalizador'];
$scriptsExtra = ['algoritmosVista.js', 'fotosPiezas.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$datosPagina = [
    'componentes' => $componentes,
    'piezas' => $piezasEnviadas,
    'errores' => $errores,
];
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise las piezas marcadas en rojo.</p>
    </div>
<?php endif; ?>

<?php if ($componentes === []): ?>
    <div class="estadoVacio">
        <p>Primero registre en el inventario los componentes que se cuentan por unidad, como perlas o dijes.</p>
        <p><a href="<?= $urlBase ?>/componentes/nuevo" class="boton">Agregar componente</a></p>
    </div>
<?php else: ?>
    <section class="formularioSeccion">
        <h2>1. Tome una foto con varias piezas</h2>
        <div class="alertaFondoFoto" role="note">
            <div class="muestrasFondo" aria-hidden="true">
                <span class="muestraFondo muestraVerde">Verde</span>
                <span class="muestraFondo muestraAzul">Azul</span>
                <span class="muestraFondo muestraBlanca">Blanco</span>
            </div>
            <div>
                <p class="tituloAlertaFondo">Tome la foto sobre una hoja lisa verde, azul o blanca</p>
                <ul class="consejosFoto">
                    <li>Verde o azul sirven para todas las piezas.</li>
                    <li>Blanco solo si ninguna pieza es blanca, plateada o transparente: esas se borrarían.</li>
                    <li>Nada de telas con dibujo, mesas de madera ni manteles.</li>
                    <li>Separe las piezas un poco entre sí y del borde de la hoja.</li>
                    <li>Tome la foto desde arriba, con buena luz y sin flash.</li>
                </ul>
            </div>
        </div>
        <div class="campo" id="campoFoto">
            <label for="fotoPiezas" class="boton">Elegir o tomar foto</label>
            <input type="file" id="fotoPiezas" accept="image/jpeg,image/png,image/webp" class="campoArchivoOculto">
            <p class="textoAyuda">JPG, PNG o WEBP de hasta 15 MB. La foto no se sube: se procesa en su navegador y solo se guardan las piezas recortadas.</p>
        </div>
    </section>

    <section class="formularioSeccion" id="seccionResultado" hidden>
        <h2>2. Revise el recorte</h2>
        <div class="fotosComparadas">
            <figure>
                <canvas id="lienzoOriginal" aria-label="Foto original"></canvas>
                <figcaption>Foto original</figcaption>
            </figure>
            <figure>
                <canvas id="lienzoResultado" class="fondoCuadros" aria-label="Foto sin fondo con las piezas encontradas"></canvas>
                <figcaption>Sin fondo, con las piezas encontradas</figcaption>
            </figure>
        </div>
        <div class="campo campoRango">
            <label for="tolerancia">Tolerancia del fondo: <strong id="toleranciaValor">70</strong></label>
            <input type="range" id="tolerancia" min="20" max="140" step="1" value="70">
            <p class="textoAyuda">Súbala si quedan restos de la hoja o sombras; bájela si se borran partes de las piezas.</p>
        </div>
        <ul class="avisosFoto" id="avisosFoto" aria-live="polite"></ul>
    </section>

    <form method="post" action="<?= $urlBase ?>/personalizador/fotos" novalidate class="validarFormulario" id="formularioPiezas" hidden>
        <?= Csrf::campo() ?>
        <section class="formularioSeccion">
            <h2>3. Diga qué es cada pieza</h2>
            <p class="textoAyuda">Elija qué componente es cada pieza que quiera guardar: al elegirlo se marca sola. Las piezas sin marcar no se guardan,
                así que puede guardar las que salieron bien y dejar las demás. Si una pieza ya tenía foto, la nueva la reemplaza.</p>
            <div class="piezasEncontradas" id="piezasEncontradas"></div>
            <p class="errorCampo" id="errorPiezas" <?= isset($errores['piezas']) ? '' : 'hidden' ?>><?= htmlspecialchars($errores['piezas'] ?? '') ?></p>
            <div class="grupoBotones">
                <button type="submit" class="boton" id="botonGuardarPiezas">Guardar piezas</button>
                <a href="<?= $urlBase ?>/personalizador" class="boton botonSecundario">Cancelar</a>
            </div>
        </section>
    </form>

    <script type="application/json" id="datosFotosPiezas"><?= json_encode($datosPagina, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>