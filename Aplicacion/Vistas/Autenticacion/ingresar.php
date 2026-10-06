<?php
/**
 *
 * @var array $mensajes
 */

use Aplicacion\Nucleo\Csrf;
use Configuracion\Configuracion;

$urlBase = rtrim((string) Configuracion::obtener('appUrl', ''), '/');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | BisuBox</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= $urlBase ?>/css/estilos.css">
</head>

<body>
    <div class="paginaAcceso">
        <div class="tarjetaAcceso">
            <p class="logo">BisuBox</p>
            <h1 class="tituloPagina">Iniciar sesión</h1>
            <p class="subtitulo">Ingrese con su correo y contraseña.</p>

            <?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

            <form method="post" action="<?= $urlBase ?>/ingresar" novalidate class="validarFormulario">
                <?= Csrf::campo() ?>

                <div class="campo">
                    <label for="correo">Correo electrónico <span class="obligatorio">*</span></label>
                    <input type="email" id="correo" name="correo" maxlength="150" required autofocus
                        autocomplete="username" data-mensaje-requerido="Ingrese su correo">
                </div>

                <div class="campo">
                    <label for="contrasena">Contraseña <span class="obligatorio">*</span></label>
                    <input type="password" id="contrasena" name="contrasena" maxlength="20" required
                        autocomplete="current-password" data-mensaje-requerido="Ingrese su contraseña">
                </div>

                <button type="submit" class="boton botonCompleto">Ingresar</button>
            </form>
        </div>
    </div>

    <script src="<?= $urlBase ?>/js/alertas.js"></script>
    <script src="<?= $urlBase ?>/js/validaciones.js"></script>
</body>

</html>