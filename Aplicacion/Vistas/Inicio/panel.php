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
        'texto' => 'Carrito',
        'permiso' => 'carrito.usar',
        'ruta' => '/carrito',
        'descripcion' => 'Revise lo que escogió y envíe su pedido a cada tienda.'
    ],
    [
        'texto' => 'Mis pedidos',
        'permiso' => 'misPedidos.ver',
        'ruta' => '/mis-pedidos',
        'descripcion' => 'Vea en qué va cada pedido y cancélelo si la tienda todavía no lo confirma.'
    ],
    [
        'texto' => 'Pedidos',
        'permiso' => 'pedidos.gestionar',
        'ruta' => '/pedidos',
        'descripcion' => 'Confirme los pedidos de sus clientes y márquelos como entregados.'
    ],
    [
        'texto' => 'Clientes',
        'permiso' => 'clientes.ver',
        'ruta' => '/clientes',
        'descripcion' => 'Vea quién le ha comprado, cuántos pedidos le ha hecho y cómo contactarle.'
    ],
    [
        'texto' => 'Mi perfil',
        'permiso' => 'perfil.ver',
        'ruta' => '/perfil',
        'descripcion' => 'Edite sus datos y cambie su contraseña.'
    ],
    [
        'texto' => 'Mi tienda',
        'permiso' => 'tienda.gestionar',
        'ruta' => '/tienda',
        'descripcion' => 'Cambie el nombre, el logo y los contactos de su tienda.'
    ],
    [
        'texto' => 'Inventario',
        'permiso' => 'inventario.gestionar',
        'ruta' => '/inventario',
        'descripcion' => 'Vea qué componentes tiene, cuánto le queda de cada uno y cuánto vale su inventario.'
    ],
    [
        'texto' => 'Proveedores',
        'permiso' => 'proveedores.gestionar',
        'ruta' => '/proveedores',
        'descripcion' => 'Registre a quién le compra sus materiales.'
    ],
    [
        'texto' => 'Historial de sesiones',
        'permiso' => 'sesiones.ver',
        'ruta' => '/sesiones',
        'descripcion' => 'Consulte cuándo ha ingresado al sistema.'
    ],
    [
        'texto' => 'Productos',
        'permiso' => 'productos.gestionar',
        'ruta' => '/productos',
        'descripcion' => 'Registre lo que hace: se descuentan los componentes y el sistema le ayuda a ponerle precio.'
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