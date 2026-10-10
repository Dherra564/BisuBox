<?php
/**
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;

$titulo = 'Inicio';
$paginaActual = 'inicio';
require __DIR__ . '/../Plantilla/encabezado.php';

$accesos = [
    [
        'texto' => 'Mi perfil',
        'permiso' => 'perfil.ver',
        'ruta' => '/perfil',
        'descripcion' => 'Edite sus datos y cambie su contraseña.'
    ],
    [
        'texto' => 'Historial de sesiones',
        'permiso' => 'sesiones.ver',
        'ruta' => '/sesiones',
        'descripcion' => 'Consulte cuándo ha ingresado al sistema.'
    ],
];
$accesos = array_filter($accesos, fn(array $acceso): bool => Permiso::puede($acceso['permiso']));
?>

<p class="subtitulo">Hola, <?= htmlspecialchars((string) UsuarioActual::nombre()) ?>.</p>

<div class="tarjetas">
    <?php foreach ($accesos as $acceso): ?>
        <section class="tarjeta">
            <h2><?= htmlspecialchars($acceso['texto']) ?></h2>
            <p class="textoAyuda"><?= htmlspecialchars($acceso['descripcion']) ?></p>
            <p class="tarjetaAccion">
                <a href="<?= $urlBase . $acceso['ruta'] ?>" class="boton botonSecundario botonPequeno">Abrir</a>
            </p>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>