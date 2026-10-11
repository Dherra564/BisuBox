<?php
/**
 * @var \Aplicacion\Modelos\Proveedor|null $proveedor
 * @var array $datos
 * @var array $errores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\TipoIdentificacion;

$esNuevo = $proveedor === null;
$titulo = $esNuevo ? 'Nuevo proveedor' : 'Editar proveedor';
$paginaActual = 'proveedores';
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$tiposIdentificacion = TipoIdentificacion::paraProveedores();
$tipoElegido = $datos['tipoIdentificacion'] ?? '';
$rutaCancelar = $esNuevo ? '/proveedores' : '/proveedores/detalle?id=' . (int) $proveedor->getIdProveedor();
?>

<p><a href="<?= $urlBase ?>/proveedores">← Proveedores</a></p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/proveedores/<?= $esNuevo ? 'crear' : 'actualizar' ?>"
      novalidate class="validarFormulario"
      <?php if (!$esNuevo): ?>
          data-confirmar="¿Desea guardar los cambios de <?= htmlspecialchars((string) $proveedor->getNombre()) ?>?"
          data-titulo="Guardar cambios" data-boton="Guardar"
      <?php endif; ?>>
    <?= Csrf::campo() ?>
    <?php if (!$esNuevo): ?>
        <input type="hidden" name="id" value="<?= (int) $proveedor->getIdProveedor() ?>">
    <?php endif; ?>

    <section class="formularioSeccion">
        <h2>Datos del proveedor</h2>

        <div class="<?= $claseCampo('nombre') ?>">
            <label for="nombre">Nombre de la empresa <span class="obligatorio">*</span></label>
            <input type="text" id="nombre" name="nombre" minlength="3" maxlength="50" required
                   data-regla="nombreComercial" data-contador
                   data-mensaje-requerido="Ingrese el nombre del proveedor"
                   data-mensaje-largo="El nombre debe tener entre 3 y 50 caracteres"
                   value="<?= $valor('nombre') ?>">
            <p class="textoAyuda">Por ejemplo Perlas del Valle S.A.</p>
            <?= $mensajeError('nombre') ?>
        </div>

        <div class="filaCampos">
            <div class="<?= $claseCampo('tipoIdentificacion') ?>">
                <label for="tipoIdentificacion">Tipo de identificación <span class="obligatorio">*</span></label>
                <select id="tipoIdentificacion" name="tipoIdentificacion" required
                        data-mensaje-requerido="Seleccione el tipo de identificación">
                    <option value="">Seleccione el tipo</option>
                    <?php foreach ($tiposIdentificacion as $codigo => $tipo): ?>
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
                       data-mensaje-requerido="Ingrese la identificación"
                       value="<?= htmlspecialchars(TipoIdentificacion::formatear($tipoElegido, $datos['numeroIdentificacion'] ?? '')) ?>">
                <p class="textoAyuda" id="ayudaIdentificacion">
                    <?= htmlspecialchars(isset($tiposIdentificacion[$tipoElegido])
                        ? $tiposIdentificacion[$tipoElegido]['ayuda']
                        : 'Primero seleccione el tipo') ?>
                </p>
                <?= $mensajeError('numeroIdentificacion') ?>
            </div>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>Contacto</h2>

        <div class="filaCampos">
            <div class="<?= $claseCampo('telefono') ?>">
                <label for="telefono">Teléfono o celular <span class="obligatorio">*</span></label>
                <input type="tel" id="telefono" name="telefono" maxlength="15" required inputmode="tel"
                       data-regla="telefono" data-mensaje-requerido="Ingrese el teléfono"
                       value="<?= $valor('telefono') ?>">
                <p class="textoAyuda">8 dígitos, por ejemplo 88451290</p>
                <?= $mensajeError('telefono') ?>
            </div>
            <div class="<?= $claseCampo('correo') ?>">
                <label for="correo">Correo <span class="obligatorio">*</span></label>
                <input type="email" id="correo" name="correo" maxlength="150" required autocomplete="off"
                       data-regla="correoGeneral" data-mensaje-requerido="Ingrese el correo"
                       value="<?= $valor('correo') ?>">
                <p class="textoAyuda">Por ejemplo ventas@proveedor.com</p>
                <?= $mensajeError('correo') ?>
            </div>
        </div>
    </section>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase . $rutaCancelar ?>" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton"><?= $esNuevo ? 'Registrar proveedor' : 'Guardar cambios' ?></button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>