<?php
/**
 * @var int $totalUsuarios Viene de InicioControlador
 * @var string $urlBase Viene de encabezado.php
 */
$titulo = 'Inicio';
$paginaActual = 'inicio';
require __DIR__ . '/../Plantilla/encabezado.php';
?>

<div class="tarjetas">
    <section class="tarjeta tarjetaDato">
        <h2>Usuarios registrados</h2>
        <p class="tarjetaNumero"><?= (int) $totalUsuarios ?></p>
    </section>
    <section class="tarjeta tarjetaDato">
        <h2>Conexión a la base</h2>
        <p><span class="etiqueta etiquetaNormal">✔ Correcta</span></p>
    </section>
</div>

<div class="cajaInformativa">
    <strong>Página de prueba:</strong> confirma que la plantilla, el enrutador y la base de datos funcionan juntos.
</div>

<div class="tablaContenedor">
    <table class="tabla">
        <thead>
            <tr><th>Ejemplo</th><th>Estado</th><th class="numero">Cantidad</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <tr>
                <td data-etiqueta="Ejemplo">Cuenta perla 6mm</td>
                <td data-etiqueta="Estado"><span class="etiqueta etiquetaBajo">Bajo</span></td>
                <td data-etiqueta="Cantidad" class="numero">2</td>
                <td data-etiqueta="Acciones" class="acciones"><a href="#">Editar</a></td>
            </tr>
            <tr>
                <td data-etiqueta="Ejemplo">Cadena dorada</td>
                <td data-etiqueta="Estado"><span class="etiqueta etiquetaAgotado">Agotado</span></td>
                <td data-etiqueta="Cantidad" class="numero">0</td>
                <td data-etiqueta="Acciones" class="acciones"><a href="#">Editar</a></td>
            </tr>
            <tr>
                <td data-etiqueta="Ejemplo">Arete luna plata</td>
                <td data-etiqueta="Estado"><span class="etiqueta etiquetaNormal">Normal</span></td>
                <td data-etiqueta="Cantidad" class="numero">18</td>
                <td data-etiqueta="Acciones" class="acciones"><a href="#">Editar</a></td>
            </tr>
        </tbody>
    </table>
</div>

<p class="grupoBotones" style="margin-top: 20px;">
    <a href="<?= $urlBase ?>/prueba/mensajes" class="boton">Probar mensajes</a>
    <a href="<?= $urlBase ?>/fotos/perfil" class="boton botonSecundario">Ver avatar por defecto</a>
</p>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>