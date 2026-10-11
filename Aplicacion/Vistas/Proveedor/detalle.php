<?php
/**
 * @var \Aplicacion\Modelos\Proveedor $proveedor
 * @var array $periodo
 * @var \Aplicacion\Modelos\CompraProveedor[] $compras  las del período, de la más reciente a la más antigua
 * @var int $totalCompras
 * @var array $resumen
 * @var string $urlBase
 */

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\PeriodoFechas;
use Aplicacion\Nucleo\TipoIdentificacion;

$titulo = 'Detalle del proveedor';
$paginaActual = 'proveedores';
require __DIR__ . '/../Plantilla/encabezado.php';

$nombre = htmlspecialchars((string) $proveedor->getNombre());
$telefono = (string) $proveedor->getTelefono();
$telefono = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$estaActivo = $proveedor->getEstado();
$idProveedor = (int) $proveedor->getIdProveedor();
$enlacePeriodo = fn(string $codigo): string => $urlBase . '/proveedores/detalle?' . http_build_query(['id' => $idProveedor, 'periodo' => $codigo]);
?>

<p><a href="<?= $urlBase ?>/proveedores">← Proveedores</a></p>

<section class="formularioSeccion">
    <h2><?= $nombre ?></h2>
    <dl class="listaDatos">
        <dt>Identificación</dt>
        <dd>
            <?= htmlspecialchars(TipoIdentificacion::nombre($proveedor->getTipoIdentificacion())) ?>:
            <?= htmlspecialchars(TipoIdentificacion::formatear($proveedor->getTipoIdentificacion(), $proveedor->getNumeroIdentificacion())) ?>
        </dd>

        <dt>Teléfono</dt>
        <dd><?= htmlspecialchars($telefono) ?></dd>

        <dt>Correo</dt>
        <dd><?= htmlspecialchars((string) $proveedor->getCorreo()) ?></dd>

        <dt>Registrado el</dt>
        <dd><?= $proveedor->getFechaRegistro()->format('d/m/Y') ?></dd>

        <dt>Estado</dt>
        <dd>
            <?php if ($estaActivo): ?>
                <span class="etiqueta etiquetaActivo">Activo</span>
            <?php else: ?>
                <span class="etiqueta etiquetaInactivo">Inactivo</span>
                <p class="textoAyuda">No aparece al registrar compras nuevas.</p>
            <?php endif; ?>
        </dd>
    </dl>

    <div class="grupoBotones">
        <a href="<?= $urlBase ?>/proveedores/editar?id=<?= (int) $proveedor->getIdProveedor() ?>" class="boton">Editar</a>
        <form method="post" action="<?= $urlBase ?>/proveedores/estado" class="formularioEnLinea"
              data-confirmar="<?= $estaActivo
                  ? "¿Desea desactivar a {$nombre}? Ya no podrá elegirlo en compras nuevas."
                  : "¿Desea activar a {$nombre}? Podrá elegirlo de nuevo en sus compras." ?>"
              data-titulo="<?= $estaActivo ? 'Desactivar proveedor' : 'Activar proveedor' ?>"
              data-boton="<?= $estaActivo ? 'Desactivar' : 'Activar' ?>" <?= $estaActivo ? 'data-peligro' : '' ?>>
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= (int) $proveedor->getIdProveedor() ?>">
            <input type="hidden" name="activo" value="<?= $estaActivo ? '0' : '1' ?>">
            <input type="hidden" name="volver" value="detalle">
            <button type="submit" class="boton <?= $estaActivo ? 'botonPeligro' : 'botonSecundario' ?>">
                <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
            </button>
        </form>
    </div>
</section>

<section class="formularioSeccion comprasProveedor" id="compras">
    <div class="encabezadoSeccion">
        <h2>Compras a este proveedor</h2>
        <?php if ($estaActivo): ?>
            <a href="<?= $urlBase ?>/compras-proveedor/nueva?proveedor=<?= $idProveedor ?>" class="boton botonPequeno">Registrar compra a este proveedor</a>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= $urlBase ?>/proveedores/detalle#compras" class="filtros filtrosFechasProveedor">
        <input type="hidden" name="id" value="<?= $idProveedor ?>">
        <?php require __DIR__ . '/../Plantilla/filtroFechas.php'; ?>
        <div class="filtrosBotones">
            <button type="submit" class="boton botonSecundario">Ver este rango</button>
        </div>
    </form>

    <div class="resumenProveedor">
        <div>
            <span class="textoAyuda">Total comprado</span>
            <strong><?= Numero::moneda($resumen['total']) ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Compras</span>
            <strong><?= $resumen['compras'] ?></strong>
        </div>
        <div>
            <span class="textoAyuda">Envíos</span>
            <strong><?= Numero::moneda($resumen['envios']) ?></strong>
        </div>
    </div>

    <?php if ($compras === []): ?>
        <p class="estadoVacio estadoVacioPequeno"><?= $periodo['periodo'] === PeriodoFechas::TODO ? 'Todavía no hay compras a este proveedor.' : 'No hay compras a este proveedor en este período.' ?></p>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla tablaCompras">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Componentes</th>
                        <th class="numero">Total</th>
                        <th>Estado</th>
                        <th><span class="soloLectores">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $compra): ?>
                        <tr class="<?= $compra->estaAnulada() ? 'filaInactiva' : '' ?>">
                            <td data-etiqueta="Fecha"><?= $compra->getFecha()->format('d/m/Y') ?></td>
                            <td data-etiqueta="Componentes"><?= $compra->getCantidadLineas() === 1 ? '1 componente' : $compra->getCantidadLineas() . ' componentes' ?></td>
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
        <?php if ($totalCompras > count($compras)): ?>
            <p class="textoAyuda">
                Se muestran las <?= count($compras) ?> más recientes de <?= $totalCompras ?>.
                <a href="<?= htmlspecialchars($urlBase . '/compras-proveedor?' . http_build_query(['proveedor' => $idProveedor, 'estado' => 'todas'] + PeriodoFechas::parametros($periodo))) ?>">Ver todas</a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>