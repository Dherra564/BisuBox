<?php
/**
 * @var \Aplicacion\Modelos\Vendedor $vendedor
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosTienda;

$titulo = 'Mi tienda';
$paginaActual = 'tienda';
$scriptsExtra = ['tienda.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$editando = $errores !== [];

$valor = fn (string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn (string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn (string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$soloLectura = $editando ? '' : 'readonly';
$contactos = $datos['contactos'] ?? [];
$contactosOriginales = DatosTienda::contactosParaFormulario($vendedor);
$logo = $vendedor->getTiendaLogo();
$urlLogo = $logo !== null
    ? $urlBase . '/fotos/logo?archivo=' . urlencode($logo)
    : $urlBase . '/imagenes/logoPorDefecto.svg';
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/tienda/actualizar" enctype="multipart/form-data" novalidate
      class="validarFormulario formularioEditable <?= $editando ? 'editando' : '' ?>"
      data-confirmar="¿Desea guardar los cambios de su tienda?" data-titulo="Guardar cambios" data-boton="Guardar">
    <?= Csrf::campo() ?>

    <section class="formularioSeccion">
        <h2>Datos de la tienda</h2>
        <div class="disenoFormulario">
            <div>
                <div class="<?= $claseCampo('tiendaNombre') ?>">
                    <label for="tiendaNombre">Nombre de la tienda <span class="obligatorio">*</span></label>
                    <input type="text" id="tiendaNombre" name="tiendaNombre" minlength="3" maxlength="100"
                           required data-regla="nombreTienda" <?= $soloLectura ?>
                           data-editable data-original="<?= htmlspecialchars((string) $vendedor->getTiendaNombre()) ?>"
                           data-mensaje-requerido="Ingrese el nombre de la tienda"
                           data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                           value="<?= $valor('tiendaNombre') ?>">
                    <?= $mensajeError('tiendaNombre') ?>
                </div>
                <div class="<?= $claseCampo('tiendaEnlace') ?>">
                    <label for="tiendaEnlace">Enlace de la tienda <span class="obligatorio">*</span></label>
                    <div class="campoConPrefijo">
                        <span class="prefijoCampo" aria-hidden="true">bisubox/tienda/</span>
                        <input type="text" id="tiendaEnlace" name="tiendaEnlace" minlength="3" maxlength="60"
                               required data-regla="enlaceTienda" aria-describedby="ayudaEnlace" <?= $soloLectura ?>
                               data-editable data-original="<?= htmlspecialchars((string) $vendedor->getTiendaEnlace()) ?>"
                               data-mensaje-requerido="Ingrese el enlace de la tienda"
                               data-mensaje-largo="El enlace debe tener entre 3 y 60 caracteres"
                               value="<?= $valor('tiendaEnlace') ?>">
                    </div>
                    <p class="textoAyuda" id="ayudaEnlace">
                        Minúsculas, números y guiones. Si lo cambia, la dirección anterior deja de funcionar.
                    </p>
                    <?= $mensajeError('tiendaEnlace') ?>
                </div>
                <div class="<?= $claseCampo('tiendaDescripcion') ?>">
                    <label for="tiendaDescripcion">Descripción</label>
                    <textarea id="tiendaDescripcion" name="tiendaDescripcion" rows="3" maxlength="300"
                              data-contador <?= $soloLectura ?>
                              data-editable data-original="<?= htmlspecialchars((string) $vendedor->getTiendaDescripcion()) ?>"
                              ><?= $valor('tiendaDescripcion') ?></textarea>
                    <?= $mensajeError('tiendaDescripcion') ?>
                </div>
                <div class="campo campoCasilla">
                    <input type="checkbox" id="tiendaActiva" name="tiendaActiva" value="1"
                           <?= !empty($datos['tiendaActiva']) ? 'checked' : '' ?> <?= $editando ? '' : 'disabled' ?>
                           data-editable data-original="<?= $vendedor->getTiendaActiva() ? '1' : '0' ?>">
                    <label for="tiendaActiva">
                        Mostrar mi tienda
                        <span class="textoAyuda">Si la desmarca, los clientes no podrán verla.</span>
                    </label>
                </div>
            </div>
            <div class="<?= $claseCampo('tiendaLogo') ?>">
                <div class="zonaFoto">
                    <img src="<?= htmlspecialchars($urlLogo) ?>" alt="Logo de la tienda" class="fotoPerfilGrande">
                    <label for="tiendaLogo" class="boton botonSecundario"><?= $logo !== null ? 'Cambiar logo' : 'Elegir logo' ?></label>
                    <input type="file" id="tiendaLogo" name="tiendaLogo" accept="image/jpeg,image/png"
                           class="campoArchivo" <?= $editando ? '' : 'disabled' ?>>
                    <span class="nombreArchivo">JPG o PNG, máximo 5 MB</span>
                    <?php if ($logo !== null): ?>
                        <div class="campoCasilla">
                            <input type="checkbox" id="quitarLogo" name="quitarLogo" value="1"
                                   <?= $editando ? '' : 'disabled' ?> data-editable data-original="0">
                            <label for="quitarLogo">Quitar el logo</label>
                        </div>
                    <?php endif; ?>
                </div>
                <?= $mensajeError('tiendaLogo') ?>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/../Plantilla/contactos.php'; ?>

    <div class="grupoBotones accionesFormulario">
        <button type="button" class="boton formularioEditar">Editar</button>
        <button type="button" class="boton botonSecundario formularioCancelar">Cancelar</button>
        <button type="submit" class="boton formularioGuardar">Guardar cambios</button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>