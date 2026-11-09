<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\MovimientoInventario;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$esProducto = $articulo['tipo'] === MovimientoInventario::PRODUCTO;
$titulo = 'Movimientos de «' . $articulo['nombre'] . '»';
$paginaActual = $esProducto ? 'productos' : 'inventario';
$volverA = $esProducto
    ? ['texto' => $articulo['nombre'], 'ruta' => $articulo['detalle']]
    : ['texto' => 'Inventario', 'ruta' => '/inventario'];
$scriptsExtra = ['ajusteInventario.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$claseCampo = fn(string $campo): string => isset($errores[$campo]) ? 'campo campoConError' : 'campo';
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$cantidad = fn(float $numero): string => UnidadMedida::cantidad($numero, $articulo['unidad']);
$parametro = MovimientoInventario::parametro($articulo['tipo']);
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo. No se cambió la existencia.</p>
    </div>
<?php endif; ?>

<div class="resumenInventario resumenCompras">
    <div class="tarjeta tarjetaResumen">
        <span class="tarjetaResumenTitulo">Existencia actual</span>
        <span class="tarjetaNumero"><?= $cantidad($articulo['existencia']) ?></span>
        <span class="textoAyuda"><?= $esProducto ? 'Listos para vender' : 'En el inventario' ?></span>
    </div>
    <div class="tarjeta tarjetaResumen">
        <span class="tarjetaResumenTitulo">Movimientos</span>
        <span class="tarjetaNumero"><?= $total ?></span>
        <span
            class="textoAyuda"><?= $cuadra ? 'La existencia cuadra con el historial' : 'Hay existencia de antes del historial' ?></span>
    </div>
</div>

<section class="formularioSeccion" id="ajuste">
    <h2>Ajustar existencia</h2>
    <p class="textoAyuda">Use esto cuando la existencia cambia por algo que no es una compra, un pedido o una
        producción. El motivo queda en el historial.</p>
    <form method="post" action="<?= $urlBase ?>/inventario/ajustar" novalidate class="validarFormulario" data-ajuste
        data-titulo="Guardar ajuste" data-boton="Guardar ajuste"
        data-confirmar="¿Guardar el ajuste de «<?= htmlspecialchars($articulo['nombre']) ?>»? Quedará anotado en el historial.">
        <?= Csrf::campo() ?>
        <input type="hidden" name="articulo" value="<?= $parametro ?>">
        <input type="hidden" name="id" value="<?= $articulo['id'] ?>">

        <div class="filaCampos filaTres">
            <fieldset class="<?= $claseCampo('tipo') ?> opcionesUnidad">
                <legend class="etiquetaCampo">¿Entra o sale? <span class="obligatorio">*</span></legend>
                <div class="opcionesUnidadLista">
                    <label class="opcionUnidad">
                        <input type="radio" name="tipo" value="<?= MovimientoInventario::ENTRADA ?>" required
                            data-mensaje-requerido="<?= MovimientoInventario::MENSAJE_TIPO ?>"
                            <?= $datos['tipo'] === MovimientoInventario::ENTRADA ? 'checked' : '' ?>>
                        Entrada (suma)
                    </label>
                    <label class="opcionUnidad">
                        <input type="radio" name="tipo" value="<?= MovimientoInventario::SALIDA ?>" required
                            data-mensaje-requerido="<?= MovimientoInventario::MENSAJE_TIPO ?>"
                            <?= $datos['tipo'] === MovimientoInventario::SALIDA ? 'checked' : '' ?>>
                        Salida (resta)
                    </label>
                </div>
                <?= $mensajeError('tipo') ?>
            </fieldset>

            <div class="<?= $claseCampo('cantidad') ?>">
                <label for="cantidad">Cantidad <span class="obligatorio">*</span></label>
                <div class="campoConUnidad">
                    <input type="text" id="cantidad" name="cantidad" inputmode="decimal" maxlength="13" required
                        data-regla="ajuste" <?= $articulo['soloEnteros'] ? 'data-entero' : '' ?>
                        data-existencia="<?= htmlspecialchars((string) $articulo['existencia']) ?>"
                        data-mensaje-negativo="<?= htmlspecialchars(MovimientoInventario::mensajeNegativo($articulo['existencia'])) ?>"
                        data-mensaje-requerido="Escriba la cantidad"
                        value="<?= htmlspecialchars($datos['cantidad']) ?>">
                    <span
                        class="unidadCampo"><?= htmlspecialchars(UnidadMedida::todas()[$articulo['unidad']]['abreviatura'] ?? '') ?></span>
                </div>
                <p class="textoAyuda" data-ajuste-resultado aria-live="polite"></p>
                <?= $mensajeError('cantidad') ?>
            </div>

            <div class="<?= $claseCampo('motivo') ?>">
                <label for="motivo">Motivo <span class="obligatorio">*</span></label>
                <select id="motivo" name="motivo" required
                    data-mensaje-requerido="<?= MovimientoInventario::MENSAJE_MOTIVO ?>">
                    <option value="">Escoja el motivo</option>
                    <?php foreach (MovimientoInventario::MOTIVOS_AJUSTE as $motivo): ?>
                        <option value="<?= htmlspecialchars($motivo) ?>" <?= $datos['motivo'] === $motivo ? 'selected' : '' ?>>
                            <?= htmlspecialchars($motivo) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $mensajeError('motivo') ?>
            </div>
        </div>

        <div class="<?= $claseCampo('nota') ?>">
            <label for="nota">Nota</label>
            <input type="text" id="nota" name="nota" maxlength="300"
                data-mensaje-largo="<?= MovimientoInventario::MENSAJE_NOTA ?>"
                value="<?= htmlspecialchars($datos['nota']) ?>">
            <p class="textoAyuda">Opcional, salvo con el motivo Otro. Por ejemplo: «Se quebraron al caer».</p>
            <?= $mensajeError('nota') ?>
        </div>

        <div class="grupoBotones">
            <button type="submit" class="boton">Guardar ajuste</button>
            <a href="<?= $urlBase . $articulo['detalle'] ?>"
                class="boton botonSecundario"><?= $esProducto ? 'Ver producto' : 'Editar componente' ?></a>
        </div>
    </form>
</section>

<section class="formularioSeccion" id="historial">
    <h2>Historial</h2>
    <?php if ($movimientos === []): ?>
        <p class="textoAyuda">Todavía no hay movimientos. Cada compra, pedido, producción o ajuste quedará anotado aquí.</p>
    <?php else: ?>
        <div class="tablaContenedor">
            <table class="tabla tablaMovimientos">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Movimiento</th>
                        <th class="numero">Cantidad</th>
                        <th class="numero">Antes</th>
                        <th class="numero">Después</th>
                        <th>Origen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <?php
                        $esEntrada = $movimiento['tipo'] === MovimientoInventario::ENTRADA;
                        $enlace = MovimientoInventario::enlace($movimiento);
                        $referencia = MovimientoInventario::textoReferencia($movimiento);
                        ?>
                        <tr>
                            <td data-etiqueta="Fecha">
                                <span><?= $movimiento['fecha']->format('d/m/Y') ?><br>
                                    <span class="textoAyuda"><?= $movimiento['fecha']->format('H:i') ?></span></span>
                            </td>
                            <td data-etiqueta="Movimiento">
                                <span>
                                    <span
                                        class="etiqueta <?= $esEntrada ? 'etiquetaActivo' : 'etiquetaBajo' ?>"><?= $movimiento['tipo'] ?></span>
                                    <?= htmlspecialchars($movimiento['motivo']) ?>
                                    <?php if ($movimiento['nota'] !== null): ?>
                                        <br><span class="textoAyuda"><?= htmlspecialchars($movimiento['nota']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td data-etiqueta="Cantidad" class="numero">
                                <strong
                                    class="<?= $esEntrada ? 'cantidadEntrada' : 'cantidadSalida' ?>"><?= ($esEntrada ? '+' : '−') . Numero::formatear($movimiento['cantidad']) ?></strong>
                            </td>
                            <td data-etiqueta="Antes" class="numero"><?= Numero::formatear($movimiento['anterior']) ?></td>
                            <td data-etiqueta="Después" class="numero"><?= Numero::formatear($movimiento['nueva']) ?></td>
                            <td data-etiqueta="Origen">
                                <span>
                                    <?php if ($enlace !== null): ?>
                                        <a href="<?= $urlBase . $enlace ?>"><?= htmlspecialchars($referencia) ?></a>
                                    <?php elseif ($referencia !== ''): ?>
                                        <?= htmlspecialchars($referencia) ?>
                                    <?php else: ?>
                                        <span
                                            class="textoAyuda"><?= $movimiento['referenciaTipo'] === MovimientoInventario::REFERENCIA_AJUSTE ? 'Ajuste a mano' : '—' ?></span>
                                    <?php endif; ?>
                                    <?php if ($movimiento['usuario'] !== ''): ?>
                                        <br><span class="textoAyuda"><?= htmlspecialchars($movimiento['usuario']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <?php
            $rutaPaginacion = '/inventario/movimientos';
            $parametrosPaginacion = ['articulo' => $parametro, 'id' => $articulo['id']];
            $nombreRegistros = 'movimientos';
            require __DIR__ . '/../Plantilla/paginacion.php';
            ?>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>