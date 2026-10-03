<?php
/**
 * Panel de inicio según el rol.
 *
 * @var int $totalUsuarios Viene de InicioControlador
 * @var string $urlBase Viene de encabezado.php
 */

use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;

$titulo = 'Inicio';
$paginaActual = 'inicio';
require __DIR__ . '/../Plantilla/encabezado.php';

// Accesos directos: cada rol ve solo los que tiene permitidos
$accesos = [
    ['texto' => 'Mi perfil', 'permiso' => 'perfil.ver', 'ruta' => '/perfil',
     'descripcion' => 'Edite sus datos y cambie su contraseña.'],
    ['texto' => 'Historial de sesiones', 'permiso' => 'sesiones.ver', 'ruta' => '/sesiones',
     'descripcion' => 'Consulte quién ha ingresado al sistema.'],
    ['texto' => 'Vendedores', 'permiso' => 'vendedores.gestionar', 'ruta' => '/vendedores',
     'descripcion' => 'Administre las cuentas de los vendedores.'],
];
$accesos = array_filter($accesos, fn (array $acceso): bool => Permiso::puede($acceso['permiso']));
?>

<p class="subtitulo">Hola, <?= htmlspecialchars((string) UsuarioActual::nombre()) ?>.</p>

<?php if (UsuarioActual::esSuperAdmin()): ?>
    <div class="tarjetas">
        <section class="tarjeta tarjetaDato">
            <h2>Usuarios registrados</h2>
            <p class="tarjetaNumero"><?= (int) $totalUsuarios ?></p>
        </section>
    </div>
<?php endif; ?>

<div class="tarjetas">
    <?php foreach ($accesos as $acceso): ?>
        <section class="tarjeta">
            <h2><?= htmlspecialchars($acceso['texto']) ?></h2>
            <p class="textoAyuda"><?= htmlspecialchars($acceso['descripcion']) ?></p>
            <p style="margin-top: 14px;">
                <a href="<?= $urlBase . $acceso['ruta'] ?>" class="boton botonSecundario botonPequeno">Abrir</a>
            </p>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>