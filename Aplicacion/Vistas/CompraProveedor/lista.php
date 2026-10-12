<?php
/**
 * @var \Aplicacion\Modelos\CompraProveedor[] $compras
 * @var array $resumen
 * @var \Aplicacion\Modelos\Proveedor[] $proveedores
 * @var int|null $proveedor
 * @var string $estado
 * @var array $estados
 * @var array $conteos
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
    ['texto' => '+ Registrar compra', 'ruta' => '/compras-proveedor/nueva' . ($proveedor !== null ? '?proveedor=' . $proveedor : '')],
];
$scriptsExtra = ['filtrosCompras.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$parametros = array_filter(['proveedor' => $proveedor, 'estado' => $estado !== 'todas' ? $estado : null])
    + PeriodoFechas::parametros($periodo);
$enlace = function (array $cambios) use ($urlBase, $parametros): string {
    $consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== null && $valor !== '');
    if (($consulta['periodo'] ?? null) === PeriodoFechas::TODO) {
        unset($consulta['periodo']);
    }
    return htmlspecialchars($urlBase . '/compras-proveedor' . ($consulta !== [] ? '?' . http_build_query($consulta) : ''));
};
$sinFechas = ['desde' => null, 'hasta' => null];

$nombreProveedor = null;
foreach ($proveedores as $opcion) {
    if ($opcion->getIdProveedor() === $proveedor) {
        $nombreProveedor = (string) $opcion->getNombre();
    }
}
$palabraEstado = ['todas' => '', 'registradas' => ' registrada', 'anuladas' => ' anulada'][$estado];
$frase = 'Mostrando ' . ($total === 1 ? '1 compra' . $palabraEstado : $total . ' compras' . ($palabraEstado !== '' ? $palabraEstado . 's' : ''))
    . ($nombreProveedor !== null ? ' a ' . $nombreProveedor : '')
    . (PeriodoFechas::enFrase($periodo) !== '' ? ' ' . PeriodoFechas::enFrase($periodo) : '');
$etiquetaFecha = PeriodoFechas::etiqueta($periodo);
$hayFiltros = $nombreProveedor !== null || $etiquetaFecha !== '';
$esRango = $periodo['periodo'] === PeriodoFechas::RANGO;
?>

<?php if (!$hayCompras): ?>
    <div class="estadoVacio">
        <p>Todavía no ha registrado compras. Cuando registre una, lo que compró se suma solo a su inventario.</p>
        <a href="<?= $urlBase ?>/compras-proveedor/nueva" class="boton">Registrar la primera compra</a>
    </div>
<?php else: ?>
    <p class="introPagina">Lo que le ha comprado a sus proveedores. Cada compra suma sola al inventario.</p>

    <div class="resumenInventario resumenCompras">
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Total comprado</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['total']) ?></span>
            <span class="textoAyuda">Con envíos incluidos</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Compras registradas</span>
            <span class="tarjetaNumero"><?= $resumen['compras'] ?></span>
            <span class="textoAyuda">Las anuladas no cuentan</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Envíos pagados</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['envios']) ?></span>
            <span class="textoAyuda">Suma de los costos de envío</span>
        </div>
    </div>

    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de las compras">
            <?php foreach ($estados as $codigo => $nombre): ?>
                <a href="<?= $enlace(['estado' => $codigo === 'todas' ? null : $codigo, 'pagina' => null]) ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="get" action="<?= $urlBase ?>/compras-proveedor" class="filtrosCompras">
            <?php if ($estado !== 'todas'): ?>
                <input type="hidden" name="estado" value="<?= $estado ?>">
            <?php endif; ?>
            <div class="filaFiltros">
                <div class="campo campoProveedor">
                    <label for="proveedor">Proveedor</label>
                    <select id="proveedor" name="proveedor" data-enviar-al-cambiar>
                        <option value="">Todos los proveedores</option>
                        <?php foreach ($proveedores as $opcion): ?>
                            <option value="<?= $opcion->getIdProveedor() ?>" <?= $proveedor === $opcion->getIdProveedor() ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $opcion->getNombre()) ?>        <?= $opcion->getEstado() ? '' : ' (inactivo)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo campoFechas">
                    <span class="etiquetaCampo">Fecha</span>
                    <div class="botonesFecha" role="group" aria-label="Fecha de la compra">
                        <?php foreach (PeriodoFechas::PERIODOS as $codigo => $nombre): ?>
                            <a href="<?= $enlace(['periodo' => $codigo, 'pagina' => null] + $sinFechas) ?>"
                                class="botonFecha <?= $periodo['periodo'] === $codigo ? 'botonFechaActivo' : '' ?>"
                                <?= $periodo['periodo'] === $codigo ? 'aria-current="true"' : '' ?>><?= $nombre ?></a>
                        <?php endforeach; ?>
                        <a href="<?= $enlace(['periodo' => PeriodoFechas::RANGO, 'pagina' => null] + $sinFechas) ?>"
                            class="botonFecha <?= $esRango ? 'botonFechaActivo' : '' ?>" <?= $esRango ? 'aria-current="true"' : '' ?>>Elegir fechas…</a>
                    </div>
                </div>
            </div>

            <?php if ($esRango): ?>
                <div class="rangoFechas">
                    <input type="hidden" name="periodo" value="<?= PeriodoFechas::RANGO ?>">
                    <div class="campo">
                        <label for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" value="<?= $periodo['desde']?->format('Y-m-d') ?>">
                    </div>
                    <div class="campo">
                        <label for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" value="<?= $periodo['hasta']?->format('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="boton">Ver esas fechas</button>
                </div>
            <?php else: ?>
                <input type="hidden" name="periodo" value="<?= $periodo['periodo'] ?>">
            <?php endif; ?>

            <div class="fraseFiltros">
                <span><?= htmlspecialchars($frase) ?></span>
                <?php if ($nombreProveedor !== null): ?>
                    <a href="<?= $enlace(['proveedor' => null, 'pagina' => null]) ?>" class="chipFiltro"
                        aria-label="Quitar el filtro de proveedor <?= htmlspecialchars($nombreProveedor) ?>"><?= htmlspecialchars($nombreProveedor) ?>
                        <span aria-hidden="true">✕</span></a>
                <?php endif; ?>
                <?php if ($etiquetaFecha !== ''): ?>
                    <a href="<?= $enlace(['periodo' => PeriodoFechas::TODO, 'pagina' => null] + $sinFechas) ?>"
                        class="chipFiltro"
                        aria-label="Quitar el filtro de fecha <?= htmlspecialchars($etiquetaFecha) ?>"><?= htmlspecialchars($etiquetaFecha) ?>
                        <span aria-hidden="true">✕</span></a>
                <?php endif; ?>
                <?php if ($hayFiltros): ?>
                    <a href="<?= $enlace(['proveedor' => null, 'periodo' => PeriodoFechas::TODO, 'pagina' => null] + $sinFechas) ?>"
                        class="enlaceQuitarFiltros">Quitar filtros</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($compras === []): ?>
            <p class="sinResultados">No hay compras con esos filtros.</p>
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
                                <td data-etiqueta="Proveedor">
                                    <strong><?= htmlspecialchars($compra->getNombreProveedor()) ?></strong></td>
                                <td data-etiqueta="Componentes">
                                    <?= $compra->getCantidadLineas() === 1 ? '1 componente' : $compra->getCantidadLineas() . ' componentes' ?>
                                </td>
                                <td data-etiqueta="Envío" class="numero">
                                    <?= $compra->getCostoEnvio() > 0 ? Numero::moneda($compra->getCostoEnvio()) : '—' ?></td>
                                <td data-etiqueta="Total" class="numero"><strong><?= Numero::moneda($compra->getTotal()) ?></strong>
                                </td>
                                <td data-etiqueta="Estado">
                                    <span
                                        class="etiqueta <?= $compra->estaAnulada() ? 'etiquetaInactivo' : 'etiquetaActivo' ?>"><?= $compra->getEstado() ?></span>
                                </td>
                                <td class="acciones">
                                    <a href="<?= $urlBase ?>/compras-proveedor/detalle?id=<?= $compra->getIdCompraProveedor() ?>"
                                        class="boton botonSecundario botonPequeno">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($totalPaginas > 1): ?>
        <?php
        $rutaPaginacion = '/compras-proveedor';
        $parametrosPaginacion = $parametros;
        $nombreRegistros = 'compras';
        require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>