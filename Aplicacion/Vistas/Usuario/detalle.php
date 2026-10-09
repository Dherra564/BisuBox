<?php
/**
 * @var \Aplicacion\Modelos\Usuario $usuario
 * @var int|null $idUsuarioActual
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\TipoIdentificacion;

$titulo = 'Detalle del usuario';
$paginaActual = 'usuarios';
require __DIR__ . '/../Plantilla/encabezado.php';

$nombre = htmlspecialchars((string) $usuario->getNombreCompleto());
$telefono = (string) $usuario->getNumeroTelefonico();
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$estaActivo = $usuario->getEstado();
$esUsted = $usuario->getIdUsuario() === $idUsuarioActual;
?>

<p><a href="<?= $urlBase ?>/usuarios">← Usuarios</a></p>

<div class="disenoFormulario">
    <section class="formularioSeccion">
        <h2><?= $nombre ?></h2>
        <dl class="listaDatos">
            <dt>Rol</dt>
            <dd><span class="etiqueta etiquetaRol"><?= htmlspecialchars(Rol::nombre($usuario->getRol())) ?></span></dd>

            <dt>Identificación</dt>
            <dd>
                <?= htmlspecialchars(TipoIdentificacion::nombre($usuario->getTipoIdentificacion())) ?>:
                <?= htmlspecialchars(TipoIdentificacion::formatear($usuario->getTipoIdentificacion(), $usuario->getNumeroIdentificacion())) ?>
            </dd>
            <dt>Correo</dt>
            <dd><?= htmlspecialchars((string) $usuario->getCorreoUsuario()) ?></dd>

            <dt>Teléfono</dt>
            <dd><?= htmlspecialchars($telefono) ?></dd>

            <dt>Registrado el</dt>
            <dd><?= $usuario->getFechaRegistro()->format('d/m/Y') ?></dd>

            <dt>Estado</dt>
            <dd>
                <?php if ($estaActivo): ?>
                    <span class="etiqueta etiquetaActivo">Activo</span>
                <?php else: ?>
                    <span class="etiqueta etiquetaInactivo">Inactivo</span>
                    <p class="textoAyuda">No puede iniciar sesión hasta que se active de nuevo.</p>
                <?php endif; ?>
            </dd>
        </dl>

        <div class="grupoBotones">
            <a href="<?= $urlBase ?>/usuarios/editar?id=<?= (int) $usuario->getIdUsuario() ?>"
                class="boton">Editar</a>
            <?php if ($esUsted): ?>
                <p class="textoAyuda">Esta es su cuenta: no puede desactivarla ni cambiar su rol.</p>
            <?php else: ?>
            <form method="post" action="<?= $urlBase ?>/usuarios/estado" class="formularioEnLinea" data-confirmar="<?= $estaActivo
                  ? "¿Desea desactivar a {$nombre}? No podrá iniciar sesión hasta que se active de nuevo."
                  : "¿Desea activar a {$nombre}? Podrá iniciar sesión de nuevo." ?>"
                data-titulo="<?= $estaActivo ? 'Desactivar usuario' : 'Activar usuario' ?>"
                data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>" <?= $estaActivo ? 'data-peligro' : '' ?>>
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $usuario->getIdUsuario() ?>">
                <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
                <input type="hidden" name="volver" value="detalle">
                <button type="submit" class="boton <?= $estaActivo ? 'botonPeligro' : 'botonSecundario' ?>">
                    <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </section>

    <aside>
        <section class="formularioSeccion textoCentrado">
            <img src="<?= $urlBase ?>/fotos/perfil?archivo=<?= urlencode((string) ($usuario->getFotoPerfil() ?? '')) ?>"
                alt="Foto de perfil de <?= $nombre ?>" class="fotoPerfilGrande">
        </section>
    </aside>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>