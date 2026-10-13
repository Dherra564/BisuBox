<?php
/**
 * @var \Aplicacion\Modelos\Proceso[] $procesos
 * @var array $resumen
 * @var string $busqueda
 * @var string $estado
 * @var array $estados
 * @var array $conteos  cuántos procesos hay en cada pestaña
 * @var array $periodo
 * @var int $pagina
 * @var int $totalPaginas
 * @var int $total
 * @var int $porPagina
 * @var bool $hayProcesos
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\PeriodoFechas;

$titulo = 'Procesos';
$paginaActual = 'procesos';
require __DIR__ . '/../Plantilla/encabezado.php';


$parametros = array_filter(['busqueda' => $busqueda, 'estado' => $estado !== 'todos' ? $estado : null])
    + PeriodoFechas::parametros($periodo);
$enlace = function (array $cambios) use ($urlBase, $parametros): string {
    $consulta = array_filter(array_merge($parametros, $cambios), fn($valor): bool => $valor !== null && $valor !== '');
    if (($consulta['periodo'] ?? null) === PeriodoFechas::TODO) {
        unset($consulta['periodo']);
    }
    return htmlspecialchars($urlBase . '/procesos' . ($consulta !== [] ? '?' . http_build_query($consulta) : ''));
};
$sinFechas = ['desde' => null, 'hasta' => null];
$cantidadTexto = fn(int $cantidad, string $uno, string $varios): string => $cantidad === 1 ? "1 {$uno}" : "{$cantidad} {$varios}";


$etiquetaFecha = PeriodoFechas::etiqueta($periodo);
$hayFiltros = $busqueda !== '' || $etiquetaFecha !== '';
$esRango = $periodo['periodo'] === PeriodoFechas::RANGO;
?>

<?php if (!$hayProcesos): ?>
    <div class="estadoVacio">
        <p>Todavía no ha registrado procesos. Un proceso es cada vez que hace un producto: anota qué componentes usó, cuánto
            tardó y el sistema calcula el precio.</p>
        <a href="<?= $urlBase ?>/procesos/nuevo" class="boton">Registrar el primer proceso</a>
    </div>
<?php else: ?>
    <p class="introPagina">Cada producto que ha hecho. Al registrarlo, lo que usó se descuenta solo del inventario.</p>

    <div class="resumenInventario resumenCompras">
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Valor de lo producido</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['valor']) ?></span>
            <span class="textoAyuda">Suma de los precios finales</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Procesos activos</span>
            <span class="tarjetaNumero"><?= $resumen['procesos'] ?></span>
            <span class="textoAyuda">Los anulados no cuentan</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Tiempo trabajado</span>
            <span class="tarjetaNumero"><?= Numero::duracion($resumen['minutos']) ?></span>
            <span class="textoAyuda">Suma de los tiempos</span>
        </div>
    </div>

    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de los procesos">
            <?php foreach ($estados as $codigo => $nombre): ?>
                <a href="<?= $enlace(['estado' => $codigo === 'todos' ? null : $codigo, 'pagina' => null]) ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="get" action="<?= $urlBase ?>/procesos" class="filtrosCompras">
            <?php if ($estado !== 'todos'): ?>
                <input type="hidden" name="estado" value="<?= $estado ?>">
            <?php endif; ?>
            <div class="filaFiltros">
                <div class="campo campoProveedor">
                    <label for="busqueda">Buscar</label>
                    <input type="search" id="busqueda" name="busqueda" maxlength="100" placeholder="Nombre del producto"
                        value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="campo campoFechas">
                    <span class="etiquetaCampo">Fecha</span>
                    <div class="botonesFecha" role="group" aria-label="Fecha del proceso">
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

            <?php if ($hayFiltros): ?>
                <div class="fraseFiltros">
                    <?php if ($busqueda !== ''): ?>
                        <a href="<?= $enlace(['busqueda' => null, 'pagina' => null]) ?>" class="chipFiltro"
                            aria-label="Quitar la búsqueda <?= htmlspecialchars($busqueda) ?>">«<?= htmlspecialchars($busqueda) ?>»
                            <span aria-hidden="true">✕</span></a>
                    <?php endif; ?>
                    <?php if ($etiquetaFecha !== ''): ?>
                        <a href="<?= $enlace(['periodo' => PeriodoFechas::TODO, 'pagina' => null] + $sinFechas) ?>"
                            class="chipFiltro"
                            aria-label="Quitar el filtro de fecha <?= htmlspecialchars($etiquetaFecha) ?>"><?= htmlspecialchars($etiquetaFecha) ?>
                            <span aria-hidden="true">✕</span></a>
                    <?php endif; ?>
                    <a href="<?= $enlace(['busqueda' => null, 'periodo' => PeriodoFechas::TODO, 'pagina' => null] + $sinFechas) ?>"
                        class="enlaceQuitarFiltros">Quitar filtros</a>
                </div>
            <?php endif; ?>
        </form>

        <?php if ($procesos === []): ?>
            <p class="sinResultados">No hay procesos con esos filtros.</p>
        <?php else: ?>
            <div class="tablaContenedor">
                <table class="tabla tablaCompras">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Componentes</th>
                            <th>Tiempo</th>
                            <th class="numero">Precio final</th>
                            <th>Estado</th>
                            <th><span class="soloLectores">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($procesos as $proceso): ?>
                            <tr class="<?= $proceso->estaAnulado() ? 'filaInactiva' : '' ?>">
                                <td data-etiqueta="Fecha"><?= $proceso->getFecha()->format('d/m/Y') ?></td>
                                <td data-etiqueta="Producto"><strong><?= htmlspecialchars($proceso->getNombre()) ?></strong></td>
                                <td data-etiqueta="Componentes">
                                    <?= $cantidadTexto($proceso->getCantidadComponentes(), 'componente', 'componentes') ?></td>
                                <td data-etiqueta="Tiempo"><?= Numero::duracion($proceso->getMinutos()) ?></td>
                                <td data-etiqueta="Precio final" class="numero">
                                    <strong><?= Numero::moneda($proceso->getPrecio()) ?></strong></td>
                                <td data-etiqueta="Estado">
                                    <span
                                        class="etiqueta <?= $proceso->estaAnulado() ? 'etiquetaInactivo' : 'etiquetaActivo' ?>"><?= $proceso->getEstado() ?></span>
                                </td>
                                <td class="acciones">
                                    <a href="<?= $urlBase ?>/procesos/detalle?id=<?= $proceso->getIdProceso() ?>"
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
        $rutaPaginacion = '/procesos';
        $parametrosPaginacion = $parametros;
        $nombreRegistros = 'procesos';
        require __DIR__ . '/../Plantilla/paginacion.php';
    ?>
    <?php endif; ?>

    <div class="grupoBotones accionesLista">
        <a href="<?= $urlBase ?>/procesos/nuevo" class="boton">Registrar proceso</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>