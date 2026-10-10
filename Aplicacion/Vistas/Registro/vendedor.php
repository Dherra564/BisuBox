<?php
/**
 * @var array $datos
 * @var array $errores
 * @var array $mensajes
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\TipoIdentificacion;

$titulo = 'Crear cuenta de vendedor';
$claseTarjeta = 'tarjetaRegistro';
$scriptsExtra = ['tienda.js'];
require __DIR__ . '/../Plantilla/encabezadoAcceso.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$tipoElegido = $datos['tipoIdentificacion'] ?? '';
$contactos = $datos['contactos'] ?? [];
?>
<div class="encabezadoRegistro">
    <h1 class="tituloPagina">Crear cuenta de vendedor</h1>
    <p class="subtitulo">
        Sus datos, los de su tienda y una contraseña. Los campos con <span class="obligatorio">*</span> son
        obligatorios.
    </p>
</div>

<?php require __DIR__ . '/../Plantilla/mensajes.php'; ?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/registro/vendedor" enctype="multipart/form-data" novalidate
    class="validarFormulario">
    <?= Csrf::campo() ?>

    <section class="formularioSeccion">
        <h2>Datos personales</h2>
        <div class="<?= $claseCampo('nombreCompleto') ?>">
            <label for="nombreCompleto">Nombre completo <span class="obligatorio">*</span></label>
            <input type="text" id="nombreCompleto" name="nombreCompleto" minlength="3" maxlength="100" required
                data-regla="soloLetras" autocomplete="name" data-mensaje-requerido="Ingrese su nombre completo"
                data-mensaje-regla="El nombre solo puede tener letras y espacios"
                data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                value="<?= $valor('nombreCompleto') ?>">
            <?= $mensajeError('nombreCompleto') ?>
        </div>
        <div class="filaCampos">
            <div class="<?= $claseCampo('tipoIdentificacion') ?>">
                <label for="tipoIdentificacion">Tipo de identificación <span class="obligatorio">*</span></label>
                <select id="tipoIdentificacion" name="tipoIdentificacion" required
                    data-mensaje-requerido="Seleccione el tipo de identificación">
                    <option value="">Seleccione el tipo</option>
                    <?php foreach (TipoIdentificacion::todos() as $codigo => $tipo): ?>
                        <option value="<?= $codigo ?>" <?= $tipoElegido === $codigo ? 'selected' : '' ?>
                            data-patron="<?= htmlspecialchars($tipo['patron']) ?>"
                            data-mensaje="<?= htmlspecialchars($tipo['mensaje']) ?>"
                            data-ayuda="<?= htmlspecialchars($tipo['ayuda']) ?>">
                            <?= htmlspecialchars($tipo['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $mensajeError('tipoIdentificacion') ?>
            </div>
            <div class="<?= $claseCampo('numeroIdentificacion') ?>">
                <label for="numeroIdentificacion">Identificación <span class="obligatorio">*</span></label>
                <input type="text" id="numeroIdentificacion" name="numeroIdentificacion" maxlength="25" required
                    data-regla="identificacion" data-tipo="tipoIdentificacion" data-campo-ayuda="ayudaIdentificacion"
                    data-mensaje-requerido="Ingrese la identificación" value="<?= $valor('numeroIdentificacion') ?>">
                <p class="textoAyuda" id="ayudaIdentificacion">
                    <?= htmlspecialchars(TipoIdentificacion::existe($tipoElegido)
                        ? TipoIdentificacion::todos()[$tipoElegido]['ayuda']
                        : 'Primero seleccione el tipo') ?>
                </p>
                <?= $mensajeError('numeroIdentificacion') ?>
            </div>
        </div>
        <div class="filaCampos">
            <div class="<?= $claseCampo('correoUsuario') ?>">
                <label for="correoUsuario">Correo <span class="obligatorio">*</span></label>
                <input type="email" id="correoUsuario" name="correoUsuario" maxlength="150" required data-regla="correo"
                    autocomplete="email" data-mensaje-requerido="Ingrese su correo"
                    value="<?= $valor('correoUsuario') ?>">
                <p class="textoAyuda">Con este correo inicia sesión</p>
                <?= $mensajeError('correoUsuario') ?>
            </div>
            <div class="<?= $claseCampo('numeroTelefonico') ?>">
                <label for="numeroTelefonico">Teléfono personal <span class="obligatorio">*</span></label>
                <input type="tel" id="numeroTelefonico" name="numeroTelefonico" maxlength="15" required
                    data-regla="telefono" inputmode="tel" autocomplete="tel"
                    data-mensaje-requerido="Ingrese su teléfono" value="<?= $valor('numeroTelefonico') ?>">
                <p class="textoAyuda">8 dígitos. No se muestra en la tienda</p>
                <?= $mensajeError('numeroTelefonico') ?>
            </div>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>Su tienda</h2>
        <div class="disenoFormulario">
            <div>
                <div class="<?= $claseCampo('tiendaNombre') ?>">
                    <label for="tiendaNombre">Nombre de la tienda <span class="obligatorio">*</span></label>
                    <input type="text" id="tiendaNombre" name="tiendaNombre" minlength="3" maxlength="100" required
                        data-regla="nombreTienda" data-mensaje-requerido="Ingrese el nombre de la tienda"
                        data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                        value="<?= $valor('tiendaNombre') ?>">
                    <?= $mensajeError('tiendaNombre') ?>
                </div>
                <div class="<?= $claseCampo('tiendaEnlace') ?>">
                    <label for="tiendaEnlace">Enlace de la tienda <span class="obligatorio">*</span></label>
                    <div class="campoConPrefijo">
                        <span class="prefijoCampo" aria-hidden="true">bisubox/tienda/</span>
                        <input type="text" id="tiendaEnlace" name="tiendaEnlace" minlength="3" maxlength="60" required
                            data-regla="enlaceTienda" data-sugerir-desde="tiendaNombre" aria-describedby="ayudaEnlace"
                            data-mensaje-requerido="Ingrese el enlace de la tienda"
                            data-mensaje-largo="El enlace debe tener entre 3 y 60 caracteres"
                            value="<?= $valor('tiendaEnlace') ?>">
                    </div>
                    <p class="textoAyuda" id="ayudaEnlace">
                        Minúsculas, números y guiones. Es la dirección que comparte con sus clientes.
                    </p>
                    <?= $mensajeError('tiendaEnlace') ?>
                </div>
                <div class="<?= $claseCampo('tiendaDescripcion') ?>">
                    <label for="tiendaDescripcion">Descripción</label>
                    <textarea id="tiendaDescripcion" name="tiendaDescripcion" rows="3" maxlength="300"
                        data-contador><?= $valor('tiendaDescripcion') ?></textarea>
                    <?= $mensajeError('tiendaDescripcion') ?>
                </div>
            </div>
            <div class="<?= $claseCampo('tiendaLogo') ?>">
                <div class="zonaFoto">
                    <img src="<?= $urlBase ?>/imagenes/logoPorDefecto.svg" alt="Vista previa del logo"
                        class="fotoPerfilGrande">
                    <label for="tiendaLogo" class="boton botonSecundario">Elegir logo</label>
                    <input type="file" id="tiendaLogo" name="tiendaLogo" accept="image/jpeg,image/png"
                        class="campoArchivo">
                    <span class="nombreArchivo">JPG o PNG, máximo 5 MB. Opcional</span>
                </div>
                <?= $mensajeError('tiendaLogo') ?>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/../Plantilla/contactos.php'; ?>

    <section class="formularioSeccion">
        <h2>Contraseña</h2>
        <div class="filaCampos">
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
                    data-igual-a="contrasena" autocomplete="new-password"
                    data-mensaje-requerido="Confirme la contraseña">
                <?= $mensajeError('confirmarContrasena') ?>
            </div>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/ingresar" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton">Crear mi cuenta</button>
    </div>
</form>

<p class="textoCentrado registroPie">
    ¿Ya tiene cuenta? <a href="<?= $urlBase ?>/ingresar">Iniciar sesión</a>
</p>
<?php require __DIR__ . '/../Plantilla/pieAcceso.php'; ?>