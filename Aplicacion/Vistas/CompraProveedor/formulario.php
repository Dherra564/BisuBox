<?php
/**
* @var array $datos
 * @var array $errores
 * @var \Aplicacion\Modelos\Proveedor[] $proveedores
 * @var \Aplicacion\Modelos\Componente[] $componentes
 * @var \Aplicacion\Modelos\TipoComponente[] $tipos
 * @var string $urlBase
 */

use Aplicacion\Nucleo\ClaseDato;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosComponente;
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
$mensajeError = fn(string $campo, string $antes = ''): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($antes . $errores[$campo]) . '</p>'
    : '';
$unidades = UnidadMedida::todas();
$sePuedeCrear = $tipos !== [];

$tiposConComponentes = [];
foreach ($componentes as $componente) {
    $tiposConComponentes[$componente->getIdTipoComponente()] = $componente->getNombreTipo();
}
asort($tiposConComponentes);

$datosEnCorto = function (array $linea) use ($tipos): string {
    $tipo = $tipos[(int) $linea['tipo']] ?? null;
    if ($tipo === null) {
        return '';
    }
    $partes = [];
    foreach ($tipo->getAtributosActivos() as $atributo) {
        $valor = $linea['valores'][(int) $atributo->getIdAtributo()] ?? '';
        if ($atributo->getClase() === ClaseDato::SINO) {
            $partes[] = $valor === '1' ? $atributo->getNombre() : '';
        } elseif ($atributo->getClase() === ClaseDato::NUMERO && $valor !== '') {
            $numero = Numero::leer($valor);
            $partes[] = $numero === null ? $valor : DatosComponente::mostrarValor($atributo, (string) $numero);
        } elseif ($valor !== '') {
            $partes[] = DatosComponente::mostrarValor($atributo, $valor);
        }
    }
    return implode(', ', array_filter($partes));
};

$infoLinea = function (array $linea) use ($componentes, $tipos, $datosEnCorto): array {
    if ($linea['componente'] === DatosCompraProveedor::NUEVO) {
        $nombreTipo = isset($tipos[(int) $linea['tipo']]) ? $tipos[(int) $linea['tipo']]->getNombre() : '';
        $enCorto = $datosEnCorto($linea);
        return [
            'nombre' => $linea['nombre'],
            'detalle' => $nombreTipo . ($enCorto !== '' ? ' · ' . $enCorto : ''),
            'unidad' => UnidadMedida::existe($linea['unidad']) ? $linea['unidad'] : UnidadMedida::UNIDAD,
            'existencia' => 0.0,
            'nuevo' => true,
            'atributos' => isset($tipos[(int) $linea['tipo']]) ? $tipos[(int) $linea['tipo']]->getAtributosActivos() : [],
        ];
    }
    $componente = $componentes[(int) $linea['componente']] ?? null;
    return [
        'nombre' => $componente?->getNombre() ?? 'Componente que ya no está disponible',
        'detalle' => $componente?->getNombreTipo() ?? '',
        'unidad' => $componente?->getUnidad() ?? UnidadMedida::UNIDAD,
        'existencia' => $componente?->getExistencia() ?? 0.0,
        'nuevo' => false,
        'atributos' => [],
    ];
};

