<?php
/**
 * @var string $urlBase
 * @var string[]|null $scriptsExtra
 */

use Configuracion\Configuracion;

// Si la página usó el diseño de la tienda, también usa su pie
if (!empty($usaDisenoTienda)) {
    require __DIR__ . '/pieTienda.php';
    return;
}

$scriptsExtra = $scriptsExtra ?? [];
?>
            </main>
        </div>
    </div>

    <?php require __DIR__ . '/modalConfirmacion.php'; ?>

    <script src="<?= Configuracion::recurso('js/alertas.js') ?>"></script>
    <script src="<?= Configuracion::recurso('js/validaciones.js') ?>"></script>
    <?php foreach ($scriptsExtra as $script): ?>
        <script src="<?= htmlspecialchars(Configuracion::recurso('js/' . $script)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>