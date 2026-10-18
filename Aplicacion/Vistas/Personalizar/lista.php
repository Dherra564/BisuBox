<?php

use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;

$titulo = UsuarioActual::esVendedor() ? 'Mis compras' : 'Mis pedidos';
$paginaActual = 'misPedidos';
require __DIR__ . '/../Plantilla/encabezado.php';

$pestanas = ['' => 'Todos', EstadoPersonalizado::EN_REVISION => 'En revisión', EstadoPersonalizado::COTIZADO => 'Por decidir',
    EstadoPersonalizado::COMPRADO => 'Comprados', EstadoPersonalizado::CANCELADO => 'Cancelados'];
?>

<nav class="cambioVista" aria-label="Tipo de pedido">
    <a href="<?= $urlBase ?>/mis-pedidos">Pedidos</a>
    <a href="<?= $urlBase ?>/mis-pedidos/personalizados" class="cambioVistaActivo" aria-current="page">
        Personalizados
        <?php if ($conteos[EstadoPersonalizado::COTIZADO] > 0): ?>
            <span class="numeroMenu"><?= $conteos[EstadoPersonalizado::COTIZADO] ?></span>
        <?php endif; ?>
    </a>
</nav>

<?php if ($conteos[''] === 0): ?>
    <div class="estadoVacio">
        <p>Todavía no ha pedido piezas personalizadas. En cualquier tienda, toque «Personalizar» en un producto.</p>
        <p><a href="<?= $urlBase ?>/" class="boton">Ver productos</a></p>
    </div>
<?php else: ?>
    <section class="panelCompras">
        <nav class="pestanas" aria-label="Estado de los pedidos personalizados">
            <?php foreach ($pestanas as $codigo => $nombre): ?>
                <a href="<?= $urlBase ?>/mis-pedidos/personalizados<?= $codigo !== '' ? '?estado=' . urlencode($codigo) : '' ?>"
                    class="pestana <?= $estado === $codigo ? 'pestanaActiva' : '' ?>" <?= $estado === $codigo ? 'aria-current="page"' : '' ?>>
                    <?= $nombre ?> <span class="pestanaCantidad"><?= $conteos[$codigo] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($personalizados === []): ?>
            <p class="estadoVacio estadoVacioPequeno">No tiene pedidos personalizados en este estado.</p>
        <?php else: ?>
            <div class="listaPersonalizados">
                <?php foreach ($personalizados as $personalizado): ?>
                    <?php
                    $foto = $personalizado->getFotoProducto();
                    $cambios = array_map(fn($linea): string => $linea->getNombre(), $personalizado->getCambios());
                    $enlace = $urlBase . '/mis-pedidos/personalizado?id=' . $personalizado->getIdPersonalizado();
                    ?>
                    <article class="tarjetaPersonalizado <?= $personalizado->estaCotizado() ? 'tarjetaPersonalizadoCotizado' : '' ?>">
                        <img src="<?= $foto !== null ? $urlBase . '/fotos/producto?archivo=' . urlencode($foto) : $urlBase . '/imagenes/productoPorDefecto.svg' ?>"
                            alt="" class="fotoPersonalizado" loading="lazy">
                        <div class="textoPersonalizado">
                            <div class="tituloPersonalizado">
                                <strong><?= htmlspecialchars($personalizado->getNombre()) ?>, personalizado</strong>
                                <span class="etiqueta <?= EstadoPersonalizado::claseEtiqueta($personalizado->getEstado()) ?>"><?= $personalizado->getEstado() ?></span>
                            </div>
                            <span class="textoAyuda">
                                #<?= $personalizado->getIdPersonalizado() ?> · <?= htmlspecialchars($personalizado->getNombreTienda()) ?> ·
                                <?= $personalizado->getPiezas() === 1 ? '1 pieza' : $personalizado->getPiezas() . ' piezas' ?> ·
                                <?= $personalizado->getFechaRegistro()->format('d/m/Y H:i') ?>
                            </span>
                            <?php if ($cambios !== []): ?>
                                <span class="textoAyuda">Con: <?= htmlspecialchars(implode(', ', $cambios)) ?></span>
                            <?php endif; ?>

                            <?php if ($personalizado->estaCotizado()): ?>
                                <span>La tienda ya le mandó el precio: <strong class="precioPersonalizado"><?= Numero::moneda((float) $personalizado->getTotal()) ?></strong></span>
                            <?php elseif ($personalizado->estaEnRevision()): ?>
                                <span>La tienda está revisando su pedido para darle el precio.</span>
                            <?php elseif ($personalizado->getEstado() === EstadoPersonalizado::COMPRADO): ?>
                                <span>Lo compró por <?= Numero::moneda((float) $personalizado->getTotal()) ?>.
                                    <a href="<?= $urlBase ?>/mis-pedidos/detalle?id=<?= $personalizado->getIdPedido() ?>">Ver el pedido #<?= $personalizado->getIdPedido() ?></a></span>
                            <?php else: ?>
                                <span><?= $personalizado->getCanceladoPor() === Rol::CLIENTE ? 'Usted lo canceló.' : 'La tienda no puede hacerlo.' ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $enlace ?>" class="boton <?= $personalizado->estaCotizado() ? '' : 'botonSecundario' ?>">
                            <?= $personalizado->estaCotizado() ? 'Ver y decidir' : 'Ver detalle' ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php
            $rutaPaginacion = '/mis-pedidos/personalizados';
            $parametrosPaginacion = ['estado' => $estado];
            $nombreRegistros = 'pedidos personalizados';
            require __DIR__ . '/../Plantilla/paginacion.php';
            ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>