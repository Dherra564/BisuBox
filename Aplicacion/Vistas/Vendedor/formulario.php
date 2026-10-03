<?php
/**
 * Formulario para registrar un vendedor nuevo o editar uno existente.
 *
 * @var \Aplicacion\Modelos\Vendedor|null $vendedor null al registrar; el vendedor al editar
 * @var array $datos Valores escritos (se conservan si hay errores; la contraseña nunca)
 * @var array $errores ['campo' => 'mensaje']
 * @var string $urlBase Viene de encabezado.php
 */

use Aplicacion\Nucleo\Csrf;

$esNuevo = $vendedor === null;
$titulo = $esNuevo ? 'Nuevo vendedor' : 'Editar vendedor';
$paginaActual = 'vendedores';
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn (string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn (string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn (string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$rutaCancelar = $esNuevo ? '/vendedores' : '/vendedores/detalle?id=' . (int) $vendedor->getIdVendedor();
?>

<p><a href="<?= $urlBase ?>/vendedores">← Vendedores</a></p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/vendedores/<?= $esNuevo ? 'crear' : 'actualizar' ?>"
      enctype="multipart/form-data" novalidate class="validarFormulario">
    <?= Csrf::campo() ?>
    <?php if (!$esNuevo): ?>
        <input type="hidden" name="id" value="<?= (int) $vendedor->getIdVendedor() ?>">
    <?php endif; ?>

    <div class="disenoFormulario">
        <div>
            <section class="formularioSeccion">
                <h2>Datos personales</h2>
                <div class="<?= $claseCampo('nombreCompleto') ?>">
                    <label for="nombreCompleto">Nombre completo <span class="obligatorio">*</span></label>
                    <input type="text" id="nombreCompleto" name="nombreCompleto" minlength="3" maxlength="100"
                           required data-regla="soloLetras" data-contador
                           data-mensaje-requerido="Ingrese el nombre completo"
                           data-mensaje-regla="El nombre solo puede tener letras y espacios"
                           data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                           value="<?= $valor('nombreCompleto') ?>">
                    <?= $mensajeError('nombreCompleto') ?>
                </div>
                <div class="filaCampos">
                    <div class="<?= $claseCampo('numeroIdentificacion') ?>">
                        <label for="numeroIdentificacion">Identificación <span class="obligatorio">*</span></label>
                        <input type="text" id="numeroIdentificacion" name="numeroIdentificacion" minlength="6" maxlength="20"
                               required data-regla="alfanumerico"
                               data-mensaje-requerido="Ingrese la identificación"
                               data-mensaje-largo="La identificación debe tener entre 6 y 20 caracteres"
                               value="<?= $valor('numeroIdentificacion') ?>">
                        <p class="textoAyuda">Solo letras y números, sin espacios ni guiones</p>
                        <?= $mensajeError('numeroIdentificacion') ?>
                    </div>
                    <div class="<?= $claseCampo('numeroTelefonico') ?>">
                        <label for="numeroTelefonico">Teléfono <span class="obligatorio">*</span></label>
                        <input type="tel" id="numeroTelefonico" name="numeroTelefonico" maxlength="15"
                               required data-regla="telefono" inputmode="tel"
                               data-mensaje-requerido="Ingrese el teléfono"
                               value="<?= $valor('numeroTelefonico') ?>">
                        <p class="textoAyuda">8 dígitos, por ejemplo 88451290</p>
                        <?= $mensajeError('numeroTelefonico') ?>
                    </div>
                </div>
            </section>

            <section class="formularioSeccion">
                <h2><?= $esNuevo ? 'Acceso al sistema' : 'Acceso y contraseña' ?></h2>
                <div class="<?= $claseCampo('correoUsuario') ?>">
                    <label for="correoUsuario">Correo <span class="obligatorio">*</span></label>
                    <input type="email" id="correoUsuario" name="correoUsuario" maxlength="150"
                           required data-regla="correo" autocomplete="off"
                           data-mensaje-requerido="Ingrese el correo"
                           value="<?= $valor('correoUsuario') ?>">
                    <p class="textoAyuda">Con este correo inicia sesión</p>
                    <?= $mensajeError('correoUsuario') ?>
                </div>
                <?php if (!$esNuevo): ?>
                    <p class="textoAyuda">Para restablecer la contraseña, escriba una nueva. Si deja los campos vacíos, se mantiene la actual.</p>
                <?php endif; ?>
                <div class="filaCampos">
                    <div class="<?= $claseCampo('contrasena') ?>">
                        <label for="contrasena">
                            <?= $esNuevo ? 'Contraseña' : 'Contraseña nueva' ?>
                            <?php if ($esNuevo): ?><span class="obligatorio">*</span><?php endif; ?>
                        </label>
                        <input type="password" id="contrasena" name="contrasena" maxlength="20"
                               <?= $esNuevo ? 'required' : '' ?> data-regla="contrasena" autocomplete="new-password"
                               data-mensaje-requerido="Ingrese la contraseña">
                        <p class="textoAyuda">Entre 8 y 20 caracteres, con mayúscula, minúscula y número, sin espacios</p>
                        <?= $mensajeError('contrasena') ?>
                    </div>
                    <div class="<?= $claseCampo('confirmarContrasena') ?>">
                        <label for="confirmarContrasena">
                            Confirmar contraseña
                            <?php if ($esNuevo): ?><span class="obligatorio">*</span><?php endif; ?>
                        </label>
                        <input type="password" id="confirmarContrasena" name="confirmarContrasena" maxlength="20"
                               <?= $esNuevo ? 'required' : '' ?> data-igual-a="contrasena" autocomplete="new-password"
                               data-mensaje-requerido="Confirme la contraseña">
                        <?= $mensajeError('confirmarContrasena') ?>
                    </div>
                </div>
            </section>
        </div>

        <aside>
            <section class="formularioSeccion">
                <h2>Foto de perfil</h2>
                <div class="<?= $claseCampo('fotoPerfil') ?>">
                    <div class="zonaFoto">
                        <img src="<?= $urlBase ?>/fotos/perfil?archivo=<?= urlencode((string) ($vendedor?->getFotoPerfil() ?? '')) ?>"
                             alt="Foto de perfil del vendedor" class="fotoPerfilGrande">
                        <label for="fotoPerfil" class="boton botonSecundario">
                            <?= $vendedor?->getFotoPerfil() ? 'Cambiar foto' : 'Elegir foto' ?>
                        </label>
                        <input type="file" id="fotoPerfil" name="fotoPerfil" accept="image/jpeg,image/png" class="campoArchivo">
                        <span class="nombreArchivo">JPG o PNG, máximo 5 MB. Opcional</span>
                    </div>
                    <?= $mensajeError('fotoPerfil') ?>
                </div>
            </section>
        </aside>
    </div>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase . $rutaCancelar ?>" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton"><?= $esNuevo ? 'Registrar vendedor' : 'Guardar cambios' ?></button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>