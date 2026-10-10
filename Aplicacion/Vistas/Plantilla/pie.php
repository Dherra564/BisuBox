<?php
/**
 * @var string $urlBase
 * @var string[]|null $scriptsExtra  nombres de archivos de Publico/js que necesita la página
 */

$scriptsExtra = $scriptsExtra ?? [];
?>
            </main>
        </div>
    </div>

    <?php require __DIR__ . '/modalConfirmacion.php'; ?>

    <script src="<?= $urlBase ?>/js/alertas.js"></script>
    <script src="<?= $urlBase ?>/js/validaciones.js"></script>
    <?php foreach ($scriptsExtra as $script): ?>
        <script src="<?= $urlBase ?>/js/<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
</body>
</html>