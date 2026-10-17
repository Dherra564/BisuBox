<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Ingresar';
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';
?>
<h1 class="tituloPagina">Ingresar</h1>
<p class="subtitulo">Use el correo y la contraseña de su cuenta.</p>

<?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

<form method="post" action="<?= $urlBase ?>/ingresar" novalidate class="validarFormulario">
    <?= Csrf::campo() ?>

    <div class="campo">
        <label for="correo">Correo electrónico <span class="obligatorio">*</span></label>
        <input type="email" id="correo" name="correo" maxlength="150" required autofocus autocomplete="username"
            placeholder="nombre@gmail.com" data-mensaje-requerido="Ingrese su correo"
            value="<?= htmlspecialchars($correoIntento ?? '') ?>">
    </div>

    <div class="campo">
        <label for="contrasena">Contraseña <span class="obligatorio">*</span></label>
        <input type="password" id="contrasena" name="contrasena" maxlength="20" required autocomplete="current-password"
            data-mensaje-requerido="Ingrese su contraseña">
    </div>

    <button type="submit" class="boton botonCompleto">Ingresar</button>
</form>

<div class="separadorAcceso"><span>¿Es su primera vez?</span></div>
<a href="<?= $urlBase ?>/registro" class="boton botonSecundario botonCompleto">Crear cuenta</a>
<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>