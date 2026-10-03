<?php
/**
 * Paginación para cualquier lista. La vista define estas variables y después la incluye:
 *   require __DIR__ . '/../Plantilla/paginacion.php';
 *
 * @var int $pagina Página actual
 * @var int $totalPaginas
 * @var int $total Cantidad de registros que cumplen los filtros
 * @var int $porPagina
 * @var string $rutaPaginacion Ruta de la lista, por ejemplo '/vendedores'
 * @var array $parametrosPaginacion Filtros que se conservan al cambiar de página, por ejemplo ['busqueda' => 'ana']
 * @var string $nombreRegistros Palabra del resumen, por ejemplo 'vendedores'
 * @var string $urlBase Viene de encabezado.php
 */

// Enlace a otra página con los mismos filtros; los filtros vacíos no se agregan a la dirección
$enlacePagina = fn (int $numero): string => $urlBase . $rutaPaginacion . '?' . http_build_query(array_filter(
    $parametrosPaginacion + ['pagina' => $numero],
    fn ($valor): bool => $valor !== '' && $valor !== null
));

// Se muestran la primera, la última y las vecinas de la actual: 1 … 4 5 6 … 12
$numeros = array_unique(array_filter(
    [1, $pagina - 1, $pagina, $pagina + 1, $totalPaginas],
    fn (int $numero): bool => $numero >= 1 && $numero <= $totalPaginas
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
                        <a href="<?= htmlspecialchars($enlacePagina($numero)) ?>" aria-label="Página <?= $numero ?>"><?= $numero ?></a>
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