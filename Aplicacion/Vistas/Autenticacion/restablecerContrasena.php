<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Contraseña nueva';
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';

$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>
<h1 class="tituloPagina">Contraseña nueva</h1>
<p class="subtitulo">Escriba la contraseña nueva para su cuenta. Al cambiarla se cierran las sesiones abiertas.</p>

<?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

<form method="post" action="<?= $urlBase ?>/restablecer-contrasena" novalidate class="validarFormulario">
    <?= Csrf::campo() ?>
    <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">

    <div class="<?= $claseCampo('contrasenaNueva') ?>">
        <label for="contrasenaNueva">Contraseña nueva <span class="obligatorio">*</span></label>
        <input type="password" id="contrasenaNueva" name="contrasenaNueva" maxlength="20" required autofocus
            data-regla="contrasena" data-ver-contrasena autocomplete="new-password"
            data-mensaje-requerido="Ingrese la contraseña nueva">
        <p class="textoAyuda">De 8 a 20 caracteres, con mayúscula, minúscula y número</p>
        <?= $mensajeError('contrasenaNueva') ?>
    </div>

    <div class="<?= $claseCampo('confirmarContrasena') ?>">
        <label for="confirmarContrasena">Confirmar contraseña nueva <span class="obligatorio">*</span></label>
        <input type="password" id="confirmarContrasena" name="confirmarContrasena" maxlength="20" required
            data-ver-contrasena data-igual-a="contrasenaNueva" autocomplete="new-password"
            data-mensaje-requerido="Confirme la contraseña nueva">
        <?= $mensajeError('confirmarContrasena') ?>
    </div>

    <button type="submit" class="boton botonCompleto">Cambiar contraseña</button>
</form>

<div class="separadorAcceso"><span>¿No fue usted?</span></div>
<a href="<?= $urlBase ?>/ingresar" class="boton botonSecundario botonCompleto">Volver a ingresar</a>
<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>