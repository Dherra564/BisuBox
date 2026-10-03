<?php
/**
 * Todos los datos de un vendedor, con su foto y sus acciones.
 *
 * @var \Aplicacion\Modelos\Vendedor $vendedor Viene de VendedorControlador
 * @var string $urlBase Viene de encabezado.php
 */

use Aplicacion\Nucleo\Csrf;

$titulo = 'Detalle del vendedor';
$paginaActual = 'vendedores';
require __DIR__ . '/../Plantilla/encabezado.php';

$nombre = htmlspecialchars((string) $vendedor->getNombreCompleto());
$telefono = (string) $vendedor->getNumeroTelefonico();
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$estaActivo = $vendedor->getEstadoVendedor();
?>

<p><a href="<?= $urlBase ?>/vendedores">← Vendedores</a></p>

<div class="disenoFormulario">
    <section class="formularioSeccion">
        <h2><?= $nombre ?></h2>
        <dl class="listaDatos">
            <dt>Identificación</dt>
            <dd><?= htmlspecialchars((string) $vendedor->getNumeroIdentificacion()) ?></dd>

            <dt>Correo</dt>
            <dd><?= htmlspecialchars((string) $vendedor->getCorreoUsuario()) ?></dd>

            <dt>Teléfono</dt>
            <dd><?= htmlspecialchars($telefono) ?></dd>

            <dt>Registrado el</dt>
            <dd><?= $vendedor->getRegistroFechaVendedor()->format('d/m/Y') ?></dd>

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
            <a href="<?= $urlBase ?>/vendedores/editar?id=<?= (int) $vendedor->getIdVendedor() ?>" class="boton">Editar</a>
            <form method="post" action="<?= $urlBase ?>/vendedores/estado" class="formularioEnLinea"
                  data-confirmar="<?= $estaActivo
                      ? "¿Desea desactivar a {$nombre}? No podrá iniciar sesión hasta que se active de nuevo."
                      : "¿Desea activar a {$nombre}? Podrá iniciar sesión de nuevo." ?>"
                  data-titulo="<?= $estaActivo ? 'Desactivar vendedor' : 'Activar vendedor' ?>"
                  data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>"
                  <?= $estaActivo ? 'data-peligro' : '' ?>>
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $vendedor->getIdVendedor() ?>">
                <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
                <input type="hidden" name="volver" value="detalle">
                <button type="submit" class="boton <?= $estaActivo ? 'botonPeligro' : 'botonSecundario' ?>">
                    <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
                </button>
            </form>
        </div>
    </section>

    <aside>
        <section class="formularioSeccion textoCentrado">
            <img src="<?= $urlBase ?>/fotos/perfil?archivo=<?= urlencode((string) ($vendedor->getFotoPerfil() ?? '')) ?>"
                 alt="Foto de perfil de <?= $nombre ?>" class="fotoPerfilGrande">
        </section>
    </aside>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>