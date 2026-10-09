<?php
/**
 * @var \Aplicacion\Modelos\Proceso $proceso
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Formato;

$titulo = 'Detalle del proceso';
$paginaActual = 'procesos';
require __DIR__ . '/../Plantilla/encabezado.php';

$nombre = htmlspecialchars($proceso->getNombre());
$estaActivo = $proceso->getActivo();
?>

<p><a href="<?= $urlBase ?>/procesos">← Procesos</a></p>

<div class="disenoFormulario">
    <section class="formularioSeccion">
        <h2><?= $nombre ?></h2>
        <dl class="listaDatos">
            <dt>Descripción</dt>
            <dd><?= $proceso->getDescripcion() !== '' ? htmlspecialchars($proceso->getDescripcion()) : '—' ?></dd>

            <dt>Tiempo estimado</dt>
            <dd><?= Formato::minutos($proceso->getTiempoEstimado()) ?></dd>

            <dt>Costo de mano de obra</dt>
            <dd><?= Formato::colones($proceso->getCostoManoObra()) ?></dd>

            <dt>Estado</dt>
            <dd>
                <?php if ($estaActivo): ?>
                    <span class="etiqueta etiquetaActivo">Activo</span>
                <?php else: ?>
                    <span class="etiqueta etiquetaInactivo">Inactivo</span>
                    <p class="textoAyuda">No aparece al armar productos nuevos hasta que se active de nuevo.</p>
                <?php endif; ?>
            </dd>
        </dl>

        <div class="grupoBotones">
            <a href="<?= $urlBase ?>/procesos/editar?id=<?= (int) $proceso->getIdProceso() ?>" class="boton">Editar</a>
            <form method="post" action="<?= $urlBase ?>/procesos/estado" class="formularioEnLinea"
                  data-confirmar="<?= $estaActivo
                      ? "¿Desea desactivar el proceso {$nombre}? No aparecerá al armar productos nuevos."
                      : "¿Desea activar el proceso {$nombre}? Podrá usarse de nuevo en los productos." ?>"
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
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>