$filaLinea = function (string $clave, array $linea, array $info) use ($claseCampo, $mensajeError, $unidades): string {
    $campo = 'linea.' . $clave . '.';
    $nombre = 'linea[' . $clave . ']';
    $unidad = $unidades[$info['unidad']] ?? $unidades[UnidadMedida::UNIDAD];
    $esMetro = $info['unidad'] === UnidadMedida::METRO;
    $enCentimetros = $linea['medida'] === DatosCompraProveedor::CENTIMETROS;
    $porQue = ['Metro' => 'por metro', 'Gramo' => 'por gramo'][$info['unidad']] ?? 'cada uno';

    $valores = '';
    foreach ($linea['valores'] as $idAtributo => $valor) {
        $valores .= '<input type="hidden" name="' . $nombre . '[valor][' . (int) $idAtributo . ']" value="' . htmlspecialchars($valor) . '">';
    }
    $erroresDatos = $mensajeError($campo . 'componente') . $mensajeError($campo . 'tipo') . $mensajeError($campo . 'nombre') . $mensajeError($campo . 'unidad');
    foreach ($info['atributos'] as $atributo) {
        $erroresDatos .= $mensajeError($campo . 'valor.' . $atributo->getIdAtributo(), $atributo->getNombre() . ': ');
    }

    return '<article class="lineaCompra" data-linea data-clave="' . $clave . '"'
        . ' data-componente="' . htmlspecialchars($linea['componente']) . '"'
        . ' data-existencia="' . $info['existencia'] . '" data-unidad="' . htmlspecialchars($info['unidad']) . '"'
        . ' data-nuevo="' . ($info['nuevo'] ? '1' : '0') . '" data-detalle="' . htmlspecialchars($info['detalle']) . '">'
        . '<input type="hidden" name="' . $nombre . '[componente]" value="' . htmlspecialchars($linea['componente']) . '" data-linea-componente>'
        . '<input type="hidden" name="' . $nombre . '[tipo]" value="' . htmlspecialchars($linea['tipo']) . '" data-linea-tipo>'
        . '<input type="hidden" name="' . $nombre . '[nombre]" value="' . htmlspecialchars($linea['nombre']) . '" data-linea-nombre>'
        . '<input type="hidden" name="' . $nombre . '[unidad]" value="' . htmlspecialchars($linea['unidad']) . '" data-linea-unidad>'
        . '<span data-linea-valores>' . $valores . '</span>'
        . '<div class="lineaCompraNombre">'
        . '<span><strong data-linea-titulo>' . htmlspecialchars($info['nombre']) . '</strong>'
        . ' <span class="etiqueta etiquetaNuevo" data-linea-marca-nuevo' . ($info['nuevo'] ? '' : ' hidden') . '>Nuevo</span></span>'
        . '<span class="lineaCompraDetalle" data-linea-detalle>' . htmlspecialchars($info['detalle']) . '</span>'
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'cantidad') . '">'
        . '<label for="linea-' . $clave . '-cantidad" class="etiquetaLinea">Cantidad</label>'
        . '<div class="campoConUnidad">'
        . '<input type="text" id="linea-' . $clave . '-cantidad" name="' . $nombre . '[cantidad]" maxlength="13" inputmode="decimal"'
        . ' required data-mensaje-requerido="Escriba cuánto compró"'
        . ' data-linea-cantidad placeholder="0" value="' . htmlspecialchars($linea['cantidad']) . '">'
        . '<select name="' . $nombre . '[medida]" class="selectorMedida" aria-label="Metros o centímetros" data-linea-medida' . ($esMetro ? '' : ' hidden') . '>'
        . '<option value="m"' . ($enCentimetros ? '' : ' selected') . '>m</option>'
        . '<option value="cm"' . ($enCentimetros ? ' selected' : '') . '>cm</option>'
        . '</select>'
        . '<span class="unidadCampo" data-linea-abreviatura' . ($esMetro ? ' hidden' : '') . '>' . $unidad['abreviatura'] . '</span>'
        . '</div>'
        . $mensajeError($campo . 'cantidad')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'total') . '">'
        . '<label for="linea-' . $clave . '-total" class="etiquetaLinea">Precio total del componente</label>'
        . '<div class="campoConUnidad campoConMoneda">'
        . '<span class="unidadCampo">₡</span>'
        . '<input type="text" id="linea-' . $clave . '-total" name="' . $nombre . '[total]" maxlength="13" inputmode="decimal"'
        . ' required data-mensaje-requerido="Escriba cuánto pagó en total por este componente"'
        . ' data-linea-total placeholder="0" value="' . htmlspecialchars($linea['total']) . '">'
        . '</div>'
        . $mensajeError($campo . 'total')
        . '</div>'
        . '<div class="lineaCompraCosto">'
        . '<span class="etiquetaLinea">Costo</span>'
        . '<strong data-linea-costo>—</strong>'
        . '<span class="lineaCompraDetalle" data-linea-por>' . $porQue . '</span>'
        . '</div>'
        . '<button type="button" class="botonQuitarLinea" aria-label="Quitar ' . htmlspecialchars($info['nombre']) . ' de la compra"'
        . ' title="Quitar de la compra" data-linea-quitar>'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>'
        . ($erroresDatos !== '' ? '<div class="lineaCompraErrores">' . $erroresDatos . '</div>' : '')
        . '</article>';
};

