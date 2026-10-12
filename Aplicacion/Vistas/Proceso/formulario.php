<?php
/**
 * @var array $datos
 * @var array $errores
 * @var \Aplicacion\Modelos\Componente[] $componentes
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\DatosProceso;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Registrar proceso';
$paginaActual = 'procesos';
$volverA = ['texto' => 'Procesos', 'ruta' => '/procesos'];
$scriptsExtra = ['procesos.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo, string $extra = ''): string =>
    trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$unidades = UnidadMedida::todas();

$componentesPorTipo = [];
foreach ($componentes as $componente) {
    $componentesPorTipo[$componente->getNombreTipo()][] = $componente;
}

$iconoQuitar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';

$filaComponente = function (string $clave, array $linea) use ($claseCampo, $mensajeError, $componentesPorTipo, $unidades, $iconoQuitar): string {
    $campo = 'componente.' . $clave . '.';
    $nombre = 'componente[' . $clave . ']';

    $opciones = '<option value="">Escoja un componente</option>';
    foreach ($componentesPorTipo as $nombreTipo => $lista) {
        $opciones .= '<optgroup label="' . htmlspecialchars($nombreTipo) . '">';
        foreach ($lista as $componente) {
            $unidad = $unidades[$componente->getUnidad()] ?? $unidades[UnidadMedida::UNIDAD];
            $agotado = $componente->getExistencia() <= 0;
            $opciones .= '<option value="' . $componente->getIdComponente() . '"'
                . ((string) $componente->getIdComponente() === $linea['componente'] ? ' selected' : '')
                . ($agotado ? ' disabled' : '')
                . ' data-existencia="' . $componente->getExistencia() . '"'
                . ' data-costo="' . $componente->getCostoUnitario() . '"'
                . ' data-plural="' . $unidad['plural'] . '"'
                . ' data-metro="' . ($componente->getUnidad() === UnidadMedida::METRO ? '1' : '0') . '"'
                . ' data-etiqueta-costo="' . htmlspecialchars($unidad['costo']) . '"'
                . ' data-entera="' . ($componente->getUnidad() === UnidadMedida::UNIDAD ? '1' : '0') . '">'
                . htmlspecialchars($componente->getNombre()) . ($agotado ? ' (agotado)' : '') . '</option>';
        }
        $opciones .= '</optgroup>';
    }

    $enCentimetros = ($linea['medida'] ?? '') === DatosCompraProveedor::CENTIMETROS;

    return '<div class="lineaProceso" data-linea data-clave="' . $clave . '">'
        . '<div class="lineaProcesoCampos">'
        . '<div class="' . $claseCampo($campo . 'componente', 'campoComponente') . '">'
        . '<label for="componente-' . $clave . '-componente">Componente</label>'
        . '<select id="componente-' . $clave . '-componente" name="' . $nombre . '[componente]" data-linea-componente>' . $opciones . '</select>'
        . '<p class="textoAyuda avisoExistencia" data-linea-aviso hidden></p>'
        . $mensajeError($campo . 'componente')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'cantidad') . '">'
        . '<label for="componente-' . $clave . '-cantidad">Cantidad que usó</label>'
        . '<div class="campoConUnidad">'
        . '<input type="text" id="componente-' . $clave . '-cantidad" name="' . $nombre . '[cantidad]" maxlength="13" inputmode="decimal"'
        . ' data-linea-cantidad placeholder="0" value="' . htmlspecialchars($linea['cantidad']) . '">'
        . '<select name="' . $nombre . '[medida]" class="selectorMedida" aria-label="Metros o centímetros" data-linea-medida hidden>'
        . '<option value="m"' . ($enCentimetros ? '' : ' selected') . '>m</option>'
        . '<option value="cm"' . ($enCentimetros ? ' selected' : '') . '>cm</option>'
        . '</select>'
        . '<span class="unidadCampo" data-linea-unidad></span>'
        . '</div>'
        . $mensajeError($campo . 'cantidad')
        . '</div>'
        . '<div class="campo campoSubtotal">'
        . '<span class="etiquetaCampo" data-linea-etiqueta-costo>Costo de cada uno</span>'
        . '<span class="subtotalLinea" data-linea-costo>₡0</span>'
        . '</div>'
        . '<div class="campo campoSubtotal">'
        . '<span class="etiquetaCampo">Subtotal</span>'
        . '<span class="subtotalLinea" data-linea-subtotal>₡0</span>'
        . '</div>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar este componente" title="Quitar este componente" data-linea-quitar>'
        . $iconoQuitar . '</button>'
        . '</div>'
        . '</div>';
};

$filaExtra = function (string $clave, array $extra) use ($claseCampo, $mensajeError, $iconoQuitar): string {
    $campo = 'extra.' . $clave . '.';
    $nombre = 'extra[' . $clave . ']';

    return '<div class="lineaProceso" data-extra data-clave="' . $clave . '">'
        . '<div class="lineaExtraCampos">'
        . '<div class="' . $claseCampo($campo . 'concepto') . '">'
        . '<label for="extra-' . $clave . '-concepto">¿Para qué fue?</label>'
        . '<input type="text" id="extra-' . $clave . '-concepto" name="' . $nombre . '[concepto]" maxlength="60" list="sugerenciasExtra"'
        . ' data-extra-concepto placeholder="Por ejemplo Luz o Transporte" value="' . htmlspecialchars($extra['concepto']) . '">'
        . $mensajeError($campo . 'concepto')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'monto') . '">'
        . '<label for="extra-' . $clave . '-monto">Monto</label>'
        . '<div class="campoConUnidad campoConMoneda">'
        . '<span class="unidadCampo">₡</span>'
        . '<input type="text" id="extra-' . $clave . '-monto" name="' . $nombre . '[monto]" maxlength="13" inputmode="decimal"'
        . ' data-extra-monto placeholder="0" value="' . htmlspecialchars($extra['monto']) . '">'
        . '</div>'
        . $mensajeError($campo . 'monto')
        . '</div>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar este costo extra" title="Quitar este costo extra" data-extra-quitar>'
        . $iconoQuitar . '</button>'
        . '</div>'
        . '</div>';
};

$lineaVacia = ['componente' => '', 'cantidad' => '', 'medida' => 'm'];
$lineas = $datos['componentes'] !== [] ? $datos['componentes'] : [0 => $lineaVacia];
$extras = $datos['extras'];
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo. No se guardó nada todavía.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/procesos/nuevo" novalidate class="validarFormulario formularioProceso"
    data-maximo-componentes="<?= DatosProceso::MAXIMO_COMPONENTES ?>"
    data-maximo-extras="<?= DatosProceso::MAXIMO_EXTRAS ?>"
    data-confirmar="Al registrar el proceso se descuenta del inventario lo que usó. Si después encuentra un error, puede anularlo y los componentes vuelven al inventario."
    data-titulo="Registrar proceso" data-boton="Registrar proceso">
    <?= Csrf::campo() ?>

    <section class="formularioSeccion">
        <h2>1. ¿Qué producto hizo?</h2>

        <div class="<?= $claseCampo('nombre') ?>">
            <label for="nombre">Producto <span class="obligatorio">*</span></label>
            <input type="text" id="nombre" name="nombre" minlength="3" maxlength="60" required
                data-regla="nombreComercial" data-contador
                data-mensaje-requerido="Escriba qué producto hizo, por ejemplo Collar de perlas"
                data-mensaje-largo="El nombre debe tener entre 3 y 60 caracteres" value="<?= $valor('nombre') ?>">
            <p class="textoAyuda">Por ejemplo Collar de perlas o Aretes de flor</p>
            <?= $mensajeError('nombre') ?>
        </div>

        <div class="filaCampos">
            <div class="<?= $claseCampo('fecha') ?>">
                <label for="fecha">Fecha <span class="obligatorio">*</span></label>
                <input type="date" id="fecha" name="fecha" required max="<?= date('Y-m-d') ?>" min="2000-01-01"
                    data-mensaje-requerido="Escoja en el calendario el día en que hizo el producto"
                    value="<?= $valor('fecha') ?>">
                <?= $mensajeError('fecha') ?>
            </div>
            <div class="<?= $claseCampo('descripcion') ?>">
                <label for="descripcion">Descripción</label>
                <input type="text" id="descripcion" name="descripcion" maxlength="200" data-contador
                    placeholder="Opcional" value="<?= $valor('descripcion') ?>">
                <p class="textoAyuda">Opcional. Por ejemplo, el modelo o algún detalle.</p>
                <?= $mensajeError('descripcion') ?>
            </div>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>2. ¿Qué componentes usó?</h2>
        <p class="textoAyuda">Una línea por componente. Al registrar, la cantidad se descuenta de su inventario.</p>
        <?= $mensajeError('lineas') ?>

        <div class="listaLineasProceso" data-lineas>
            <?php foreach ($lineas as $clave => $linea): ?>
                <?= $filaComponente((string) $clave, $linea) ?>
            <?php endforeach; ?>
        </div>

        <div class="contactosPie">
            <button type="button" class="boton botonSecundario botonPequeno" data-linea-agregar>Agregar otro
                componente</button>
            <span class="textoAyuda" data-lineas-cuenta></span>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>3. ¿Cuánto tardó y cuánto cobra?</h2>

        <div class="filaCampos">
            <div class="<?= $claseCampo('tiempo') ?>">
                <label for="horas">Tiempo que duró <span class="obligatorio">*</span></label>
                <div class="filaTiempo">
                    <div class="campoConUnidad">
                        <input type="text" id="horas" name="horas" maxlength="3" inputmode="numeric" placeholder="0"
                            data-horas value="<?= $valor('horas') ?>">
                        <span class="unidadCampo">horas</span>
                    </div>
                    <div class="campoConUnidad">
                        <input type="text" id="minutos" name="minutos" maxlength="2" inputmode="numeric" placeholder="0"
                            data-minutos aria-label="Minutos" value="<?= $valor('minutos') ?>">
                        <span class="unidadCampo">minutos</span>
                    </div>
                </div>
                <p class="textoAyuda">Por ejemplo 1 hora y 30 minutos.</p>
                <?= $mensajeError('tiempo') ?>
            </div>
            <div class="<?= $claseCampo('tarifaHora') ?>">
                <label for="tarifaHora">Lo que cobra por hora <span class="obligatorio">*</span></label>
                <div class="campoConUnidad campoConMoneda">
                    <span class="unidadCampo">₡</span>
                    <input type="text" id="tarifaHora" name="tarifaHora" maxlength="13" inputmode="decimal" required
                        data-regla="cantidad" data-tarifa placeholder="0"
                        data-mensaje-requerido="Escriba cuánto cobra por hora, o 0 si no cobra mano de obra"
                        data-mensaje-regla="Escriba un monto, por ejemplo 2000 o 2000,50"
                        value="<?= $valor('tarifaHora') ?>">
                </div>
                <p class="textoAyuda">Escriba 0 si no cobra mano de obra.</p>
                <?= $mensajeError('tarifaHora') ?>
            </div>
        </div>
        <p class="textoAyuda detalleManoObra" data-mano-obra-detalle aria-live="polite"></p>
    </section>

    <section class="formularioSeccion">
        <h2>4. Costos extra</h2>
        <p class="textoAyuda">Opcional. Todo lo que gastó además de los componentes y su trabajo: luz, transporte,
            empaque...</p>
        <?= $mensajeError('extras') ?>

        <datalist id="sugerenciasExtra">
            <option value="Luz"></option>
            <option value="Agua"></option>
            <option value="Transporte"></option>
            <option value="Empaque"></option>
        </datalist>

        <p class="textoAyuda" data-extras-vacio <?= $extras !== [] ? 'hidden' : '' ?>>No ha agregado costos extra.</p>
        <div class="listaLineasProceso" data-extras>
            <?php foreach ($extras as $clave => $extra): ?>
                <?= $filaExtra((string) $clave, $extra) ?>
            <?php endforeach; ?>
        </div>

        <div class="contactosPie">
            <button type="button" class="boton botonSecundario botonPequeno" data-extra-agregar>Agregar costo
                extra</button>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>Precio del producto</h2>
        <div class="totalesCompra" aria-live="polite">
            <div><span>Componentes</span><strong data-total-componentes>₡0</strong></div>
            <div><span>Mano de obra</span><strong data-total-mano-obra>₡0</strong></div>
            <div><span>Costos extra</span><strong data-total-extras>₡0</strong></div>
            <div class="totalCompra"><span>Precio final</span><strong data-total-precio>₡0</strong></div>
            <p class="textoAyuda">Se calcula solo: componentes (con el costo que tienen en su inventario) + mano de obra
                (tiempo × lo que cobra por hora) + costos extra.</p>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario accionesIzquierda">
        <button type="submit" class="boton">Registrar proceso</button>
        <a href="<?= $urlBase ?>/procesos" class="boton botonSecundario">Cancelar</a>
    </div>
</form>

<template data-linea-plantilla>
    <?= $filaComponente('__n__', $lineaVacia) ?>
</template>

<template data-extra-plantilla>
    <?= $filaExtra('__n__', ['concepto' => '', 'monto' => '']) ?>
</template>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>