<?php
/**
 * @var \Aplicacion\Modelos\TipoComponente $tipoComponente
 * @var \Aplicacion\Modelos\Componente|null $componente
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\ClaseDato;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosComponente;
use Aplicacion\Nucleo\UnidadMedida;

$editando = $componente !== null;
$titulo = $editando ? 'Editar componente' : 'Agregar componente';
$paginaActual = 'inventario';
$volverA = ['texto' => 'Inventario', 'ruta' => '/inventario'];
$scriptsExtra = ['componente.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo, string $extra = ''): string =>
    trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$unidades = UnidadMedida::todas();
$plural = $unidades[$datos['unidad']]['plural'] ?? 'unidades';
$esMetro = $datos['unidad'] === UnidadMedida::METRO;
$enCentimetros = ($datos['medida'] ?? '') === DatosComponente::CENTIMETROS;
$atributos = $tipoComponente->getAtributosActivos();
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<div class="tipoElegido">
    <span>Tipo: <strong><?= htmlspecialchars($tipoComponente->getNombre()) ?></strong></span>
    <?php if (!$editando): ?>
        <a href="<?= $urlBase ?>/componentes/nuevo">Cambiar tipo</a>
    <?php endif; ?>
</div>

<form method="post" action="<?= $urlBase ?>/componentes/<?= $editando ? 'editar' : 'nuevo' ?>" novalidate
    class="validarFormulario formularioComponente">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= $componente->getIdComponente() ?>">
    <?php else: ?>
        <input type="hidden" name="tipo" value="<?= $tipoComponente->getIdTipoComponente() ?>">
    <?php endif; ?>

    <section class="formularioSeccion">
        <h2>Datos comunes</h2>
        <div class="<?= $claseCampo('nombre') ?>">
            <label for="nombre">Nombre del componente <span class="obligatorio">*</span></label>
            <input type="text" id="nombre" name="nombre" minlength="2" maxlength="100" required
                placeholder="Por ejemplo: Perla de vidrio rosada 8 mm"
                data-mensaje-requerido="Escriba el nombre del componente, por ejemplo Perla rosada 8 mm"
                data-mensaje-largo="El nombre debe tener entre 2 y 100 caracteres" value="<?= $valor('nombre') ?>">
            <?= $mensajeError('nombre') ?>
        </div>

        <div class="filaCampos">
            <fieldset class="<?= $claseCampo('unidad', 'opcionesUnidad') ?>">
                <legend class="etiquetaCampo">¿Cómo se calcula el precio? <span class="obligatorio">*</span></legend>
                <div class="opcionesUnidadLista">
                    <?php foreach ($unidades as $codigo => $unidad): ?>
                        <label class="opcionUnidad">
                            <input type="radio" name="unidad" value="<?= $codigo ?>" data-unidad
                                data-plural="<?= $unidad['plural'] ?>"
                                data-ayuda="<?= htmlspecialchars($unidad['ayuda']) ?>"
                                data-costo="<?= htmlspecialchars($unidad['costo']) ?>" <?= $datos['unidad'] === $codigo ? 'checked' : '' ?>>
                            Por <?= mb_strtolower($unidad['nombre'], 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= $mensajeError('unidad') ?>
            </fieldset>
            <div class="<?= $claseCampo('existenciaMinima') ?>">
                <label for="existenciaMinima">Avisarme cuando solo queden</label>
                <div class="campoConUnidad">
                    <input type="text" id="existenciaMinima" name="existenciaMinima" maxlength="13" inputmode="decimal"
                        data-regla="cantidad" data-mensaje-regla="Escriba un número con hasta 2 decimales, por ejemplo 12 o 12,50"
                        placeholder="0" value="<?= $valor('existenciaMinima') ?>">
                    <span class="unidadCampo" data-unidad-plural><?= $plural ?></span>
                </div>
                <p class="textoAyuda">Opcional. Con esa cantidad o menos, se marca como "Por acabarse".</p>
                <?= $mensajeError('existenciaMinima') ?>
            </div>
        </div>

        <?php if (!$editando): ?>
            <div class="filaCampos">
                <div class="<?= $claseCampo('existencia') ?>">
                    <label for="existencia">¿Cuántas tiene ahora?</label>
                    <div class="campoConUnidad">
                        <input type="text" id="existencia" name="existencia" maxlength="13" inputmode="decimal"
                            data-regla="cantidad" data-mensaje-regla="Escriba un número con hasta 2 decimales, por ejemplo 12 o 12,50"
                            placeholder="0" value="<?= $valor('existencia') ?>">
                        <select name="medida" class="selectorMedida" aria-label="Metros o centímetros" data-medida
                            <?= $esMetro ? '' : 'hidden' ?>>
                            <option value="m" <?= $enCentimetros ? '' : 'selected' ?>>m</option>
                            <option value="cm" <?= $enCentimetros ? 'selected' : '' ?>>cm</option>
                        </select>
                        <span class="unidadCampo" data-unidad-plural data-unidad-existencia <?= $esMetro ? 'hidden' : '' ?>><?= $plural ?></span>
                    </div>
                    <p class="textoAyuda" data-unidad-ayuda>
                        <?= htmlspecialchars($unidades[$datos['unidad']]['ayuda'] ?? '') ?>
                    </p>
                    <p class="textoAyuda">Si todavía no tiene, déjelo vacío.</p>
                    <?= $mensajeError('existencia') ?>
                </div>
                <div class="<?= $claseCampo('costoTotal') ?>">
                    <label for="costoTotal">¿Cuánto pagó en total por eso?</label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="costoTotal" name="costoTotal" maxlength="13" inputmode="decimal"
                            data-regla="cantidad" data-mensaje-regla="Escriba un número con hasta 2 decimales, por ejemplo 12 o 12,50"
                            placeholder="0" data-costo-total value="<?= $valor('costoTotal') ?>">
                    </div>
                    <p class="costoCalculadoComponente" data-costo-calculado aria-live="polite"></p>
                    <?= $mensajeError('costoTotal') ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="formularioSeccion">
        <h2>Datos de <?= htmlspecialchars(mb_strtolower($tipoComponente->getNombre(), 'UTF-8')) ?></h2>
        <?php if ($atributos === []): ?>
            <p class="textoAyuda">Este tipo solo pide los datos comunes.
                <a href="<?= $urlBase ?>/tipos-componente/editar?id=<?= $tipoComponente->getIdTipoComponente() ?>">Agregarle
                    datos</a>.
            </p>
        <?php else: ?>
            <div class="datosDelTipo">
                <?php foreach ($atributos as $atributo): ?>
                    <?php
                    $idAtributo = (int) $atributo->getIdAtributo();
                    $campo = 'valor.' . $idAtributo;
                    $idCampo = 'valor-' . $idAtributo;
                    $nombreCampo = 'valor[' . $idAtributo . ']';
                    $valorActual = (string) ($datos['valores'][$idAtributo] ?? '');
                    $obligatorio = $atributo->getObligatorio();
                    $marca = $obligatorio ? ' <span class="obligatorio">*</span>' : '';
                    $mensajeRequerido = 'Este tipo siempre pide ' . mb_strtolower($atributo->getNombre(), 'UTF-8');
                    ?>
                    <?php if ($atributo->getClase() === ClaseDato::SINO): ?>
                        <div class="campo campoCasilla">
                            <input type="checkbox" id="<?= $idCampo ?>" name="<?= $nombreCampo ?>" value="1" <?= $valorActual === '1' ? 'checked' : '' ?>>
                            <label for="<?= $idCampo ?>"><?= htmlspecialchars($atributo->getNombre()) ?></label>
                        </div>
                    <?php else: ?>
                        <div class="<?= $claseCampo($campo) ?>">
                            <label for="<?= $idCampo ?>"><?= htmlspecialchars($atributo->getNombre()) . $marca ?></label>
                            <?php if ($atributo->getClase() === ClaseDato::LISTA): ?>
                                <select id="<?= $idCampo ?>" name="<?= $nombreCampo ?>" <?= $obligatorio ? 'required' : '' ?>
                                    data-mensaje-requerido="<?= htmlspecialchars($mensajeRequerido) ?>">
                                    <option value="">Escoja una opción</option>
                                    <?php foreach ($atributo->getOpciones() as $opcion): ?>
                                        <?php
                                        $esActual = $valorActual === (string) $opcion->getIdOpcion();
                                        if (!$opcion->getActivo() && !$esActual) {
                                            continue;
                                        }
                                        ?>
                                        <option value="<?= $opcion->getIdOpcion() ?>" <?= $esActual ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opcion->getValor()) ?>
                                            <?= $opcion->getActivo() ? '' : ' (ya no se usa)' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($atributo->getClase() === ClaseDato::NUMERO): ?>
                                <div class="campoConUnidad">
                                    <input type="text" id="<?= $idCampo ?>" name="<?= $nombreCampo ?>" maxlength="13"
                                        inputmode="decimal" data-regla="cantidad"
                                        data-mensaje-regla="Escriba un número, por ejemplo 8 o 8,5" data-atributo-numero
                                        data-mensaje-requerido="<?= htmlspecialchars($mensajeRequerido) ?>" <?= $obligatorio ? 'required' : '' ?> value="<?= htmlspecialchars($valorActual) ?>">
                                    <?php if ($atributo->getUnidad()): ?>
                                        <span class="unidadCampo"><?= htmlspecialchars($atributo->getUnidad()) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <input type="text" id="<?= $idCampo ?>" name="<?= $nombreCampo ?>" maxlength="300" data-atributo-texto
                                    data-mensaje-requerido="<?= htmlspecialchars($mensajeRequerido) ?>" <?= $obligatorio ? 'required' : '' ?> value="<?= htmlspecialchars($valorActual) ?>">
                            <?php endif; ?>
                            <?= $mensajeError($campo) ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="grupoBotones accionesFormulario accionesIzquierda">
        <button type="submit" class="boton"><?= $editando ? 'Guardar cambios' : 'Agregar al inventario' ?></button>
        <?php if (!$editando): ?>
            <button type="submit" name="siguiente" value="otro" class="boton botonSecundario">Guardar y agregar
                otro</button>
        <?php endif; ?>
        <a href="<?= $urlBase ?>/inventario" class="boton botonSecundario">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>