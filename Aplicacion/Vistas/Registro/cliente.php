<?php
/**
 * @var array $datos
 * @var array $errores
 * @var array $mensajes
 */

use Aplicacion\Nucleo\Csrf;

$titulo = 'Crear cuenta de cliente';
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>
<h1 class="tituloPagina">Crear cuenta de cliente</h1>
<p class="subtitulo">
    Con una sola cuenta puede comprar en cualquier tienda. La dirección de entrega se pide cuando hace un pedido.
</p>

<?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

<form method="post" action="<?= $urlBase ?>/registro/cliente" novalidate class="validarFormulario">
    <?= Csrf::campo() ?>

    <div class="<?= $claseCampo('nombreCompleto') ?>">
        <label for="nombreCompleto">Nombre completo <span class="obligatorio">*</span></label>
        <input type="text" id="nombreCompleto" name="nombreCompleto" minlength="3" maxlength="100" required
            data-regla="soloLetras" autocomplete="name" data-mensaje-requerido="Ingrese su nombre completo"
            data-mensaje-regla="El nombre solo puede tener letras y espacios"
            data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres" value="<?= $valor('nombreCompleto') ?>">
        <?= $mensajeError('nombreCompleto') ?>
    </div>

    <div class="<?= $claseCampo('correoUsuario') ?>">
        <label for="correoUsuario">Correo <span class="obligatorio">*</span></label>
        <input type="email" id="correoUsuario" name="correoUsuario" maxlength="150" required data-regla="correo"
            autocomplete="email" data-mensaje-requerido="Ingrese su correo" value="<?= $valor('correoUsuario') ?>">
        <p class="textoAyuda">Con este correo inicia sesión</p>
        <?= $mensajeError('correoUsuario') ?>
    </div>

    <div class="<?= $claseCampo('numeroTelefonico') ?>">
        <label for="numeroTelefonico">Teléfono <span class="obligatorio">*</span></label>
        <input type="tel" id="numeroTelefonico" name="numeroTelefonico" maxlength="15" required data-regla="telefono"
            inputmode="tel" autocomplete="tel" data-mensaje-requerido="Ingrese su teléfono"
            value="<?= $valor('numeroTelefonico') ?>">
        <p class="textoAyuda">8 dígitos. Para que el vendedor pueda contactarle por su pedido</p>
        <?= $mensajeError('numeroTelefonico') ?>
    </div>

    <div class="<?= $claseCampo('contrasena') ?>">
        <label for="contrasena">Contraseña <span class="obligatorio">*</span></label>
        <input type="password" id="contrasena" name="contrasena" maxlength="20" required data-regla="contrasena"
            autocomplete="new-password" data-mensaje-requerido="Ingrese una contraseña">
        <p class="textoAyuda">De 8 a 20 caracteres, con mayúscula, minúscula y número</p>
        <?= $mensajeError('contrasena') ?>
    </div>

    <div class="<?= $claseCampo('confirmarContrasena') ?>">
        <label for="confirmarContrasena">Confirmar contraseña <span class="obligatorio">*</span></label>
        <input type="password" id="confirmarContrasena" name="confirmarContrasena" maxlength="20" required
            data-igual-a="contrasena" autocomplete="new-password" data-mensaje-requerido="Confirme la contraseña">
        <?= $mensajeError('confirmarContrasena') ?>
    </div>

    <button type="submit" class="boton botonCompleto">Crear mi cuenta</button>
</form>

<p class="textoCentrado registroPie">
    ¿Ya tiene cuenta? <a href="<?= $urlBase ?>/ingresar">Iniciar sesión</a>
</p>
<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>