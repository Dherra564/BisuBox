<?php
/**
 * Pie de la página de la tienda: contactos, enlaces de la cuenta y el botón flotante de WhatsApp.
 * Usa las variables que dejó encabezadoTienda.php
 * @var \Aplicacion\Modelos\Vendedor $tiendaPagina
 * @var \Aplicacion\Modelos\VendedorContacto|null $whatsAppTienda
 * @var string $rutaTienda
 * @var bool $esClienteAqui
 * @var string[]|null $scriptsExtra
 */

use Aplicacion\Nucleo\TipoContacto;
use Aplicacion\Nucleo\WhatsApp;
use Configuracion\Configuracion;

$scriptsExtra = $scriptsExtra ?? [];
$enlaceWhatsApp = $whatsAppTienda !== null
    ? WhatsApp::enlace($whatsAppTienda->getValor(), 'Hola, les escribo desde su tienda en línea.')
    : null;
?>
    </main>

    <footer class="tiendaPie" id="contacto">
        <div class="tiendaContenedor tiendaPieColumnas">
            <div>
                <p class="tiendaPieNombre"><?= htmlspecialchars((string) $tiendaPagina->getTiendaNombre()) ?></p>
                <?php if ((string) $tiendaPagina->getTiendaDescripcion() !== ''): ?>
                    <p><?= htmlspecialchars((string) $tiendaPagina->getTiendaDescripcion()) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <p class="tiendaPieTitulo">Contacto</p>
                <?php if ($tiendaPagina->getContactos() === []): ?>
                    <p>Esta tienda todavía no tiene contactos.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($tiendaPagina->getContactos() as $contacto): ?>
                            <li>
                                <span class="tiendaPieDato"><?= htmlspecialchars(TipoContacto::nombre($contacto->getTipo())) ?></span>
                                <?= htmlspecialchars(TipoContacto::formatear($contacto->getTipo(), $contacto->getValor())) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div>
                <p class="tiendaPieTitulo">Su cuenta</p>
                <ul>
                    <?php if ($esClienteAqui): ?>
                        <li><a href="<?= $urlBase ?>/perfil">Mi cuenta</a></li>
                        <li><a href="<?= $urlBase ?>/mis-pedidos">Mis pedidos</a></li>
                        <li><a href="<?= $urlBase ?>/carrito">Carrito</a></li>
                    <?php else: ?>
                        <li><a href="<?= $urlBase ?>/ingresar?volver=<?= $volverAqui ?>">Ingresar</a></li>
                        <li><a href="<?= $urlBase ?>/registro/cliente?volver=<?= $volverAqui ?>">Crear cuenta</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <p class="tiendaHecha">Tienda hecha con BisuBox</p>
    </footer>

    <?php if ($enlaceWhatsApp !== null): ?>
        <a href="<?= htmlspecialchars($enlaceWhatsApp) ?>" class="botonWhatsApp" target="_blank" rel="noopener"
            aria-label="Escribir a <?= htmlspecialchars((string) $tiendaPagina->getTiendaNombre()) ?> por WhatsApp">
            <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3Zm0 23.6a10.6 10.6 0 0 1-5.4-1.5l-.4-.2-3.9 1 1-3.8-.2-.4A10.6 10.6 0 1 1 16 26.6Zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.7 8.7 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7a.6.6 0 0 0 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8 1 3.9.8a3.3 3.3 0 0 0 2.1-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.2-.3-.3-.6-.5Z"/></svg>
        </a>
    <?php endif; ?>

    <?php require __DIR__ . '/modalConfirmacion.php'; ?>

    <script src="<?= Configuracion::recurso('js/alertas.js') ?>"></script>
    <script src="<?= Configuracion::recurso('js/validaciones.js') ?>"></script>
    <?php foreach ($scriptsExtra as $script): ?>
        <script src="<?= htmlspecialchars(Configuracion::recurso('js/' . $script)) ?>"></script>
    <?php endforeach; ?>
</body>

</html>
