<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Recuperar contraseña';
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>
<h1 class="tituloPagina">Recuperar contraseña</h1>
<p class="subtitulo">Escriba el correo de su cuenta y le enviamos un enlace para crear una contraseña nueva.</p>

<?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

<form method="post" action="<?= $urlBase ?>/olvide-contrasena" novalidate class="validarFormulario">
    <?= Csrf::campo() ?>

    <div class="<?= $claseCampo('correo') ?>">
        <label for="correo">Correo electrónico <span class="obligatorio">*</span></label>
        <input type="email" id="correo" name="correo" maxlength="150" required autofocus autocomplete="username"
            placeholder="nombre@gmail.com" data-regla="correo" data-mensaje-requerido="Ingrese su correo"
            value="<?= $valor('correo') ?>">
        <?= $mensajeError('correo') ?>
    </div>

    <button type="submit" class="boton botonCompleto">Enviar enlace</button>
</form>

<div class="separadorAcceso"><span>¿Ya la recordó?</span></div>
<a href="<?= $urlBase ?>/ingresar" class="boton botonSecundario botonCompleto">Volver a ingresar</a>
<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>