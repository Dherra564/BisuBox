<?php
/**
 *
 * @var \Aplicacion\Modelos\Vendedor[] $vendedores
 * @var string $busqueda
 * @var string $estado
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;

$titulo = 'Vendedores';
$paginaActual = 'vendedores';
$botonAccion = ['texto' => 'Nuevo vendedor', 'ruta' => '/vendedores/nuevo'];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayFiltros = $busqueda !== '' || $estado !== '';

$formatoTelefono = fn (?string $telefono): string => strlen((string) $telefono) === 8
    ? substr($telefono, 0, 4) . '-' . substr($telefono, 4)
    : (string) $telefono;
?>

<form method="get" action="<?= $urlBase ?>/vendedores" class="filtros">
    <div class="campo">
        <label for="busqueda">Buscar</label>
        <input type="search" id="busqueda" name="busqueda" maxlength="100"
               placeholder="Nombre, correo o identificación" value="<?= htmlspecialchars($busqueda) ?>">
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
            <a href="<?= $urlBase ?>/vendedores" class="boton botonSecundario">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($vendedores === []): ?>
    <div class="estadoVacio">
        <?php if ($hayFiltros): ?>
            <p>No se encontraron vendedores con esos filtros.</p>
        <?php else: ?>
            <p>No hay vendedores registrados.</p>
            <p><a href="<?= $urlBase ?>/vendedores/nuevo" class="boton">Registrar el primero</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="tablaContenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Registro</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendedores as $vendedor): ?>
                    <?php
                    $nombre = htmlspecialchars((string) $vendedor->getNombreCompleto());
                    $estaActivo = $vendedor->getEstadoVendedor();
                    ?>
                    <tr>
                        <td data-etiqueta="Nombre"><?= $nombre ?></td>
                        <td data-etiqueta="Correo"><?= htmlspecialchars((string) $vendedor->getCorreoUsuario()) ?></td>
                        <td data-etiqueta="Teléfono"><?= htmlspecialchars($formatoTelefono($vendedor->getNumeroTelefonico())) ?></td>
                        <td data-etiqueta="Registro"><?= $vendedor->getRegistroFechaVendedor()->format('d/m/Y') ?></td>
                        <td data-etiqueta="Estado">
                            <?php if ($estaActivo): ?>
                                <span class="etiqueta etiquetaActivo">Activo</span>
                            <?php else: ?>
                                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-etiqueta="Acciones" class="acciones">
                            <a href="<?= $urlBase ?>/vendedores/detalle?id=<?= (int) $vendedor->getIdVendedor() ?>">Ver</a>
                            <a href="<?= $urlBase ?>/vendedores/editar?id=<?= (int) $vendedor->getIdVendedor() ?>">Editar</a>
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
    $rutaPaginacion = '/vendedores';
    $parametrosPaginacion = ['busqueda' => $busqueda, 'estado' => $estado];
    $nombreRegistros = 'vendedores';
    require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>