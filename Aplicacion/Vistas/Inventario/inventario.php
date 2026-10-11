<?php
/**
 * @var \Aplicacion\Modelos\TipoComponente[] $tipos
 * @var \Aplicacion\Modelos\Componente[] $componentes
 * @var array $filtros
 * @var array $resumen
 * @var bool $hayComponentes
 * @var string $urlBase
 */

use Aplicacion\Nucleo\ClaseDato;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosComponente;
use Aplicacion\Nucleo\EstadoExistencia;
use Aplicacion\Nucleo\FiltrosInventario;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Inventario';
$paginaActual = 'inventario';
$scriptsExtra = ['inventario.js'];
$botonesAccion = $hayComponentes
    ? [
        ['texto' => 'Tipos de componente', 'ruta' => '/tipos-componente', 'secundario' => true],
        ['texto' => 'Agregar componente', 'ruta' => '/componentes/nuevo'],
    ]
    : [];
require __DIR__ . '/../Plantilla/encabezado.php';

$tiposPorId = [];
foreach ($tipos as $tipo) {
    $tiposPorId[(int) $tipo->getIdTipoComponente()] = $tipo;
}
$tipoElegido = $filtros['tipo'] !== null ? $tiposPorId[$filtros['tipo']] : null;
$enlace = fn(array $cambios = []): string => htmlspecialchars($urlBase . FiltrosInventario::enlace($filtros, $cambios));
$cantidadTexto = fn(int $cantidad, string $uno, string $varios): string => $cantidad === 1 ? "1 {$uno}" : "{$cantidad} {$varios}";

$detalle = function ($componente) use ($tiposPorId): string {
    $tipo = $tiposPorId[$componente->getIdTipoComponente()] ?? null;
    $partes = [];
    foreach ($tipo?->getAtributosActivos() ?? [] as $atributo) {
        $valor = DatosComponente::mostrarValor($atributo, $componente->getValor((int) $atributo->getIdAtributo()));
        if ($valor !== '' && !($atributo->getClase() === ClaseDato::SINO && $valor === 'No')) {
            $partes[] = $atributo->getClase() === ClaseDato::SINO ? $atributo->getNombre() : $atributo->getNombre() . ': ' . $valor;
        }
    }
    return implode(' · ', $partes);
};
?>

<?php if (!$hayComponentes): ?>
    <?php $hayTipos = $tipos !== []; ?>
    <p class="subtitulo">Arme su inventario en dos pasos. Después verá aquí todo lo que tiene y cuánto vale.</p>

    <div class="pasosInventario">
        <section class="pasoInventario <?= $hayTipos ? 'pasoListo' : 'pasoActual' ?>">
            <span class="pasoNumero" aria-hidden="true"><?= $hayTipos ? '✓' : '1' ?></span>
            <div>
                <h2>Cree los tipos de componente</h2>
                <p class="textoAyuda">Agrupe sus materiales, por ejemplo Perlas, Dijes o Hilos, y decida qué datos anota de cada uno.</p>
                <a href="<?= $urlBase ?>/tipos-componente" class="boton <?= $hayTipos ? 'botonSecundario' : '' ?>">
                    <?= $hayTipos ? 'Ver mis tipos (' . count($tipos) . ')' : 'Crear tipos de componente' ?>
                </a>
            </div>
        </section>
        <section class="pasoInventario <?= $hayTipos ? 'pasoActual' : 'pasoPendiente' ?>">
            <span class="pasoNumero" aria-hidden="true">2</span>
            <div>
                <h2>Agregue sus componentes</h2>
                <p class="textoAyuda">Por ejemplo "Perla de vidrio rosada 8 mm", con cuántas tiene y cuánto le costó cada una.</p>
                <?php if ($hayTipos): ?>
                    <a href="<?= $urlBase ?>/componentes/nuevo" class="boton">Agregar el primer componente</a>
                <?php else: ?>
                    <p class="textoAyuda"><em>Disponible después del paso 1.</em></p>
                <?php endif; ?>
            </div>
        </section>
    </div>
