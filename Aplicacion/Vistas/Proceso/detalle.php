<?php
/**
 * @var \Aplicacion\Modelos\Proceso $proceso
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;

$titulo = 'Detalle del proceso';
$paginaActual = 'procesos';
require __DIR__ . '/../Plantilla/encabezado.php';

$nombre = htmlspecialchars((string) $proceso->getNombre());
$estaActivo = $proceso->getEstado();
?>

<p><a href="<?= $urlBase ?>/procesos">← Procesos</a></p>

<section class="formularioSeccion">
    <h2><?= $nombre ?></h2>
    <dl class="listaDatos">
        <dt>Descripción</dt>
        <dd><?= $proceso->getDescripcion() !== null ? htmlspecialchars($proceso->getDescripcion()) : 'Sin descripción' ?></dd>

        <dt>Tiempo estimado</dt>
        <dd><?= Numero::duracion((int) $proceso->getTiempoEstimado()) ?></dd>

        <dt>Costo de mano de obra</dt>
        <dd><?= Numero::moneda((float) $proceso->getCostoManoObra()) ?></dd>

        <dt>Estado</dt>
        <dd>
            <?php if ($estaActivo): ?>
                <span class="etiqueta etiquetaActivo">Activo</span>
            <?php else: ?>
                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                <p class="textoAyuda">Está desactivado. Puede activarlo cuando quiera.</p>
            <?php endif; ?>
        </dd>
    </dl>

    <div class="grupoBotones">
        <a href="<?= $urlBase ?>/procesos/editar?id=<?= (int) $proceso->getIdProceso() ?>" class="boton">Editar</a>
        <form method="post" action="<?= $urlBase ?>/procesos/estado" class="formularioEnLinea"
              data-confirmar="<?= $estaActivo
                  ? "¿Desea desactivar el proceso «{$nombre}»? No se borra y podrá activarlo cuando quiera."
                  : "¿Desea activar el proceso «{$nombre}»?" ?>"
              data-titulo="<?= $estaActivo ? 'Desactivar proceso' : 'Activar proceso' ?>"
              data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>" <?= $estaActivo ? 'data-peligro' : '' ?>>
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= (int) $proceso->getIdProceso() ?>">
            <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
            <input type="hidden" name="volver" value="detalle">
            <button type="submit" class="boton <?= $estaActivo ? 'botonPeligro' : 'botonSecundario' ?>">
                <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
            </button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>