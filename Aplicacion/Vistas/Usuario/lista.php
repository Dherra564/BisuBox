<?php
/**
 *
 * @var \Aplicacion\Modelos\Usuario[] $usuarios
 * @var string $busqueda
 * @var string $rol
 * @var string $estado
 * @var int|null $idUsuarioActual
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Rol;

$titulo = 'Usuarios';
$paginaActual = 'usuarios';
$botonAccion = ['texto' => 'Nuevo usuario', 'ruta' => '/usuarios/nuevo'];
require __DIR__ . '/../Plantilla/encabezado.php';

$hayFiltros = $busqueda !== '' || $rol !== '' || $estado !== '';

$formatoTelefono = fn (?string $telefono): string => strlen((string) $telefono) === 8
    ? substr($telefono, 0, 4) . '-' . substr($telefono, 4)
    : (string) $telefono;
?>

<form method="get" action="<?= $urlBase ?>/usuarios" class="filtros">
    <div class="campo">
        <label for="busqueda">Buscar</label>
        <input type="search" id="busqueda" name="busqueda" maxlength="100"
               placeholder="Nombre, correo o identificación" value="<?= htmlspecialchars($busqueda) ?>">
    </div>
    <div class="campo">
        <label for="rol">Rol</label>
        <select id="rol" name="rol">
            <option value="" <?= $rol === '' ? 'selected' : '' ?>>Todos</option>
            <?php foreach (Rol::todos() as $codigo => $datosRol): ?>
                <option value="<?= $codigo ?>" <?= $rol === $codigo ? 'selected' : '' ?>>
                    <?= htmlspecialchars($datosRol['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
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
            <a href="<?= $urlBase ?>/usuarios" class="boton botonSecundario">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($usuarios === []): ?>
    <div class="estadoVacio">
        <?php if ($hayFiltros): ?>
            <p>No se encontraron usuarios con esos filtros.</p>
        <?php else: ?>
            <p>No hay usuarios registrados.</p>
            <p><a href="<?= $urlBase ?>/usuarios/nuevo" class="boton">Registrar el primero</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="tablaContenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <?php
                    $nombre = htmlspecialchars((string) $usuario->getNombreCompleto());
                    $estaActivo = $usuario->getEstado();
                    $esUsted = $usuario->getIdUsuario() === $idUsuarioActual;
                    ?>
                    <tr>
                        <td data-etiqueta="Nombre">
                            <?= $nombre ?>
                            <?php if ($esUsted): ?><span class="textoAyuda">(usted)</span><?php endif; ?>
                        </td>
                        <td data-etiqueta="Rol"><span class="etiqueta etiquetaRol"><?= htmlspecialchars(Rol::nombre($usuario->getRol())) ?></span></td>
                        <td data-etiqueta="Contacto">
                            <?= htmlspecialchars((string) $usuario->getCorreoUsuario()) ?>
                            <br><span class="textoAyuda"><?= htmlspecialchars($formatoTelefono($usuario->getNumeroTelefonico())) ?></span>
                        </td>
                        <td data-etiqueta="Estado">
                            <?php if ($estaActivo): ?>
                                <span class="etiqueta etiquetaActivo">Activo</span>
                            <?php else: ?>
                                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-etiqueta="Acciones" class="acciones">
                            <a href="<?= $urlBase ?>/usuarios/detalle?id=<?= (int) $usuario->getIdUsuario() ?>">Ver</a>
                            <a href="<?= $urlBase ?>/usuarios/editar?id=<?= (int) $usuario->getIdUsuario() ?>">Editar</a>
                            <?php if (!$esUsted): ?>
                            <form method="post" action="<?= $urlBase ?>/usuarios/estado" class="formularioEnLinea"
                                  data-confirmar="<?= $estaActivo
                                      ? "¿Desea desactivar a {$nombre}? No podrá iniciar sesión hasta que se active de nuevo."
                                      : "¿Desea activar a {$nombre}? Podrá iniciar sesión de nuevo." ?>"
                                  data-titulo="<?= $estaActivo ? 'Desactivar usuario' : 'Activar usuario' ?>"
                                  data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>"
                                  <?= $estaActivo ? 'data-peligro' : '' ?>>
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $usuario->getIdUsuario() ?>">
                                <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
                                <button type="submit" class="botonEnlace">
                                    <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $rutaPaginacion = '/usuarios';
    $parametrosPaginacion = ['busqueda' => $busqueda, 'rol' => $rol, 'estado' => $estado];
    $nombreRegistros = 'usuarios';
    require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>