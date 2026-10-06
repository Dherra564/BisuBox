<?php
/**
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var string $rutaPaginacion
 * @var array $parametrosPaginacion
 * @var string $nombreRegistros
 * @var string $urlBase
 */

$enlacePagina = fn(int $numero): string => $urlBase . $rutaPaginacion . '?' . http_build_query(array_filter(
    $parametrosPaginacion + ['pagina' => $numero],
    fn($valor): bool => $valor !== '' && $valor !== null
));

$numeros = array_unique(array_filter(
    [1, $pagina - 1, $pagina, $pagina + 1, $totalPaginas],
    fn(int $numero): bool => $numero >= 1 && $numero <= $totalPaginas
));
sort($numeros);

$desde = $total === 0 ? 0 : ($pagina - 1) * $porPagina + 1;
$hasta = min($pagina * $porPagina, $total);
$anterior = 0;
?>
<nav class="paginacion" aria-label="Páginas">
    <span>Mostrando <?= $desde ?> a <?= $hasta ?> de <?= $total ?> <?= htmlspecialchars($nombreRegistros) ?></span>

    <?php if ($totalPaginas > 1): ?>
        <ul class="paginacionEnlaces">
            <?php if ($pagina > 1): ?>
                <li><a href="<?= htmlspecialchars($enlacePagina($pagina - 1)) ?>">‹ Anterior</a></li>
            <?php endif; ?>

            <?php foreach ($numeros as $numero): ?>
                <?php if ($numero - $anterior > 1): ?>
                    <li><span class="paginaSeparador" aria-hidden="true">…</span></li>
                <?php endif; ?>
                <li>
                    <?php if ($numero === $pagina): ?>
                        <span class="paginaActual" aria-current="page"><?= $numero ?></span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($enlacePagina($numero)) ?>"
                            aria-label="Página <?= $numero ?>"><?= $numero ?></a>
                    <?php endif; ?>
                </li>
                <?php $anterior = $numero; ?>
            <?php endforeach; ?>

            <?php if ($pagina < $totalPaginas): ?>
                <li><a href="<?= htmlspecialchars($enlacePagina($pagina + 1)) ?>">Siguiente ›</a></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</nav>