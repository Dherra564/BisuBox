<?php
/**
 * Mi perfil: el SuperAdmin cambia todos sus datos; el Vendedor solo su nombre y su foto.
 *
 * @var \Aplicacion\Modelos\Usuario $usuario Viene de PerfilControlador
 * @var string $rol 'SuperAdmin' o 'Vendedor'
 * @var bool $puedeEditarAcceso true para el SuperAdmin: puede cambiar su identificacion y su correo
 * @var array $datos Valores escritos (se conservan si hay errores)
 * @var array $errores ['campo' => 'mensaje']
 * @var string $urlBase Viene de encabezado.php
 */

use Aplicacion\Nucleo\Csrf;

$titulo = 'Mi perfil';
$paginaActual = 'perfil';
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn (string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn (string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn (string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/perfil/actualizar" enctype="multipart/form-data" novalidate class="validarFormulario">
    <?= Csrf::campo() ?>

    <div class="disenoFormulario">
        <div>
            <section class="formularioSeccion">
                <h2>Datos personales</h2>
                <div class="<?= $claseCampo('nombreCompleto') ?>">
                    <label for="nombreCompleto">Nombre completo <span class="obligatorio">*</span></label>
                    <input type="text" id="nombreCompleto" name="nombreCompleto" maxlength="100"
                           required data-regla="soloLetras" value="<?= $valor('nombreCompleto') ?>">
                    <?= $mensajeError('nombreCompleto') ?>
                </div>
                <div class="filaCampos">
                    <?php if ($puedeEditarAcceso): ?>
                        <div class="<?= $claseCampo('numeroIdentificacion') ?>">
                            <label for="numeroIdentificacion">Identificación <span class="obligatorio">*</span></label>
                            <input type="text" id="numeroIdentificacion" name="numeroIdentificacion" maxlength="20"
                                   required data-regla="alfanumerico" value="<?= $valor('numeroIdentificacion') ?>">
                            <?= $mensajeError('numeroIdentificacion') ?>
                        </div>
                        <div class="<?= $claseCampo('correoUsuario') ?>">
                            <label for="correoUsuario">Correo <span class="obligatorio">*</span></label>
                            <input type="email" id="correoUsuario" name="correoUsuario" maxlength="150"
                                   required data-regla="correo" value="<?= $valor('correoUsuario') ?>">
                            <p class="textoAyuda">Con este correo inicia sesión</p>
                            <?= $mensajeError('correoUsuario') ?>
                        </div>
                    <?php else: ?>
                        <div class="campo">
                            <label for="numeroIdentificacion">Identificación</label>
                            <input type="text" id="numeroIdentificacion" readonly
                                   value="<?= htmlspecialchars((string) $usuario->getNumeroIdentificacion()) ?>">
                        </div>
                        <div class="campo">
                            <label for="correoUsuario">Correo</label>
                            <input type="email" id="correoUsuario" readonly
                                   value="<?= htmlspecialchars((string) $usuario->getCorreoUsuario()) ?>">
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!$puedeEditarAcceso): ?>
                    <p class="textoAyuda">Para cambiar la identificación o el correo, comuníquese con el administrador.</p>
                <?php endif; ?>
            </section>

            <section class="formularioSeccion">
                <h2>Cuenta</h2>
                <dl class="listaDatos">
                    <dt>Rol</dt>
                    <dd><span class="etiqueta etiquetaRol"><?= htmlspecialchars($rol) ?></span></dd>

                    <dt>Miembro desde</dt>
                    <dd><?= $usuario->getFechaRegistro()->format('d/m/Y') ?></dd>

                    <dt>Contraseña</dt>
                    <dd><a href="<?= $urlBase ?>/perfil/contrasena">Cambiar contraseña</a></dd>
                </dl>
            </section>
        </div>

        <aside>
            <section class="formularioSeccion">
                <h2>Foto de perfil</h2>
                <div class="<?= $claseCampo('fotoPerfil') ?>">
                    <div class="zonaFoto">
                        <img src="<?= $urlBase ?>/fotos/perfil?archivo=<?= urlencode((string) ($usuario->getFotoPerfil() ?? '')) ?>"
                             alt="Su foto de perfil" class="fotoPerfilGrande">
                        <label for="fotoPerfil" class="boton botonSecundario">
                            <?= $usuario->getFotoPerfil() ? 'Cambiar foto' : 'Elegir foto' ?>
                        </label>
                        <input type="file" id="fotoPerfil" name="fotoPerfil" accept="image/jpeg,image/png" class="campoArchivo">
                        <span class="nombreArchivo">JPG o PNG, máximo 5 MB</span>
                    </div>
                    <?= $mensajeError('fotoPerfil') ?>
                </div>
            </section>
        </aside>
    </div>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/perfil" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton">Guardar cambios</button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>