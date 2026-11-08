<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCompraProveedor;
use Aplicacion\Nucleo\DatosCotizacion;
use Aplicacion\Nucleo\DatosProducto;
use Aplicacion\Nucleo\EstadoCotizacion;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;
use Aplicacion\Nucleo\Validador;

$estado = $cotizacion['estado'];
$puedeResponder = in_array($estado, [EstadoCotizacion::ENVIADA, EstadoCotizacion::RESPONDIDA], true);
$respondida = $cotizacion['precio'] !== null;

$titulo = 'Solicitud #' . $cotizacion['id'];
$paginaActual = 'cotizaciones';
$volverA = ['texto' => 'Cotizaciones', 'ruta' => '/cotizaciones'];
$scriptsExtra = $puedeResponder ? ['cotizacion.js'] : [];
require __DIR__ . '/../Plantilla/encabezado.php';

$explicaciones = [
    EstadoCotizacion::ENVIADA => 'El cliente espera su respuesta. Elija los componentes que lleva la pieza, ponga el precio y envíela.',
    EstadoCotizacion::RESPONDIDA => 'Ya respondió. Mientras el cliente no la acepte, puede ajustar los componentes y el precio.',
    EstadoCotizacion::ACEPTADA => 'El cliente aceptó la cotización y se creó su pedido. Confírmelo en Pedidos.',
    EstadoCotizacion::RECHAZADA => 'El cliente rechazó la propuesta.',
    EstadoCotizacion::CANCELADA => 'El cliente canceló la solicitud.',
];

$claseCampo = fn(string $campo, string $extra = ''): string =>
    trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));

$costoComponentes = round(array_sum(array_column($cotizacion['detalles'], 'subtotal')), 2);
$costoTotal = round($costoComponentes + (float) $cotizacion['manoObra'], 2);
?>

<p class="cajaInformativa"><?= htmlspecialchars($explicaciones[$estado] ?? '') ?></p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo. No se guardó nada todavía.</p>
    </div>
<?php endif; ?>

