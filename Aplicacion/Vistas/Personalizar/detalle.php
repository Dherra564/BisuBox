<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UnidadMedida;

$titulo = 'Pedido personalizado #' . $personalizado->getIdPersonalizado();
$paginaActual = 'misPedidos';
$volverA = ['texto' => 'Mis pedidos personalizados', 'ruta' => '/mis-pedidos/personalizados'];
require __DIR__ . '/../Plantilla/encabezado.php';

$fotoComponente = fn(?string $foto): string => $foto !== null
    ? $urlBase . '/fotos/componente?archivo=' . urlencode($foto)
    : $urlBase . '/imagenes/productoPorDefecto.svg';
$foto = $personalizado->getFotoProducto();
$estado = $personalizado->getEstado();
$cancelado = $estado === EstadoPersonalizado::CANCELADO;
$comprado = $estado === EstadoPersonalizado::COMPRADO;
$cotizado = $personalizado->estaCotizado();
$paso = match ($estado) {
    EstadoPersonalizado::EN_REVISION => 2,
    EstadoPersonalizado::COTIZADO => 3,
    default => 4,
};
$numero = $personalizado->getIdPersonalizado();
$partes = array_merge(['los materiales', 'la mano de obra'], array_map(fn($extra): string => mb_strtolower($extra->getConcepto(), 'UTF-8'), $personalizado->getExtras()));
$incluye = count($partes) > 1 ? implode(', ', array_slice($partes, 0, -1)) . ' y ' . end($partes) : $partes[0];
?>

<div class="estadoPersonalizado">
    <span class="etiqueta <?= EstadoPersonalizado::claseEtiqueta($estado) ?>"><?= $estado ?></span>
    <?php if (!$cancelado): ?>
        <ol class="avancePersonalizado" aria-label="Avance">
            <li class="pasoHecho"><span aria-hidden="true">✓</span>Enviado</li>
            <li class="<?= $paso > 2 ? 'pasoHecho' : 'pasoActualAvance' ?>"><span aria-hidden="true"><?= $paso > 2 ? '✓' : '2' ?></span>La tienda cotiza</li>
            <li class="<?= $paso > 3 ? 'pasoHecho' : ($paso === 3 ? 'pasoActualAvance' : '') ?>"><span aria-hidden="true"><?= $paso > 3 ? '✓' : '3' ?></span><?= $comprado ? 'Comprado' : 'Usted decide' ?></li>
        </ol>
    <?php endif; ?>
</div>

<?php if ($cancelado): ?>
    <div class="cajaInformativa">
        <p><strong><?= $personalizado->getCanceladoPor() === Rol::CLIENTE ? 'Usted canceló este pedido.' : 'La tienda no puede hacer este pedido.' ?></strong></p>
        <?php if ($personalizado->getCanceladoPor() === Rol::VENDEDOR && $personalizado->getNotaTienda() !== null): ?>
            <p>«<?= htmlspecialchars($personalizado->getNotaTienda()) ?>»</p>
        <?php endif; ?>
    </div>
<?php elseif ($comprado): ?>
    <p class="cajaInformativa">
        Ya lo compró. Siga su pedido en <a href="<?= $urlBase ?>/mis-pedidos/detalle?id=<?= $personalizado->getIdPedido() ?>">el pedido #<?= $personalizado->getIdPedido() ?></a>.
    </p>
<?php endif; ?>

