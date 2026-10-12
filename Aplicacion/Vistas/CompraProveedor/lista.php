<?php
/**
 * @var \Aplicacion\Modelos\CompraProveedor[] $compras
 * @var array $resumen
 * @var \Aplicacion\Modelos\Proveedor[] $proveedores
 * @var int|null $proveedor
 * @var string $estado
 * @var array $estados
 * @var array $periodo
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var bool $hayCompras
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\PeriodoFechas;

$titulo = 'Compras a proveedores';
$paginaActual = 'proveedores';
$volverA = ['texto' => 'Proveedores', 'ruta' => '/proveedores'];
$botonesAccion = [
    ['texto' => 'Ver inventario', 'ruta' => '/inventario', 'secundario' => true],
    ['texto' => 'Registrar compra', 'ruta' => '/compras-proveedor/nueva' . ($proveedor !== null ? '?proveedor=' . $proveedor : '')],
];
require __DIR__ . '/../Plantilla/encabezado.php';

// Los filtros que no son de fechas se conservan al tocar un período
$filtrosFijos = array_filter(['proveedor' => $proveedor, 'estado' => $estado !== 'todas' ? $estado : null]);
$enlacePeriodo = fn(string $codigo): string => $urlBase . '/compras-proveedor?' . http_build_query($filtrosFijos + ['periodo' => $codigo]);
$cantidadTexto = fn(int $cantidad, string $uno, string $varios): string => $cantidad === 1 ? "1 {$uno}" : "{$cantidad} {$varios}";
?>

<?php if (!$hayCompras): ?>
    <div class="estadoVacio">
        <p>Todavía no ha registrado compras. Cuando registre una, lo que compró se suma solo a su inventario.</p>
        <a href="<?= $urlBase ?>/compras-proveedor/nueva" class="boton">Registrar la primera compra</a>
    </div>
<?php else: ?>
    <div class="resumenInventario resumenCompras">
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Total comprado</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['total']) ?></span>
            <span class="textoAyuda">Con envíos incluidos</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Compras</span>
            <span class="tarjetaNumero"><?= $resumen['compras'] ?></span>
            <span class="textoAyuda">Registradas en el período</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Envíos pagados</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['envios']) ?></span>
            <span class="textoAyuda">Suma de los costos de envío</span>
        </div>
    </div>

    <form method="get" action="<?= $urlBase ?>/compras-proveedor" class="filtros filtrosCompras">
        <?php require __DIR__ . '/../Plantilla/filtroFechas.php'; ?>
        <div class="campo">
            <label for="proveedor">Proveedor</label>
            <select id="proveedor" name="proveedor">
                <option value="">Todos</option>
                <?php foreach ($proveedores as $opcion): ?>
                    <option value="<?= $opcion->getIdProveedor() ?>" <?= $proveedor === $opcion->getIdProveedor() ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $opcion->getNombre()) ?><?= $opcion->getEstado() ? '' : ' (inactivo)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <?php foreach ($estados as $codigo => $nombre): ?>
                    <option value="<?= $codigo ?>" <?= $estado === $codigo ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtrosBotones">
            <button type="submit" class="boton">Aplicar</button>
            <a href="<?= $urlBase ?>/compras-proveedor" class="boton botonSecundario">Quitar filtros</a>
        </div>
    </form>

    <?php if ($compras === []): ?>
        <div class="estadoVacio">
            <p>No hay compras con esos filtros.</p>
        </div>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla tablaCompras">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Componentes</th>
                        <th class="numero">Envío</th>
                        <th class="numero">Total</th>
                        <th>Estado</th>
                        <th><span class="soloLectores">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $compra): ?>
                        <tr class="<?= $compra->estaAnulada() ? 'filaInactiva' : '' ?>">
                            <td data-etiqueta="Fecha"><?= $compra->getFecha()->format('d/m/Y') ?></td>
                            <td data-etiqueta="Proveedor"><?= htmlspecialchars($compra->getNombreProveedor()) ?></td>
                            <td data-etiqueta="Componentes"><?= $cantidadTexto($compra->getCantidadLineas(), 'componente', 'componentes') ?></td>
                            <td data-etiqueta="Envío" class="numero"><?= $compra->getCostoEnvio() > 0 ? Numero::moneda($compra->getCostoEnvio()) : '—' ?></td>
                            <td data-etiqueta="Total" class="numero"><strong><?= Numero::moneda($compra->getTotal()) ?></strong></td>
                            <td data-etiqueta="Estado">
                                <span class="etiqueta <?= $compra->estaAnulada() ? 'etiquetaInactivo' : 'etiquetaActivo' ?>"><?= $compra->getEstado() ?></span>
                            </td>
                            <td class="acciones">
                                <a href="<?= $urlBase ?>/compras-proveedor/detalle?id=<?= $compra->getIdCompraProveedor() ?>" class="boton botonSecundario botonPequeno">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        $rutaPaginacion = '/compras-proveedor';
        $parametrosPaginacion = $filtrosFijos + PeriodoFechas::parametros($periodo);
        $nombreRegistros = 'compras';
        require __DIR__ . '/../Plantilla/paginacion.php';
        ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>