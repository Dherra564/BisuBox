<?php

use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\PeriodoFechas;

$titulo = 'Compras por pagar';
$paginaActual = 'comprasPorPagar';
$scriptsExtra = ['filtrosCompras.js'];
$botonesAccion = $saldos !== [] ? [['texto' => '+ Registrar compra', 'ruta' => '/compras-proveedor/nueva']] : [];
require __DIR__ . '/../Plantilla/encabezado.php';

$parametros = array_filter(['proveedor' => $proveedor, 'vista' => $vencidas ? 'vencidas' : null]) + PeriodoFechas::parametros($periodo);
$enlace = function (array $cambios) use ($urlBase, $parametros): string {
    $consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== null && $valor !== '');
    if (($consulta['periodo'] ?? null) === PeriodoFechas::TODO) {
        unset($consulta['periodo']);
    }
    return htmlspecialchars($urlBase . '/compras-por-pagar' . ($consulta !== [] ? '?' . http_build_query($consulta) : ''));
};
$sinFechas = ['desde' => null, 'hasta' => null];
$etiquetaFecha = PeriodoFechas::etiqueta($periodo);
$esRango = $periodo['periodo'] === PeriodoFechas::RANGO;
$nombreProveedor = null;
foreach ($proveedores as $opcion) {
    if ($opcion->getIdProveedor() === $proveedor) {
        $nombreProveedor = (string) $opcion->getNombre();
    }
}
?>

<?php if ($saldos === [] && $proveedor === null && $etiquetaFecha === ''): ?>
    <div class="estadoVacio">
        <p>No le debe nada a sus proveedores. Cuando registre una compra a crédito, aparece aquí con su fecha límite para
            pagar.</p>
        <p><a href="<?= $urlBase ?>/compras-proveedor/nueva" class="boton">Registrar compra</a></p>
    </div>
<?php else: ?>
    <p class="introPagina">Las compras que hizo a crédito y todavía no ha terminado de pagar.</p>

    <div class="resumenInventario resumenCompras">
        <div class="tarjeta tarjetaResumen tarjetaResumenDestacada">
            <span class="tarjetaResumenTitulo">Total por pagar</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['saldo']) ?></span>
            <span class="textoAyuda"><?= $resumen['compras'] === 1 ? '1 compra' : $resumen['compras'] . ' compras' ?></span>
        </div>
        <a href="<?= $enlace(['vista' => 'vencidas', 'pagina' => null]) ?>"
            class="tarjeta tarjetaResumen <?= $resumen['vencido'] > 0 ? 'tarjetaError tarjetaVencido' : '' ?> <?= $vencidas ? 'tarjetaElegida' : '' ?>">
            <span class="tarjetaResumenTitulo">Vencido</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['vencido']) ?></span>
            <span
                class="textoAyuda"><?= $resumen['vencidas'] === 0 ? 'Nada vencido' : ($resumen['vencidas'] === 1 ? '1 compra vencida · ver cuál' : $resumen['vencidas'] . ' compras vencidas · ver cuáles') ?></span>
        </a>
        <div class="tarjeta tarjetaResumen <?= $resumen['semana'] > 0 ? 'tarjetaAdvertencia' : '' ?>">
            <span class="tarjetaResumenTitulo">Vence esta semana</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['semana']) ?></span>
            <span class="textoAyuda">En los próximos 7 días</span>
        </div>
    </div>

    <section class="panelCompras panelVentas panelPorPagar">
        <nav class="pestanas" aria-label="Compras por pagar">
            <a href="<?= $enlace(['vista' => null, 'pagina' => null]) ?>"
                class="pestana <?= !$vencidas ? 'pestanaActiva' : '' ?>" <?= !$vencidas ? 'aria-current="page"' : '' ?>>Por
                pagar <span class="pestanaCantidad"><?= $conteos['todas'] ?></span></a>
            <a href="<?= $enlace(['vista' => 'vencidas', 'pagina' => null]) ?>"
                class="pestana <?= $vencidas ? 'pestanaActiva' : '' ?>" <?= $vencidas ? 'aria-current="page"' : '' ?>>Vencidas <span class="pestanaCantidad"><?= $conteos['vencidas'] ?></span></a>
        </nav>

        <form method="get" action="<?= $urlBase ?>/compras-por-pagar" class="filtrosCompras">
            <?php if ($vencidas): ?>
                <input type="hidden" name="vista" value="vencidas">
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
                    <span class="etiquetaCampo">Fecha de la compra</span>
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

            <?php if ($nombreProveedor !== null || $etiquetaFecha !== ''): ?>
                <div class="fraseFiltros">
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
                    <a href="<?= $enlace(['proveedor' => null, 'periodo' => PeriodoFechas::TODO, 'pagina' => null] + $sinFechas) ?>"
                        class="enlaceQuitarFiltros">Quitar filtros</a>
                </div>
            <?php endif; ?>
        </form>

        <?php if ($compras === []): ?>
            <p class="sinResultados">
                <?= $vencidas ? 'No tiene compras vencidas con esos filtros.' : 'No hay compras por pagar con esos filtros.' ?>
            </p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla tablaCompras">
                    <thead>
                        <tr>
                            <th>Compra</th>
                            <th>Proveedor</th>
                            <th class="numero">Total</th>
                            <th class="numero">Saldo</th>
                            <th>Vence</th>
                            <th><span class="soloLectores">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $compra): ?>
                            <tr class="<?= $compra->estaVencida() ? 'filaVencida' : '' ?>">
                                <td data-etiqueta="Compra">
                                    <span><strong>#<?= $compra->getIdCompraProveedor() ?></strong><br>
                                        <span class="textoAyuda"><?= $compra->getFecha()->format('d/m/Y') ?></span></span>
                                </td>
                                <td data-etiqueta="Proveedor"><?= htmlspecialchars($compra->getNombreProveedor()) ?></td>
                                <td data-etiqueta="Total" class="numero"><?= Numero::moneda($compra->getTotal()) ?></td>
                                <td data-etiqueta="Saldo" class="numero"><strong><?= Numero::moneda($compra->getSaldo()) ?></strong>
                                </td>
                                <td data-etiqueta="Vence">
                                    <span>
                                        <span
                                            class="etiqueta <?= DatosCompraProveedor::clasePago($compra) ?>"><?= DatosCompraProveedor::textoPago($compra) ?></span>
                                        <?php if ($compra->getVencimiento() !== null): ?>
                                            <br><span class="textoAyuda"><?= $compra->getVencimiento()->format('d/m/Y') ?></span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td data-etiqueta="" class="acciones accionesVenta">
                                    <a href="<?= $urlBase ?>/compras-proveedor/detalle?id=<?= $compra->getIdCompraProveedor() ?>#monto"
                                        class="boton botonPequeno">Abonar</a>
                                    <a href="<?= $urlBase ?>/compras-proveedor/detalle?id=<?= $compra->getIdCompraProveedor() ?>"
                                        class="enlaceAccion">Ver</a>
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
        $rutaPaginacion = '/compras-por-pagar';
        $parametrosPaginacion = $parametros;
        $nombreRegistros = 'compras';
        require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>