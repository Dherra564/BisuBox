<?php
/**
 * @var \Aplicacion\Modelos\Producto $producto
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;

$titulo = 'Editar producto';
$paginaActual = 'productos';
$volverA = ['texto' => $producto->getNombre(), 'ruta' => '/productos/detalle?id=' . $producto->getIdProducto()];
$scriptsExtra = ['productos.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo, string $extra = ''): string =>
    trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));

$foto = $producto->getFoto();
$urlFoto = $foto !== null
    ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto)
    : $urlBase . '/imagenes/productoPorDefecto.svg';
$productoCostos = $producto;
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/productos/editar" enctype="multipart/form-data" novalidate
    class="validarFormulario formularioProducto">
    <?= Csrf::campo() ?>
    <input type="hidden" name="id" value="<?= $producto->getIdProducto() ?>">

    <div class="creadorProducto">
        <div class="pasosProducto">
            <section class="formularioSeccion">
                <h2>Datos del producto</h2>

                <div class="encabezadoProducto">
                    <div class="<?= $claseCampo('foto', 'campoFotoProducto') ?>">
                        <div class="zonaFoto zonaFotoProducto">
                            <?php $fotoPendiente = $datos['fotoPendiente'] ?? null; ?>
                            <img src="<?= htmlspecialchars($fotoPendiente !== null ? $urlBase . '/fotos/producto?archivo=' . urlencode($fotoPendiente) : $urlFoto) ?>" alt="" class="fotoPerfilGrande fotoProducto">
                            <label for="foto" class="enlaceFoto"><?= $foto !== null || $fotoPendiente !== null ? 'Cambiar foto' : 'Agregar foto' ?></label>
                            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" class="campoArchivo">
                            <?php if ($fotoPendiente !== null): ?>
                                <input type="hidden" name="fotoPendiente" value="<?= htmlspecialchars($fotoPendiente) ?>">
                                <span class="nombreArchivo">Foto nueva elegida</span>
                            <?php else: ?>
                                <span class="nombreArchivo">JPG o PNG</span>
                            <?php endif; ?>
                            <?php if ($foto !== null): ?>
                                <div class="campoCasilla">
                                    <input type="checkbox" id="quitarFoto" name="quitarFoto" value="1" <?= !empty($datos['quitarFoto']) ? 'checked' : '' ?>>
                                    <label for="quitarFoto">Quitar</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?= $mensajeError('foto') ?>
                    </div>

                    <div class="datosProducto">
                        <div class="<?= $claseCampo('nombre') ?>">
                            <label for="nombre">Nombre <span class="obligatorio">*</span></label>
                            <input type="text" id="nombre" name="nombre" minlength="3" maxlength="60" required
                                   data-regla="nombreComercial"
                                   data-mensaje-requerido="Escriba el nombre del producto, por ejemplo Collar de perlas"
                                   data-mensaje-largo="El nombre debe tener entre 3 y 60 caracteres"
                                   value="<?= $valor('nombre') ?>">
                            <?= $mensajeError('nombre') ?>
                        </div>
                        <div class="<?= $claseCampo('descripcion') ?>">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" maxlength="300" rows="3" data-contador
                                      placeholder="Opcional. Así lo verán sus clientes en la tienda"><?= $valor('descripcion') ?></textarea>
                            <?= $mensajeError('descripcion') ?>
                        </div>
                    </div>
                </div>
                <p class="textoAyuda">Lo que lleva cada uno ya quedó guardado y no se cambia aquí.</p>
            </section>
        </div>

        <aside class="columnaPrecio">
            <?php require __DIR__ . '/precio.php'; ?>

            <div class="grupoBotones accionesFormulario accionesIzquierda">
                <button type="submit" class="boton">Guardar cambios</button>
                <a href="<?= $urlBase ?>/productos/detalle?id=<?= $producto->getIdProducto() ?>" class="boton botonSecundario">Cancelar</a>
            </div>
        </aside>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>