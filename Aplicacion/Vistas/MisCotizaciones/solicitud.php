<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCotizacion;

$esEdicion = $cotizacion !== null;
$nombreTienda = $esEdicion ? $cotizacion['nombreTienda'] : (string) $tiendaVista->getTiendaNombre();
$enlaceTienda = $esEdicion ? $cotizacion['enlaceTienda'] : (string) $tiendaVista->getTiendaEnlace();
$titulo = $esEdicion ? 'Editar solicitud #' . $cotizacion['id'] : 'Cotizar por foto';
$paginaActual = $esEdicion ? 'misCotizaciones' : 'inicio';
$volverA = $esEdicion
    ? ['texto' => 'Solicitud #' . $cotizacion['id'], 'ruta' => '/mis-cotizaciones/detalle?id=' . $cotizacion['id']]
    : ['texto' => $nombreTienda, 'ruta' => '/tienda/' . $enlaceTienda];
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo, string $extra = ''): string => trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';

$fotoPendiente = $datos['fotoPendiente'] ?? null;
$fotoActual = $fotoPendiente ?? ($esEdicion ? $cotizacion['foto'] : null);
$accion = $esEdicion ? '/mis-cotizaciones/editar' : '/mis-cotizaciones/crear';
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase . $accion ?>" enctype="multipart/form-data" novalidate class="validarFormulario"
    data-confirmar="<?= $esEdicion ? '¿Guardar los cambios de la solicitud?' : '¿Enviar la solicitud a ' . htmlspecialchars($nombreTienda) . '?' ?>"
    data-titulo="<?= $esEdicion ? 'Guardar solicitud' : 'Enviar solicitud' ?>" data-boton="<?= $esEdicion ? 'Guardar' : 'Enviar solicitud' ?>">
    <?= Csrf::campo() ?>
    <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= (int) $cotizacion['id'] ?>">
    <?php else: ?>
        <input type="hidden" name="tienda" value="<?= (int) $tiendaVista->getIdVendedor() ?>">
    <?php endif; ?>

    <section class="formularioSeccion">
        <h2>Pieza que quiere en <?= htmlspecialchars($nombreTienda) ?></h2>

        <div class="<?= $claseCampo('foto', 'campoFotoProducto') ?>">
            <div class="zonaFoto zonaFotoProducto">
                <img src="<?= $fotoActual !== null
                    ? $urlBase . '/fotos/cotizacion?archivo=' . urlencode($fotoActual)
                    : $urlBase . '/imagenes/productoPorDefecto.svg' ?>" alt="" class="fotoPerfilGrande fotoProducto">
                <label for="foto" class="enlaceFoto"><?= $fotoActual !== null ? 'Cambiar foto' : 'Agregar foto' ?> <?= $fotoActual === null ? '<span class="obligatorio">*</span>' : '' ?></label>
                <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" class="campoArchivo"
                    <?= $fotoActual === null ? 'required data-mensaje-requerido="' . htmlspecialchars(DatosCotizacion::MENSAJE_FOTO) . '"' : '' ?>>
                <?php if ($fotoPendiente !== null): ?>
                    <input type="hidden" name="fotoPendiente" value="<?= htmlspecialchars($fotoPendiente) ?>">
                    <span class="nombreArchivo">Foto elegida</span>
                <?php else: ?>
                    <span class="nombreArchivo">JPG o PNG</span>
                <?php endif; ?>
            </div>
            <?= $mensajeError('foto') ?>
        </div>

        <div class="<?= $claseCampo('descripcion') ?>">
            <label for="descripcion">¿Cómo la quiere? <span class="obligatorio">*</span></label>
            <textarea id="descripcion" name="descripcion" rows="4" minlength="10" maxlength="300" required data-contador
                data-regla="descripcionTexto"
                data-mensaje-requerido="Cuéntenos cómo la quiere: colores, medidas o materiales"
                data-mensaje-largo="La descripción debe tener entre 10 y 300 caracteres"><?= $valor('descripcion') ?></textarea>
            <p class="textoAyuda">Colores, medidas o materiales. Por ejemplo: pulsera de 17 cm con perlas blancas.</p>
            <?= $mensajeError('descripcion') ?>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase . $volverA['ruta'] ?>" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton"><?= $esEdicion ? 'Guardar cambios' : 'Enviar solicitud' ?></button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>