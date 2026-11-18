<?php

use Aplicacion\Nucleo\Icono;

$titulo = 'Agregar componente';
$paginaActual = 'inventario';
$volverA = ['texto' => 'Inventario', 'ruta' => '/inventario'];
require __DIR__ . '/../Plantilla/encabezado.php';
?>

<p class="subtitulo">¿De qué tipo es el componente? Según el tipo, el formulario le pedirá sus datos.</p>

<ol class="pasosComponente" aria-label="Pasos">
    <li class="pasoComponenteActual"><span aria-hidden="true">1</span> Escoja el tipo</li>
    <li><span aria-hidden="true">2</span> Llene los datos</li>
</ol>

<div class="tarjetasEleccion">
    <?php foreach ($tipos as $tipo): ?>
        <a href="<?= $urlBase ?>/componentes/nuevo?tipo=<?= $tipo->getIdTipoComponente() ?>" class="tarjetaEleccion">
            <span class="iconoEleccion" aria-hidden="true"><?= Icono::svg('inventario') ?></span>
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
            <span class="tarjetaEleccionAccion">Escoger</span>
        </a>
    <?php endforeach; ?>
    <a href="<?= $urlBase ?>/tipos-componente/nuevo" class="tarjetaEleccion tarjetaEleccionNueva">
        <span class="masEleccion" aria-hidden="true">+</span>
        <span class="tarjetaEleccionNombre">Crear un tipo nuevo</span>
        <span class="textoAyuda">Si no está el que necesita</span>
    </a>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>