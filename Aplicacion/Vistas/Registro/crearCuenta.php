<?php

use Aplicacion\Nucleo\Csrf;

$titulo = 'Crear cuenta';
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>
<h1 class="tituloPagina">Crear cuenta</h1>
<p class="subtitulo">Le toma un minuto. Con su cuenta puede seguir sus pedidos y, si quiere, abrir su propia tienda.</p>

<?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

<form method="post" action="<?php echo $urlBase ?>/registro" novalidate class="validarFormulario">
    <?php echo Csrf::campo() ?>

    <div class="<?php echo $claseCampo('nombreCompleto') ?>">
        <label for="nombreCompleto">Nombre completo <span class="obligatorio">*</span></label>
        <input type="text" id="nombreCompleto" name="nombreCompleto" minlength="3" maxlength="100" required
            data-regla="soloLetras" autocomplete="name" data-mensaje-requerido="Ingrese su nombre completo"
            data-mensaje-regla="El nombre solo puede tener letras y espacios"
            data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
            value="<?php echo $valor('nombreCompleto') ?>">
        <?php echo $mensajeError('nombreCompleto') ?>
    </div>

    <div class="<?php echo $claseCampo('correoUsuario') ?>">
        <label for="correoUsuario">Correo <span class="obligatorio">*</span></label>
        <input type="email" id="correoUsuario" name="correoUsuario" maxlength="150" required data-regla="correo"
            autocomplete="email" data-mensaje-requerido="Ingrese su correo"
            value="<?php echo $valor('correoUsuario') ?>">
        <p class="textoAyuda">Con este correo inicia sesión</p>
        <?php echo $mensajeError('correoUsuario') ?>
    </div>

    <div class="<?php echo $claseCampo('numeroTelefonico') ?>">
        <label for="numeroTelefonico">Teléfono <span class="obligatorio">*</span></label>
        <input type="tel" id="numeroTelefonico" name="numeroTelefonico" maxlength="15" required data-regla="telefono"
            data-mascara-telefono inputmode="tel" autocomplete="tel" data-mensaje-requerido="Ingrese su teléfono"
            value="<?php echo $valor('numeroTelefonico') ?>">
        <p class="textoAyuda">8 dígitos, por ejemplo 8845-1290. Para que el vendedor pueda contactarle por su pedido</p>
        <?php echo $mensajeError('numeroTelefonico') ?>
    </div>

    <div class="<?= $claseCampo('contrasena') ?>">
        <label for="contrasena">Contraseña <span class="obligatorio">*</span></label>
        <input type="password" id="contrasena" name="contrasena" maxlength="20" required data-regla="contrasena"
            data-ver-contrasena value="<?= $valor('contrasena') ?>" autocomplete="new-password"
            data-mensaje-requerido="Ingrese una contraseña">
        <p class="textoAyuda">De 8 a 20 caracteres, con mayúscula, minúscula y número</p>
        <?= $mensajeError('contrasena') ?>
    </div>

    <div class="<?= $claseCampo('confirmarContrasena') ?>">
        <label for="confirmarContrasena">Confirmar contraseña <span class="obligatorio">*</span></label>
        <input type="password" id="confirmarContrasena" name="confirmarContrasena" maxlength="20" required
            data-ver-contrasena value="<?= $valor('confirmarContrasena') ?>" data-igual-a="contrasena"
            autocomplete="new-password" data-mensaje-requerido="Confirme la contraseña">
        <?= $mensajeError('confirmarContrasena') ?>
    </div>

    <button type="submit" class="boton botonCompleto">Crear mi cuenta</button>
</form>

<p class="textoCentrado registroPie">
    ¿Ya tiene cuenta? <a href="<?php echo $urlBase ?>/ingresar">Ingresar</a>
</p>
<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>