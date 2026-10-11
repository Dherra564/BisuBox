<?php
/**
 * @var array $periodo
 * @var callable $enlacePeriodo
 */

use Aplicacion\Nucleo\PeriodoFechas;
?>
<div class="filtroFechas">
    <?php if ($periodo['periodo'] !== PeriodoFechas::RANGO): ?>
        <input type="hidden" name="periodo" value="<?= $periodo['periodo'] ?>">
    <?php endif; ?>
    <span class="filtroFechasTitulo">Período</span>
    <div class="chipsPeriodo" role="group" aria-label="Períodos">
        <?php foreach (PeriodoFechas::PERIODOS as $codigo => $nombre): ?>
            <a href="<?= htmlspecialchars($enlacePeriodo($codigo)) ?>"
               class="chipPeriodo <?= $periodo['periodo'] === $codigo ? 'chipElegido' : '' ?>"
               <?= $periodo['periodo'] === $codigo ? 'aria-current="true"' : '' ?>><?= $nombre ?></a>
        <?php endforeach; ?>
    </div>
    <p class="textoAyuda periodoActual">Mostrando: <strong><?= htmlspecialchars(PeriodoFechas::describir($periodo)) ?></strong></p>
</div>
<?php
?>
<div class="campo campoFecha">
    <label for="desde">Desde</label>
    <input type="date" id="desde" name="desde" value="<?= $periodo['desde']?->format('Y-m-d') ?>">
</div>
<div class="campo campoFecha">
    <label for="hasta">Hasta</label>
    <input type="date" id="hasta" name="hasta" value="<?= $periodo['hasta']?->format('Y-m-d') ?>">
</div>