<?php
/**
 *
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

$valor = fn (string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn (string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn (string $campo): string => isset($errores[$campo])
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
          data-confirmar="¿Desea guardar los cambios del proceso <?= htmlspecialchars($proceso->getNombre()) ?>?"
          data-titulo="Guardar cambios" data-boton="Guardar"
      <?php endif; ?>>
    <?= Csrf::campo() ?>
    <?php if (!$esNuevo): ?>
        <input type="hidden" name="id" value="<?= (int) $proceso->getIdProceso() ?>">
    <?php endif; ?>

    <div class="disenoFormulario">
        <div>
            <section class="formularioSeccion">
                <h2>Datos del proceso</h2>

                <div class="<?= $claseCampo('nombre') ?>">
                    <label for="nombre">Nombre <span class="obligatorio">*</span></label>
                    <input type="text" id="nombre" name="nombre" minlength="3" maxlength="60"
                           required data-regla="soloLetras" data-contador
                           data-mensaje-requerido="Ingrese el nombre del proceso"
                           data-mensaje-regla="El nombre solo puede tener letras y espacios"
                           data-mensaje-largo="El nombre debe tener entre 3 y 60 caracteres"
                           value="<?= $valor('nombre') ?>">
                    <p class="textoAyuda">Por ejemplo: Ensartar, Armar, Soldar, Pegar o Empacar</p>
                    <?= $mensajeError('nombre') ?>
                </div>

                <div class="<?= $claseCampo('descripcion') ?>">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="200" data-contador><?= $valor('descripcion') ?></textarea>
                    <p class="textoAyuda">Opcional, hasta 200 caracteres</p>
                    <?= $mensajeError('descripcion') ?>
                </div>

                <div class="filaCampos">
                    <div class="<?= $claseCampo('tiempoEstimado') ?>">
                        <label for="tiempoEstimado">Tiempo estimado (minutos) <span class="obligatorio">*</span></label>
                        <input type="text" id="tiempoEstimado" name="tiempoEstimado" maxlength="5"
                               required data-regla="entero" inputmode="numeric"
                               data-mensaje-requerido="Ingrese el tiempo estimado en minutos"
                               data-mensaje-regla="El tiempo debe ser un número entero de minutos"
                               value="<?= $valor('tiempoEstimado') ?>">
                        <p class="textoAyuda">Número entero mayor que 0, por ejemplo 45</p>
                        <?= $mensajeError('tiempoEstimado') ?>
                    </div>

                    <div class="<?= $claseCampo('costoManoObra') ?>">
                        <label for="costoManoObra">Costo de mano de obra (₡) <span class="obligatorio">*</span></label>
                        <input type="text" id="costoManoObra" name="costoManoObra" maxlength="14"
                               required data-regla="monto" inputmode="decimal"
                               data-mensaje-requerido="Ingrese el costo de mano de obra"
                               data-mensaje-regla="Ingrese un monto válido, con máximo 2 decimales"
                               value="<?= $valor('costoManoObra') ?>">
                        <p class="textoAyuda">En colones, con máximo 2 decimales, por ejemplo 1250,50</p>
                        <?= $mensajeError('costoManoObra') ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase . $rutaCancelar ?>" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton"><?= $esNuevo ? 'Registrar proceso' : 'Guardar cambios' ?></button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>