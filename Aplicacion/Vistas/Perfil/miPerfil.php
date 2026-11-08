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
    use Aplicacion\Nucleo\Validador;

    $titulo       = 'Mi perfil';
    $paginaActual = 'perfil';
    require __DIR__ . '/../Plantilla/encabezado.php';

    $editando      = $errores !== [] || ! empty($seguirEditando);
    $fotoPendiente = $datos['fotoPerfilPendiente'] ?? null;

    $valor        = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
    $claseCampo   = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
    $mensajeError = fn(string $campo): string => isset($errores[$campo])
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

<form method="post" action="<?php echo $urlBase ?>/perfil/actualizar" enctype="multipart/form-data" novalidate
      class="validarFormulario formularioEditable <?php echo $editando ? 'editando' : '' ?>"
      data-confirmar="¿Desea guardar los cambios de su perfil?"
      data-titulo="Guardar cambios" data-boton="Guardar">
    <?php echo Csrf::campo() ?>

    <div class="disenoFormulario">
        <div>
            <section class="formularioSeccion">
                <h2>Datos personales</h2>
                <div class="<?php echo $claseCampo('nombreCompleto') ?>">
                    <label for="nombreCompleto">Nombre completo <span class="obligatorio">*</span></label>
                    <input type="text" id="nombreCompleto" name="nombreCompleto" minlength="3" maxlength="100"
                           required data-regla="soloLetras" data-contador <?php echo $soloLectura ?>
                           data-editable data-original="<?php echo htmlspecialchars((string) $usuario->getNombreCompleto()) ?>"
                           data-mensaje-requerido="Ingrese su nombre completo"
                           data-mensaje-regla="El nombre solo puede tener letras y espacios"
                           data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                           value="<?php echo $valor('nombreCompleto') ?>">
                    <?php echo $mensajeError('nombreCompleto') ?>
                </div>
                <?php if ($tieneIdentificacion): ?>
                    <div class="filaCampos">
                        <div class="<?php echo $claseCampo('tipoIdentificacion') ?>">
                            <label for="tipoIdentificacion">Tipo de identificación <span class="obligatorio">*</span></label>
                            <select id="tipoIdentificacion" name="tipoIdentificacion" required <?php echo $editando ? '' : 'disabled' ?>
                                    data-editable data-original="<?php echo htmlspecialchars((string) $usuario->getTipoIdentificacion()) ?>"
                                    data-mensaje-requerido="Seleccione el tipo de identificación">
                                <option value="">Seleccione el tipo</option>
                                <?php foreach (TipoIdentificacion::todos() as $codigo => $tipo): ?>
                                    <option value="<?php echo $codigo ?>" <?php echo($datos['tipoIdentificacion'] ?? '') === $codigo ? 'selected' : '' ?>
                                    data-patron="<?php echo htmlspecialchars($tipo['patron']) ?>"
                                    data-mensaje="<?php echo htmlspecialchars($tipo['mensaje']) ?>"
                                    data-ayuda="<?php echo htmlspecialchars($tipo['ayuda']) ?>"
                                    data-grupos="<?php echo htmlspecialchars($tipo['grupos']) ?>"
                                    data-solo-numeros="<?php echo $tipo['soloNumeros'] ? '1' : '0' ?>"
                                    data-largo-maximo="<?php echo $tipo['largoMaximo'] ?>">
                                    <?php echo htmlspecialchars($tipo['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php echo $mensajeError('tipoIdentificacion') ?>
                        </div>
                        <div class="<?php echo $claseCampo('numeroIdentificacion') ?>">
                            <label for="numeroIdentificacion">Identificación <span class="obligatorio">*</span></label>
                            <input type="text" id="numeroIdentificacion" name="numeroIdentificacion" maxlength="25"
                                   required data-regla="identificacion" data-tipo="tipoIdentificacion"
                                   data-campo-ayuda="ayudaIdentificacion" <?php echo $soloLectura ?>
                                   data-editable data-original="<?= htmlspecialchars(TipoIdentificacion::formatear($usuario->getTipoIdentificacion(), $usuario->getNumeroIdentificacion())) ?>"
                                   data-mensaje-requerido="Ingrese la identificación"
                                   value="<?php echo $valor('numeroIdentificacion') ?>">
                            <p class="textoAyuda" id="ayudaIdentificacion">
                                <?php echo htmlspecialchars(TipoIdentificacion::existe($datos['tipoIdentificacion'] ?? null)
                                            ? TipoIdentificacion::todos()[$datos['tipoIdentificacion']]['ayuda']
                                        : 'Primero seleccione el tipo') ?>
                            </p>
                            <?php echo $mensajeError('numeroIdentificacion') ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="filaCampos">
                    <div class="<?php echo $claseCampo('correoUsuario') ?>">
                        <label for="correoUsuario">Correo <span class="obligatorio">*</span></label>
                        <input type="email" id="correoUsuario" name="correoUsuario" maxlength="150"
                               required data-regla="correo" <?php echo $soloLectura ?>
                               data-editable data-original="<?php echo htmlspecialchars((string) $usuario->getCorreoUsuario()) ?>"
                               data-mensaje-requerido="Ingrese el correo"
                               value="<?php echo $valor('correoUsuario') ?>">
                        <p class="textoAyuda">Con este correo inicia sesión</p>
                        <?php echo $mensajeError('correoUsuario') ?>
                    </div>
                    <div class="<?php echo $claseCampo('numeroTelefonico') ?>">
                        <label for="numeroTelefonico">Teléfono <span class="obligatorio">*</span></label>
                        <input type="tel" id="numeroTelefonico" name="numeroTelefonico" maxlength="15"
                        required data-regla="telefono" data-mascara-telefono inputmode="tel" <?= $soloLectura ?>
                        data-editable data-original="<?= htmlspecialchars(Validador::formatearTelefono($usuario->getNumeroTelefonico())) ?>"
                        data-mensaje-requerido="Ingrese su teléfono"
                        value="<?= $valor('numeroTelefonico') ?>">
                        <p class="textoAyuda">
                            8 dígitos, por ejemplo 88451290<?php echo $tieneIdentificacion ? '. No se muestra en la tienda' : '' ?>
                        </p>
                        <?php echo $mensajeError('numeroTelefonico') ?>
                    </div>
                </div>
            </section>

            <section class="formularioSeccion">
                <h2>Cuenta</h2>
                <dl class="listaDatos">
                    <dt>Rol</dt>
                    <dd><span class="etiqueta etiquetaRol"><?php echo htmlspecialchars($rol) ?></span></dd>

                    <dt>Miembro desde</dt>
                    <dd><?php echo $usuario->getFechaRegistro()->format('d/m/Y') ?></dd>

                    <dt>Contraseña</dt>
                    <dd><a href="<?php echo $urlBase ?>/perfil/contrasena">Cambiar contraseña</a></dd>
                </dl>
            </section>
        </div>

        <aside>
            <section class="formularioSeccion">
                <h2>Foto de perfil</h2>
                <div class="<?php echo $claseCampo('fotoPerfil') ?>">
                    <div class="zonaFoto">
                        <img src="<?php echo $urlBase ?>/fotos/perfil?archivo=<?php echo urlencode((string) ($fotoPendiente ?? $usuario->getFotoPerfil() ?? '')) ?>"
                             alt="Su foto de perfil" class="fotoPerfilGrande">
                        <label for="fotoPerfil" class="boton botonSecundario">
                            <?php echo $usuario->getFotoPerfil() ? 'Cambiar foto' : 'Elegir foto' ?>
                        </label>
                        <input type="file" id="fotoPerfil" name="fotoPerfil" accept="image/jpeg,image/png"
                               class="campoArchivo" <?php echo $editando ? '' : 'disabled' ?>>
                        <?php if ($fotoPendiente !== null): ?>
                            <!-- La foto que ya eligió no se pierde aunque el formulario tenga errores -->
                            <input type="hidden" name="fotoPerfilPendiente" value="<?php echo htmlspecialchars($fotoPendiente) ?>">
                            <span class="nombreArchivo">Ya eligió esta foto. Se guarda con los cambios</span>
                        <?php else: ?>
                            <span class="nombreArchivo">JPG o PNG, máximo 5 MB</span>
                        <?php endif; ?>
                    </div>
                    <?php echo $mensajeError('fotoPerfil') ?>
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