<section class="factura">
    <h2>Solicitud de <?= htmlspecialchars($cotizacion['nombreCliente']) ?></h2>

    <?php if ($cotizacion['foto'] !== null): ?>
        <img src="<?= $urlBase ?>/fotos/cotizacion?archivo=<?= urlencode($cotizacion['foto']) ?>"
            alt="Foto de la pieza que pidió el cliente" class="fotoCotizacion">
    <?php endif; ?>

    <dl class="listaDatos">
        <dt>Estado</dt>
        <dd><span class="etiqueta <?= EstadoCotizacion::claseEtiqueta($estado) ?>"><?= $estado ?></span></dd>
        <dt>Recibida</dt>
        <dd><?= $cotizacion['fecha']->format('d/m/Y H:i') ?></dd>
        <dt>Teléfono</dt>
        <dd><?= htmlspecialchars(Validador::formatearTelefono($cotizacion['telefonoCliente'])) ?></dd>
        <dt>Lo que pidió</dt>
        <dd><?= nl2br(htmlspecialchars($cotizacion['descripcion'])) ?></dd>
    </dl>

    <?php if ($respondida && !$puedeResponder): ?>
        <h3>Su respuesta</h3>
        <div class="tablaContenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th class="numero">Cantidad</th>
                        <th class="numero">Costo</th>
                        <th class="numero">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cotizacion['detalles'] as $detalle): ?>
                        <tr>
                            <td data-etiqueta="Componente"><?= htmlspecialchars($detalle['nombre']) ?></td>
                            <td data-etiqueta="Cantidad" class="numero"><?= htmlspecialchars(UnidadMedida::cantidad($detalle['cantidad'], $detalle['unidad'])) ?></td>
                            <td data-etiqueta="Costo" class="numero"><?= Numero::moneda($detalle['costoUnitario']) ?></td>
                            <td data-etiqueta="Subtotal" class="numero"><?= Numero::moneda($detalle['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <dl class="listaDatos">
            <dt>Componentes</dt>
            <dd><?= Numero::moneda($costoComponentes) ?></dd>
            <dt>Mano de obra</dt>
            <dd><?= Numero::moneda((float) $cotizacion['manoObra']) ?></dd>
            <dt>Costo total</dt>
            <dd><?= Numero::moneda($costoTotal) ?></dd>
            <dt>Precio para el cliente</dt>
            <dd><strong><?= Numero::moneda($cotizacion['precio']) ?></strong></dd>
            <dt>Ganancia</dt>
            <dd><?= Numero::moneda(round($cotizacion['precio'] - $costoTotal, 2)) ?></dd>
            <?php if ($cotizacion['respuesta'] !== null): ?>
                <dt>Mensaje al cliente</dt>
                <dd><?= nl2br(htmlspecialchars($cotizacion['respuesta'])) ?></dd>
            <?php endif; ?>
        </dl>
    <?php endif; ?>
</section>

<?php if ($estado === EstadoCotizacion::ACEPTADA && $cotizacion['idPedido'] !== null): ?>
    <div class="grupoBotones">
        <a href="<?= $urlBase ?>/pedidos/detalle?id=<?= $cotizacion['idPedido'] ?>" class="boton">Ver el pedido #<?= $cotizacion['idPedido'] ?></a>
    </div>
<?php endif; ?>

<?php if ($puedeResponder): ?>
    <?php
    $unidades = UnidadMedida::todas();
    $iconoQuitar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';

    $componentesPorTipo = [];
    foreach ($componentes as $componente) {
        $componentesPorTipo[$componente->getNombreTipo()][] = $componente;
    }

    $filaComponente = function (string $clave, array $linea) use ($claseCampo, $mensajeError, $componentesPorTipo, $unidades, $iconoQuitar): string {
        $campo = 'componente.' . $clave . '.';
        $nombre = 'componente[' . $clave . ']';

        $opciones = '<option value="">Escoja un componente</option>';
        foreach ($componentesPorTipo as $nombreTipo => $lista) {
            $opciones .= '<optgroup label="' . htmlspecialchars($nombreTipo) . '">';
            foreach ($lista as $componente) {
                $unidad = $unidades[$componente->getUnidad()] ?? $unidades[UnidadMedida::UNIDAD];
                $agotado = $componente->getExistencia() <= 0;
                $opciones .= '<option value="' . $componente->getIdComponente() . '"'
                    . ((string) $componente->getIdComponente() === $linea['componente'] ? ' selected' : '')
                    . ' data-existencia="' . $componente->getExistencia() . '"'
                    . ' data-costo="' . $componente->getCostoUnitario() . '"'
                    . ' data-plural="' . $unidad['plural'] . '"'
                    . ' data-metro="' . ($componente->getUnidad() === UnidadMedida::METRO ? '1' : '0') . '"'
                    . ' data-entera="' . ($componente->getUnidad() === UnidadMedida::UNIDAD ? '1' : '0') . '">'
                    . htmlspecialchars($componente->getNombre()) . ($agotado ? ' (agotado)' : '') . '</option>';
            }
            $opciones .= '</optgroup>';
        }

        $enCentimetros = ($linea['medida'] ?? '') === DatosCompraProveedor::CENTIMETROS;

        return '<div class="lineaReceta" data-linea data-clave="' . $clave . '">'
            . '<div class="' . $claseCampo($campo . 'componente', 'campoComponente') . '">'
            . '<label for="componente-' . $clave . '-componente" class="soloLectores">Componente</label>'
            . '<select id="componente-' . $clave . '-componente" name="' . $nombre . '[componente]" data-linea-componente>' . $opciones . '</select>'
            . '<p class="textoAyuda avisoExistencia" data-linea-aviso hidden></p>'
            . $mensajeError($campo . 'componente')
            . '</div>'
            . '<div class="' . $claseCampo($campo . 'cantidad', 'campoCantidadReceta') . '">'
            . '<label for="componente-' . $clave . '-cantidad" class="soloLectores">Cuánto lleva</label>'
            . '<div class="campoConUnidad">'
            . '<input type="text" id="componente-' . $clave . '-cantidad" name="' . $nombre . '[cantidad]" maxlength="13" inputmode="decimal"'
            . ' data-linea-cantidad placeholder="0" value="' . htmlspecialchars($linea['cantidad']) . '">'
            . '<select name="' . $nombre . '[medida]" class="selectorMedida" aria-label="Metros o centímetros" data-linea-medida hidden>'
            . '<option value="m"' . ($enCentimetros ? '' : ' selected') . '>m</option>'
            . '<option value="cm"' . ($enCentimetros ? ' selected' : '') . '>cm</option>'
            . '</select>'
            . '<span class="unidadCampo" data-linea-unidad></span>'
            . '</div>'
            . $mensajeError($campo . 'cantidad')
            . '</div>'
            . '<span class="subtotalReceta" data-linea-subtotal>₡0</span>'
            . '<button type="button" class="botonQuitar" aria-label="Quitar este componente" title="Quitar este componente" data-linea-quitar>'
            . $iconoQuitar . '</button>'
            . '</div>';
    };

    $lineaVacia = ['componente' => '', 'cantidad' => '', 'medida' => 'm'];
    $lineas = $datos['componentes'] !== [] ? $datos['componentes'] : [0 => $lineaVacia];
    $ajustando = $estado === EstadoCotizacion::RESPONDIDA;
    ?>

    <form method="post" action="<?= $urlBase ?>/cotizaciones/responder" novalidate class="validarFormulario formularioProducto"
        data-cotizacion data-maximo-componentes="<?= DatosCotizacion::MAXIMO_COMPONENTES ?>"
        data-confirmar="<?= $ajustando ? '¿Guardar los cambios? El cliente verá el precio nuevo.' : '¿Enviar la respuesta al cliente? Podrá ajustarla mientras no la acepte.' ?>"
        data-titulo="<?= $ajustando ? 'Ajustar respuesta' : 'Enviar respuesta' ?>" data-boton="<?= $ajustando ? 'Guardar cambios' : 'Enviar respuesta' ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= (int) $cotizacion['id'] ?>">

        <div class="pasosProducto">
            <section class="formularioSeccion pasoProducto">
                <h2><span class="numeroPaso" aria-hidden="true">1</span> ¿Qué lleva la pieza?</h2>
                <p class="textoAyuda">Elija los componentes de su inventario y cuánto lleva de cada uno.</p>
                <?= $mensajeError('lineas') ?>

                <div class="listaReceta" data-lineas>
                    <?php foreach ($lineas as $clave => $linea): ?>
                        <?= $filaComponente((string) $clave, $linea) ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="enlaceAgregar" data-linea-agregar>+ Otro componente</button>
            </section>

            <section class="formularioSeccion pasoProducto">
                <h2><span class="numeroPaso" aria-hidden="true">2</span> Su trabajo</h2>
                <div class="<?= $claseCampo('manoObra') ?>">
                    <label for="manoObra">Mano de obra <span class="obligatorio">*</span></label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="manoObra" name="manoObra" maxlength="13" inputmode="decimal" required
                            data-regla="cantidad" data-mano-obra placeholder="0"
                            data-mensaje-requerido="Escriba la mano de obra, o 0 si no la cobra"
                            data-mensaje-regla="Escriba un monto, por ejemplo 2000 o 2000,50"
                            value="<?= $valor('manoObra') ?>">
                    </div>
                    <p class="textoAyuda">Escriba 0 si no cobra su trabajo.</p>
                    <?= $mensajeError('manoObra') ?>
                </div>
            </section>
        </div>

        <div class="cierreProducto">
            <section class="ticketPrecio" data-precio>
                <h2>Precio para el cliente</h2>

                <dl class="cuentaPrecio" aria-live="polite">
                    <div>
                        <dt>Componentes</dt>
                        <dd data-total-componentes>₡0</dd>
                    </div>
                    <div>
                        <dt>Su trabajo</dt>
                        <dd data-total-mano-obra>₡0</dd>
                    </div>
                    <div class="cuentaTotal">
                        <dt>Costo total</dt>
                        <dd data-costo-total>₡0</dd>
                    </div>
                </dl>

                <fieldset class="opcionesGanancia">
                    <legend>¿Cuánto quiere ganar?</legend>
                    <div class="chipsGanancia">
                        <?php foreach (DatosProducto::GANANCIAS as $porcentaje): ?>
                            <label class="chipGanancia">
                                <input type="radio" name="ganancia" value="<?= $porcentaje ?>" data-ganancia
                                    <?= ($datos['ganancia'] ?? '') === (string) $porcentaje ? 'checked' : '' ?>>
                                <span><?= $porcentaje ?>%</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="<?= $claseCampo('precio', 'campoPrecioVenta') ?>">
                    <label for="precio">Precio <span class="obligatorio">*</span></label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="precio" name="precio" maxlength="13" inputmode="decimal" required
                            data-regla="cantidad" data-precio-venta placeholder="0"
                            data-mensaje-requerido="Escriba el precio para el cliente"
                            data-mensaje-regla="Escriba un monto, por ejemplo 8500 o 8500,50" value="<?= $valor('precio') ?>">
                    </div>
                    <p class="textoAyuda">Se llena solo con la ganancia que escoja; puede cambiarlo.</p>
                    <?= $mensajeError('precio') ?>
                </div>

                <p class="resultadoGanancia" data-resultado-ganancia hidden></p>
            </section>

            <div class="<?= $claseCampo('respuesta') ?>">
                <label for="respuesta">Mensaje para el cliente</label>
                <textarea id="respuesta" name="respuesta" rows="3" maxlength="300" data-contador data-mensaje
                    data-mensaje-largo="El mensaje puede tener hasta 300 caracteres"><?= $valor('respuesta') ?></textarea>
                <p class="textoAyuda">Opcional. Por ejemplo, el tiempo que tarda o una variación del diseño.</p>
                <?= $mensajeError('respuesta') ?>
            </div>

            <div class="grupoBotones accionesFormulario">
                <a href="<?= $urlBase ?>/cotizaciones" class="boton botonSecundario">Cancelar</a>
                <button type="submit" class="boton"><?= $ajustando ? 'Guardar cambios' : 'Enviar respuesta' ?></button>
            </div>
        </div>
    </form>

    <template data-linea-plantilla>
        <?= $filaComponente('__n__', $lineaVacia) ?>
    </template>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>