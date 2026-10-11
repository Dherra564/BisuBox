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
use Aplicacion\Nucleo\EstadoExistencia;
use Aplicacion\Nucleo\Numero;
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
$singular = mb_strtolower($unidades[$datos['unidad']]['nombre'] ?? 'unidad', 'UTF-8');
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
            <p class="textoAyuda">Un nombre que lo distinga de los demás: así lo encontrará en el inventario.</p>
            <?= $mensajeError('nombre') ?>
        </div>

        <div class="filaCampos">
            <div class="<?= $claseCampo('unidad') ?>">
                <label for="unidad">¿Cómo lo cuenta? <span class="obligatorio">*</span></label>
                <select id="unidad" name="unidad" required data-unidad>
                    <?php foreach ($unidades as $codigo => $unidad): ?>
                        <option value="<?= $codigo ?>" data-plural="<?= $unidad['plural'] ?>"
                            data-singular="<?= mb_strtolower($unidad['nombre'], 'UTF-8') ?>" <?= $datos['unidad'] === $codigo ? 'selected' : '' ?>>
                            Por <?= mb_strtolower($unidad['nombre'], 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="textoAyuda">Las perlas se cuentan por unidad; los hilos, por metro.</p>
                <?= $mensajeError('unidad') ?>
            </div>
            <div class="<?= $claseCampo('existenciaMinima') ?>">
                <label for="existenciaMinima">Avisarme cuando queden</label>
                <div class="campoConUnidad">
                    <input type="text" id="existenciaMinima" name="existenciaMinima" maxlength="13" inputmode="decimal"
                        data-regla="cantidad" placeholder="0" value="<?= $valor('existenciaMinima') ?>">
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
                            data-regla="cantidad" placeholder="0" value="<?= $valor('existencia') ?>">
                        <span class="unidadCampo" data-unidad-plural><?= $plural ?></span>
                    </div>
                    <p class="textoAyuda">Si todavía no tiene, déjelo vacío. Después sube con las compras.</p>
                    <?= $mensajeError('existencia') ?>
                </div>
                <div class="<?= $claseCampo('costoUnitario') ?>">
                    <label for="costoUnitario">Costo de cada <span data-unidad-singular><?= $singular ?></span> <span
                            class="obligatorio">*</span></label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="costoUnitario" name="costoUnitario" maxlength="13" inputmode="decimal"
                            required data-regla="cantidad" placeholder="0"
                            data-mensaje-requerido="Escriba cuánto le costó cada unidad. Si no lo sabe, ponga 0"
                            value="<?= $valor('costoUnitario') ?>">
                    </div>
                    <p class="textoAyuda">Lo que pagó por cada una. Sirve para saber cuánto vale su inventario.</p>
                    <?= $mensajeError('costoUnitario') ?>
                </div>
            </div>
        <?php else: ?>
            <?php $estado = $componente->getEstadoExistencia(); ?>
            <div class="resumenComponente">
                <div>
                    <span class="textoAyuda">Existencia</span>
                    <strong><?= UnidadMedida::cantidad($componente->getExistencia(), $componente->getUnidad()) ?></strong>
                    <span
                        class="etiqueta <?= EstadoExistencia::clase($estado) ?>"><?= EstadoExistencia::nombre($estado) ?></span>
                </div>
                <div>
                    <span class="textoAyuda">Costo unitario</span>
                    <strong><?= Numero::moneda($componente->getCostoUnitario()) ?></strong>
                </div>
                <div>
                    <span class="textoAyuda">Valor en existencia</span>
                    <strong><?= Numero::moneda($componente->getValorTotal()) ?></strong>
                </div>
                <p class="textoAyuda resumenComponenteNota">La existencia y el costo cambian solos con las compras y la
                    producción.</p>
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
                                            <?= htmlspecialchars($opcion->getValor()) ?>                    <?= $opcion->getActivo() ? '' : ' (ya no se usa)' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($atributo->getClase() === ClaseDato::NUMERO): ?>
                                <div class="campoConUnidad">
                                    <input type="text" id="<?= $idCampo ?>" name="<?= $nombreCampo ?>" maxlength="13"
                                        inputmode="decimal" data-regla="cantidad"
                                        data-mensaje-regla="Escriba un número, por ejemplo 8 o 8,5"
                                        data-mensaje-requerido="<?= htmlspecialchars($mensajeRequerido) ?>" <?= $obligatorio ? 'required' : '' ?> value="<?= htmlspecialchars($valorActual) ?>">
                                    <?php if ($atributo->getUnidad()): ?>
                                        <span class="unidadCampo"><?= htmlspecialchars($atributo->getUnidad()) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <input type="text" id="<?= $idCampo ?>" name="<?= $nombreCampo ?>" maxlength="300"
                                    data-mensaje-requerido="<?= htmlspecialchars($mensajeRequerido) ?>" <?= $obligatorio ? 'required' : '' ?> value="<?= htmlspecialchars($valorActual) ?>">
                            <?php endif; ?>
                            <?= $mensajeError($campo) ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/inventario" class="boton botonSecundario">Cancelar</a>
        <?php if (!$editando): ?>
            <button type="submit" name="siguiente" value="otro" class="boton botonSecundario">Guardar y agregar
                otro</button>
        <?php endif; ?>
        <button type="submit" class="boton"><?= $editando ? 'Guardar cambios' : 'Agregar al inventario' ?></button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>