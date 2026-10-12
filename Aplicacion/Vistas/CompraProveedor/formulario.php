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

$componentesPorTipo = [];
foreach ($componentes as $componente) {
    $componentesPorTipo[$componente->getNombreTipo()][] = $componente;
}

$filaLinea = function (string $clave, array $linea) use ($claseCampo, $mensajeError, $componentesPorTipo, $tipos, $unidades, $sePuedeCrear): string {
    $campo = 'linea.' . $clave . '.';
    $nombre = 'linea[' . $clave . ']';
    $esNuevo = $linea['componente'] === DatosCompraProveedor::NUEVO;

    $opciones = '<option value="">Escoja un componente</option>';
    foreach ($componentesPorTipo as $nombreTipo => $lista) {
        $opciones .= '<optgroup label="' . htmlspecialchars($nombreTipo) . '">';
        foreach ($lista as $componente) {
            $unidad = $unidades[$componente->getUnidad()] ?? $unidades[UnidadMedida::UNIDAD];
            $opciones .= '<option value="' . $componente->getIdComponente() . '"'
                . ((string) $componente->getIdComponente() === $linea['componente'] ? ' selected' : '')
                . ' data-existencia="' . $componente->getExistencia() . '"'
                . ' data-plural="' . $unidad['plural'] . '"'
                . ' data-entera="' . ($componente->getUnidad() === UnidadMedida::UNIDAD ? '1' : '0') . '">'
                . htmlspecialchars($componente->getNombre()) . '</option>';
        }
        $opciones .= '</optgroup>';
    }
    if ($sePuedeCrear) {
        $opciones .= '<optgroup label="¿No está en la lista?"><option value="' . DatosCompraProveedor::NUEVO . '"'
            . ($esNuevo ? ' selected' : '') . '>+ Crear componente nuevo</option></optgroup>';
    }

    $opcionesTipo = '<option value="">Escoja el tipo</option>';
    foreach ($tipos as $tipo) {
        $opcionesTipo .= '<option value="' . $tipo->getIdTipoComponente() . '"'
            . ((string) $tipo->getIdTipoComponente() === $linea['tipo'] ? ' selected' : '') . '>'
            . htmlspecialchars($tipo->getNombre()) . '</option>';
    }

    $opcionesUnidad = '';
    foreach ($unidades as $codigo => $unidad) {
        $opcionesUnidad .= '<option value="' . $codigo . '" data-plural="' . $unidad['plural'] . '"'
            . ' data-entera="' . ($codigo === UnidadMedida::UNIDAD ? '1' : '0') . '"'
            . ($codigo === ($linea['unidad'] ?: UnidadMedida::UNIDAD) ? ' selected' : '') . '>'
            . 'Por ' . mb_strtolower($unidad['nombre'], 'UTF-8') . '</option>';
    }

    return '<div class="lineaCompra" data-linea data-clave="' . $clave . '">'
        . '<div class="lineaCompraCampos">'
        . '<div class="' . $claseCampo($campo . 'componente', 'campoComponente') . '">'
        . '<label for="linea-' . $clave . '-componente">Componente</label>'
        . '<select id="linea-' . $clave . '-componente" name="' . $nombre . '[componente]" data-linea-componente>' . $opciones . '</select>'
        . '<p class="textoAyuda avisoExistencia" data-linea-aviso hidden></p>'
        . $mensajeError($campo . 'componente')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'cantidad') . '">'
        . '<label for="linea-' . $clave . '-cantidad">Cantidad</label>'
        . '<div class="campoConUnidad">'
        . '<input type="text" id="linea-' . $clave . '-cantidad" name="' . $nombre . '[cantidad]" maxlength="13" inputmode="decimal"'
        . ' data-linea-cantidad placeholder="0" value="' . htmlspecialchars($linea['cantidad']) . '">'
        . '<span class="unidadCampo" data-linea-unidad></span>'
        . '</div>'
        . $mensajeError($campo . 'cantidad')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'costo') . '">'
        . '<label for="linea-' . $clave . '-costo">Costo de cada uno</label>'
        . '<div class="campoConUnidad campoConMoneda">'
        . '<span class="unidadCampo">₡</span>'
        . '<input type="text" id="linea-' . $clave . '-costo" name="' . $nombre . '[costo]" maxlength="13" inputmode="decimal"'
        . ' data-linea-costo placeholder="0" value="' . htmlspecialchars($linea['costo']) . '">'
        . '</div>'
        . $mensajeError($campo . 'costo')
        . '</div>'
        . '<div class="campo campoSubtotal">'
        . '<span class="etiquetaCampo">Subtotal</span>'
        . '<span class="subtotalLinea" data-linea-subtotal>₡0</span>'
        . '</div>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar esta línea" title="Quitar esta línea" data-linea-quitar>'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>'
        . '</div>'
        . '<div class="componenteNuevo" data-linea-nuevo' . ($esNuevo ? '' : ' hidden') . '>'
        . '<p class="componenteNuevoTitulo">Componente nuevo: se agrega al inventario al registrar la compra</p>'
        . '<div class="filaCampos">'
        . '<div class="' . $claseCampo($campo . 'tipo') . '">'
        . '<label for="linea-' . $clave . '-tipo">Tipo</label>'
        . '<select id="linea-' . $clave . '-tipo" name="' . $nombre . '[tipo]">' . $opcionesTipo . '</select>'
        . $mensajeError($campo . 'tipo')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'nombre') . '">'
        . '<label for="linea-' . $clave . '-nombre">Nombre</label>'
        . '<input type="text" id="linea-' . $clave . '-nombre" name="' . $nombre . '[nombre]" maxlength="100"'
        . ' placeholder="Por ejemplo: Perla blanca 8 mm" value="' . htmlspecialchars($linea['nombre']) . '">'
        . $mensajeError($campo . 'nombre')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'unidad') . '">'
        . '<label for="linea-' . $clave . '-unidad">¿Cómo se cuenta?</label>'
        . '<select id="linea-' . $clave . '-unidad" name="' . $nombre . '[unidad]" data-linea-unidad-nueva>' . $opcionesUnidad . '</select>'
        . $mensajeError($campo . 'unidad')
        . '</div>'
        . '</div>'
        . '<p class="textoAyuda">Sus otros datos (color, tamaño...) los puede completar después en el inventario, con Editar.</p>'
        . '</div>'
        . '</div>';
};

