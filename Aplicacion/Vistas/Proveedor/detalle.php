<?php
/**
 * @var \Aplicacion\Modelos\Proveedor $proveedor
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\TipoIdentificacion;

$titulo = 'Detalle del proveedor';
$paginaActual = 'proveedores';
require __DIR__ . '/../Plantilla/encabezado.php';

$nombre = htmlspecialchars((string) $proveedor->getNombre());
$telefono = (string) $proveedor->getTelefono();
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$estaActivo = $proveedor->getEstado();
?>

<p><a href="<?= $urlBase ?>/proveedores">← Proveedores</a></p>

<section class="formularioSeccion">
    <h2><?= $nombre ?></h2>
    <dl class="listaDatos">
        <dt>Identificación</dt>
        <dd>
            <?= htmlspecialchars(TipoIdentificacion::nombre($proveedor->getTipoIdentificacion())) ?>:
            <?= htmlspecialchars(TipoIdentificacion::formatear($proveedor->getTipoIdentificacion(), $proveedor->getNumeroIdentificacion())) ?>
        </dd>

        <dt>Teléfono</dt>
        <dd><?= htmlspecialchars($telefono) ?></dd>

        <dt>Correo</dt>
        <dd><?= htmlspecialchars((string) $proveedor->getCorreo()) ?></dd>

        <dt>Registrado el</dt>
        <dd><?= $proveedor->getFechaRegistro()->format('d/m/Y') ?></dd>

        <dt>Estado</dt>
        <dd>
            <?php if ($estaActivo): ?>
                <span class="etiqueta etiquetaActivo">Activo</span>
            <?php else: ?>
                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                <p class="textoAyuda">No aparece al registrar compras nuevas.</p>
            <?php endif; ?>
        </dd>
    </dl>

    <div class="grupoBotones">
        <a href="<?= $urlBase ?>/proveedores/editar?id=<?= (int) $proveedor->getIdProveedor() ?>" class="boton">Editar</a>
        <form method="post" action="<?= $urlBase ?>/proveedores/estado" class="formularioEnLinea"
              data-confirmar="<?= $estaActivo
                  ? "¿Desea desactivar a {$nombre}? Ya no podrá elegirlo en compras nuevas."
                  : "¿Desea activar a {$nombre}? Podrá elegirlo de nuevo en sus compras." ?>"
              data-titulo="<?= $estaActivo ? 'Desactivar proveedor' : 'Activar proveedor' ?>"
              data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>" <?= $estaActivo ? 'data-peligro' : '' ?>>
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= (int) $proveedor->getIdProveedor() ?>">
            <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
            <input type="hidden" name="volver" value="detalle">
            <button type="submit" class="boton <?= $estaActivo ? 'botonPeligro' : 'botonSecundario' ?>">
                <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
            </button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>