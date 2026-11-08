<?php
/**
 * @var \Aplicacion\Modelos\Proveedor|null $proveedor
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;

$esNuevo = $proveedor === null;
$titulo = $esNuevo ? 'Nuevo proveedor' : 'Editar proveedor';
$paginaActual = 'proveedores';
$volverA = $esNuevo ? ['texto' => 'Volver', 'ruta' => '/proveedores'] : ['texto' => 'Proveedores', 'ruta' => '/proveedores'];

require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$rutaCancelar = $esNuevo ? '/proveedores' : '/proveedores/detalle?id=' . (int) $proveedor->getIdProveedor();
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo $urlBase ?>/proveedores/<?php echo $esNuevo ? 'crear' : 'actualizar' ?>"
    novalidate class="validarFormulario" <?php if (!$esNuevo): ?>
        data-confirmar="¿Desea guardar los cambios de <?php echo htmlspecialchars((string) $proveedor->getNombre()) ?>?"
        data-titulo="Guardar cambios" data-boton="Guardar" <?php endif; ?>>
    <?php echo Csrf::campo() ?>
    <?php if (!$esNuevo): ?>
        <input type="hidden" name="id" value="<?php echo (int) $proveedor->getIdProveedor() ?>">
    <?php endif; ?>

    <section class="formularioSeccion">
        <h2>Datos del proveedor</h2>

        <div class="<?php echo $claseCampo('nombre') ?>">
            <label for="nombre">Nombre de la empresa <span class="obligatorio">*</span></label>
            <input type="text" id="nombre" name="nombre" minlength="3" maxlength="50" required
                data-regla="nombreComercial" data-contador data-mensaje-requerido="Ingrese el nombre del proveedor"
                data-mensaje-largo="El nombre debe tener entre 3 y 50 caracteres"
                value="<?php echo $valor('nombre') ?>">
            <p class="textoAyuda">Por ejemplo Perlas del Valle S.A.</p>
            <?php echo $mensajeError('nombre') ?>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>Contacto</h2>

        <div class="filaCampos">
            <div class="<?php echo $claseCampo('telefono') ?>">
                <label for="telefono">Teléfono o celular <span class="obligatorio">*</span></label>
                <input type="tel" id="telefono" name="telefono" maxlength="15" required inputmode="tel"
                    data-regla="telefono" data-mensaje-requerido="Ingrese el teléfono" data-mascara-telefono
                    value="<?php echo $valor('telefono') ?>">
                <p class="textoAyuda">8 dígitos, por ejemplo 8845-1290</p>
                <?php echo $mensajeError('telefono') ?>
            </div>
            <div class="<?php echo $claseCampo('correo') ?>">
                <label for="correo">Correo <span class="obligatorio">*</span></label>
                <input type="email" id="correo" name="correo" maxlength="150" required autocomplete="off"
                    data-regla="correo" data-mensaje-requerido="Ingrese el correo"
                    value="<?php echo $valor('correo') ?>">
                <p class="textoAyuda">Por ejemplo ventas@proveedor.com</p>
                <?php echo $mensajeError('correo') ?>
            </div>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?php echo $urlBase . $rutaCancelar ?>" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton"><?php echo $esNuevo ? 'Registrar proveedor' : 'Guardar cambios' ?></button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>