<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosProducto;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UnidadMedida;
use Aplicacion\Nucleo\WhatsApp;

$enRevision = $personalizado->estaEnRevision();
$titulo = ($enRevision ? 'Cotizar pedido personalizado #' : 'Pedido personalizado #') . $personalizado->getIdPersonalizado();
$paginaActual = 'pedidos';
$volverA = ['texto' => 'Pedidos personalizados', 'ruta' => '/pedidos/personalizados'];
$scriptsExtra = $enRevision ? ['cotizar.js'] : [];
require __DIR__ . '/../Plantilla/encabezado.php';

$fotoComponente = fn(?string $foto): string => $foto !== null
    ? $urlBase . '/fotos/componente?archivo=' . urlencode($foto)
    : $urlBase . '/imagenes/productoPorDefecto.svg';
$claseCampo = fn(string $campo, string $extra = ''): string => trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';

$estado = $personalizado->getEstado();
$numero = $personalizado->getIdPersonalizado();
$piezas = $personalizado->getPiezas();
$foto = $personalizado->getFotoProducto();
$telefono = $personalizado->getTelefonoCliente();
$telefonoBonito = strlen($telefono) === 8 ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : $telefono;
$whatsApp = WhatsApp::enlace($telefono, 'Hola ' . $personalizado->getNombreCliente() . ', le escribo por su pedido personalizado #' . $numero . '.');
$nombreCorto = explode(' ', trim($personalizado->getNombreCliente()))[0];

$costoComponentes = 0.0;
foreach ($personalizado->getComponentes() as $linea) {
    $costoComponentes += $linea->getCantidad() * ($enRevision ? $linea->getCostoActual() : $linea->getCostoUnitario());
}
$costoComponentes = round($costoComponentes, 2);
$necesidades = $personalizado->getNecesidades();

$filaExtra = function (string $clave, array $extra) use ($claseCampo, $mensajeError): string {
    $campo = 'extra.' . $clave . '.';
    $nombre = 'extra[' . $clave . ']';
    return '<div class="filaExtraCotizar" data-extra>'
        . '<div class="' . $claseCampo($campo . 'concepto') . '">'
        . '<label for="extra-' . $clave . '-concepto" class="soloLectores">Para qué es</label>'
        . '<input type="text" id="extra-' . $clave . '-concepto" name="' . $nombre . '[concepto]" maxlength="60" placeholder="Por ejemplo: empaque de regalo" value="' . htmlspecialchars($extra['concepto']) . '">'
        . $mensajeError($campo . 'concepto') . '</div>'
        . '<div class="' . $claseCampo($campo . 'monto') . '">'
        . '<label for="extra-' . $clave . '-monto" class="soloLectores">Monto</label>'
        . '<div class="campoConUnidad campoConMoneda"><span class="unidadCampo">₡</span>'
        . '<input type="text" id="extra-' . $clave . '-monto" name="' . $nombre . '[monto]" maxlength="13" inputmode="decimal" placeholder="0" data-extra-monto value="' . htmlspecialchars($extra['monto']) . '"></div>'
        . $mensajeError($campo . 'monto') . '</div>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar esta tarifa" title="Quitar esta tarifa" data-extra-quitar>'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>'
        . '</div>';
};
?>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> <?= isset($errores['inventario']) ? htmlspecialchars($errores['inventario']) : 'Revise los campos marcados en rojo.' ?></p>
    </div>
<?php endif; ?>

