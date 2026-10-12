<?php
/**
 
 * @var array $datos
 * @var array $errores
 * @var \Aplicacion\Modelos\Proveedor[] $proveedores
 * @var \Aplicacion\Modelos\Componente[] $componentes
 * @var \Aplicacion\Modelos\TipoComponente[] $tipos
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Registrar compra';
$paginaActual = 'proveedores';
$volverA = ['texto' => 'Compras a proveedores', 'ruta' => '/compras-proveedor'];
$scriptsExtra = ['compraProveedor.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo, string $extra = ''): string =>
    trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$unidades = UnidadMedida::todas();
$sePuedeCrear = $tipos !== [];

$tiposConComponentes = [];
foreach ($componentes as $componente) {
    $tiposConComponentes[$componente->getIdTipoComponente()] = $componente->getNombreTipo();
}
asort($tiposConComponentes);

$infoLinea = function (array $linea) use ($componentes, $tipos): array {
    if ($linea['componente'] === DatosCompraProveedor::NUEVO) {
        return [
            'nombre' => $linea['nombre'],
            'tipo' => isset($tipos[(int) $linea['tipo']]) ? $tipos[(int) $linea['tipo']]->getNombre() : '',
            'unidad' => UnidadMedida::existe($linea['unidad']) ? $linea['unidad'] : UnidadMedida::UNIDAD,
            'existencia' => 0.0,
            'nuevo' => true,
        ];
    }
    $componente = $componentes[(int) $linea['componente']] ?? null;
    return [
        'nombre' => $componente?->getNombre() ?? 'Componente que ya no está disponible',
        'tipo' => $componente?->getNombreTipo() ?? '',
        'unidad' => $componente?->getUnidad() ?? UnidadMedida::UNIDAD,
        'existencia' => $componente?->getExistencia() ?? 0.0,
        'nuevo' => false,
    ];
};

$filaLinea = function (string $clave, array $linea, array $info) use ($claseCampo, $mensajeError, $unidades): string {
    $campo = 'linea.' . $clave . '.';
    $nombre = 'linea[' . $clave . ']';
    $unidad = $unidades[$info['unidad']] ?? $unidades[UnidadMedida::UNIDAD];
    $esEntera = $info['unidad'] === UnidadMedida::UNIDAD;

    return '<article class="lineaCompra" data-linea data-clave="' . $clave . '"'
        . ' data-componente="' . htmlspecialchars($linea['componente']) . '"'
        . ' data-existencia="' . $info['existencia'] . '" data-plural="' . $unidad['plural'] . '"'
        . ' data-entera="' . ($esEntera ? '1' : '0') . '" data-unidad="' . htmlspecialchars($info['unidad']) . '"'
        . ' data-nuevo="' . ($info['nuevo'] ? '1' : '0') . '">'
        . '<input type="hidden" name="' . $nombre . '[componente]" value="' . htmlspecialchars($linea['componente']) . '" data-linea-componente>'
        . '<input type="hidden" name="' . $nombre . '[tipo]" value="' . htmlspecialchars($linea['tipo']) . '" data-linea-tipo>'
        . '<input type="hidden" name="' . $nombre . '[nombre]" value="' . htmlspecialchars($linea['nombre']) . '" data-linea-nombre>'
        . '<input type="hidden" name="' . $nombre . '[unidad]" value="' . htmlspecialchars($linea['unidad']) . '" data-linea-unidad>'
        . '<div class="lineaCompraEncabezado">'
        . '<div class="lineaCompraNombre">'
        . '<strong data-linea-titulo>' . htmlspecialchars($info['nombre']) . '</strong>'
        . '<span class="etiqueta etiquetaTipo" data-linea-tipo-nombre>' . htmlspecialchars($info['tipo']) . '</span>'
        . '<span class="etiqueta etiquetaNuevo" data-linea-marca-nuevo' . ($info['nuevo'] ? '' : ' hidden') . '>Nuevo</span>'
        . '</div>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar ' . htmlspecialchars($info['nombre']) . ' de la compra"'
        . ' title="Quitar de la compra" data-linea-quitar>'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>'
        . '</div>'
        . $mensajeError($campo . 'componente') . $mensajeError($campo . 'tipo') . $mensajeError($campo . 'nombre') . $mensajeError($campo . 'unidad')
        . '<div class="lineaCompraCampos">'
        . '<div class="' . $claseCampo($campo . 'cantidad') . '">'
        . '<label for="linea-' . $clave . '-cantidad">Cantidad</label>'
        . '<div class="campoConUnidad">'
        . '<input type="text" id="linea-' . $clave . '-cantidad" name="' . $nombre . '[cantidad]" maxlength="13" inputmode="decimal"'
        . ' required data-mensaje-requerido="Escriba cuánto compró"'
        . ' data-linea-cantidad placeholder="0" value="' . htmlspecialchars($linea['cantidad']) . '">'
        . '<span class="unidadCampo" data-linea-plural>' . $unidad['plural'] . '</span>'
        . '</div>'
        . '<p class="textoAyuda" data-linea-ayuda>' . htmlspecialchars($unidad['ayuda']) . '</p>'
        . $mensajeError($campo . 'cantidad')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'total') . '">'
        . '<label for="linea-' . $clave . '-total">Precio total</label>'
        . '<div class="campoConUnidad campoConMoneda">'
        . '<span class="unidadCampo">₡</span>'
        . '<input type="text" id="linea-' . $clave . '-total" name="' . $nombre . '[total]" maxlength="13" inputmode="decimal"'
        . ' required data-mensaje-requerido="Escriba cuánto pagó en total por este componente"'
        . ' data-linea-total placeholder="0" value="' . htmlspecialchars($linea['total']) . '">'
        . '</div>'
        . '<p class="textoAyuda">Lo que pagó por todo lo de esta línea</p>'
        . $mensajeError($campo . 'total')
        . '</div>'
        . '<div class="campo campoCostoCalculado">'
        . '<span class="etiquetaCampo" data-linea-etiqueta-costo>' . htmlspecialchars($unidad['costo']) . '</span>'
        . '<span class="costoCalculado" data-linea-costo>—</span>'
        . '<p class="textoAyuda">Lo calcula el sistema</p>'
        . '</div>'
        . '</div>'
        . '<p class="avisoExistencia" data-linea-aviso></p>'
        . '</article>';
};

$plantillaVacia = ['componente' => '', 'tipo' => '', 'nombre' => '', 'unidad' => '', 'cantidad' => '', 'total' => ''];
$infoVacia = ['nombre' => '', 'tipo' => '', 'unidad' => UnidadMedida::UNIDAD, 'existencia' => 0.0, 'nuevo' => false];
$enCompra = array_map(fn(array $linea): string => $linea['componente'], $datos['lineas']);
$abrirNuevo = $componentes === [];
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo. No se guardó nada todavía.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/compras-proveedor/nueva" novalidate class="validarFormulario formularioCompra"
      data-maximo-lineas="<?= DatosCompraProveedor::MAXIMO_LINEAS ?>"
      data-confirmar="Al registrar la compra se suma al inventario lo que compró. Si después encuentra un error, puede anularla."
      data-titulo="Registrar compra" data-boton="Registrar compra">
    <?= Csrf::campo() ?>

    <div class="registroCompra">
        <div class="registroCompraPasos">

            <section class="formularioSeccion pasoCompra">
                <div class="pasoEncabezado">
                    <span class="pasoNumero" aria-hidden="true">1</span>
                    <h2>Datos de la compra</h2>
                </div>
                <div class="filaCampos filaTres">
                    <div class="<?= $claseCampo('proveedor') ?>">
                        <label for="proveedor">Proveedor <span class="obligatorio">*</span></label>
                        <select id="proveedor" name="proveedor" required data-mensaje-requerido="Escoja a qué proveedor le compró">
                            <option value="">Escoja el proveedor</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?= $proveedor->getIdProveedor() ?>" <?= (string) $proveedor->getIdProveedor() === $datos['proveedor'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $proveedor->getNombre()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $mensajeError('proveedor') ?>
                    </div>
                    <div class="<?= $claseCampo('fecha') ?>">
                        <label for="fecha">Fecha de la compra <span class="obligatorio">*</span></label>
                        <input type="date" id="fecha" name="fecha" required max="<?= date('Y-m-d') ?>" min="2000-01-01"
                               data-mensaje-requerido="Escoja en el calendario el día de la compra"
                               value="<?= htmlspecialchars($datos['fecha']) ?>">
                        <p class="textoAyuda">El día en que compró; no hace falta que sea hoy.</p>
                        <?= $mensajeError('fecha') ?>
                    </div>
                    <div class="<?= $claseCampo('costoEnvio') ?>">
                        <label for="costoEnvio">Costo de envío <span class="textoOpcional">(opcional)</span></label>
                        <div class="campoConUnidad campoConMoneda">
                            <span class="unidadCampo">₡</span>
                            <input type="text" id="costoEnvio" name="costoEnvio" maxlength="13" inputmode="decimal" data-regla="cantidad"
                                   data-mensaje-regla="Escriba un monto, por ejemplo 2500 o 2500,50" placeholder="0"
                                   data-costo-envio value="<?= htmlspecialchars($datos['costoEnvio']) ?>">
                        </div>
                        <p class="textoAyuda">Si no pagó envío, déjelo vacío.</p>
                        <?= $mensajeError('costoEnvio') ?>
                    </div>
                </div>
            </section>

            <section class="formularioSeccion pasoCompra pasoEscoger">
                <div class="pasoEncabezado">
                    <span class="pasoNumero" aria-hidden="true">2</span>
                    <div>
                        <h2>Escoja lo que compró</h2>
                        <p class="textoAyuda">Búsquelo en su inventario o, si es la primera vez que lo compra, créelo aquí mismo.</p>
                    </div>
                </div>

                <div class="pestanas pestanasEscoger" role="tablist" aria-label="De dónde sale el componente">
                    <button type="button" class="pestana <?= $abrirNuevo ? '' : 'pestanaActiva' ?>" role="tab" id="pestanaInventario"
                            aria-controls="panelInventario" aria-selected="<?= $abrirNuevo ? 'false' : 'true' ?>" data-pestana="inventario">
                        <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="4.5"></circle><path d="M10.5 10.5L14 14"></path></svg>
                        De mi inventario
                    </button>
                    <button type="button" class="pestana <?= $abrirNuevo ? 'pestanaActiva' : '' ?>" role="tab" id="pestanaNuevo"
                            aria-controls="panelNuevo" aria-selected="<?= $abrirNuevo ? 'true' : 'false' ?>" data-pestana="nuevo">
                        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
                        Componente nuevo
                    </button>
                </div>

                <div class="panelEscoger" role="tabpanel" id="panelInventario" aria-labelledby="pestanaInventario" data-panel="inventario" <?= $abrirNuevo ? 'hidden' : '' ?>>
                    <?php if ($componentes === []): ?>
                        <p class="sinResultados">Todavía no tiene componentes en su inventario. Créelos en la pestaña "Componente nuevo".</p>
                    <?php else: ?>
                        <span class="etiquetaCampo" id="tituloTipos">Seleccione el tipo de componente</span>
                        <div class="botonesFecha botonesTipo" role="group" aria-labelledby="tituloTipos">
                            <button type="button" class="botonFecha botonFechaActivo" aria-pressed="true" data-filtro-tipo="">Todos</button>
                            <?php foreach ($tiposConComponentes as $idTipo => $nombreTipo): ?>
                                <button type="button" class="botonFecha" aria-pressed="false" data-filtro-tipo="<?= $idTipo ?>"><?= htmlspecialchars($nombreTipo) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <label class="buscadorComponentes">
                            <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="4.5"></circle><path d="M10.5 10.5L14 14"></path></svg>
                            <span class="soloLectores">Buscar componente</span>
                            <input type="search" placeholder="Buscar por nombre, por ejemplo perla" autocomplete="off" data-buscar-componente>
                        </label>
                        <div class="tarjetasComponentes" data-tarjetas>
                            <?php foreach ($componentes as $componente): ?>
                                <?php
                                $unidad = $unidades[$componente->getUnidad()] ?? $unidades[UnidadMedida::UNIDAD];
                                $agregado = in_array((string) $componente->getIdComponente(), $enCompra, true);
                                ?>
                                <div class="tarjetaComponente" data-tarjeta
                                     data-id="<?= $componente->getIdComponente() ?>" data-tipo="<?= $componente->getIdTipoComponente() ?>"
                                     data-nombre="<?= htmlspecialchars($componente->getNombre()) ?>"
                                     data-tipo-nombre="<?= htmlspecialchars($componente->getNombreTipo()) ?>"
                                     data-unidad="<?= htmlspecialchars($componente->getUnidad()) ?>"
                                     data-existencia="<?= $componente->getExistencia() ?>">
                                    <div class="tarjetaComponenteTexto">
                                        <strong><?= htmlspecialchars($componente->getNombre()) ?></strong>
                                        <span class="textoAyuda"><?= htmlspecialchars($componente->getNombreTipo()) ?> · Tiene <?= Numero::formatear($componente->getExistencia()) . ' ' . ($componente->getExistencia() == 1 ? mb_strtolower($unidad['nombre'], 'UTF-8') : $unidad['plural']) ?></span>
                                    </div>
                                    <span class="enLaCompra" data-en-compra <?= $agregado ? '' : 'hidden' ?>>✓ En la compra</span>
                                    <button type="button" class="boton botonPequeno" data-agregar <?= $agregado ? 'hidden' : '' ?>
                                            aria-label="Agregar <?= htmlspecialchars($componente->getNombre()) ?> a la compra">Agregar</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="sinCoincidencias" data-sin-coincidencias hidden>
                            <span>No tiene ningún componente que se llame «<span data-texto-buscado></span>».</span>
                            <?php if ($sePuedeCrear): ?>
                                <button type="button" class="boton botonSecundario botonPequeno" data-crear-desde-busqueda>Crearlo como componente nuevo</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panelEscoger" role="tabpanel" id="panelNuevo" aria-labelledby="pestanaNuevo" data-panel="nuevo" <?= $abrirNuevo ? '' : 'hidden' ?>>
                    <?php if (!$sePuedeCrear): ?>
                        <p class="sinResultados">Para crear componentes primero necesita sus tipos, por ejemplo Perlas o Hilos.
                            <a href="<?= $urlBase ?>/tipos-componente">Crear tipos de componente</a></p>
                    <?php else: ?>
                        <div class="filaCampos">
                            <div class="campo">
                                <label for="nuevoTipo">Tipo</label>
                                <select id="nuevoTipo" data-nuevo-tipo>
                                    <option value="">Escoja el tipo</option>
                                    <?php foreach ($tipos as $tipo): ?>
                                        <option value="<?= $tipo->getIdTipoComponente() ?>"><?= htmlspecialchars($tipo->getNombre()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo">
                                <label for="nuevoNombre">Nombre</label>
                                <input type="text" id="nuevoNombre" maxlength="100" placeholder="Por ejemplo Hilo encerado negro" autocomplete="off" data-nuevo-nombre>
                            </div>
                        </div>
                        <fieldset class="campo opcionesUnidad">
                            <legend class="etiquetaCampo">¿Cómo se cuenta?</legend>
                            <div class="opcionesUnidadLista">
                                <?php foreach ($unidades as $codigo => $unidad): ?>
                                    <label class="opcionUnidad">
                                        <input type="radio" name="unidadNueva" value="<?= $codigo ?>" <?= $codigo === UnidadMedida::UNIDAD ? 'checked' : '' ?> data-nueva-unidad form="sinFormulario">
                                        <strong>Por <?= mb_strtolower($unidad['nombre'], 'UTF-8') ?></strong>
                                        <span><?= htmlspecialchars($unidad['ejemplo']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <div class="pieNuevo">
                            <span class="textoAyuda">Sus otros datos (color, tamaño…) los completa después en el inventario, con Editar.</span>
                            <button type="button" class="boton" data-agregar-nuevo>Agregar a la compra</button>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="formularioSeccion pasoCompra">
                <div class="pasoEncabezado">
                    <span class="pasoNumero" aria-hidden="true">3</span>
                    <div>
                        <h2>Cantidad y precio</h2>
                        <p class="textoAyuda">Escriba cuánto compró y cuánto pagó en total por eso; el costo de cada uno lo calcula el sistema.</p>
                    </div>
                </div>
                <?= $mensajeError('lineas') ?>
                <p class="lineasVacias" data-sin-lineas <?= $datos['lineas'] === [] ? '' : 'hidden' ?>>Todavía no ha agregado componentes. Escójalos en el paso 2.</p>
                <div class="listaLineasCompra" data-lineas>
                    <?php foreach ($datos['lineas'] as $clave => $linea): ?>
                        <?= $filaLinea((string) $clave, $linea, $infoLinea($linea)) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <aside class="resumenCompra" aria-label="Resumen de la compra">
            <h2>Resumen</h2>
            <dl class="resumenCompraDatos">
                <dt>Proveedor</dt>
                <dd data-resumen-proveedor>Sin escoger</dd>
                <dt>Fecha</dt>
                <dd data-resumen-fecha>Sin escoger</dd>
            </dl>
            <div class="totalesCompra" aria-live="polite">
                <div><span data-resumen-cantidad>0 componentes</span><strong data-total-subtotal>₡0</strong></div>
                <div><span>Envío</span><strong data-total-envio>Sin envío</strong></div>
                <div class="totalCompra"><span>Total</span><strong data-total-compra>₡0</strong></div>
            </div>
            <p class="textoAyuda">El envío se reparte entre los componentes según lo que costó cada uno y se suma a su costo en el inventario.</p>
            <button type="submit" class="boton botonCompleto botonRegistrar">Registrar compra</button>
            <a href="<?= $urlBase ?>/compras-proveedor" class="enlaceCancelar">Cancelar</a>
        </aside>
    </div>
</form>

<template data-linea-plantilla>
    <?= $filaLinea('__n__', $plantillaVacia, $infoVacia) ?>
</template>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>