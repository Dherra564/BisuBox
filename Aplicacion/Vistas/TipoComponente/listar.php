<?php
/**
 * @var \Aplicacion\Modelos\TipoComponente[] $tiposComponente
 * @var array $sugeridos
 * @var string $urlBase
 */

use Aplicacion\Nucleo\ClaseDato;
use Aplicacion\Nucleo\Csrf;

$titulo = 'Tipos de componente';
$paginaActual = 'inventario';
$volverA = ['texto' => 'Inventario', 'ruta' => '/inventario'];
$botonAccion = $tiposComponente !== [] ? ['texto' => 'Nuevo tipo', 'ruta' => '/tipos-componente/nuevo'] : null;
require __DIR__ . '/../Plantilla/encabezado.php';

$chip = fn(string $nombre, string $clase, ?string $unidad = null): string =>
    '<li class="chipDato" title="' . htmlspecialchars(ClaseDato::nombre($clase)) . '">'
    . '<span class="iconoClase iconoClase' . htmlspecialchars($clase) . '" aria-hidden="true"></span>'
    . htmlspecialchars($nombre) . ($unidad ? ' <span class="chipUnidad">(' . htmlspecialchars($unidad) . ')</span>' : '')
    . '</li>';

usort($tiposComponente, fn($a, $b): int => (int) $b->getActivo() <=> (int) $a->getActivo());
?>

<?php if ($tiposComponente === []): ?>
    <div class="bienvenidaTipos">
        <p class="subtitulo">
            Todavía no tiene tipos de componente. Un tipo agrupa materiales parecidos, como las perlas o los hilos,
            y decide qué datos se anotan de cada uno.
        </p>

        <form method="post" action="<?= $urlBase ?>/tipos-componente/sugeridos" class="formularioSeccion">
            <?= Csrf::campo() ?>
            <h2>Empiece con estos tipos ya armados</h2>
            <p class="textoAyuda">Marque los que le sirven. Después puede cambiarlos, quitarles datos o agregarles otros.
            </p>

            <div class="tarjetasSugeridas">
                <?php foreach ($sugeridos as $nombre => $datosSugeridos): ?>
                    <label class="tarjetaSugerida">
                        <input type="checkbox" name="sugeridos[]" value="<?= htmlspecialchars($nombre) ?>" checked>
                        <span class="tarjetaSugeridaNombre"><?= htmlspecialchars($nombre) ?></span>
                        <ul class="listaChips">
                            <?php foreach ($datosSugeridos as $dato): ?>
                                <?= $chip($dato['nombre'], $dato['clase'], $dato['unidad'] ?? null) ?>
                            <?php endforeach; ?>
                        </ul>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="grupoBotones accionesFormulario">
                <button type="submit" class="boton">Agregar los tipos marcados</button>
                <a href="<?= $urlBase ?>/tipos-componente/nuevo" class="boton botonSecundario">Prefiero crear uno desde
                    cero</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <?php
    $totalComponentes = array_sum(array_map(fn($tipo): int => $tipo->getCantidadComponentes(), $tiposComponente));
    ?>
    <?php if ($totalComponentes === 0): ?>
        <div class="cajaPaso">
            <p><strong>Siguiente paso:</strong> agregue sus componentes. Por ejemplo, "Perla de vidrio rosada 8 mm" en Perlas.</p>
            <a href="<?= $urlBase ?>/componentes/nuevo" class="boton">Agregar componente</a>
        </div>
    <?php endif; ?>

    <p class="cajaInformativa">
        Todos los componentes piden nombre, unidad de medida, costo y existencia mínima.
        Aquí decide qué otros datos pide cada tipo.
    </p>

    <div class="tarjetas tarjetasTipo">
        <?php foreach ($tiposComponente as $tipoComponente): ?>
            <?php
            $cantidad = $tipoComponente->getCantidadComponentes();
            $atributos = $tipoComponente->getAtributosActivos();
            ?>
            <article class="tarjeta tarjetaTipo <?= $tipoComponente->getActivo() ? '' : 'tarjetaInactiva' ?>">
                <div class="tarjetaTipoEncabezado">
                    <h2><?= htmlspecialchars($tipoComponente->getNombre()) ?></h2>
                    <?php if ($tipoComponente->getActivo()): ?>
                        <span class="etiqueta etiquetaActivo">Activo</span>
                    <?php else: ?>
                        <span class="etiqueta etiquetaInactivo">Desactivado</span>
                    <?php endif; ?>
                </div>
                <p class="textoAyuda">
                    <?= $cantidad === 0 ? 'Sin componentes todavía' : ($cantidad === 1 ? '1 componente' : $cantidad . ' componentes') ?>
                </p>

                <?php if ($atributos !== []): ?>
                    <ul class="listaChips">
                        <?php foreach ($atributos as $atributo): ?>
                            <?= $chip($atributo->getNombre(), $atributo->getClase(), $atributo->getUnidad()) ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="textoAyuda">Solo pide los datos comunes.</p>
                <?php endif; ?>

                <div class="grupoBotones tarjetaAccion">
                    <a href="<?= $urlBase ?>/tipos-componente/editar?id=<?= $tipoComponente->getIdTipoComponente() ?>"
                        class="boton botonSecundario botonPequeno">Editar</a>
                    <?php if ($tipoComponente->getActivo()): ?>
                        <form method="post" action="<?= $urlBase ?>/tipos-componente/estado" class="formularioEnLinea"
                            data-titulo="Desactivar tipo" data-boton="Desactivar"
                            data-confirmar="¿Desactivar «<?= htmlspecialchars($tipoComponente->getNombre()) ?>»? Sus componentes siguen funcionando, pero no podrá crear componentes nuevos de este tipo.">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="id" value="<?= $tipoComponente->getIdTipoComponente() ?>">
                            <input type="hidden" name="activo" value="0">
                            <button type="submit" class="boton botonSecundario botonPequeno">Desactivar</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?= $urlBase ?>/tipos-componente/estado" class="formularioEnLinea">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="id" value="<?= $tipoComponente->getIdTipoComponente() ?>">
                            <input type="hidden" name="activo" value="1">
                            <button type="submit" class="boton botonSecundario botonPequeno">Activar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>