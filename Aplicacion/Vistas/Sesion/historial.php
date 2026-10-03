<?php
/**
 * Historial de sesiones.
 *
 * @var array $sesiones Viene de SesionControlador
 * @var string $urlBase Viene de encabezado.php
 */


$titulo = 'Historial de sesiones';
$paginaActual = 'sesiones';
require __DIR__ . '/../Plantilla/encabezado.php';

$formatearFecha = fn (?string $fecha): string => $fecha !== null
    ? (new DateTime($fecha))->format('d/m/Y H:i')
    : '—';
?>

<p class="subtitulo">Se muestran las últimas 100 sesiones, de la más reciente a la más antigua.</p>

<?php if ($sesiones === []): ?>
    <div class="cajaInformativa">Todavía no hay sesiones registradas.</div>
<?php else: ?>
    <div class="tablaContenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Inicio</th>
                    <th>Cierre</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sesiones as $sesion): ?>
                    <tr>
                        <td data-etiqueta="Usuario">
                            <?= htmlspecialchars($sesion['nombre'] ?? 'Usuario eliminado') ?>
                            <?php if ($sesion['correo'] !== null): ?>
                                <br><span class="textoAyuda"><?= htmlspecialchars($sesion['correo']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-etiqueta="Rol"><?= htmlspecialchars((string) $sesion['tipo']) ?></td>
                        <td data-etiqueta="Inicio"><?= $formatearFecha($sesion['inicio']) ?></td>
                        <td data-etiqueta="Cierre"><?= $formatearFecha($sesion['cierre']) ?></td>
                        <td data-etiqueta="Estado">
                            <?php if ((int) $sesion['abierta'] === 1): ?>
                                <span class="etiqueta etiquetaNormal">Abierta</span>
                            <?php else: ?>
                                <span class="etiqueta etiquetaInactivo">Cerrada</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>