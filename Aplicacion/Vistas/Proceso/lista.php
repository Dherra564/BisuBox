<?php
/**
 *
 * @var \Aplicacion\Modelos\Proceso[] $procesos
 * @var string $busqueda
 * @var string $estado
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Formato;

$titulo = 'Procesos';
$paginaActual = 'procesos';
$botonAccion = ['texto' => 'Nuevo proceso', 'ruta' => '/procesos/nuevo'];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayFiltros = $busqueda !== '' || $estado !== '';
?>

<form method="get" action="<?= $urlBase ?>/procesos" class="filtros">
    <div class="campo">
        <label for="busqueda">Buscar</label>
        <input type="search" id="busqueda" name="busqueda" maxlength="100"
               placeholder="Nombre o descripción" value="<?= htmlspecialchars($busqueda) ?>">
    </div>
    <div class="campo">
        <label for="estado">Estado</label>
        <select id="estado" name="estado">
            <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todos</option>
            <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option>
            <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
    </div>
    <div class="filtrosBotones">
        <button type="submit" class="boton">Buscar</button>
        <?php if ($hayFiltros): ?>
            <a href="<?= $urlBase ?>/procesos" class="boton botonSecundario">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($procesos === []): ?>
    <div class="estadoVacio">
        <?php if ($hayFiltros): ?>
            <p>No se encontraron procesos con esos filtros.</p>
        <?php else: ?>
            <p>No hay procesos registrados.</p>
            <p><a href="<?= $urlBase ?>/procesos/nuevo" class="boton">Registrar el primero</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="tablaContenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Tiempo estimado</th>
                    <th>Mano de obra</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($procesos as $proceso): ?>
                    <?php
                    $nombre = htmlspecialchars($proceso->getNombre());
                    $estaActivo = $proceso->getActivo();
                    ?>
                    <tr>
                        <td data-etiqueta="Nombre"><?= $nombre ?></td>
                        <td data-etiqueta="Descripción"><?= $proceso->getDescripcion() !== '' ? htmlspecialchars($proceso->getDescripcion()) : '—' ?></td>
                        <td data-etiqueta="Tiempo estimado"><?= Formato::minutos($proceso->getTiempoEstimado()) ?></td>
                        <td data-etiqueta="Mano de obra"><?= Formato::colones($proceso->getCostoManoObra()) ?></td>
                        <td data-etiqueta="Estado">
                            <?php if ($estaActivo): ?>
                                <span class="etiqueta etiquetaActivo">Activo</span>
                            <?php else: ?>
                                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-etiqueta="Acciones" class="acciones">
                            <a href="<?= $urlBase ?>/procesos/detalle?id=<?= (int) $proceso->getIdProceso() ?>">Ver</a>
                            <a href="<?= $urlBase ?>/procesos/editar?id=<?= (int) $proceso->getIdProceso() ?>">Editar</a>
                            <form method="post" action="<?= $urlBase ?>/procesos/estado" class="formularioEnLinea"
                                  data-confirmar="<?= $estaActivo
                                      ? "¿Desea desactivar el proceso {$nombre}? No aparecerá al armar productos nuevos."
                                      : "¿Desea activar el proceso {$nombre}? Podrá usarse de nuevo en los productos." ?>"
                                  data-titulo="<?= $estaActivo ? 'Desactivar proceso' : 'Activar proceso' ?>"
                                  data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>"
                                  <?= $estaActivo ? 'data-peligro' : '' ?>>
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $proceso->getIdProceso() ?>">
                                <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
                                <button type="submit" class="botonEnlace">
                                    <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $rutaPaginacion = '/procesos';
    $parametrosPaginacion = ['busqueda' => $busqueda, 'estado' => $estado];
    $nombreRegistros = 'procesos';
    require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>