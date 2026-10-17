<?php
    /**
 * @var \Aplicacion\Modelos\Vendedor $vendedor
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

    use Aplicacion\Nucleo\Csrf;
    use Aplicacion\Nucleo\DatosTienda;

    $titulo       = 'Mi tienda';
    $paginaActual = 'tienda';
    $scriptsExtra = ['tienda.js'];
    require __DIR__ . '/../Plantilla/encabezado.php';

    $editando = $errores !== [] || ! empty($seguirEditando);

    $valor        = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
    $claseCampo   = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
    $mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
    $soloLectura          = $editando ? '' : 'readonly';
    $contactos            = $datos['contactos'] ?? [];
    $contactosOriginales  = DatosTienda::contactosParaFormulario($vendedor);
    $contactosSoloLectura = ! $editando;
    $logo                 = $vendedor->getTiendaLogo();
    $logoPendiente        = $datos['tiendaLogoPendiente'] ?? null;
    $urlLogo              = ($logoPendiente ?? $logo) !== null
    ? $urlBase . '/fotos/logo?archivo=' . urlencode($logoPendiente ?? $logo)
    : $urlBase . '/imagenes/logoPorDefecto.svg';

    $esquemaTienda   = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $direccionTienda = $esquemaTienda . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . $urlBase . '/tienda/' . $vendedor->getTiendaEnlace();
?>
<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo $urlBase ?>/tienda/actualizar" enctype="multipart/form-data" novalidate
      class="validarFormulario formularioEditable <?php echo $editando ? 'editando' : '' ?>"
      data-confirmar="¿Desea guardar los cambios de su tienda?" data-titulo="Guardar cambios" data-boton="Guardar">
    <?php echo Csrf::campo() ?>

    <section class="formularioSeccion">
        <h2>Datos de la tienda</h2>
        <div class="disenoFormulario">
            <div>
                <div class="<?php echo $claseCampo('tiendaNombre') ?>">
                    <label for="tiendaNombre">Nombre de la tienda <span class="obligatorio">*</span></label>
                    <input type="text" id="tiendaNombre" name="tiendaNombre" minlength="3" maxlength="100"
                           required data-regla="nombreTienda" <?php echo $soloLectura ?>
                           data-editable data-original="<?php echo htmlspecialchars((string) $vendedor->getTiendaNombre()) ?>"
                           data-mensaje-requerido="Ingrese el nombre de la tienda"
                           data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                           value="<?php echo $valor('tiendaNombre') ?>">
                    <?php echo $mensajeError('tiendaNombre') ?>
                </div>
                               <div class="campo">
                    <label for="direccionTienda">Dirección de su tienda</label>
                    <div class="campoConBoton">
                        <input type="text" id="direccionTienda" readonly value="<?php echo htmlspecialchars($direccionTienda) ?>">
                        <button type="button" class="boton botonSecundario" data-copiar="direccionTienda">Copiar</button>
                        <a href="<?php echo $urlBase ?>/tienda/<?php echo htmlspecialchars((string) $vendedor->getTiendaEnlace()) ?>"
   class="boton botonSecundario" target="_blank" rel="noopener">Abrir</a>
                    </div>
                    <p class="textoAyuda">Se creó sola con el nombre de su tienda. Compártala con sus clientes para que vean sus productos.</p>
                </div>
                <div class="<?php echo $claseCampo('tiendaDescripcion') ?>">
                    <label for="tiendaDescripcion">Descripción</label>
                    <textarea id="tiendaDescripcion" name="tiendaDescripcion" rows="3" maxlength="300"
                              data-contador <?php echo $soloLectura ?>
                              data-editable data-original="<?php echo htmlspecialchars((string) $vendedor->getTiendaDescripcion()) ?>"
                              ><?php echo $valor('tiendaDescripcion') ?></textarea>
                    <?php echo $mensajeError('tiendaDescripcion') ?>
                </div>
                <div class="campo campoCasilla">
                    <input type="checkbox" id="tiendaActiva" name="tiendaActiva" value="1"
                           <?php echo ! empty($datos['tiendaActiva']) ? 'checked' : '' ?> <?php echo $editando ? '' : 'disabled' ?>
                           data-editable data-original="<?php echo $vendedor->getTiendaActiva() ? '1' : '0' ?>">
                    <label for="tiendaActiva">
                        Mostrar mi tienda
                        <span class="textoAyuda">Si la desmarca, los clientes no podrán verla.</span>
                    </label>
                </div>
            </div>
            <div class="<?php echo $claseCampo('tiendaLogo') ?>">
                <div class="zonaFoto">
                    <img src="<?php echo htmlspecialchars($urlLogo) ?>" alt="Logo de la tienda" class="fotoPerfilGrande">
                    <label for="tiendaLogo" class="boton botonSecundario"><?php echo $logo !== null ? 'Cambiar logo' : 'Elegir logo' ?></label>
                    <input type="file" id="tiendaLogo" name="tiendaLogo" accept="image/jpeg,image/png"
                           class="campoArchivo" <?php echo $editando ? '' : 'disabled' ?>>
                    <?php if ($logoPendiente !== null): ?>
                        <!-- El logo que ya eligió no se pierde aunque el formulario tenga errores -->
                        <input type="hidden" name="tiendaLogoPendiente" value="<?php echo htmlspecialchars($logoPendiente) ?>">
                        <span class="nombreArchivo">Ya eligió este logo. Se guarda con los cambios</span>
                    <?php else: ?>
                        <span class="nombreArchivo">JPG o PNG, máximo 5 MB</span>
                    <?php endif; ?>
                    <?php if ($logo !== null): ?>
                        <div class="campoCasilla">
                            <input type="checkbox" id="quitarLogo" name="quitarLogo" value="1"
                                   <?php echo ! empty($datos['quitarLogo']) ? 'checked' : '' ?>
                                   <?php echo $editando ? '' : 'disabled' ?> data-editable data-original="0">
                            <label for="quitarLogo">Quitar el logo</label>
                        </div>
                    <?php endif; ?>
                </div>
                <?php echo $mensajeError('tiendaLogo') ?>
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