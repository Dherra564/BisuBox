<?php
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Numero;

$titulo = 'Personalizador';
$paginaActual = 'personalizador';
$botonesAccion = [['texto' => 'Agregar fotos de piezas', 'ruta' => '/personalizador/fotos']];
require __DIR__ . '/../Plantilla/encabezado.php';

$valor = fn(string $campo): string => htmlspecialchars((string) ($datos[$campo] ?? ''));
$claseCampo = fn(string $campo, string $extra = 'campo'): string => isset($errores[$campo]) ? $extra . ' campoConError' : $extra;
$mensajeError = fn(string $campo): string => isset($errores[$campo])
    ? '<p class="errorCampo">' . htmlspecialchars($errores[$campo]) . '</p>'
    : '';
$total = count($piezas);
$faltan = $total - $conFoto;
$piezasConFoto = array_filter($piezas, fn(array $pieza): bool => $pieza['foto'] !== null);
$piezasSinFoto = array_filter($piezas, fn(array $pieza): bool => $pieza['foto'] === null);
?>

<p class="introPagina">Aquí prepara las piezas que sus clientes usan para armar pulseras, tobilleras y collares viendo
    cómo quedan.</p>

<?php if ($errores !== []): ?>
    <div class="alerta alertaError alertaFormulario" role="alert">
        <span class="alertaIcono" aria-hidden="true">✖</span>
        <p><strong>Error:</strong> Revise los campos marcados en rojo.</p>
    </div>
<?php endif; ?>

<?php if ($faltan > 0): ?>
    <p class="avisoPiezasFoto" role="note">
        <strong><?= $faltan === 1 ? 'Le falta 1 pieza con foto.' : 'Le faltan ' . $faltan . ' piezas con foto.' ?></strong>
        Sus clientes solo pueden usar las piezas con foto.
        <a href="<?= $urlBase ?>/personalizador/fotos">Agregar fotos de piezas</a>
    </p>
<?php endif; ?>

