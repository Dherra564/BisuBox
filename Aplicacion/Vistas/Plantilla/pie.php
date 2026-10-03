<?php
/**
 * Final de todas las paginas. Cada vista lo incluye al final:
 *   <?php require __DIR__ . '/../Plantilla/pie.php'; ?>
 *
 * @var string $urlBase Viene de encabezado.php
 */
?>
            </main>
        </div>
    </div>

      <?php require __DIR__ . '/modalConfirmacion.php'; ?>

    <script src="<?= $urlBase ?>/js/alertas.js"></script>
    <script src="<?= $urlBase ?>/js/validaciones.js"></script>
</body>
</html>