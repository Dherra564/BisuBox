<?php
/**
 * @var \Aplicacion\Modelos\TipoComponente[] $tipos
 * @var string $urlBase
 */

$titulo = 'Agregar componente';
$paginaActual = 'inventario';
$volverA = ['texto' => 'Inventario', 'ruta' => '/inventario'];
require __DIR__ . '/../Plantilla/encabezado.php';
?>

<p class="subtitulo">¿De qué tipo es el componente? Según el tipo, el formulario le pedirá sus datos.</p>

<div class="tarjetasEleccion">
    <?php foreach ($tipos as $tipo): ?>
        <a href="<?= $urlBase ?>/componentes/nuevo?tipo=<?= $tipo->getIdTipoComponente() ?>" class="tarjetaEleccion">
            <span class="tarjetaEleccionNombre"><?= htmlspecialchars($tipo->getNombre()) ?></span>
            <?php if ($tipo->getAtributosActivos() !== []): ?>
                <ul class="listaChips">
                    <?php foreach ($tipo->getAtributosActivos() as $atributo): ?>
                        <li class="chipDato">
                            <span class="iconoClase iconoClase<?= htmlspecialchars($atributo->getClase()) ?>"
                                aria-hidden="true"></span>
                            <?= htmlspecialchars($atributo->getNombre()) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <span class="textoAyuda">Solo pide los datos comunes</span>
            <?php endif; ?>
            <span class="tarjetaEleccionAccion">Escoger →</span>
        </a>
    <?php endforeach; ?>
</div>

<p class="textoAyuda">
    ¿No está el tipo que necesita? <a href="<?= $urlBase ?>/tipos-componente/nuevo">Cree un tipo nuevo</a>.
</p>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>