<section class="formularioSeccion seccionPersonalizador">
    <h2>En su tienda</h2>
    <?php if ($hilos === []): ?>
        <div class="cajaAdvertencia">
            <p>Primero registre en el inventario el hilo que usa, por ejemplo "Hilo elástico transparente", contado por
                metro.</p>
            <p><a href="<?= $urlBase ?>/componentes/nuevo" class="boton botonPequeno">Agregar componente</a></p>
        </div>
    <?php else: ?>
        <form method="post" action="<?= $urlBase ?>/personalizador/configuracion" novalidate class="validarFormulario">
            <?= Csrf::campo() ?>
            <div class="filaCampos filaTres">
                <div class="<?= $claseCampo('hilo') ?>">
                    <label for="hilo">Hilo de las piezas <span class="obligatorio">*</span></label>
                    <select id="hilo" name="hilo" required data-mensaje-requerido="Elija el hilo que se usa en las piezas">
                        <option value="">Elija un hilo</option>
                        <?php foreach ($hilos as $hilo): ?>
                            <option value="<?= $hilo['idComponente'] ?>" <?= $datos['hilo'] === (string) $hilo['idComponente'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hilo['nombre']) ?> (<?= Numero::formatear($hilo['existencia']) ?> m)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="textoAyuda">Se cobra por los centímetros que lleva cada pieza.</p>
                    <?= $mensajeError('hilo') ?>
                </div>
                <div class="<?= $claseCampo('manoObra') ?>">
                    <label for="manoObra">Mano de obra por pieza <span class="obligatorio">*</span></label>
                    <div class="campoConUnidad campoConMoneda">
                        <span class="unidadCampo">₡</span>
                        <input type="text" id="manoObra" name="manoObra" maxlength="12" inputmode="decimal" required
                            data-regla="manoObra" data-mensaje-requerido="Escriba la mano de obra"
                            value="<?= $valor('manoObra') ?>">
                    </div>
                    <?= $mensajeError('manoObra') ?>
                </div>
                <div class="<?= $claseCampo('ganancia') ?>">
                    <label for="ganancia">Ganancia <span class="obligatorio">*</span></label>
                    <div class="campoConUnidad">
                        <input type="text" id="ganancia" name="ganancia" maxlength="3" inputmode="numeric" required
                            data-regla="ganancia" data-mensaje-requerido="Escriba la ganancia"
                            value="<?= $valor('ganancia') ?>">
                        <span class="unidadCampo">%</span>
                    </div>
                    <?= $mensajeError('ganancia') ?>
                </div>
            </div>
            <p class="formulaPrecio">Precio para el cliente = (piezas + hilo + mano de obra) + ganancia, redondeado a ₡50.
            </p>
            <div class="pieConfiguracion">
                <div class="<?= $claseCampo('activo', 'campo campoCasilla') ?>">
                    <input type="checkbox" id="activo" name="activo" value="1" <?= $datos['activo'] ? 'checked' : '' ?>>
                    <label for="activo">Mostrar el personalizador en mi tienda
                        <span class="textoAyuda">Sus clientes verán el botón "Arme su pieza".</span></label>
                    <?= $mensajeError('activo') ?>
                </div>
                <div class="grupoBotones">
                    <?php if ($datos['activo']): ?>
                        <a href="<?= $urlBase ?>/tienda/<?= htmlspecialchars((string) $tienda->getTiendaEnlace()) ?>/arma-tu-pieza"
                            class="boton botonSecundario" target="_blank" rel="noopener">Ver como cliente</a>
                    <?php endif; ?>
                    <button type="submit" class="boton">Guardar</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="formularioSeccion seccionPersonalizador">
    <div class="encabezadoSeccion">
        <h2>Piezas</h2>
        <span class="etiqueta <?= $conFoto > 0 ? 'etiquetaActivo' : 'etiquetaInactivo' ?>"><?= $conFoto ?> de
            <?= $total ?> con foto</span>
    </div>

    <?php if ($total === 0): ?>
        <div class="estadoVacio">
            <p>No tiene componentes que se cuenten por unidad, como perlas o dijes.</p>
            <p><a href="<?= $urlBase ?>/componentes/nuevo" class="boton">Agregar componente</a></p>
        </div>
    <?php else: ?>
        <?php if ($piezasConFoto !== []): ?>
            <h3 class="subtituloPiezas">Con foto <span>Aparecen para sus clientes</span></h3>
            <p class="textoAyuda">El tamaño es lo que la pieza ocupa a lo largo del hilo.</p>
            <ul class="piezasConFoto">
                <?php foreach ($piezasConFoto as $pieza): ?>
                    <?php $id = $pieza['idComponente'];
                    $nombre = htmlspecialchars($pieza['nombre']); ?>
                    <li class="piezaConFoto">
                        <div class="miniaturaPieza fondoCuadros">
                            <img src="<?= $urlBase ?>/fotos/pieza?archivo=<?= urlencode($pieza['foto']) ?>" alt="<?= $nombre ?>"
                                loading="lazy">
                        </div>
                        <div class="cuerpoPieza">
                            <strong><?= $nombre ?></strong>
                            <span class="textoAyuda"><?= htmlspecialchars($pieza['tipo']) ?> · <?= $pieza['existencia'] ?> en
                                existencia</span>
                            <form method="post" action="<?= $urlBase ?>/personalizador/tamano" novalidate
                                class="validarFormulario tamanoPieza">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="componente" value="<?= $id ?>">
                                <div class="campo">
                                    <label for="tamano<?= $id ?>">Tamaño</label>
                                    <div class="campoConUnidad">
                                        <input type="text" id="tamano<?= $id ?>" name="tamano" maxlength="4" inputmode="decimal"
                                            required data-regla="tamanoPieza"
                                            data-mensaje-requerido="Escriba el tamaño en milímetros"
                                            value="<?= htmlspecialchars(str_replace('.', ',', rtrim(rtrim(number_format((float) $pieza['tamano'], 1, '.', ''), '0'), '.'))) ?>">
                                        <span class="unidadCampo">mm</span>
                                    </div>
                                </div>
                                <button type="submit" class="boton botonSecundario botonPequeno">Guardar</button>
                            </form>
                        </div>
                        <form method="post" action="<?= $urlBase ?>/personalizador/quitar" class="formularioEnLinea quitarFotoPieza"
                            data-titulo="Quitar foto" data-boton="Quitar" data-peligro
                            data-confirmar="¿Quitar la foto de «<?= $nombre ?>»? Sus clientes ya no podrán usar esta pieza. El componente sigue en su inventario.">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="componente" value="<?= $id ?>">
                            <button type="submit" class="botonEnlace">Quitar foto</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($piezasSinFoto !== []): ?>
            <h3 class="subtituloPiezas">Sin foto <span>Sus clientes todavía no las ven</span></h3>
            <ul class="piezasSinFoto">
                <?php foreach ($piezasSinFoto as $pieza): ?>
                    <li>
                        <span class="nombrePiezaSinFoto"><strong><?= htmlspecialchars($pieza['nombre']) ?></strong>
                            <span class="textoAyuda"><?= htmlspecialchars($pieza['tipo']) ?> · <?= $pieza['existencia'] ?> en
                                existencia</span></span>
                        <a href="<?= $urlBase ?>/personalizador/fotos" class="botonEnlace">Agregar foto</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>