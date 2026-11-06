<?php

use Aplicacion\Nucleo\CalculoDiseno;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;

$avisoDiseno = $avisoDiseno ?? null;
$quitarDiseno = $quitarDiseno ?? false;
$nombreDiseno = CalculoDiseno::nombreDiseno($disenoLinea['tipo']);
?>
<div class="lineaDiseno <?= $avisoDiseno !== null ? 'filaConAviso' : '' ?>">
    <canvas class="miniaturaDiseno fondoCuadros" width="560" height="560" role="img"
        aria-label="Vista de la <?= htmlspecialchars(mb_strtolower($nombreDiseno, 'UTF-8')) ?>"
        data-diseno="<?= htmlspecialchars(CalculoDiseno::datosDibujo($disenoLinea, $urlBase)) ?>"></canvas>
    <div class="textoDiseno">
        <strong><?= htmlspecialchars($nombreDiseno) ?></strong>
        <span class="textoAyuda"><?= htmlspecialchars(CalculoDiseno::descripcion($disenoLinea)) ?></span>
        <ul class="materialesDiseno">
            <?php foreach (CalculoDiseno::materiales($disenoLinea) as $material): ?>
                <li><?= htmlspecialchars($material) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($avisoDiseno !== null): ?>
            <span class="errorCampo"><?= htmlspecialchars($avisoDiseno) ?></span>
        <?php endif; ?>
    </div>
    <div class="precioLineaDiseno">
        <strong><?= Numero::moneda($precioDiseno) ?></strong>
        <?php if ($quitarDiseno): ?>
            <form method="post" action="<?= $urlBase ?>/carrito/quitar-pieza" class="formularioEnLinea"
                data-titulo="Quitar pieza" data-boton="Quitar" data-peligro
                data-confirmar="¿Quitar la <?= htmlspecialchars(mb_strtolower($nombreDiseno, 'UTF-8')) ?> del carrito? Tendría que armarla de nuevo.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="diseno" value="<?= (int) $disenoLinea['id'] ?>">
                <button type="submit" class="botonEnlace">Quitar</button>
            </form>
        <?php endif; ?>
    </div>
</div>
