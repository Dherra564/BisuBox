<?php
/**
 * @var array $datos
 * @var array $errores
 * @var \Aplicacion\Modelos\Componente[] $componentes
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\DatosProducto;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Nuevo producto';
$paginaActual = 'productos';
$volverA = ['texto' => 'Productos', 'ruta' => '/productos'];
$scriptsExtra = ['productos.js'];
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

// Una línea por componente: lo que lleva un solo producto
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
                . ' data-entera="' . ($componente->getUnidad() === UnidadMedida::UNIDAD ? '1' : '0') . '">'
                . htmlspecialchars($componente->getNombre()) . ($agotado ? ' (agotado)' : '') . '</option>';
        }
        $opciones .= '</optgroup>';
    }

    $enCentimetros = ($linea['medida'] ?? '') === DatosCompraProveedor::CENTIMETROS;

    return '<div class="lineaReceta" data-linea data-clave="' . $clave . '">'
        . '<div class="' . $claseCampo($campo . 'componente', 'campoComponente') . '">'
        . '<label for="componente-' . $clave . '-componente" class="soloLectores">Componente</label>'
        . '<select id="componente-' . $clave . '-componente" name="' . $nombre . '[componente]" data-linea-componente>' . $opciones . '</select>'
        . '<p class="textoAyuda avisoExistencia" data-linea-aviso hidden></p>'
        . $mensajeError($campo . 'componente')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'cantidad', 'campoCantidadReceta') . '">'
        . '<label for="componente-' . $clave . '-cantidad" class="soloLectores">Cuánto lleva</label>'
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
        . '<span class="subtotalReceta" data-linea-subtotal>₡0</span>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar este componente" title="Quitar este componente" data-linea-quitar>'
        . $iconoQuitar . '</button>'
        . '</div>';
};

$filaExtra = function (string $clave, array $extra) use ($claseCampo, $mensajeError, $iconoQuitar): string {
    $campo = 'extra.' . $clave . '.';
    $nombre = 'extra[' . $clave . ']';

    return '<div class="lineaReceta lineaRecetaExtra" data-extra data-clave="' . $clave . '">'
        . '<div class="' . $claseCampo($campo . 'concepto') . '">'
        . '<label for="extra-' . $clave . '-concepto" class="soloLectores">¿Para qué fue?</label>'
        . '<input type="text" id="extra-' . $clave . '-concepto" name="' . $nombre . '[concepto]" maxlength="60" list="sugerenciasExtra"'
        . ' data-extra-concepto placeholder="Por ejemplo Empaque" value="' . htmlspecialchars($extra['concepto']) . '">'
        . $mensajeError($campo . 'concepto')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'monto', 'campoCantidadReceta') . '">'
        . '<label for="extra-' . $clave . '-monto" class="soloLectores">Monto</label>'
        . '<div class="campoConUnidad campoConMoneda">'
        . '<span class="unidadCampo">₡</span>'
        . '<input type="text" id="extra-' . $clave . '-monto" name="' . $nombre . '[monto]" maxlength="13" inputmode="decimal"'
        . ' data-extra-monto placeholder="0" value="' . htmlspecialchars($extra['monto']) . '">'
        . '</div>'
        . $mensajeError($campo . 'monto')
        . '</div>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar este gasto" title="Quitar este gasto" data-extra-quitar>'
        . $iconoQuitar . '</button>'
        . '</div>';
};

$lineaVacia = ['componente' => '', 'cantidad' => '', 'medida' => 'm'];
$lineas = $datos['componentes'] !== [] ? $datos['componentes'] : [0 => $lineaVacia];
$extras = $datos['extras'];
$hayErrorExtras = array_filter(array_keys($errores), fn(string $campo): bool => str_starts_with($campo, 'extra')) !== [];
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo. No se guardó nada todavía.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/productos/nuevo" enctype="multipart/form-data" novalidate
    class="validarFormulario formularioProducto" data-receta
    data-maximo-componentes="<?= DatosProducto::MAXIMO_COMPONENTES ?>"
    data-maximo-extras="<?= DatosProducto::MAXIMO_EXTRAS ?>"
    data-confirmar="Al guardar, se descuenta del inventario lo que llevan todos los que hizo."
    data-titulo="Guardar producto" data-boton="Guardar producto">
    <?= Csrf::campo() ?>

    <div class="pasosProducto">
        <section class="formularioSeccion pasoProducto">
            <h2><span class="numeroPaso" aria-hidden="true">1</span> ¿Qué hizo?</h2>

            <div class="encabezadoProducto">
                <div class="<?= $claseCampo('foto', 'campoFotoProducto') ?>">
                    <div class="zonaFoto zonaFotoProducto">
                        <img src="<?= $urlBase ?>/imagenes/productoPorDefecto.svg" alt=""
                            class="fotoPerfilGrande fotoProducto">
                        <label for="foto" class="enlaceFoto">Agregar foto</label>
                        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" class="campoArchivo">
                        <span class="nombreArchivo">JPG o PNG</span>
                    </div>
                    <?= $mensajeError('foto') ?>
                </div>

                <div class="datosProducto">
                    <div class="<?= $claseCampo('nombre') ?>">
                        <label for="nombre">Nombre <span class="obligatorio">*</span></label>
                        <input type="text" id="nombre" name="nombre" minlength="3" maxlength="60" required
                            data-regla="nombreComercial" placeholder="Por ejemplo Collar de perlas"
                            data-mensaje-requerido="Escriba el nombre del producto, por ejemplo Collar de perlas"
                            data-mensaje-largo="El nombre debe tener entre 3 y 60 caracteres"
                            value="<?= $valor('nombre') ?>">
                        <?= $mensajeError('nombre') ?>
                    </div>

                    <div class="<?= $claseCampo('cantidad') ?>">
                        <label for="cantidad">¿Cuántos hizo? <span class="obligatorio">*</span></label>
                        <div class="selectorCantidad">
                            <button type="button" class="botonCantidad" data-cantidad-menos
                                aria-label="Uno menos">−</button>
                            <input type="text" id="cantidad" name="cantidad" maxlength="4" inputmode="numeric"
                                data-cantidad value="<?= $valor('cantidad') ?>">
                            <button type="button" class="botonCantidad" data-cantidad-mas
                                aria-label="Uno más">+</button>
                        </div>
                        <?= $mensajeError('cantidad') ?>
                    </div>
                </div>
            </div>

            <details class="detalleOpcional" <?= $datos['descripcion'] !== '' || isset($errores['descripcion']) ? 'open' : '' ?>>
                <summary>Agregar una descripción</summary>
                <div class="<?= $claseCampo('descripcion') ?>">
                    <label for="descripcion" class="soloLectores">Descripción</label>
                    <textarea id="descripcion" name="descripcion" maxlength="300" rows="2" data-contador
                        placeholder="Así lo verán sus clientes en la tienda"><?= $valor('descripcion') ?></textarea>
                    <?= $mensajeError('descripcion') ?>
                </div>
            </details>
        </section>

        <section class="formularioSeccion pasoProducto">
            <h2><span class="numeroPaso" aria-hidden="true">2</span> ¿Qué lleva cada uno?</h2>
            <p class="textoAyuda">Anote lo que lleva <strong>un solo</strong> producto. El sistema lo multiplica por los
                que hizo.</p>
            <?= $mensajeError('lineas') ?>

            <div class="listaReceta" data-lineas>
                <?php foreach ($lineas as $clave => $linea): ?>
                    <?= $filaComponente((string) $clave, $linea) ?>
                <?php endforeach; ?>
            </div>
            <button type="button" class="enlaceAgregar" data-linea-agregar>+ Otro componente</button>
        </section>

        <section class="formularioSeccion pasoProducto">
            <h2><span class="numeroPaso" aria-hidden="true">3</span> Su trabajo</h2>

            <div class="filaCampos">
                <div class="<?= $claseCampo('tiempo') ?>">
                    <label for="horas">¿Cuánto tarda en hacer uno? <span class="obligatorio">*</span></label>
                    <div class="filaTiempo">
                        <div class="campoConUnidad">
                            <input type="text" id="horas" name="horas" maxlength="3" inputmode="numeric" placeholder="0"
                                data-horas value="<?= $valor('horas') ?>">
                            <span class="unidadCampo">h</span>
                        </div>
                        <div class="campoConUnidad">
                            <input type="text" id="minutos" name="minutos" maxlength="2" inputmode="numeric"
                                placeholder="0" data-minutos aria-label="Minutos" value="<?= $valor('minutos') ?>">
                            <span class="unidadCampo">min</span>
                        </div>
                    </div>
                    <?= $mensajeError('tiempo') ?>
                </div>
                <div class="<?= $claseCampo('tarifaHora') ?>">
                    <label for="tarifaHora">Cobra por hora <span class="obligatorio">*</span></label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="tarifaHora" name="tarifaHora" maxlength="13" inputmode="decimal" required
                            data-regla="cantidad" data-tarifa placeholder="0"
                            data-mensaje-requerido="Escriba cuánto cobra por hora, o 0 si no cobra mano de obra"
                            data-mensaje-regla="Escriba un monto, por ejemplo 2000 o 2000,50"
                            value="<?= $valor('tarifaHora') ?>">
                    </div>
                    <p class="textoAyuda">Escriba 0 si no cobra su trabajo.</p>
                    <?= $mensajeError('tarifaHora') ?>
                </div>
            </div>

            <details class="detalleOpcional" <?= $extras !== [] || $hayErrorExtras ? 'open' : '' ?>>
                <summary>Otros gastos de cada uno (luz, transporte…)</summary>
                <?= $mensajeError('extras') ?>
                <datalist id="sugerenciasExtra">
                    <option value="Empaque"></option>
                    <option value="Transporte"></option>
                    <option value="Luz"></option>
                    <option value="Etiqueta"></option>
                </datalist>
                <div class="listaReceta" data-extras>
                    <?php foreach ($extras as $clave => $extra): ?>
                        <?= $filaExtra((string) $clave, $extra) ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="enlaceAgregar" data-extra-agregar>+ Agregar gasto</button>
            </details>
        </section>
    </div>

    <div class="cierreProducto">
        <?php require __DIR__ . '/precio.php'; ?>

        <div class="resumenDescuento" data-resumen-descuento hidden>
            <p class="resumenDescuentoTitulo" data-resumen-titulo></p>
            <ul data-resumen-lista></ul>
        </div>

        <div class="grupoBotones accionesFormulario">
            <a href="<?= $urlBase ?>/productos" class="boton botonSecundario">Cancelar</a>
            <button type="submit" class="boton">Guardar producto</button>
        </div>
    </div>
</form>

<template data-linea-plantilla>
    <?= $filaComponente('__n__', $lineaVacia) ?>
</template>

<template data-extra-plantilla>
    <?= $filaExtra('__n__', ['concepto' => '', 'monto' => '']) ?>
</template>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>