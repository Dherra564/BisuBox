<?php

use Aplicacion\Nucleo\Csrf;

$esNueva = $linea === null;
$titulo = $esNueva ? 'Nueva línea de producto' : 'Editar línea';
$paginaActual = 'productos';
$volverA = ['texto' => 'Líneas de producto', 'ruta' => '/lineas'];
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/lineas/<?= $esNueva ? 'nueva' : 'editar' ?>" novalidate
    class="validarFormulario" <?php if (!$esNueva): ?>
        data-confirmar="¿Guardar los cambios de la línea «
    <?= htmlspecialchars($linea->getNombre()) ?>»?"
        data-titulo="Guardar cambios" data-boton="Guardar"
    <?php endif; ?>>
    <?= Csrf::campo() ?>
    <?php if (!$esNueva): ?>
        <input type="hidden" name="id" value="<?= (int) $linea->getIdLinea() ?>">
    <?php endif; ?>

    <section class="formularioSeccion">
        <h2>Datos de la línea</h2>

        <div class="<?= $claseCampo('nombre') ?>">
            <label for="nombre">Nombre <span class="obligatorio">*</span></label>
            <input type="text" id="nombre" name="nombre" minlength="3" maxlength="50" required data-contador
                data-regla="nombreComercial" data-mensaje-requerido="Escriba el nombre de la línea, por ejemplo Perlas"
                data-mensaje-largo="El nombre debe tener entre 3 y 50 caracteres" value="<?= $valor('nombre') ?>">
            <p class="textoAyuda">Por ejemplo Perlas, Cadenas, Herrajes, Aretes o Pulseras.</p>
            <?= $mensajeError('nombre') ?>
        </div>

        <div class="<?= $claseCampo('descripcion') ?>">
            <label for="descripcion">Descripción <span class="textoOpcional">(opcional)</span></label>
            <textarea id="descripcion" name="descripcion" maxlength="300" rows="3" data-contador
                data-mensaje-largo="La descripción puede tener hasta 300 caracteres"><?= $valor('descripcion') ?></textarea>
            <?= $mensajeError('descripcion') ?>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/lineas" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton">
            <?= $esNueva ? 'Crear línea' : 'Guardar cambios' ?>
        </button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>