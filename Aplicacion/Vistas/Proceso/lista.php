<?php
/**
 * @var \Aplicacion\Modelos\Proceso[] $procesos
 * @var array $resumen
 * @var string $busqueda
 * @var string $estado
 * @var array $estados
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
$botonAccion = ['texto' => 'Registrar proceso', 'ruta' => '/procesos/nuevo'];
require __DIR__ . '/../Plantilla/encabezado.php';

// Los filtros que no son de fechas se conservan al tocar un período
$filtrosFijos = array_filter(['busqueda' => $busqueda, 'estado' => $estado !== 'todos' ? $estado : null]);
$enlacePeriodo = fn(string $codigo): string => $urlBase . '/procesos?' . http_build_query($filtrosFijos + ['periodo' => $codigo]);
$cantidadTexto = fn(int $cantidad, string $uno, string $varios): string => $cantidad === 1 ? "1 {$uno}" : "{$cantidad} {$varios}";
?>

<?php if (!$hayProcesos): ?>
    <div class="estadoVacio">
        <p>Todavía no ha registrado procesos. Un proceso es cada vez que hace un producto: anota qué componentes usó, cuánto tardó y el sistema calcula el precio.</p>
        <a href="<?= $urlBase ?>/procesos/nuevo" class="boton">Registrar el primer proceso</a>
    </div>
<?php else: ?>
    <div class="resumenInventario resumenCompras">
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Valor de lo producido</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['valor']) ?></span>
            <span class="textoAyuda">Suma de los precios finales</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Procesos</span>
            <span class="tarjetaNumero"><?= $resumen['procesos'] ?></span>
            <span class="textoAyuda">Registrados en el período</span>
        </div>
        <div class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Tiempo trabajado</span>
            <span class="tarjetaNumero"><?= Numero::duracion($resumen['minutos']) ?></span>
            <span class="textoAyuda">Suma de los tiempos</span>
        </div>
    </div>

    <form method="get" action="<?= $urlBase ?>/procesos" class="filtros filtrosCompras">
        <?php require __DIR__ . '/../Plantilla/filtroFechas.php'; ?>
        <div class="campo">
            <label for="busqueda">Buscar</label>
            <input type="search" id="busqueda" name="busqueda" maxlength="100"
                   placeholder="Nombre del producto" value="<?= htmlspecialchars($busqueda) ?>">
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
            <a href="<?= $urlBase ?>/procesos" class="boton botonSecundario">Quitar filtros</a>
        </div>
    </form>

    <?php if ($procesos === []): ?>
        <div class="estadoVacio">
            <p>No hay procesos con esos filtros.</p>
        </div>
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
                            <td data-etiqueta="Producto"><?= htmlspecialchars($proceso->getNombre()) ?></td>
                            <td data-etiqueta="Componentes"><?= $cantidadTexto($proceso->getCantidadComponentes(), 'componente', 'componentes') ?></td>
                            <td data-etiqueta="Tiempo"><?= Numero::duracion($proceso->getMinutos()) ?></td>
                            <td data-etiqueta="Precio final" class="numero"><strong><?= Numero::moneda($proceso->getPrecio()) ?></strong></td>
                            <td data-etiqueta="Estado">
                                <span class="etiqueta <?= $proceso->estaAnulado() ? 'etiquetaInactivo' : 'etiquetaActivo' ?>"><?= $proceso->getEstado() ?></span>
                            </td>
                            <td class="acciones">
                                <a href="<?= $urlBase ?>/procesos/detalle?id=<?= $proceso->getIdProceso() ?>" class="boton botonSecundario botonPequeno">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        $rutaPaginacion = '/procesos';
        $parametrosPaginacion = $filtrosFijos + PeriodoFechas::parametros($periodo);
        $nombreRegistros = 'procesos';
        require __DIR__ . '/../Plantilla/paginacion.php';
        ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>