$plantillaVacia = ['componente' => '', 'tipo' => '', 'nombre' => '', 'unidad' => '', 'cantidad' => '', 'medida' => 'm', 'total' => '', 'valores' => []];
$infoVacia = ['nombre' => '', 'detalle' => '', 'unidad' => UnidadMedida::UNIDAD, 'existencia' => 0.0, 'nuevo' => false, 'atributos' => []];
$enCompra = array_map(fn(array $linea): string => $linea['componente'], $datos['lineas']);
$abrirNuevo = $componentes === [];
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo. No se guardó nada todavía.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/compras-proveedor/nueva" novalidate
    class="validarFormulario formularioCompra" data-maximo-lineas="<?= DatosCompraProveedor::MAXIMO_LINEAS ?>"
    data-confirmar="Al registrar la compra se suma al inventario lo que compró. Si después encuentra un error, puede anularla."
    data-titulo="Registrar compra" data-boton="Registrar compra">
    <?= Csrf::campo() ?>

    <div class="registroCompra">
        <section class="formularioSeccion pasoCompra">
            <div class="pasoEncabezado">
                <span class="pasoNumero" aria-hidden="true">1</span>
                <h2>Datos de la compra</h2>
            </div>
            <div class="filaCampos filaTres">
                <div class="<?= $claseCampo('proveedor') ?>">
                    <label for="proveedor">Proveedor <span class="obligatorio">*</span></label>
                    <select id="proveedor" name="proveedor" required
                        data-mensaje-requerido="Escoja a qué proveedor le compró">
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
                    <?= $mensajeError('fecha') ?>
                </div>
                <div class="<?= $claseCampo('costoEnvio') ?>">
                    <label for="costoEnvio">Costo de envío <span class="textoOpcional">(opcional)</span></label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="costoEnvio" name="costoEnvio" maxlength="13" inputmode="decimal"
                            data-regla="cantidad"
                            data-mensaje-regla="Escriba un monto, por ejemplo 2500 o 2500,50. Si no pagó envío, déjelo vacío"
                            data-costo-envio value="<?= htmlspecialchars($datos['costoEnvio']) ?>">
                    </div>
                    <?= $mensajeError('costoEnvio') ?>
                </div>
            </div>
        </section>

        <section class="formularioSeccion pasoCompra pasoEscoger">
            <div class="pasoEncabezado">
                <span class="pasoNumero" aria-hidden="true">2</span>
                <div>
                    <h2>Escoja lo que compró</h2>
                    <p class="textoAyuda">Búsquelo en su inventario o créelo si es la primera vez que lo compra. Puede
                        agregar todos los componentes que quiera.</p>
                </div>
            </div>

            <div class="pestanas pestanasEscoger" role="tablist" aria-label="De dónde sale el componente">
                <button type="button" class="pestana <?= $abrirNuevo ? '' : 'pestanaActiva' ?>" role="tab"
                    id="pestanaInventario" aria-controls="panelInventario"
                    aria-selected="<?= $abrirNuevo ? 'false' : 'true' ?>" data-pestana="inventario">De mi
                    inventario</button>
                <button type="button" class="pestana <?= $abrirNuevo ? 'pestanaActiva' : '' ?>" role="tab"
                    id="pestanaNuevo" aria-controls="panelNuevo" aria-selected="<?= $abrirNuevo ? 'true' : 'false' ?>"
                    data-pestana="nuevo">Componente nuevo</button>
            </div>

            <div class="panelEscoger" role="tabpanel" id="panelInventario" aria-labelledby="pestanaInventario"
                data-panel="inventario" <?= $abrirNuevo ? 'hidden' : '' ?>>
                <?php if ($componentes === []): ?>
                    <p class="sinResultados">Todavía no tiene componentes en su inventario. Créelos en la pestaña
                        "Componente nuevo".</p>
                <?php else: ?>
                    <div class="botonesTipo" role="group" aria-label="Tipo de componente">
                        <button type="button" class="botonTipo botonTipoActivo" aria-pressed="true"
                            data-filtro-tipo="">Todos</button>
                        <?php foreach ($tiposConComponentes as $idTipo => $nombreTipo): ?>
                            <button type="button" class="botonTipo" aria-pressed="false"
                                data-filtro-tipo="<?= $idTipo ?>"><?= htmlspecialchars($nombreTipo) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="search" class="buscadorComponentes" aria-label="Buscar componente"
                        placeholder="Buscar por nombre" autocomplete="off" data-buscar-componente>
                    <ul class="listaInventario">
                        <?php foreach ($componentes as $componente): ?>
                            <?php
                            $unidad = $unidades[$componente->getUnidad()] ?? $unidades[UnidadMedida::UNIDAD];
                            $agregado = in_array((string) $componente->getIdComponente(), $enCompra, true);
                            ?>
                            <li class="filaInventario" data-fila-inventario data-id="<?= $componente->getIdComponente() ?>"
                                data-tipo="<?= $componente->getIdTipoComponente() ?>"
                                data-nombre="<?= htmlspecialchars($componente->getNombre()) ?>"
                                data-tipo-nombre="<?= htmlspecialchars($componente->getNombreTipo()) ?>"
                                data-unidad="<?= htmlspecialchars($componente->getUnidad()) ?>"
                                data-existencia="<?= $componente->getExistencia() ?>">
                                <div class="filaInventarioTexto">
                                    <strong><?= htmlspecialchars($componente->getNombre()) ?></strong>
                                    <span><?= htmlspecialchars($componente->getNombreTipo()) ?> · tiene
                                        <?= UnidadMedida::cantidad($componente->getExistencia(), $componente->getUnidad()) ?></span>
                                </div>
                                <span class="enLaCompra" data-en-compra <?= $agregado ? '' : 'hidden' ?>>Agregado ✓</span>
                                <button type="button" class="boton botonSecundario botonPequeno" data-agregar <?= $agregado ? 'hidden' : '' ?>
                                    aria-label="Agregar <?= htmlspecialchars($componente->getNombre()) ?> a la compra">Agregar</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="sinCoincidencias" data-sin-coincidencias hidden>
                        No tiene ningún componente que se llame «<span data-texto-buscado></span>».
                        <?php if ($sePuedeCrear): ?>
                            <button type="button" class="botonEnlace" data-crear-desde-busqueda>Crearlo como componente
                                nuevo</button>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="panelEscoger" role="tabpanel" id="panelNuevo" aria-labelledby="pestanaNuevo" data-panel="nuevo"
                <?= $abrirNuevo ? '' : 'hidden' ?>>
                <?php if (!$sePuedeCrear): ?>
                    <p class="sinResultados">Para crear componentes primero necesita sus tipos, por ejemplo Perlas o Hilos.
                        <a href="<?= $urlBase ?>/tipos-componente">Crear tipos de componente</a>
                    </p>
                <?php else: ?>
                    <div class="filaCampos">
                        <div class="campo">
                            <label for="nuevoNombre">Nombre del componente</label>
                            <input type="text" id="nuevoNombre" maxlength="100" placeholder="Por ejemplo: Perla blanca 8 mm"
                                autocomplete="off" data-nuevo-nombre>
                        </div>
                        <div class="campo">
                            <label for="nuevoTipo">Tipo</label>
                            <select id="nuevoTipo" data-nuevo-tipo>
                                <option value="">Escoja el tipo</option>
                                <?php foreach ($tipos as $tipo): ?>
                                    <option value="<?= $tipo->getIdTipoComponente() ?>">
                                        <?= htmlspecialchars($tipo->getNombre()) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="atributosNuevo" data-atributos-vacio>
                        <div class="campo">
                            <label for="atributosBloqueados">Datos del tipo</label>
                            <input type="text" id="atributosBloqueados" disabled placeholder="Escoja primero el tipo">
                        </div>
                    </div>
                    <?php foreach ($tipos as $tipo): ?>
                        <div class="atributosNuevo" data-atributos-tipo="<?= $tipo->getIdTipoComponente() ?>" hidden>
                            <?php if ($tipo->getAtributosActivos() === []): ?>
                                <p class="textoAyuda">Este tipo no tiene datos extra.</p>
                            <?php endif; ?>
                            <?php foreach ($tipo->getAtributosActivos() as $atributo): ?>
                                <?php
                                $idCampo = 'nuevoAtributo' . $atributo->getIdAtributo();
                                $marca = ' <span class="obligatorio">*</span>';
                                $datosAtributo = 'data-atributo="' . $atributo->getIdAtributo() . '" data-clase="' . $atributo->getClase() . '"'
                                    . ' data-nombre="' . htmlspecialchars($atributo->getNombre()) . '"'
                                    . ' data-unidad="' . htmlspecialchars((string) $atributo->getUnidad()) . '"'
                                    . ' data-obligatorio';
                                ?>
                                <div class="campo">
                                    <?php if ($atributo->getClase() === ClaseDato::SINO): ?>
                                        <span class="etiquetaCampo"><?= htmlspecialchars($atributo->getNombre()) ?></span>
                                        <label class="casillaAtributo">
                                            <input type="checkbox" id="<?= $idCampo ?>" value="1" <?= $datosAtributo ?>> Sí
                                        </label>
                                    <?php else: ?>
                                        <label for="<?= $idCampo ?>"><?= htmlspecialchars($atributo->getNombre()) . $marca ?></label>
                                        <?php if ($atributo->getClase() === ClaseDato::LISTA): ?>
                                            <select id="<?= $idCampo ?>" <?= $datosAtributo ?>>
                                                <option value="">Escoja</option>
                                                <?php foreach ($atributo->getOpcionesActivas() as $opcion): ?>
                                                    <option value="<?= $opcion->getIdOpcion() ?>"><?= htmlspecialchars($opcion->getValor()) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif ($atributo->getClase() === ClaseDato::NUMERO): ?>
                                            <div class="campoConUnidad">
                                                <input type="text" id="<?= $idCampo ?>" maxlength="13" inputmode="decimal" placeholder="0"
                                                    <?= $datosAtributo ?>>
                                                <?php if ($atributo->getUnidad()): ?>
                                                    <span class="unidadCampo"><?= htmlspecialchars($atributo->getUnidad()) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <input type="text" id="<?= $idCampo ?>" maxlength="300" <?= $datosAtributo ?>>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <fieldset class="campo opcionesUnidad">
                        <legend class="etiquetaCampo">¿Cómo se calcula el precio?</legend>
                        <div class="opcionesUnidadLista">
                            <?php foreach ($unidades as $codigo => $unidad): ?>
                                <label class="opcionUnidad">
                                    <input type="radio" name="unidadNueva" value="<?= $codigo ?>"
                                        <?= $codigo === UnidadMedida::UNIDAD ? 'checked' : '' ?> data-nueva-unidad
                                        form="sinFormulario">
                                    Por <?= mb_strtolower($unidad['nombre'], 'UTF-8') ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <div>
                        <button type="button" class="boton" data-agregar-nuevo>Agregar a la compra</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="formularioSeccion pasoCompra">
            <div class="pasoEncabezado">
                <span class="pasoNumero" aria-hidden="true">3</span>
                <h2>Cantidad y precio</h2>
            </div>
            <?php if (isset($errores['lineas'])): ?>
                <p class="errorCampo" data-error-lineas><?= htmlspecialchars($errores['lineas']) ?></p>
            <?php endif; ?>
            <p class="lineasVacias" data-sin-lineas <?= $datos['lineas'] === [] ? '' : 'hidden' ?>>Todavía no ha agregado
                componentes.</p>
            <div class="lineasEncabezado" aria-hidden="true" data-encabezado-lineas <?= $datos['lineas'] === [] ? 'hidden' : '' ?>>
                <span>Componente</span><span>Cantidad</span><span>Precio total del
                    componente</span><span>Costo</span><span></span>
            </div>
            <div class="listaLineasCompra" data-lineas>
                <?php foreach ($datos['lineas'] as $clave => $linea): ?>
                    <?= $filaLinea((string) $clave, $linea, $infoLinea($linea)) ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="resumenCompra" aria-label="Resumen de la compra">
            <div class="resumenCompraTotales" aria-live="polite">
                <div><span data-resumen-cantidad>0 componentes</span><strong data-total-subtotal>₡0</strong></div>
                <div><span>Envío</span><strong data-total-envio>Sin envío</strong></div>
                <div class="resumenCompraTotal"><span>Total</span><strong data-total-compra>₡0</strong></div>
            </div>
            <div class="resumenCompraBotones">
                <a href="<?= $urlBase ?>/compras-proveedor" class="enlaceCancelar">Cancelar</a>
                <button type="submit" class="boton botonRegistrar">Registrar compra</button>
            </div>
        </section>
    </div>
</form>

<template data-linea-plantilla>
    <?= $filaLinea('__n__', $plantillaVacia, $infoVacia) ?>
</template>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>