<div class="detallePersonalizado">
    <div class="tarjetaBase">
        <div class="fotoBase">
            <img src="<?= $foto !== null ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto) : $urlBase . '/imagenes/productoPorDefecto.svg' ?>"
                alt="" class="<?= $foto === null ? 'fotoBaseVacia' : '' ?>">
        </div>
        <div class="textoBase">
            <strong class="nombreBase"><?= htmlspecialchars($personalizado->getNombre()) ?>, a su gusto</strong>
            <span class="textoAyuda"><?= htmlspecialchars($personalizado->getNombreTienda()) ?></span>
            <span class="textoAyuda"><?= $personalizado->getPiezas() === 1 ? '1 pieza' : $personalizado->getPiezas() . ' piezas' ?> · enviado el <?= $personalizado->getFechaRegistro()->format('d/m/Y H:i') ?></span>
        </div>
    </div>

    <section aria-labelledby="tituloLleva" class="llevaPersonalizado">
        <h2 id="tituloLleva">Lo que va a llevar cada pieza</h2>
        <ul class="listaLlevaPersonalizado">
            <?php foreach ($personalizado->getComponentes() as $linea): ?>
                <li class="<?= $linea->fueCambiado() ? 'lineaCambiada' : '' ?>">
                    <img src="<?= htmlspecialchars($fotoComponente($linea->getFoto())) ?>" alt="" loading="lazy">
                    <span class="lineaTexto">
                        <span class="tipoPieza"><?= htmlspecialchars($linea->getNombreTipo()) ?><?= $linea->fueCambiado() ? ' · su cambio' : '' ?></span>
                        <strong><?= htmlspecialchars($linea->getNombre()) ?></strong>
                        <?php if ($linea->fueCambiado()): ?>
                            <span class="textoAyuda">Antes: <?= htmlspecialchars($linea->getNombreOriginal()) ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="lineaCantidad"><?= htmlspecialchars(UnidadMedida::cantidad($linea->getCantidad(), $linea->getUnidad())) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($personalizado->getNotaCliente() !== null): ?>
            <p class="notaPersonalizado"><strong>Su nota:</strong> «<?= htmlspecialchars($personalizado->getNotaCliente()) ?>»</p>
        <?php endif; ?>
        <?php if (!$cancelado && $personalizado->getNotaTienda() !== null): ?>
            <p class="notaPersonalizado"><strong>La tienda le dice:</strong> «<?= htmlspecialchars($personalizado->getNotaTienda()) ?>»</p>
        <?php endif; ?>
    </section>
</div>

<?php if ($personalizado->estaEnRevision()): ?>
    <section class="franjaPrecio">
        <div class="franjaPrecioTexto">
            <div>
                <span class="textoAyuda"><strong>Total de componentes</strong></span>
                <strong class="totalComponentes"><?= Numero::moneda($personalizado->getTotalComponentes()) ?></strong>
            </div>
            <p class="textoAyuda">Es solo lo de los materiales. La tienda le suma la mano de obra y le avisamos aquí cuando le mande el precio final.</p>
        </div>
        <form method="post" action="<?= $urlBase ?>/mis-pedidos/personalizado/cancelar" class="formularioEnLinea"
            data-titulo="Cancelar pedido" data-boton="Sí, cancelar" data-peligro
            data-confirmar="¿Ya no quiere esta pieza? El pedido se cancela y le avisamos a la tienda.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= $numero ?>">
            <button type="submit" class="boton botonSecundario botonPeligroSuave">Ya no lo quiero</button>
        </form>
    </section>
<?php elseif ($cotizado): ?>
    <section class="franjaPrecio franjaPrecioCotizado" aria-labelledby="tituloPrecio">
        <div class="franjaPrecioTexto">
            <div>
                <h2 id="tituloPrecio">Precio de la tienda</h2>
                <strong class="precioFinal"><?= Numero::moneda((float) $personalizado->getTotal()) ?></strong>
            </div>
            <p class="textoAyuda">
                <?= $personalizado->getPiezas() === 1 ? '1 pieza' : $personalizado->getPiezas() . ' piezas de ' . Numero::moneda((float) $personalizado->getPrecio()) ?>.
                Incluye <?= htmlspecialchars($incluye) ?>.
                Al comprar escoge cómo se lo entregan y cómo paga.
            </p>
        </div>
        <div class="grupoBotones">
            <form method="post" action="<?= $urlBase ?>/mis-pedidos/personalizado/cancelar" class="formularioEnLinea"
                data-titulo="¿No quiere comprarlo?" data-boton="Sí, cancelar pedido" data-peligro
                data-confirmar="El pedido se cancela y le avisamos a la tienda. No se le cobra nada.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= $numero ?>">
                <button type="submit" class="boton botonSecundario botonRegistrar">No, gracias</button>
            </form>
            <a href="<?= $urlBase ?>/mis-pedidos/personalizado/comprar?id=<?= $numero ?>" class="boton botonRegistrar">Comprar</a>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>