<section class="clientePersonalizado" aria-label="Cliente">
    <img src="<?= $foto !== null ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto) : $urlBase . '/imagenes/productoPorDefecto.svg' ?>" alt="" class="fotoPersonalizado">
    <div class="clientePersonalizadoTexto">
        <div class="tituloPersonalizado">
            <strong><?= htmlspecialchars($personalizado->getNombre()) ?>, personalizado</strong>
            <span class="etiqueta <?= EstadoPersonalizado::claseEtiqueta($estado) ?>"><?= $estado ?></span>
        </div>
        <span class="textoAyuda">
            <?= htmlspecialchars($personalizado->getNombreCliente()) ?> · <?= htmlspecialchars($telefonoBonito) ?> ·
            enviado el <?= $personalizado->getFechaRegistro()->format('d/m/Y H:i') ?> ·
            <?= $piezas === 1 ? '1 pieza' : $piezas . ' piezas' ?>
        </span>
        <?php if ($whatsApp !== null): ?>
            <a href="<?= htmlspecialchars($whatsApp) ?>" target="_blank" rel="noopener">Escribirle por WhatsApp</a>
        <?php endif; ?>
    </div>
    <?php if ($personalizado->getNotaCliente() !== null): ?>
        <p class="notaPersonalizado"><strong>Nota de <?= htmlspecialchars($nombreCorto) ?>:</strong> «<?= htmlspecialchars($personalizado->getNotaCliente()) ?>»</p>
    <?php endif; ?>
</section>

<?php if ($personalizado->estaCotizado()): ?>
    <p class="cajaInformativa">
        Le mandó la cotización el <?= $personalizado->getFechaCotizado()?->format('d/m/Y H:i') ?>. Está esperando que <?= htmlspecialchars($nombreCorto) ?> decida si lo compra.
    </p>
<?php elseif ($estado === EstadoPersonalizado::COMPRADO): ?>
    <p class="cajaInformativa">
        <?= htmlspecialchars($nombreCorto) ?> lo compró. Atiéndalo en <a href="<?= $urlBase ?>/pedidos/detalle?id=<?= $personalizado->getIdPedido() ?>">el pedido #<?= $personalizado->getIdPedido() ?></a>;
        al confirmarlo se rebajan del inventario los componentes que lleva.
    </p>
