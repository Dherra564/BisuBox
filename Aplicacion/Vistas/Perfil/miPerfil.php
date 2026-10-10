<?php
/**
 * @var \Aplicacion\Modelos\Usuario $usuario
 * @var string $rol
 * @var bool $tieneIdentificacion
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\TipoIdentificacion;

$titulo = 'Mi perfil';
$paginaActual = 'perfil';
require __DIR__ . '/../Plantilla/encabezado.php';

$editando = $errores !== [];

$valor = fn (string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn (string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn (string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$soloLectura = $editando ? '' : 'readonly';
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/perfil/actualizar" enctype="multipart/form-data" novalidate
      class="validarFormulario formularioEditable <?= $editando ? 'editando' : '' ?>"
      data-confirmar="¿Desea guardar los cambios de su perfil?"
      data-titulo="Guardar cambios" data-boton="Guardar">
    <?= Csrf::campo() ?>

    <div class="disenoFormulario">
        <div>
            <section class="formularioSeccion">
                <h2>Datos personales</h2>
                <div class="<?= $claseCampo('nombreCompleto') ?>">
                    <label for="nombreCompleto">Nombre completo <span class="obligatorio">*</span></label>
                    <input type="text" id="nombreCompleto" name="nombreCompleto" minlength="3" maxlength="100"
                           required data-regla="soloLetras" data-contador <?= $soloLectura ?>
                           data-editable data-original="<?= htmlspecialchars((string) $usuario->getNombreCompleto()) ?>"
                           data-mensaje-requerido="Ingrese su nombre completo"
                           data-mensaje-regla="El nombre solo puede tener letras y espacios"
                           data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                           value="<?= $valor('nombreCompleto') ?>">
                    <?= $mensajeError('nombreCompleto') ?>
                </div>
                <?php if ($tieneIdentificacion): ?>
                    <div class="filaCampos">
                        <div class="<?= $claseCampo('tipoIdentificacion') ?>">
                            <label for="tipoIdentificacion">Tipo de identificación <span class="obligatorio">*</span></label>
                            <select id="tipoIdentificacion" name="tipoIdentificacion" required <?= $editando ? '' : 'disabled' ?>
                                    data-editable data-original="<?= htmlspecialchars((string) $usuario->getTipoIdentificacion()) ?>"
                                    data-mensaje-requerido="Seleccione el tipo de identificación">
                                <option value="">Seleccione el tipo</option>
                                <?php foreach (TipoIdentificacion::todos() as $codigo => $tipo): ?>
                                    <option value="<?= $codigo ?>" <?= ($datos['tipoIdentificacion'] ?? '') === $codigo ? 'selected' : '' ?>
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
                            <input type="text" id="numeroIdentificacion" name="numeroIdentificacion" maxlength="25"
                                   required data-regla="identificacion" data-tipo="tipoIdentificacion"
                                   data-campo-ayuda="ayudaIdentificacion" <?= $soloLectura ?>
                                   data-editable data-original="<?= htmlspecialchars((string) $usuario->getNumeroIdentificacion()) ?>"
                                   data-mensaje-requerido="Ingrese la identificación"
                                   value="<?= $valor('numeroIdentificacion') ?>">
                            <p class="textoAyuda" id="ayudaIdentificacion">
                                <?= htmlspecialchars(TipoIdentificacion::existe($datos['tipoIdentificacion'] ?? null)
                                    ? TipoIdentificacion::todos()[$datos['tipoIdentificacion']]['ayuda']
                                    : 'Primero seleccione el tipo') ?>
                            </p>
                            <?= $mensajeError('numeroIdentificacion') ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="filaCampos">
                    <div class="<?= $claseCampo('correoUsuario') ?>">
                        <label for="correoUsuario">Correo <span class="obligatorio">*</span></label>
                        <input type="email" id="correoUsuario" name="correoUsuario" maxlength="150"
                               required data-regla="correo" <?= $soloLectura ?>
                               data-editable data-original="<?= htmlspecialchars((string) $usuario->getCorreoUsuario()) ?>"
                               data-mensaje-requerido="Ingrese el correo"
                               value="<?= $valor('correoUsuario') ?>">
                        <p class="textoAyuda">Con este correo inicia sesión</p>
                        <?= $mensajeError('correoUsuario') ?>
                    </div>
                    <div class="<?= $claseCampo('numeroTelefonico') ?>">
                        <label for="numeroTelefonico">Teléfono <span class="obligatorio">*</span></label>
                        <input type="tel" id="numeroTelefonico" name="numeroTelefonico" maxlength="15"
                               required data-regla="telefono" inputmode="tel" <?= $soloLectura ?>
                               data-editable data-original="<?= htmlspecialchars((string) $usuario->getNumeroTelefonico()) ?>"
                               data-mensaje-requerido="Ingrese su teléfono"
                               value="<?= $valor('numeroTelefonico') ?>">
                        <p class="textoAyuda">
                            8 dígitos, por ejemplo 88451290<?= $tieneIdentificacion ? '. No se muestra en la tienda' : '' ?>
                        </p>
                        <?= $mensajeError('numeroTelefonico') ?>
                    </div>
                </div>
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
                        <input type="file" id="fotoPerfil" name="fotoPerfil" accept="image/jpeg,image/png"
                               class="campoArchivo" <?= $editando ? '' : 'disabled' ?>>
                        <span class="nombreArchivo">JPG o PNG, máximo 5 MB</span>
                    </div>
                    <?= $mensajeError('fotoPerfil') ?>
                </div>
            </section>
        </aside>
    </div>

    <div class="grupoBotones accionesFormulario">
        <button type="button" class="boton formularioEditar">Editar</button>
        <button type="button" class="boton botonSecundario formularioCancelar">Cancelar</button>
        <button type="submit" class="boton formularioGuardar">Guardar cambios</button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>