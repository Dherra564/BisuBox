<?php
/**
 *
 * @var array $errores 
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;

$titulo = 'Cambiar contraseña';
$paginaActual = 'perfil';
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>

<p><a href="<?= $urlBase ?>/perfil">← Mi perfil</a></p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/perfil/contrasena" novalidate class="validarFormulario formularioAngosto">
    <?= Csrf::campo() ?>

    <section class="formularioSeccion">
        <h2>Nueva contraseña</h2>

        <div class="<?= $claseCampo('contrasenaActual') ?>">
            <label for="contrasenaActual">Contraseña actual <span class="obligatorio">*</span></label>
            <input type="password" id="contrasenaActual" name="contrasenaActual" maxlength="20" required
                autocomplete="current-password" data-mensaje-requerido="Ingrese su contraseña actual">
            <?= $mensajeError('contrasenaActual') ?>
        </div>

        <div class="<?= $claseCampo('contrasenaNueva') ?>">
            <label for="contrasenaNueva">Contraseña nueva <span class="obligatorio">*</span></label>
            <input type="password" id="contrasenaNueva" name="contrasenaNueva" maxlength="20" required
                data-regla="contrasena" data-distinto-de="contrasenaActual" autocomplete="new-password"
                data-mensaje-requerido="Ingrese la contraseña nueva">
            <p class="textoAyuda">Entre 8 y 20 caracteres, con mayúscula, minúscula y número, sin espacios</p>
            <?= $mensajeError('contrasenaNueva') ?>
        </div>

        <div class="<?= $claseCampo('confirmarContrasena') ?>">
            <label for="confirmarContrasena">Confirmar contraseña nueva <span class="obligatorio">*</span></label>
            <input type="password" id="confirmarContrasena" name="confirmarContrasena" maxlength="20" required
                data-igual-a="contrasenaNueva" autocomplete="new-password"
                data-mensaje-requerido="Confirme la contraseña nueva">
            <?= $mensajeError('confirmarContrasena') ?>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/perfil" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton">Cambiar contraseña</button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>