$lineaVacia = [
    'componente' => $componentes === [] ? DatosCompraProveedor::NUEVO : '',
    'tipo' => '',
    'nombre' => '',
    'unidad' => UnidadMedida::UNIDAD,
    'cantidad' => '',
    'costo' => ''
];
$lineas = $datos['lineas'] !== [] ? $datos['lineas'] : [0 => $lineaVacia];
if ($componentes === []) {
    foreach ($lineas as $clave => $linea) {
        $lineas[$clave]['componente'] = $linea['componente'] ?: DatosCompraProveedor::NUEVO;
    }
}
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

    <section class="formularioSeccion">
        <h2>Datos de la compra</h2>
        <div class="filaCampos">
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
                <p class="textoAyuda">El día en que compró, no hace falta que sea hoy.</p>
                <?= $mensajeError('fecha') ?>
            </div>
            <div class="<?= $claseCampo('costoEnvio') ?>">
                <label for="costoEnvio">Costo de envío</label>
                <div class="campoConUnidad campoConMoneda">
                    <span class="unidadCampo">₡</span>
                    <input type="text" id="costoEnvio" name="costoEnvio" maxlength="13" inputmode="decimal"
                        data-regla="cantidad" data-mensaje-regla="Escriba un monto, por ejemplo 2500 o 2500,50"
                        placeholder="0" data-costo-envio value="<?= htmlspecialchars($datos['costoEnvio']) ?>">
                </div>
                <p class="textoAyuda">Opcional. Si no pagó envío, déjelo vacío.</p>
                <?= $mensajeError('costoEnvio') ?>
            </div>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>¿Qué compró?</h2>
        <p class="textoAyuda">Una línea por componente. Si no está en su inventario, escoja "Crear componente nuevo".
        </p>
        <?= $mensajeError('lineas') ?>

        <div class="listaLineasCompra" data-lineas>
            <?php foreach ($lineas as $clave => $linea): ?>
                <?= $filaLinea((string) $clave, $linea) ?>
            <?php endforeach; ?>
        </div>

        <div class="contactosPie">
            <button type="button" class="boton botonSecundario botonPequeno" data-linea-agregar>Agregar otro
                componente</button>
            <span class="textoAyuda" data-lineas-cuenta></span>
        </div>

        <div class="totalesCompra" aria-live="polite">
            <div><span>Subtotal</span><strong data-total-subtotal>₡0</strong></div>
            <div><span>Envío</span><strong data-total-envio>₡0</strong></div>
            <div class="totalCompra"><span>Total</span><strong data-total-compra>₡0</strong></div>
            <p class="textoAyuda">El envío se reparte entre los componentes según lo que costó cada uno y se suma a su
                costo en el inventario.</p>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/compras-proveedor" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton">Registrar compra</button>
    </div>
</form>

<template data-linea-plantilla>
    <?= $filaLinea('__n__', $lineaVacia) ?>
</template>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>