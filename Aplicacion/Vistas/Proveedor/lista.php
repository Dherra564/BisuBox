<?php
/**
 * @var \Aplicacion\Modelos\Proveedor[] $proveedores
 * @var string $busqueda
 * @var string $estado
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var bool $hayProveedores
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\TipoIdentificacion;

$titulo = 'Proveedores';
$paginaActual = 'proveedores';
$botonAccion = ['texto' => 'Nuevo proveedor', 'ruta' => '/proveedores/nuevo'];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayFiltros = $busqueda !== '' || $estado !== '';

$formatoTelefono = fn(?string $telefono): string => strlen((string) $telefono) === 8
    ? substr($telefono, 0, 4) . '-' . substr($telefono, 4)
    : (string) $telefono;
?>

<?php if ($hayProveedores): ?>
    <form method="get" action="<?= $urlBase ?>/proveedores" class="filtros">
        <div class="campo">
            <label for="busqueda">Buscar</label>
            <input type="search" id="busqueda" name="busqueda" maxlength="100"
                   placeholder="Nombre, identificación, teléfono o correo" value="<?= htmlspecialchars($busqueda) ?>">
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
                <a href="<?= $urlBase ?>/proveedores" class="boton botonSecundario">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<?php if ($proveedores === []): ?>
    <div class="estadoVacio">
        <?php if ($hayFiltros): ?>
            <p>No se encontraron proveedores con esos filtros.</p>
        <?php else: ?>
            <p>Todavía no tiene proveedores registrados.</p>
            <p><a href="<?= $urlBase ?>/proveedores/nuevo" class="boton">Registrar el primero</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="tablaContenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Identificación</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proveedores as $proveedor): ?>
                    <?php
                    $nombre = htmlspecialchars((string) $proveedor->getNombre());
                    $estaActivo = $proveedor->getEstado();
                    ?>
                    <tr>
                        <td data-etiqueta="Nombre"><?= $nombre ?></td>
                        <td data-etiqueta="Identificación">
                            <span>
                                <?= htmlspecialchars(TipoIdentificacion::formatear($proveedor->getTipoIdentificacion(), $proveedor->getNumeroIdentificacion())) ?>
                                <br><span class="textoAyuda"><?= htmlspecialchars(TipoIdentificacion::nombre($proveedor->getTipoIdentificacion())) ?></span>
                            </span>
                        </td>
                        <td data-etiqueta="Contacto">
                            <span>
                                <?= htmlspecialchars($formatoTelefono($proveedor->getTelefono())) ?>
                                <br><span class="textoAyuda"><?= htmlspecialchars((string) $proveedor->getCorreo()) ?></span>
                            </span>
                        </td>
                        <td data-etiqueta="Estado">
                            <?php if ($estaActivo): ?>
                                <span class="etiqueta etiquetaActivo">Activo</span>
                            <?php else: ?>
                                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-etiqueta="Acciones" class="acciones">
                            <a href="<?= $urlBase ?>/proveedores/detalle?id=<?= (int) $proveedor->getIdProveedor() ?>">Ver</a>
                            <a href="<?= $urlBase ?>/proveedores/editar?id=<?= (int) $proveedor->getIdProveedor() ?>">Editar</a>
                            <form method="post" action="<?= $urlBase ?>/proveedores/estado" class="formularioEnLinea"
                                  data-confirmar="<?= $estaActivo
                                      ? "¿Desea desactivar a {$nombre}? Ya no podrá elegirlo en compras nuevas."
                                      : "¿Desea activar a {$nombre}? Podrá elegirlo de nuevo en sus compras." ?>"
                                  data-titulo="<?= $estaActivo ? 'Desactivar proveedor' : 'Activar proveedor' ?>"
                                  data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>"
                                  <?= $estaActivo ? 'data-peligro' : '' ?>>
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $proveedor->getIdProveedor() ?>">
                                <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
                                <button type="submit" class="botonEnlace"><?= $estaActivo ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $rutaPaginacion = '/proveedores';
    $parametrosPaginacion = ['busqueda' => $busqueda, 'estado' => $estado];
    $nombreRegistros = 'proveedores';
    require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>