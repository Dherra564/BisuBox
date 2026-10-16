<?php
/**
 
 * @var array $clientes
 * @var string $busqueda
 * @var bool $hayClientes
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Clientes';
$paginaActual = 'clientes';
require __DIR__ . '/../Plantilla/encabezado.php';

$formatoTelefono = fn(string $telefono): string => strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
?>

<?php if (!$hayClientes): ?>
    <div class="estadoVacio">
        <p>Aquí aparecerán las personas que le hagan pedidos.</p>
    </div>
<?php else: ?>
    <p class="introPagina">Personas que le han hecho pedidos. «Total comprado» suma solo los pedidos entregados.</p>

    <form method="get" action="<?= $urlBase ?>/clientes" class="filtros">
        <div class="campo">
            <label for="busqueda">Buscar</label>
            <input type="search" id="busqueda" name="busqueda" maxlength="100" placeholder="Nombre, correo o teléfono"
                value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <div class="filtrosBotones">
            <button type="submit" class="boton">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="<?= $urlBase ?>/clientes" class="boton botonSecundario">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($clientes === []): ?>
        <div class="estadoVacio"><p>No se encontraron clientes con esa búsqueda.</p></div>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th class="numero">Pedidos</th>
                        <th class="numero">Total comprado</th>
                        <th>Último pedido</th>
                        <th><span class="soloLectores">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <?php $whatsApp = WhatsApp::enlace($cliente['telefono'], 'Hola ' . $cliente['nombre'] . ', le escribo de mi tienda en BisuBox.'); ?>
                        <tr>
                            <td data-etiqueta="Cliente"><strong><?= htmlspecialchars($cliente['nombre']) ?></strong></td>
                            <td data-etiqueta="Contacto">
                                <span>
                                    <?php if ($whatsApp !== null): ?>
                                        <a href="<?= htmlspecialchars($whatsApp) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($formatoTelefono($cliente['telefono'])) ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($formatoTelefono($cliente['telefono'])) ?>
                                    <?php endif; ?>
                                    <br><span class="textoAyuda"><?= htmlspecialchars($cliente['correo']) ?></span>
                                </span>
                            </td>
                            <td data-etiqueta="Pedidos" class="numero">
                                <span><?= $cliente['pedidos'] ?>
                                    <?php if ($cliente['entregados'] > 0): ?><br><span class="textoAyuda"><?= $cliente['entregados'] ?> entregados</span><?php endif; ?></span>
                            </td>
                            <td data-etiqueta="Total comprado" class="numero"><strong><?= Numero::moneda($cliente['totalComprado']) ?></strong></td>
                            <td data-etiqueta="Último pedido"><?= $cliente['ultimoPedido']->format('d/m/Y') ?></td>
                            <td data-etiqueta="" class="acciones">
                                <a href="<?= $urlBase ?>/pedidos?cliente=<?= $cliente['idCliente'] ?>">Ver pedidos</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        $rutaPaginacion = '/clientes';
        $parametrosPaginacion = ['busqueda' => $busqueda];
        $nombreRegistros = 'clientes';
        require __DIR__ . '/../Plantilla/paginacion.php';
        ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>
