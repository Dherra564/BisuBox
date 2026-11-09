<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoCotizacion;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Solicitud #' . $cotizacion['id'];
$paginaActual = 'misCotizaciones';
$volverA = ['texto' => 'Mis cotizaciones', 'ruta' => '/mis-cotizaciones'];
$scriptsExtra = ['pedido.js'];
require __DIR__ . '/../Plantilla/encabezado.php';

$estado = $cotizacion['estado'];
$respondida = $cotizacion['precio'] !== null;
$explicaciones = [
    EstadoCotizacion::ENVIADA => 'La tienda todavía no la responde. Mientras tanto puede editarla o cancelarla.',
    EstadoCotizacion::RESPONDIDA => 'La tienda respondió. Revise los componentes y el precio, y acepte o rechace la propuesta.',
    EstadoCotizacion::ACEPTADA => 'Usted aceptó esta cotización y su compra quedó confirmada.',
    EstadoCotizacion::RECHAZADA => 'Usted rechazó esta cotización.',
    EstadoCotizacion::CANCELADA => 'Usted canceló esta solicitud.',
];

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo, string $extra = ''): string => trim('campo ' . $extra . (isset($errores[$campo]) ? ' campoConError' : ''));
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$esEnvio = ($datos['entrega'] ?? '') === FormaEntrega::ENVIO;
?>

<p class="cajaInformativa"><?= htmlspecialchars($explicaciones[$estado] ?? '') ?></p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<section class="factura">
    <h2>Solicitud a <a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars($cotizacion['enlaceTienda']) ?>" class="facturaProveedor"><?= htmlspecialchars($cotizacion['nombreTienda']) ?></a></h2>

    <?php if ($cotizacion['foto'] !== null): ?>
        <img src="<?= $urlBase ?>/fotos/cotizacion?archivo=<?= urlencode($cotizacion['foto']) ?>"
            alt="Foto de la pieza que pidió" class="fotoCotizacion">
    <?php endif; ?>

    <dl class="listaDatos">
        <dt>Estado</dt>
        <dd><span class="etiqueta <?= EstadoCotizacion::claseEtiqueta($estado) ?>"><?= $estado ?></span></dd>
        <dt>Enviada</dt>
        <dd><?= $cotizacion['fecha']->format('d/m/Y H:i') ?></dd>
        <dt>Lo que pidió</dt>
        <dd><?= nl2br(htmlspecialchars($cotizacion['descripcion'])) ?></dd>
        <?php if ($cotizacion['respuesta'] !== null): ?>
            <dt>Mensaje de la tienda</dt>
            <dd><?= nl2br(htmlspecialchars($cotizacion['respuesta'])) ?></dd>
        <?php endif; ?>
    </dl>

    <?php if ($respondida): ?>
        <h3>Lo que lleva la pieza</h3>
        <div class="tablaContenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th class="numero">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cotizacion['detalles'] as $detalle): ?>
                        <tr>
                            <td data-etiqueta="Componente"><?= htmlspecialchars($detalle['nombre']) ?></td>
                            <td data-etiqueta="Cantidad" class="numero"><?= htmlspecialchars(UnidadMedida::cantidad($detalle['cantidad'], $detalle['unidad'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="totalesCompra">
            <div class="totalCompra"><span>Precio</span><strong><?= Numero::moneda($cotizacion['precio']) ?></strong></div>
        </div>
    <?php endif; ?>
</section>

<?php if ($estado === EstadoCotizacion::ENVIADA): ?>
    <div class="grupoBotones">
        <a href="<?= $urlBase ?>/mis-cotizaciones/editar?id=<?= $cotizacion['id'] ?>" class="boton botonSecundario">Editar solicitud</a>
    </div>
    <div class="cajaAnular">
        <p>¿Ya no la quiere? Puede cancelarla mientras la tienda no la responda.</p>
        <form method="post" action="<?= $urlBase ?>/mis-cotizaciones/cancelar" class="formularioEnLinea"
            data-titulo="Cancelar solicitud" data-boton="Cancelar solicitud" data-peligro
            data-confirmar="¿Cancelar la solicitud #<?= $cotizacion['id'] ?>? Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">
            <button type="submit" class="boton botonPeligro">Cancelar solicitud</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($estado === EstadoCotizacion::RESPONDIDA): ?>
    <form method="post" action="<?= $urlBase ?>/mis-cotizaciones/aceptar" enctype="multipart/form-data" novalidate class="validarFormulario"
        data-confirmar="¿Aceptar la cotización y comprarle a <?= htmlspecialchars($cotizacion['nombreTienda']) ?> por <?= Numero::moneda($cotizacion['precio']) ?>? La compra queda confirmada de una vez."
        data-titulo="Aceptar cotización" data-boton="Aceptar y comprar">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">
        <input type="hidden" name="precioVisto" value="<?= htmlspecialchars(Numero::paraCampo($cotizacion['precio'])) ?>">

        <section class="formularioSeccion">
            <h2>¿Cómo lo quiere recibir?</h2>
            <div class="<?= $claseCampo('entrega') ?>">
                <span class="etiquetaCampo" id="etiquetaEntrega">Entrega <span class="obligatorio">*</span></span>
                <div class="opcionesUnidad" role="radiogroup" aria-labelledby="etiquetaEntrega">
                    <?php foreach (FormaEntrega::todas() as $codigo => $nombre): ?>
                        <label class="opcionUnidad">
                            <input type="radio" name="entrega" value="<?= $codigo ?>" data-entrega required
                                data-mensaje-requerido="Escoja cómo quiere recibir el pedido"
                                <?= ($datos['entrega'] ?? '') === $codigo ? 'checked' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= $mensajeError('entrega') ?>
            </div>

            <div class="<?= $claseCampo('direccion') ?>" data-campo-direccion <?= $esEnvio ? '' : 'hidden' ?>>
                <label for="direccion">Dirección de entrega <span class="obligatorio">*</span></label>
                <textarea id="direccion" name="direccion" rows="3" minlength="10" maxlength="250" data-contador
                    data-regla="direccion" <?= $esEnvio ? 'required' : '' ?>
                    data-mensaje-requerido="Escriba la dirección de entrega"
                    data-mensaje-largo="La dirección debe tener entre 10 y 250 caracteres"><?= $valor('direccion') ?></textarea>
                <p class="textoAyuda">Provincia, cantón, distrito y señas.</p>
                <?= $mensajeError('direccion') ?>
            </div>
        </section>

        <?php require __DIR__ . '/../Pedido/formularioPago.php'; ?>

        <div class="grupoBotones accionesFormulario">
            <button type="submit" class="boton">Aceptar y comprar</button>
        </div>
    </form>

    <div class="cajaAnular">
        <p>¿No le sirve la propuesta? Puede rechazarla.</p>
        <form method="post" action="<?= $urlBase ?>/mis-cotizaciones/rechazar" class="formularioEnLinea"
            data-titulo="Rechazar cotización" data-boton="Rechazar cotización" data-peligro
            data-confirmar="¿Rechazar la cotización #<?= $cotizacion['id'] ?>? Esto no se puede deshacer.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">
            <button type="submit" class="boton botonPeligro">Rechazar cotización</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($estado === EstadoCotizacion::ACEPTADA && $cotizacion['idPedido'] !== null): ?>
    <div class="grupoBotones">
        <a href="<?= $urlBase ?>/mis-pedidos/detalle?id=<?= $cotizacion['idPedido'] ?>" class="boton">Ver mi pedido #<?= $cotizacion['idPedido'] ?></a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>