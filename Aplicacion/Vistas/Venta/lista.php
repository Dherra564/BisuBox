<?php

use Aplicacion\Nucleo\DatosVenta;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\PeriodoFechas;
use Aplicacion\Nucleo\WhatsApp;

$titulo = 'Ventas';
$paginaActual = 'ventas';
$scriptsExtra = ['filtrosCompras.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$parametros = array_filter(['tipo' => $tipo, 'estado' => $estado, 'cliente' => $idCliente]) + PeriodoFechas::parametros($periodo);
$enlace = function (array $cambios) use ($urlBase, $parametros): string {
    $consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== null && $valor !== '');
    if (($consulta['periodo'] ?? null) === PeriodoFechas::TODO) {
        unset($consulta['periodo']);
    }
    return $urlBase . '/ventas' . ($consulta !== [] ? '?' . http_build_query($consulta) : '');
};
$sinFechas = ['desde' => null, 'hasta' => null];
$etiquetaFecha = PeriodoFechas::etiqueta($periodo);
$esRango = $periodo['periodo'] === PeriodoFechas::RANGO;
$pestanas = ['' => 'Todas', DatosVenta::ESTADO_POR_COBRAR => 'Por cobrar', DatosVenta::ESTADO_PAGADA => 'Pagadas'];
?>

<?php if (!$hayVentas): ?>
    <div class="estadoVacio">
        <p>Todavía no tiene ventas. Cada pedido que marque como entregado queda registrado aquí como una venta.</p>
        <p><a href="<?= $urlBase ?>/pedidos" class="boton">Ver mis pedidos</a></p>
    </div>
