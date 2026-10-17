<?php

use Configuracion\Configuracion;

$scriptsExtra = $scriptsExtra ?? [];
?>
</div>
</main>
</div>

<?php require __DIR__ . '/modalConfirmacion.php'; ?>

<script src="<?= Configuracion::recurso('js/alertas.js') ?>"></script>
<script src="<?= Configuracion::recurso('js/validaciones.js') ?>"></script>
<?php foreach ($scriptsExtra as $script): ?>
    <script src="<?= htmlspecialchars(Configuracion::recurso('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>

</html>