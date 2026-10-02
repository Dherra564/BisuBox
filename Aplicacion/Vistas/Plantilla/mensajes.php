<?php
/**
 * Muestra los mensajes de exito, error y advertencia (los de la clase Mensaje).
 * Cada uno lleva icono y texto, no solo color, para que se entienda igual sin distinguir colores.
 * La plantilla lo incluye sola; no hace falta llamarlo desde las vistas.
 *
 * @var array $mensajes Viene de encabezado.php
 */

use Aplicacion\Nucleo\Mensaje;

$iconos = [
    Mensaje::EXITO => '✔',
    Mensaje::ERROR => '✖',
    Mensaje::ADVERTENCIA => '⚠',
];
$titulos = [
    Mensaje::EXITO => 'Listo',
    Mensaje::ERROR => 'Error',
    Mensaje::ADVERTENCIA => 'Atención',
];
?>
<?php foreach ($mensajes as $mensaje): ?>
    <?php $tipo = isset($iconos[$mensaje['tipo']]) ? $mensaje['tipo'] : Mensaje::ADVERTENCIA; ?>
    <div class="alerta alerta<?= ucfirst($tipo) ?>" role="<?= $tipo === Mensaje::ERROR ? 'alert' : 'status' ?>">
        <span class="alertaIcono" aria-hidden="true"><?= $iconos[$tipo] ?></span>
        <p><strong><?= $titulos[$tipo] ?>:</strong> <?= htmlspecialchars($mensaje['texto']) ?></p>
        <button type="button" class="alertaCerrar" aria-label="Cerrar mensaje">×</button>
    </div>
<?php endforeach; ?>