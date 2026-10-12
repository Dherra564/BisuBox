<?php
/**
 * @var \Aplicacion\Modelos\Proceso|null $proceso
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;

$esNuevo = $proceso === null;
$titulo = $esNuevo ? 'Nuevo proceso' : 'Editar proceso';
$paginaActual = 'procesos';
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$rutaCancelar = $esNuevo ? '/procesos' : '/procesos/detalle?id=' . (int) $proceso->getIdProceso();
?>

<p><a href="<?= $urlBase ?>/procesos">← Procesos</a></p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/procesos/<?= $esNuevo ? 'crear' : 'actualizar' ?>"
      novalidate class="validarFormulario"
      <?php if (!$esNuevo): ?>
          data-confirmar="¿Desea guardar los cambios de <?= htmlspecialchars((string) $proceso->getNombre()) ?>?"
          data-titulo="Guardar cambios" data-boton="Guardar"
      <?php endif; ?>>
    <?= Csrf::campo() ?>
    <?php if (!$esNuevo): ?>
        <input type="hidden" name="id" value="<?= (int) $proceso->getIdProceso() ?>">
    <?php endif; ?>

    <section class="formularioSeccion">
        <h2>Datos del proceso</h2>

        <div class="<?= $claseCampo('nombre') ?>">
            <label for="nombre">Nombre del proceso <span class="obligatorio">*</span></label>
            <input type="text" id="nombre" name="nombre" minlength="3" maxlength="60" required
                   data-regla="nombreComercial" data-contador
                   data-mensaje-requerido="Ingrese el nombre del proceso"
                   data-mensaje-largo="El nombre debe tener entre 3 y 60 caracteres"
                   value="<?= $valor('nombre') ?>">
            <p class="textoAyuda">Por ejemplo Ensartado de perlas</p>
            <?= $mensajeError('nombre') ?>
        </div>

        <div class="<?= $claseCampo('descripcion') ?>">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" maxlength="200" data-contador><?= $valor('descripcion') ?></textarea>
            <p class="textoAyuda">Opcional. Explique en qué consiste este paso.</p>
            <?= $mensajeError('descripcion') ?>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>Tiempo y costo</h2>

        <div class="filaCampos">
            <div class="<?= $claseCampo('tiempoEstimado') ?>">
                <label for="tiempoEstimado">Tiempo estimado (minutos) <span class="obligatorio">*</span></label>
                <input type="text" id="tiempoEstimado" name="tiempoEstimado" maxlength="4" required inputmode="numeric"
                       data-regla="minutos" data-mensaje-requerido="Ingrese el tiempo estimado en minutos"
                       value="<?= $valor('tiempoEstimado') ?>">
                <p class="textoAyuda">Lo que tarda en hacerlo, por ejemplo 45</p>
                <?= $mensajeError('tiempoEstimado') ?>
            </div>
            <div class="<?= $claseCampo('costoManoObra') ?>">
                <label for="costoManoObra">Costo de mano de obra (₡) <span class="obligatorio">*</span></label>
                <input type="text" id="costoManoObra" name="costoManoObra" maxlength="13" required inputmode="decimal"
                       data-regla="cantidad" data-mensaje-requerido="Ingrese el costo de mano de obra"
                       data-mensaje-regla="Escriba un monto válido, por ejemplo 1500 o 1500,50"
                       value="<?= $valor('costoManoObra') ?>">
                <p class="textoAyuda">Lo que cuesta este paso en cada producto. Escriba 0 si no lo cobra.</p>
                <?= $mensajeError('costoManoObra') ?>
            </div>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase . $rutaCancelar ?>" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton"><?= $esNuevo ? 'Registrar proceso' : 'Guardar cambios' ?></button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>