<?php elseif ($estado === EstadoPersonalizado::CANCELADO): ?>
    <div class="cajaInformativa">
        <p><strong><?= $personalizado->getCanceladoPor() === Rol::CLIENTE ? 'El cliente canceló este pedido.' : 'Usted canceló este pedido.' ?></strong></p>
        <?php if ($personalizado->getCanceladoPor() === Rol::VENDEDOR && $personalizado->getNotaTienda() !== null): ?>
            <p>Motivo: «<?= htmlspecialchars($personalizado->getNotaTienda()) ?>»</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="panelCompras" aria-labelledby="tituloEscogio">
    <div class="encabezadoSeccion encabezadoPanel">
        <h2 id="tituloEscogio">Lo que escogió</h2>
        <span class="textoAyuda"><?= $enRevision ? 'Se suma solo con el costo unitario de cada componente' : 'Con el costo del día en que cotizó' ?></span>
    </div>
    <div class="tablaContenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Componente</th>
                    <th>Por pieza</th>
                    <th>Inventario</th>
                    <th class="numero">Costo unitario</th>
                    <th class="numero">Subtotal por pieza</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personalizado->getComponentes() as $linea): ?>
                    <?php
                    $costo = $enRevision ? $linea->getCostoActual() : $linea->getCostoUnitario();
                    $necesita = $necesidades[$linea->getIdComponente()]['cantidad'] ?? 0;
                    $alcanza = $necesita <= $linea->getExistencia();
                    ?>
                    <tr>
                        <td data-etiqueta="Componente">
                            <span class="productoConFoto">
                                <img src="<?= htmlspecialchars($fotoComponente($linea->getFoto())) ?>" alt="" class="miniaturaComponente" loading="lazy">
                                <span>
                                    <span class="tipoPieza"><?= htmlspecialchars($linea->getNombreTipo()) ?></span><br>
                                    <strong><?= htmlspecialchars($linea->getNombre()) ?></strong>
                                    <?php if ($linea->fueCambiado()): ?>
                                        <br><span class="textoCambio">Cambió: antes <?= htmlspecialchars($linea->getNombreOriginal()) ?></span>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </td>
                        <td data-etiqueta="Por pieza"><?= htmlspecialchars(UnidadMedida::cantidad($linea->getCantidad(), $linea->getUnidad())) ?></td>
                        <td data-etiqueta="Inventario">
                            <?php if ($enRevision): ?>
                                <span class="<?= $alcanza ? 'textoAyuda' : 'errorCampo' ?>">
                                    Necesita <?= Numero::formatear($necesita) ?>, quedan <?= Numero::formatear(max(0, $linea->getExistencia())) ?>
                                </span>
                            <?php else: ?>
                                <span class="textoAyuda">Necesita <?= Numero::formatear($necesita) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-etiqueta="Costo unitario" class="numero"><?= Numero::moneda(round($costo, 2)) ?></td>
                        <td data-etiqueta="Subtotal" class="numero"><strong><?= Numero::moneda(round($costo * $linea->getCantidad(), 2)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="totalesCompra totalComponentesCotizar">
        <div class="totalCompra"><span>Total de componentes por pieza</span><strong><?= Numero::moneda($costoComponentes) ?></strong></div>
    </div>
</section>

<?php if ($enRevision): ?>
    <form method="post" action="<?= $urlBase ?>/pedidos/personalizado/cotizar" novalidate class="formularioCotizar" data-cotizar
        data-componentes="<?= $costoComponentes ?>" data-mano-obra="<?= $personalizado->getCostoManoObra() ?>" data-piezas="<?= $piezas ?>"
        data-confirmar="¿Enviarle la cotización a <?= htmlspecialchars($nombreCorto) ?>? Después ya no la puede cambiar."
        data-titulo="Enviar cotización" data-boton="Enviar cotización">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= $numero ?>">

        <div class="columnasCotizar">
            <section class="formularioSeccion">
                <div class="encabezadoSeccion">
                    <h2>Mano de obra</h2>
                    <span class="chipBloqueado">Del producto original</span>
                </div>
                <dl class="cuentaPrecio">
                    <div><dt>Tiempo</dt><dd><?= Numero::duracion($personalizado->getMinutos()) ?></dd></div>
                    <div><dt>Tarifa por hora</dt><dd><?= Numero::moneda($personalizado->getTarifaHora()) ?></dd></div>
                    <div class="cuentaTotal"><dt>Por pieza</dt><dd><?= Numero::moneda($personalizado->getCostoManoObra()) ?></dd></div>
                </dl>
            </section>

            <section class="formularioSeccion">
                <h2>Tarifas extra por pieza</h2>
                <?= $mensajeError('extras') ?>
                <div class="listaExtrasCotizar" data-lista-extras data-maximo="<?= DatosProducto::MAXIMO_EXTRAS ?>">
                    <?php foreach ($datos['extras'] as $clave => $extra): ?>
                        <?= $filaExtra((string) $clave, $extra) ?>
                    <?php endforeach; ?>
                </div>
                <template data-plantilla-extra><?= $filaExtra('__CLAVE__', ['concepto' => '', 'monto' => '']) ?></template>
                <button type="button" class="enlaceAgregar" data-extra-agregar>+ Agregar tarifa</button>
            </section>
        </div>

        <section class="franjaCotizar" aria-labelledby="tituloPrecio">
            <div>
                <h2 id="tituloPrecio">Precio para <?= htmlspecialchars($nombreCorto) ?></h2>
                <dl class="cuentaPrecio">
                    <div><dt>Componentes</dt><dd><?= Numero::moneda($costoComponentes) ?></dd></div>
                    <div><dt>Mano de obra</dt><dd><?= Numero::moneda($personalizado->getCostoManoObra()) ?></dd></div>
                    <div><dt>Tarifas extra</dt><dd data-total-extras>₡0</dd></div>
                    <div class="cuentaTotal"><dt>Le cuesta hacer cada pieza</dt><dd data-costo>₡0</dd></div>
                </dl>
            </div>

            <div>
                <fieldset class="opcionesGanancia">
                    <legend>¿Cuánto quiere ganar?</legend>
                    <div class="chipsGanancia">
                        <?php foreach (DatosProducto::GANANCIAS as $porcentaje): ?>
                            <label class="chipGanancia">
                                <input type="radio" name="ganancia" value="<?= $porcentaje ?>" data-ganancia
                                    <?= $datos['ganancia'] === (string) $porcentaje ? 'checked' : '' ?>>
                                <span><?= $porcentaje ?>%</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="<?= $claseCampo('precio', 'campoPrecioVenta') ?>">
                    <label for="precio">Precio de cada pieza <span class="obligatorio">*</span></label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="precio" name="precio" maxlength="13" inputmode="decimal" data-precio value="<?= htmlspecialchars($datos['precio']) ?>">
                    </div>
                    <?= $mensajeError('precio') ?>
                </div>
                <p class="resultadoGanancia" data-resultado-ganancia hidden></p>
            </div>

            <div class="franjaCotizarTotal">
                <span class="textoAyuda"><strong>Total (<?= $piezas === 1 ? '1 pieza' : $piezas . ' piezas' ?>)</strong></span>
                <strong class="precioFinal" data-total>₡0</strong>
                <div class="<?= $claseCampo('notaTienda') ?>">
                    <label for="notaTienda">Mensaje para <?= htmlspecialchars($nombreCorto) ?></label>
                    <textarea id="notaTienda" name="notaTienda" rows="2" maxlength="300" data-contador
                        placeholder="Opcional. Por ejemplo: con gusto le acorto la cadena"><?= htmlspecialchars($datos['notaTienda']) ?></textarea>
                    <?= $mensajeError('notaTienda') ?>
                </div>
                <button type="submit" class="boton botonRegistrar">Enviar cotización</button>
            </div>
        </section>
    </form>
<?php elseif ($personalizado->getPrecio() !== null): ?>
    <section class="franjaCotizar" aria-labelledby="tituloPrecio">
        <div>
            <h2 id="tituloPrecio">Cotización</h2>
            <dl class="cuentaPrecio">
                <div><dt>Componentes</dt><dd><?= Numero::moneda($personalizado->getCostoComponentes()) ?></dd></div>
                <div><dt>Mano de obra</dt><dd><?= Numero::moneda($personalizado->getCostoManoObra()) ?></dd></div>
                <?php foreach ($personalizado->getExtras() as $extra): ?>
                    <div><dt><?= htmlspecialchars($extra->getConcepto()) ?></dt><dd><?= Numero::moneda($extra->getMonto()) ?></dd></div>
                <?php endforeach; ?>
                <div class="cuentaTotal"><dt>Le cuesta cada pieza</dt><dd><?= Numero::moneda($personalizado->getCostoTotal()) ?></dd></div>
            </dl>
        </div>
        <div>
            <dl class="cuentaPrecio">
                <div><dt>Precio por pieza</dt><dd><?= Numero::moneda((float) $personalizado->getPrecio()) ?></dd></div>
                <div><dt>Gana por pieza</dt><dd><?= Numero::moneda(round((float) $personalizado->getPrecio() - $personalizado->getCostoTotal(), 2)) ?></dd></div>
            </dl>
            <?php if ($personalizado->getNotaTienda() !== null && $estado !== EstadoPersonalizado::CANCELADO): ?>
                <p class="notaPersonalizado"><strong>Su mensaje:</strong> «<?= htmlspecialchars($personalizado->getNotaTienda()) ?>»</p>
            <?php endif; ?>
        </div>
        <div class="franjaCotizarTotal">
            <span class="textoAyuda"><strong>Total (<?= $piezas === 1 ? '1 pieza' : $piezas . ' piezas' ?>)</strong></span>
            <strong class="precioFinal"><?= Numero::moneda((float) $personalizado->getTotal()) ?></strong>
        </div>
    </section>
<?php endif; ?>

<?php if ($enRevision || $personalizado->estaCotizado()): ?>
    <form method="post" action="<?= $urlBase ?>/pedidos/personalizado/rechazar" class="cajaAnular cajaRechazar"
        data-titulo="No puedo hacerlo" data-boton="Cancelar pedido" data-peligro
        data-confirmar="¿Cancelar el pedido personalizado #<?= $numero ?> de <?= htmlspecialchars($personalizado->getNombreCliente()) ?>? Esto no se puede deshacer.">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= $numero ?>">
        <div class="campo">
            <label for="motivo">¿No puede hacerlo? Dígale por qué a <?= htmlspecialchars($nombreCorto) ?></label>
            <textarea id="motivo" name="motivo" rows="2" maxlength="300" placeholder="Opcional. Por ejemplo: ya no me queda ese charm"></textarea>
        </div>
        <button type="submit" class="boton botonPeligro">No puedo hacerlo</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>