<?php else: ?>
    <div class="resumenInventario">
        <a href="<?= $urlBase ?>/inventario" class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Componentes activos</span>
            <span class="tarjetaNumero"><?= $resumen['total'] ?></span>
            <span class="textoAyuda">Ver todos</span>
        </a>
        <a href="<?= $enlace(['orden' => 'valor']) ?>" class="tarjeta tarjetaResumen">
            <span class="tarjetaResumenTitulo">Valor del inventario</span>
            <span class="tarjetaNumero"><?= Numero::moneda($resumen['valor']) ?></span>
            <span class="textoAyuda">Existencia × costo unitario</span>
        </a>
        <a href="<?= $enlace(['existencia' => EstadoExistencia::BAJO, 'estado' => null]) ?>"
           class="tarjeta tarjetaResumen <?= $resumen['bajos'] > 0 ? 'tarjetaAdvertencia' : '' ?>
           <?= $filtros['existencia'] === EstadoExistencia::BAJO ? 'tarjetaElegida' : '' ?>">
            <span class="tarjetaResumenTitulo">Por acabarse</span>
            <span class="tarjetaNumero"><?= $resumen['bajos'] ?></span>
            <span class="textoAyuda"><?= $resumen['bajos'] > 0 ? 'Le queda poco; ver cuáles' : 'Nada por acabarse' ?></span>
        </a>
        <a href="<?= $enlace(['existencia' => EstadoExistencia::AGOTADO, 'estado' => null]) ?>"
           class="tarjeta tarjetaResumen <?= $resumen['agotados'] > 0 ? 'tarjetaError' : '' ?>
           <?= $filtros['existencia'] === EstadoExistencia::AGOTADO ? 'tarjetaElegida' : '' ?>">
            <span class="tarjetaResumenTitulo">Agotados</span>
            <span class="tarjetaNumero"><?= $resumen['agotados'] ?></span>
            <span class="textoAyuda"><?= $resumen['agotados'] > 0 ? 'Sin existencia; ver cuáles' : 'Nada agotado' ?></span>
        </a>
    </div>

    <form method="get" action="<?= $urlBase ?>/inventario" class="filtros filtrosInventario" id="filtrosInventario" data-filtros>
        <div class="campo">
            <label for="buscar">Buscar</label>
            <input type="search" id="buscar" name="buscar" maxlength="100" placeholder="Nombre, tipo, color..."
                   value="<?= htmlspecialchars($filtros['buscar']) ?>">
        </div>
        <div class="campo">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" data-enviar-al-cambiar data-quita-datos>
                <option value="">Todos</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?= $tipo->getIdTipoComponente() ?>" <?= $filtros['tipo'] === $tipo->getIdTipoComponente() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo->getNombre()) ?><?= $tipo->getActivo() ? '' : ' (desactivado)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="existencia">Existencia</label>
            <select id="existencia" name="existencia" data-enviar-al-cambiar>
                <option value="">Toda</option>
                <?php foreach (EstadoExistencia::todos() as $codigo => $estado): ?>
                    <option value="<?= $codigo ?>" <?= $filtros['existencia'] === $codigo ? 'selected' : '' ?>><?= $estado['nombre'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="estado">Mostrar</label>
            <select id="estado" name="estado" data-enviar-al-cambiar data-por-defecto="activos">
                <?php foreach (FiltrosInventario::ESTADOS as $codigo => $nombre): ?>
                    <option value="<?= $codigo ?>" <?= $filtros['estado'] === $codigo ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php $atributosFiltro = $tipoElegido !== null ? FiltrosInventario::atributosFiltrables($tipoElegido) : []; ?>
        <?php if ($atributosFiltro !== []): ?>
            <div class="filtrosDatos" data-filtros-datos>
                <p class="filtrosDatosTitulo">Filtrar <?= htmlspecialchars(mb_strtolower($tipoElegido->getNombre(), 'UTF-8')) ?> por:</p>
                <?php foreach ($atributosFiltro as $atributo): ?>
                    <?php $elegido = $filtros['datos'][(int) $atributo->getIdAtributo()]['valor'] ?? ''; ?>
                    <div class="campo">
                        <label for="dato-<?= $atributo->getIdAtributo() ?>"><?= htmlspecialchars($atributo->getNombre()) ?></label>
                        <select id="dato-<?= $atributo->getIdAtributo() ?>" name="dato[<?= $atributo->getIdAtributo() ?>]" data-enviar-al-cambiar>
                            <option value="">Cualquiera</option>
                            <?php if ($atributo->getClase() === ClaseDato::SINO): ?>
                                <option value="1" <?= $elegido === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $elegido === '0' ? 'selected' : '' ?>>No</option>
                            <?php else: ?>
                                <?php foreach ($atributo->getOpcionesActivas() as $opcion): ?>
                                    <option value="<?= $opcion->getIdOpcion() ?>" <?= $elegido === (string) $opcion->getIdOpcion() ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opcion->getValor()) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="filtrosBotones">
            <button type="submit" class="boton">Buscar</button>
            <?php if (FiltrosInventario::hayFiltros($filtros)): ?>
                <a href="<?= $urlBase ?>/inventario" class="boton botonSecundario">Quitar filtros</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="barraResultados">
        <p class="resultadoFiltros" aria-live="polite">
            <?= $componentes === []
                ? 'Ningún componente coincide con los filtros.'
                : 'Mostrando ' . $cantidadTexto(count($componentes), 'componente', 'componentes') . '.' ?>
        </p>
        <?php
        ?>
        <div class="campoOrden">
            <label for="orden">Ordenar por</label>
            <select id="orden" name="orden" form="filtrosInventario" data-enviar-al-cambiar data-por-defecto="nombre">
                <?php foreach (FiltrosInventario::ORDENES as $codigo => $nombre): ?>
                    <option value="<?= $codigo ?>" <?= $filtros['orden'] === $codigo ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($componentes === []): ?>
        <div class="estadoVacio">
            <p>No encontramos componentes con esos filtros.</p>
            <a href="<?= $urlBase ?>/inventario" class="boton botonSecundario">Quitar filtros</a>
        </div>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla tablaInventario">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th>Tipo</th>
                        <th>Existencia</th>
                        <th class="numero">Costo unitario</th>
                        <th class="numero">Valor</th>
                        <th><span class="soloLectores">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($componentes as $componente): ?>
                        <?php
                        $estado = $componente->getEstadoExistencia();
                        $textoDetalle = $detalle($componente);
                        ?>
                        <tr class="<?= $componente->getActivo() ? '' : 'filaInactiva' ?>">
                            <td data-etiqueta="Componente" class="celdaComponente">
                                <span class="nombreComponente"><?= htmlspecialchars($componente->getNombre()) ?></span>
                                <?php if (!$componente->getActivo()): ?>
                                    <span class="etiqueta etiquetaInactivo">Desactivado</span>
                                <?php endif; ?>
                                <?php if ($textoDetalle !== ''): ?>
                                    <span class="detalleComponente"><?= htmlspecialchars($textoDetalle) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-etiqueta="Tipo"><?= htmlspecialchars($componente->getNombreTipo()) ?></td>
                            <td data-etiqueta="Existencia" class="celdaExistencia">
                                <span class="cantidadExistencia"><?= UnidadMedida::cantidad($componente->getExistencia(), $componente->getUnidad()) ?></span>
                                <span class="etiqueta <?= EstadoExistencia::clase($estado) ?>"><?= EstadoExistencia::nombre($estado) ?></span>
                                <?php if ($componente->getExistenciaMinima() > 0): ?>
                                    <span class="textoAyuda">Mínimo: <?= Numero::formatear($componente->getExistenciaMinima()) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-etiqueta="Costo unitario" class="numero"><?= Numero::moneda($componente->getCostoUnitario()) ?></td>
                            <td data-etiqueta="Valor" class="numero"><strong><?= Numero::moneda($componente->getValorTotal()) ?></strong></td>
                            <td class="acciones">
                                <a href="<?= $urlBase ?>/componentes/editar?id=<?= $componente->getIdComponente() ?>"
                                   class="boton botonSecundario botonPequeno">Editar</a>
                                <form method="post" action="<?= $urlBase ?>/componentes/estado" class="formularioEnLinea"
                                      <?php if ($componente->getActivo()): ?>
                                          data-titulo="Desactivar componente" data-boton="Desactivar"
                                          data-confirmar="¿Desactivar «<?= htmlspecialchars($componente->getNombre()) ?>»? Dejará de salir en el inventario, pero no se borra y puede activarlo cuando quiera."
                                      <?php endif; ?>>
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="id" value="<?= $componente->getIdComponente() ?>">
                                    <input type="hidden" name="activo" value="<?= $componente->getActivo() ? '0' : '1' ?>">
                                    <button type="submit" class="botonEnlace"><?= $componente->getActivo() ? 'Desactivar' : 'Activar' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>