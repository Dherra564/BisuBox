<?php
/**
 * @var \Aplicacion\Modelos\TipoComponente|null $tipoComponente
 * @var array $datos
 * @var array $ocultos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\ClaseDato;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosTipoComponente;

$editando = $tipoComponente !== null;
$titulo = $editando ? 'Editar tipo de componente' : 'Nuevo tipo de componente';
$paginaActual = 'inventario';
$volverA = ['texto' => 'Tipos de componente', 'ruta' => '/tipos-componente'];
$scriptsExtra = ['tiposComponente.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo, string $extra = ''): string =>
    trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$icono = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';

$filaOpcion = fn(string $clave, ?int $id, string $valor, bool $deshabilitado = false): string =>
    '<div class="filaOpcion">'
    . '<input type="hidden" name="dato[' . $clave . '][opcionId][]" value="' . ($id ?? '') . '"' . ($deshabilitado ? ' disabled' : '') . '>'
    . '<input type="text" name="dato[' . $clave . '][opcionValor][]" maxlength="60" aria-label="Opción"'
    . ' placeholder="Por ejemplo: Vidrio" value="' . htmlspecialchars($valor) . '"' . ($deshabilitado ? ' disabled' : '') . '>'
    . '<button type="button" class="botonQuitar" aria-label="Quitar opción" data-opcion-quitar'
    . ($deshabilitado ? ' disabled' : '') . '>' . $icono . '</button>'
    . '</div>';

$filaDato = function (string $clave, array $dato, bool $tieneValores, bool $oculto = false) use ($claseCampo, $mensajeError, $filaOpcion, $icono): string {
    $campo = 'dato.' . $clave . '.';
    $deshabilitado = $oculto ? ' disabled' : '';
    $clase = ClaseDato::existe($dato['clase']) ? $dato['clase'] : ClaseDato::TEXTO;
    $idCasilla = 'dato-' . $clave . '-obligatorio';

    $opcionesClase = '';
    foreach (ClaseDato::todas() as $codigo => $datosClase) {
        $opcionesClase .= '<option value="' . $codigo . '"' . ($codigo === $clase ? ' selected' : '')
            . ' data-ejemplo="' . htmlspecialchars($datosClase['ejemplo']) . '"'
            . ' data-descripcion="' . htmlspecialchars($datosClase['descripcion']) . '">'
            . htmlspecialchars($datosClase['nombre']) . '</option>';
    }

    $opciones = '';
    foreach ($dato['opciones'] as $opcion) {
        $opciones .= $filaOpcion($clave, $opcion['id'], $opcion['valor'], $oculto);
    }

    $selectClase = '<select id="dato-' . $clave . '-clase" name="dato[' . $clave . '][clase]" data-dato-clase'
        . ($tieneValores || $oculto ? ' disabled' : '') . '>' . $opcionesClase . '</select>';
    if ($tieneValores) {
        $selectClase .= '<input type="hidden" name="dato[' . $clave . '][clase]" value="' . $clase . '"' . $deshabilitado . '>';
    }
    $ayudaClase = $tieneValores
        ? 'Ya hay componentes con este dato, por eso no se puede cambiar.'
        : 'Ejemplo: ' . ClaseDato::todas()[$clase]['ejemplo'];

    return '<div class="filaDato" data-dato data-clave="' . $clave . '"' . ($tieneValores ? ' data-tiene-valores' : '') . '>'
        . '<input type="hidden" name="dato[' . $clave . '][id]" value="' . ($dato['id'] ?? '') . '"' . $deshabilitado . '>'
        . '<div class="filaDatoEncabezado">'
        . '<span class="filaDatoTitulo"><span class="iconoClase iconoClase' . $clase . '" aria-hidden="true" data-dato-icono></span>'
        . '<span data-dato-titulo>' . htmlspecialchars($dato['nombre'] !== '' ? $dato['nombre'] : 'Dato nuevo') . '</span></span>'
        . '<span class="filaDatoBotones">'
        . '<button type="button" class="botonIcono" aria-label="Subir" title="Subir" data-dato-subir data-solo-activo'
        . ($oculto ? ' hidden' : '') . '>↑</button>'
        . '<button type="button" class="botonIcono" aria-label="Bajar" title="Bajar" data-dato-bajar data-solo-activo'
        . ($oculto ? ' hidden' : '') . '>↓</button>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar dato" title="Quitar dato" data-dato-quitar data-solo-activo'
        . ($oculto ? ' hidden' : '') . '>' . $icono . '</button>'
        . '<button type="button" class="boton botonSecundario botonPequeno" data-dato-restaurar'
        . ($oculto ? '' : ' hidden') . '>Volver a pedir</button>'
        . '</span>'
        . '</div>'
        . '<div class="filaCampos">'
        . '<div class="' . $claseCampo($campo . 'nombre') . '">'
        . '<label for="dato-' . $clave . '-nombre">Nombre del dato <span class="obligatorio">*</span></label>'
        . '<input type="text" id="dato-' . $clave . '-nombre" name="dato[' . $clave . '][nombre]" maxlength="60" required'
        . ' data-dato-nombre placeholder="Por ejemplo: Color" data-mensaje-requerido="Escriba el nombre del dato o quítelo"'
        . ' value="' . htmlspecialchars($dato['nombre']) . '"' . $deshabilitado . '>'
        . $mensajeError($campo . 'nombre')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'clase') . '">'
        . '<label for="dato-' . $clave . '-clase">¿Qué se anota?</label>'
        . $selectClase
        . '<p class="textoAyuda" data-dato-ejemplo>' . htmlspecialchars($ayudaClase) . '</p>'
        . $mensajeError($campo . 'clase')
        . '</div>'
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'unidad', 'campoUnidad') . '" data-solo-clase="' . ClaseDato::NUMERO . '"'
        . ($clase === ClaseDato::NUMERO ? '' : ' hidden') . '>'
        . '<label for="dato-' . $clave . '-unidad">Unidad <span class="textoAyuda">(opcional)</span></label>'
        . '<input type="text" id="dato-' . $clave . '-unidad" name="dato[' . $clave . '][unidad]" maxlength="20"'
        . ' data-dato-unidad placeholder="mm, cm, quilates..." value="' . htmlspecialchars($dato['unidad']) . '"' . $deshabilitado . '>'
        . $mensajeError($campo . 'unidad')
        . '</div>'
        . '<div class="' . $claseCampo($campo . 'opciones') . '" data-solo-clase="' . ClaseDato::LISTA . '"'
        . ($clase === ClaseDato::LISTA ? '' : ' hidden') . '>'
        . '<p class="etiquetaCampo">Opciones de la lista <span class="obligatorio">*</span></p>'
        . '<div class="listaOpciones" data-opciones>' . $opciones . '</div>'
        . '<button type="button" class="boton botonSecundario botonPequeno" data-opcion-agregar data-solo-activo'
        . ($oculto ? ' hidden' : '') . '>Agregar opción</button>'
        . $mensajeError($campo . 'opciones')
        . '</div>'
        . '<div class="campo campoCasilla">'
        . '<input type="checkbox" id="' . $idCasilla . '" name="dato[' . $clave . '][obligatorio]" value="1" data-dato-obligatorio'
        . ($dato['obligatorio'] ? ' checked' : '') . $deshabilitado . '>'
        . '<label for="' . $idCasilla . '">Pedirlo siempre'
        . '<span class="textoAyuda">Si lo marca, no se podrá guardar un componente de este tipo sin este dato.</span></label>'
        . '</div>'
        . '</div>';
};

$datoVacio = ['id' => null, 'nombre' => '', 'clase' => ClaseDato::TEXTO, 'unidad' => '', 'obligatorio' => false, 'opciones' => []];
$cantidadComponentes = $tipoComponente?->getCantidadComponentes() ?? 0;
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<?php if ($cantidadComponentes > 0): ?>
    <p class="cajaInformativa">
        Este tipo ya tiene <?= $cantidadComponentes === 1 ? '1 componente' : $cantidadComponentes . ' componentes' ?>.
        Si agrega un dato, esos componentes lo tendrán vacío hasta que los edite.
    </p>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/tipos-componente/<?= $editando ? 'editar' : 'nuevo' ?>" novalidate
    class="validarFormulario formularioTipo" data-maximo-datos="<?= DatosTipoComponente::MAXIMO_DATOS ?>"
    data-maximo-opciones="<?= ClaseDato::MAXIMO_OPCIONES ?>">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= $tipoComponente->getIdTipoComponente() ?>">
    <?php endif; ?>

    <div class="disenoTipo">
        <div>
            <section class="formularioSeccion">
                <h2>Nombre del tipo</h2>
                <div class="<?= $claseCampo('nombre') ?>">
                    <label for="nombre">Nombre <span class="obligatorio">*</span></label>
                    <input type="text" id="nombre" name="nombre" minlength="2" maxlength="60" required
                        data-regla="nombreTienda" data-tipo-nombre placeholder="Por ejemplo: Perlas"
                        data-mensaje-requerido="Escriba el nombre del tipo, por ejemplo Perlas"
                        data-mensaje-largo="El nombre debe tener entre 2 y 60 caracteres"
                        value="<?= htmlspecialchars($datos['nombre']) ?>">
                    <?= $mensajeError('nombre') ?>
                </div>
            </section>

            <section class="formularioSeccion">
                <h2>Datos que pide este tipo</h2>
                <p class="textoAyuda">
                    Agregue lo que quiere anotar de cada componente de este tipo. Por ejemplo, de las perlas: color,
                    diámetro y material.
                </p>
                <?= $mensajeError('datos') ?>

                <div class="listaDatosTipo" data-datos>
                    <?php foreach ($datos['datos'] as $clave => $dato): ?>
                        <?= $filaDato($clave, $dato, DatosTipoComponente::tieneValores($tipoComponente, $dato['id'])) ?>
                    <?php endforeach; ?>
                </div>
                <p class="estadoVacio estadoVacioPequeno" data-datos-vacio <?= $datos['datos'] !== [] ? 'hidden' : '' ?>>
                    Todavía no pide datos extra. Si no agrega ninguno, este tipo solo pedirá los datos comunes.
                </p>

                <div class="contactosPie">
                    <button type="button" class="boton botonSecundario botonPequeno" data-dato-agregar>Agregar
                        dato</button>
                    <span class="textoAyuda" data-datos-cuenta>
                        <?= count($datos['datos']) ?> de <?= DatosTipoComponente::MAXIMO_DATOS ?> datos
                    </span>
                </div>
            </section>

            <section class="formularioSeccion" data-seccion-ocultos <?= $ocultos === [] ? 'hidden' : '' ?>>
                <h2>Datos que ya no se piden</h2>
                <p class="textoAyuda">
                    Algunos componentes ya tienen estos datos, por eso se guardan. Puede volver a pedirlos cuando
                    quiera.
                </p>
                <div class="listaDatosTipo" data-datos-ocultos>
                    <?php foreach ($ocultos as $clave => $dato): ?>
                        <?= $filaDato($clave, $dato, DatosTipoComponente::tieneValores($tipoComponente, $dato['id']), true) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <aside class="vistaPrevia" aria-label="Vista previa">
            <h2>Así se verá al registrar un componente</h2>
            <div class="vistaPreviaFormulario" inert>
                <p class="vistaPreviaGrupo">Datos comunes</p>
                <div class="campo">
                    <label>Nombre del componente</label>
                    <input type="text" placeholder="Por ejemplo: Perla rosada 8 mm">
                </div>
                <div class="filaCampos">
                    <div class="campo">
                        <label>Unidad de medida</label>
                        <select>
                            <option>Unidad</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label>Existencia mínima</label>
                        <input type="text">
                    </div>
                </div>
                <p class="vistaPreviaGrupo" data-vista-previa-titulo>Datos del tipo</p>
                <div data-vista-previa></div>
            </div>
        </aside>
    </div>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/tipos-componente" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton"><?= $editando ? 'Guardar cambios' : 'Crear tipo' ?></button>
    </div>
</form>

<template data-dato-plantilla>
    <?= $filaDato('__clave__', $datoVacio, false) ?>
</template>
<template data-opcion-plantilla>
    <?= $filaOpcion('__clave__', null, '') ?>
</template>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>