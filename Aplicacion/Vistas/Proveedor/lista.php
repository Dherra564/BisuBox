<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Icono;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Proveedores';
$paginaActual = 'proveedores';
$botonesAccion = [
    ['texto' => 'Líneas de producto', 'ruta' => '/lineas', 'secundario' => true],
    ['texto' => 'Compras a proveedores', 'ruta' => '/compras-proveedor', 'secundario' => true],
    ['texto' => '+ Nuevo proveedor', 'ruta' => '/proveedores/nuevo'],
];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayFiltros = $busqueda !== '' || $estado !== '' || $idLinea !== null;
$nombresLineas = fn(int $idProveedor): array => array_map(
    fn(int $id): string => $lineas[$id]->getNombre(),
    array_filter($lineasProveedor[$idProveedor] ?? [], fn(int $id): bool => isset($lineas[$id]))
);

$formatoTelefono = fn(?string $telefono): string => strlen((string) $telefono) === 8
    ? substr($telefono, 0, 4) . '-' . substr($telefono, 4)
    : (string) $telefono;
$iniciales = function (string $nombre): string {
    $partes = array_slice(preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY), 0, 2);
    $letras = implode('', array_map(fn(string $parte): string => mb_substr($parte, 0, 1, 'UTF-8'), $partes));
    return mb_strtoupper($letras !== '' ? $letras : '?', 'UTF-8');
};
?>

<p class="introPagina">Las personas y empresas a las que les compra materiales.</p>

<section class="panelCompras panelProveedores">
    <?php if ($hayProveedores): ?>
        <form method="get" action="<?= $urlBase ?>/proveedores" class="filtros">
            <div class="campo">
                <label for="busqueda">Buscar</label>
                <input type="search" id="busqueda" name="busqueda" maxlength="100"
                       placeholder="Nombre, teléfono o correo" value="<?= htmlspecialchars($busqueda) ?>">
            </div>
            <div class="campo">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <?php if ($lineas !== []): ?>
                <div class="campo">
                    <label for="linea">Línea</label>
                    <select id="linea" name="linea">
                        <option value="">Todas</option>
                        <?php foreach ($lineas as $linea): ?>
                            <option value="<?= $linea->getIdLinea() ?>" <?= $idLinea === $linea->getIdLinea() ? 'selected' : '' ?>>
                                <?= htmlspecialchars($linea->getNombre()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
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
                <a href="<?= $urlBase ?>/proveedores/nuevo" class="boton">Registrar el primero</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla tablaProveedores">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th><span class="soloLectores">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proveedores as $proveedor): ?>
                        <?php
                        $nombre = htmlspecialchars((string) $proveedor->getNombre());
                        $estaActivo = $proveedor->getEstado();
                        $idProveedor = (int) $proveedor->getIdProveedor();
                        $lineasDelProveedor = $nombresLineas($idProveedor);
                        $whatsApp = WhatsApp::enlace((string) $proveedor->getTelefono(), 'Hola, le escribo de mi tienda en BisuBox.');
                        ?>
                        <tr class="<?= $estaActivo ? '' : 'filaInactiva' ?>">
                            <td data-etiqueta="Proveedor">
                                <span class="personaCliente">
                                    <span class="inicialesCliente" aria-hidden="true"><?= htmlspecialchars($iniciales((string) $proveedor->getNombre())) ?></span>
                                    <span>
                                        <a href="<?= $urlBase ?>/proveedores/detalle?id=<?= $idProveedor ?>" class="nombreProveedor"><?= $nombre ?></a>
                                        <?php if ($lineasDelProveedor !== []): ?>
                                            <span class="lineasProveedor">
                                                <?php foreach ($lineasDelProveedor as $nombreLinea): ?>
                                                    <span class="chipLinea"><?= htmlspecialchars($nombreLinea) ?></span>
                                                <?php endforeach; ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                            </td>
                            <td data-etiqueta="Contacto">
                                <span class="contactoProveedor">
                                    <?php if ($whatsApp !== null): ?>
                                        <a href="<?= htmlspecialchars($whatsApp) ?>" target="_blank" rel="noopener" class="telefonoCliente">
                                            <span class="botonIconoWhatsApp" aria-hidden="true"><?= Icono::svg('whatsapp') ?></span>
                                            <?= htmlspecialchars($formatoTelefono($proveedor->getTelefono())) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($formatoTelefono($proveedor->getTelefono())) ?>
                                    <?php endif; ?>
                                    <span class="textoAyuda"><?= htmlspecialchars((string) $proveedor->getCorreo()) ?></span>
                                </span>
                            </td>
                            <td data-etiqueta="Estado">
                                <?php if ($estaActivo): ?>
                                    <span class="etiqueta etiquetaActivo">Activo</span>
                                <?php else: ?>
                                    <span class="etiqueta etiquetaInactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td data-etiqueta="" class="acciones accionesInventario">
                                <a href="<?= $urlBase ?>/proveedores/detalle?id=<?= $idProveedor ?>" class="boton botonPequeno">Ver</a>
                                <a href="<?= $urlBase ?>/proveedores/editar?id=<?= $idProveedor ?>" class="enlaceAccion">Editar</a>
                                <form method="post" action="<?= $urlBase ?>/proveedores/estado" class="formularioEnLinea"
                                      data-confirmar="<?= $estaActivo
                                          ? "¿Desea desactivar a {$nombre}? Ya no podrá elegirlo en compras nuevas."
                                          : "¿Desea activar a {$nombre}? Podrá elegirlo de nuevo en sus compras." ?>"
                                      data-titulo="<?= $estaActivo ? 'Desactivar proveedor' : 'Activar proveedor' ?>"
                                      data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>"
                                      <?= $estaActivo ? 'data-peligro' : '' ?>>
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="id" value="<?= $idProveedor ?>">
                                    <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
                                    <button type="submit" class="enlaceAccion <?= $estaActivo ? 'enlaceAccionPeligro' : '' ?>">
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
        $rutaPaginacion = '/proveedores';
        $parametrosPaginacion = ['busqueda' => $busqueda, 'estado' => $estado, 'linea' => $idLinea];
        $nombreRegistros = 'proveedores';
        require __DIR__ . '/../Plantilla/paginacion.php';
        ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>