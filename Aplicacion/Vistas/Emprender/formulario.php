<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\TipoIdentificacion;
use Aplicacion\Nucleo\Validador;

$titulo = 'Abra su tienda';
$paginaActual = 'emprender';
$scriptsExtra = ['tienda.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$tipoElegido = $datos['tipoIdentificacion'] ?? '';
$contactos = $datos['contactos'] ?? [];
$logoPendiente = $datos['tiendaLogoPendiente'] ?? null;
$telefono = Validador::limpiarTelefono((string) $usuario->getNumeroTelefonico());
?>

<p class="subtitulo">Usamos los datos de su cuenta. Solo falta contarnos de su tienda.</p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= $urlBase ?>/emprender" enctype="multipart/form-data" novalidate
    class="validarFormulario" data-confirmar="¿Abrir su tienda? Su cuenta pasará a ser de vendedor."
    data-titulo="Abrir mi tienda" data-boton="Abrir mi tienda">
    <?= Csrf::campo() ?>

    <section class="formularioSeccion">
        <div class="encabezadoSeccion">
            <h2>Sus datos</h2>
            <span class="textoAyuda">Se toman de su cuenta · <a href="<?= $urlBase ?>/perfil">cambiarlos en Mi
                    perfil</a></span>
        </div>
        <dl class="datosCuenta">
            <div>
                <dt>Nombre</dt>
                <dd><?= htmlspecialchars((string) $usuario->getNombreCompleto()) ?></dd>
            </div>
            <div>
                <dt>Correo</dt>
                <dd><?= htmlspecialchars((string) $usuario->getCorreoUsuario()) ?></dd>
            </div>
            <div>
                <dt>Teléfono</dt>
                <dd><?= strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : htmlspecialchars($telefono) ?>
                </dd>
            </div>
        </dl>

        <div class="filaCampos">
            <div class="<?= $claseCampo('tipoIdentificacion') ?>">
                <label for="tipoIdentificacion">Tipo de identificación <span class="obligatorio">*</span></label>
                <select id="tipoIdentificacion" name="tipoIdentificacion" required
                    data-mensaje-requerido="Seleccione el tipo de identificación">
                    <option value="">Seleccione el tipo</option>
                    <?php foreach (TipoIdentificacion::todos() as $codigo => $tipo): ?>
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
                    data-mensaje-requerido="Ingrese la identificación" value="<?= $valor('numeroIdentificacion') ?>">
                <p class="textoAyuda" id="ayudaIdentificacion">
                    <?= htmlspecialchars(TipoIdentificacion::existe($tipoElegido)
                        ? TipoIdentificacion::todos()[$tipoElegido]['ayuda']
                        : 'Primero seleccione el tipo') ?>
                </p>
                <?= $mensajeError('numeroIdentificacion') ?>
            </div>
        </div>
    </section>

    <section class="formularioSeccion">
        <h2>Su tienda</h2>
        <div class="disenoFormulario">
            <div>
                <div class="<?= $claseCampo('tiendaNombre') ?>">
                    <label for="tiendaNombre">Nombre de la tienda <span class="obligatorio">*</span></label>
                    <input type="text" id="tiendaNombre" name="tiendaNombre" minlength="3" maxlength="100" required
                        data-regla="nombreTienda" data-mensaje-requerido="Ingrese el nombre de la tienda"
                        data-mensaje-largo="El nombre debe tener entre 3 y 100 caracteres"
                        value="<?= $valor('tiendaNombre') ?>">
                    <p class="textoAyuda">Con este nombre se arma el enlace de su tienda</p>
                    <?= $mensajeError('tiendaNombre') ?>
                </div>
                <div class="<?= $claseCampo('tiendaDescripcion') ?>">
                    <label for="tiendaDescripcion">Descripción</label>
                    <textarea id="tiendaDescripcion" name="tiendaDescripcion" rows="3" maxlength="300"
                        data-contador><?= $valor('tiendaDescripcion') ?></textarea>
                    <?= $mensajeError('tiendaDescripcion') ?>
                </div>
            </div>
            <div class="<?= $claseCampo('tiendaLogo') ?>">
                <div class="zonaFoto">
                    <img src="<?= $logoPendiente !== null
                        ? $urlBase . '/fotos/logo?archivo=' . urlencode($logoPendiente)
                        : $urlBase . '/imagenes/logoPorDefecto.svg' ?>" alt="Vista previa del logo"
                        class="fotoPerfilGrande">
                    <label for="tiendaLogo"
                        class="boton botonSecundario"><?= $logoPendiente !== null ? 'Cambiar logo' : 'Elegir logo' ?></label>
                    <input type="file" id="tiendaLogo" name="tiendaLogo" accept="image/jpeg,image/png"
                        class="campoArchivo">
                    <?php if ($logoPendiente !== null): ?>
                        <input type="hidden" name="tiendaLogoPendiente" value="<?= htmlspecialchars($logoPendiente) ?>">
                        <span class="nombreArchivo">Ya eligió este logo. Se guarda al abrir su tienda</span>
                    <?php else: ?>
                        <span class="nombreArchivo">JPG o PNG, máximo 5 MB. Opcional</span>
                    <?php endif; ?>
                </div>
                <?= $mensajeError('tiendaLogo') ?>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/../Plantilla/contactos.php'; ?>

    <div class="alerta alertaAdvertencia" role="note">
        <span class="alertaIcono" aria-hidden="true">⚠</span>
        <p>Al abrir su tienda, su cuenta pasa a ser de <strong>vendedor</strong>. Sigue entrando con el mismo correo y
            contraseña, y puede seguir comprando en otras tiendas.</p>
    </div>

    <div class="grupoBotones accionesFormulario">
        <a href="<?= $urlBase ?>/" class="boton botonSecundario">Cancelar</a>
        <button type="submit" class="boton">Abrir mi tienda</button>
    </div>
</form>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>