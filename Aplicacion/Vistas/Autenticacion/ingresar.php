<?php
/**
 * @var array $mensajes
 */

use Aplicacion\Nucleo\Csrf;

$titulo = 'Iniciar sesión';
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';
?>
<h1 class="tituloPagina">Iniciar sesión</h1>
<p class="subtitulo">Para vendedores y clientes.</p>

<?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

<form method="post" action="<?= $urlBase ?>/ingresar" novalidate class="validarFormulario">
    <?= Csrf::campo() ?>

    <div class="campo">
        <label for="correo">Correo electrónico <span class="obligatorio">*</span></label>
        <input type="email" id="correo" name="correo" maxlength="150" required autofocus autocomplete="username"
            data-mensaje-requerido="Ingrese su correo">
    </div>

    <div class="campo">
        <label for="contrasena">Contraseña <span class="obligatorio">*</span></label>
        <input type="password" id="contrasena" name="contrasena" maxlength="20" required autocomplete="current-password"
            data-mensaje-requerido="Ingrese su contraseña">
    </div>

    <button type="submit" class="boton botonCompleto">Ingresar</button>
</form>

<div class="registroOpciones">
    <p>¿Todavía no tiene cuenta?</p>
    <div class="grupoBotones">
        <a href="<?= $urlBase ?>/registro/vendedor" class="boton botonSecundario">Quiero vender</a>
        <a href="<?= $urlBase ?>/registro/cliente" class="boton botonSecundario">Quiero comprar</a>
    </div>
</div>
<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>