<?php else: ?>
    <p class="introPagina">Cada pedido entregado es una venta. Aquí ve cuánto ha vendido y quién le debe.</p>

    <div class="resumenInventario resumenCompras">
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Total vendido</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['total']) ?></span>
            <span class="textoAyuda"><?= $resumen['ventas'] === 1 ? '1 venta' : $resumen['ventas'] . ' ventas' ?></span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Por cobrar</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['porCobrar']) ?></span>
            <span
                class="textoAyuda"><?= $resumen['vencido'] > 0 ? Numero::moneda($resumen['vencido']) . ' ya vencido' : 'Lo que le deben los clientes' ?></span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Con piezas personalizadas</span>
            <span class="tarjetaNumero"><?= $resumen['conPersonalizadas'] ?></span>
            <span class="textoAyuda">Ventas hechas con el personalizador</span>
        </div>
    </div>

    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado del pago">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?= htmlspecialchars($enlace(['estado' => $codigo, 'pagina' => null])) ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="get" action="<?= $urlBase ?>/ventas" class="filtrosCompras">
            <?php if ($estado !== ''): ?>
                <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
            <?php endif; ?>
            <?php if ($idCliente !== null): ?>
                <input type="hidden" name="cliente" value="<?= $idCliente ?>">
            <?php endif; ?>
            <div class="filaFiltros">
                <div class="campo campoProveedor">
                    <label for="tipo">Tipo de venta</label>
                    <select id="tipo" name="tipo" data-enviar-al-cambiar>
                        <option value="">Todos los tipos</option>
                        <?php foreach (DatosVenta::TIPOS as $opcion): ?>
                            <option value="<?= htmlspecialchars($opcion) ?>" <?= $tipo === $opcion ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opcion) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo campoFechas">
                    <span class="etiquetaCampo">Fecha</span>
                    <div class="botonesFecha" role="group" aria-label="Fecha de la venta">
                        <?php foreach (PeriodoFechas::PERIODOS as $codigo => $nombre): ?>
                            <a href="<?= htmlspecialchars($enlace(['periodo' => $codigo, 'pagina' => null] + $sinFechas)) ?>"
                                class="botonFecha <?= $periodo['periodo'] === $codigo ? 'botonFechaActivo' : '' ?>"
                                <?= $periodo['periodo'] === $codigo ? 'aria-current="true"' : '' ?>><?= $nombre ?></a>
                        <?php endforeach; ?>
                        <a href="<?= htmlspecialchars($enlace(['periodo' => PeriodoFechas::RANGO, 'pagina' => null] + $sinFechas)) ?>"
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

            <?php if ($tipo !== '' || $etiquetaFecha !== '' || $idCliente !== null): ?>
                <div class="fraseFiltros">
                    <?php if ($idCliente !== null): ?>
                        <a href="<?= htmlspecialchars($enlace(['cliente' => null, 'pagina' => null])) ?>" class="chipFiltro"
                            aria-label="Quitar el filtro <?= htmlspecialchars((string) $nombreCliente) ?>"><?= htmlspecialchars((string) $nombreCliente) ?>
                            <span aria-hidden="true">✕</span></a>
                    <?php endif; ?>
                    <?php if ($tipo !== ''): ?>
                        <a href="<?= htmlspecialchars($enlace(['tipo' => null, 'pagina' => null])) ?>" class="chipFiltro"
                            aria-label="Quitar el filtro <?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($tipo) ?> <span
                                aria-hidden="true">✕</span></a>
                    <?php endif; ?>
                    <?php if ($etiquetaFecha !== ''): ?>
                        <a href="<?= htmlspecialchars($enlace(['periodo' => PeriodoFechas::TODO, 'pagina' => null] + $sinFechas)) ?>"
                            class="chipFiltro"
                            aria-label="Quitar el filtro de fecha <?= htmlspecialchars($etiquetaFecha) ?>"><?= htmlspecialchars($etiquetaFecha) ?>
                            <span aria-hidden="true">✕</span></a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($enlace(['tipo' => null, 'cliente' => null, 'periodo' => PeriodoFechas::TODO, 'pagina' => null] + $sinFechas)) ?>"
                        class="enlaceQuitarFiltros">Quitar filtros</a>
                </div>
            <?php endif; ?>
        </form>

        <?php if ($estado === DatosVenta::ESTADO_POR_COBRAR && $idCliente === null && $saldos !== []): ?>
            <h2>Quién le debe</h2>
            <div class="tablaContenedor">
                <table class="tabla tablaVentas">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th class="numero">Ventas</th>
                            <th class="numero">Saldo</th>
                            <th>Vencimiento</th>
                            <th><span class="soloLectores">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saldos as $saldo): ?>
                            <tr>
                                <td data-etiqueta="Cliente">
                                    <?= htmlspecialchars($saldo['cliente'] !== '' ? $saldo['cliente'] : 'Cliente') ?></td>
                                <td data-etiqueta="Ventas" class="numero"><?= $saldo['ventas'] ?></td>
                                <td data-etiqueta="Saldo" class="numero"><strong><?= Numero::moneda($saldo['saldo']) ?></strong>
                                </td>
                                <td data-etiqueta="Vencimiento">
                                    <?php if ($saldo['vencidas'] > 0): ?>
                                        <span
                                            class="etiqueta etiquetaCancelado"><?= $saldo['vencidas'] === 1 ? '1 vencida' : $saldo['vencidas'] . ' vencidas' ?></span>
                                    <?php elseif ($saldo['proximo'] !== null): ?>
                                        <span class="textoAyuda">Próxima: <?= $saldo['proximo']->format('d/m/Y') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?= htmlspecialchars($enlace(['cliente' => $saldo['idCliente'], 'pagina' => null])) ?>"
                                        class="boton botonSecundario botonPequeno">Ver sus ventas</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <h2>Ventas por cobrar</h2>
        <?php endif; ?>

        <?php if ($ventas === []): ?>
            <p class="sinResultados">No hay ventas con esos filtros.</p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla tablaVentas">
                    <thead>
                        <tr>
                            <th>Venta</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th class="numero">Total</th>
                            <th>Pago</th>
                            <th><span class="soloLectores">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta): ?>
                            <?php $vencida = $venta['saldo'] > 0 && $venta['vencimiento'] !== null && $venta['vencimiento'] < new DateTime('today'); ?>
                            <tr>
                                <td data-etiqueta="Venta">
                                    <span><strong>#<?= $venta['id'] ?></strong><br>
                                        <span class="textoAyuda"><?= $venta['fecha']->format('d/m/Y') ?></span></span>
                                </td>
                                <td data-etiqueta="Cliente">
                                    <?= htmlspecialchars($venta['cliente'] !== '' ? $venta['cliente'] : 'Cliente') ?></td>
                                <td data-etiqueta="Tipo"><?= htmlspecialchars($venta['tipo']) ?></td>
                                <td data-etiqueta="Total" class="numero"><strong><?= Numero::moneda($venta['total']) ?></strong>
                                </td>
                                <td data-etiqueta="Pago">
                                    <span>
                                        <span
                                            class="etiqueta <?= DatosVenta::claseEtiqueta($venta['estadoPago'], $venta['vencimiento'], $venta['saldo']) ?>">
                                            <?= $vencida ? 'Vencida' : htmlspecialchars($venta['estadoPago']) ?></span>
                                        <?php if ($venta['saldo'] > 0 && $venta['vencimiento'] !== null): ?>
                                            <br><span class="textoAyuda">Debe <?= Numero::moneda($venta['saldo']) ?> · vence
                                                <?= $venta['vencimiento']->format('d/m/Y') ?></span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td data-etiqueta="" class="acciones">
                                    <a href="<?= $urlBase ?>/ventas/detalle?id=<?= $venta['id'] ?>"
                                        class="boton botonSecundario botonPequeno">Ver venta</a>
                                    <?php if ($venta['estadoPago'] === DatosVenta::ESTADO_POR_COBRAR): ?>
                                        <a href="<?= $urlBase ?>/ventas/detalle?id=<?= $venta['id'] ?>#monto"
                                            class="boton botonPequeno">Abonar</a>
                                        <?php $recordar = WhatsApp::enlace($venta['telefono'], DatosVenta::recordatorio($venta, $tienda)); ?>
                                        <?php if ($recordar !== null): ?>
                                            <a href="<?= htmlspecialchars($recordar) ?>" class="boton botonSecundario botonPequeno"
                                                target="_blank" rel="noopener">WhatsApp</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($ventas !== []): ?>
        <?php
        $rutaPaginacion = '/ventas';
        $parametrosPaginacion = $parametros;
        $nombreRegistros = 'ventas';
        require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>