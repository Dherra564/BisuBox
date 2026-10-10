<?php
/**
 * @var string $urlBase
 * @var string[]|null $scriptsExtra
 */

$scriptsExtra = $scriptsExtra ?? [];
?>
        </main>
    </div>

    <?php require __DIR__ . '/modalConfirmacion.php'; ?>

    <script src="<?= $urlBase ?>/js/alertas.js"></script>
    <script src="<?= $urlBase ?>/js/validaciones.js"></script>
    <?php foreach ($scriptsExtra as $script): ?>
        <script src="<?= $urlBase ?>/js/<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
</body>

</html>