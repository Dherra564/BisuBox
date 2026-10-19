<?php

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\TipoContacto;
use Configuracion\Configuracion;

$scriptsExtra = $scriptsExtra ?? [];
?>
</main>
</div>

<footer class="pieSitio" id="contacto">
    <?php if ($tiendaSitio !== null): ?>
        <div class="pieSitioColumnas">
            <div>
                <p class="pieSitioNombre"><?= htmlspecialchars((string) $tiendaSitio->getTiendaNombre()) ?></p>
                <?php if ((string) $tiendaSitio->getTiendaDescripcion() !== ''): ?>
                    <p><?= htmlspecialchars((string) $tiendaSitio->getTiendaDescripcion()) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <p class="pieSitioTitulo">Contacto</p>
                <?php if ($tiendaSitio->getContactos() === []): ?>
                    <p>Esta tienda todavía no tiene contactos.</p>
                <?php else: ?>
                    <ul class="pieSitioContactos">
                        <?php foreach ($tiendaSitio->getContactos() as $contacto): ?>
                            <li>
                                <span
                                    class="pieSitioDato"><?= htmlspecialchars(TipoContacto::nombre($contacto->getTipo())) ?></span>
                                <?= htmlspecialchars(TipoContacto::formatear($contacto->getTipo(), $contacto->getValor())) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div>
                <p class="pieSitioTitulo">Su cuenta</p>
                <ul>
                    <?php if ($haySesion): ?>
                        <li><a href="<?= $urlBase ?>/perfil">Mi perfil</a></li>
                        <li>
                            <form method="post" action="<?= $urlBase ?>/salir" class="formularioEnLinea"
                                data-confirmar="¿Desea cerrar sesión?" data-titulo="Cerrar sesión" data-boton="Cerrar sesión">
                                <?= Csrf::campo() ?>
                                <button type="submit" class="botonEnlace textoPeligro">Cerrar sesión</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= $urlBase ?>/ingresar?volver=<?= $volverAqui ?>">Ingresar</a></li>
                        <li><a href="<?= $urlBase ?>/registro?volver=<?= $volverAqui ?>">Crear cuenta</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <p class="pieSitioHecha">Tienda hecha con BisuBox</p>
    <?php else: ?>
        <p class="pieSitioHecha">BisuBox · Sistema para negocios de bisutería</p>
    <?php endif; ?>
</footer>

<?php if ($navegacion['whatsApp'] !== null): ?>
    <a href="<?= htmlspecialchars($navegacion['whatsApp']) ?>" class="botonWhatsApp" target="_blank" rel="noopener"
        aria-label="Escribir a <?= htmlspecialchars((string) $tiendaSitio->getTiendaNombre()) ?> por WhatsApp">
        <svg viewBox="0 0 32 32" aria-hidden="true">
            <path
                d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3Zm0 23.6a10.6 10.6 0 0 1-5.4-1.5l-.4-.2-3.9 1 1-3.8-.2-.4A10.6 10.6 0 1 1 16 26.6Zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.7 8.7 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7a.6.6 0 0 0 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8 1 3.9.8a3.3 3.3 0 0 0 2.1-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.2-.3-.3-.6-.5Z" />
        </svg>
    </a>
<?php endif; ?>

<?php require __DIR__ . '/modalConfirmacion.php'; ?>

<script src="<?= Configuracion::recurso('js/alertas.js') ?>"></script>
<script src="<?= Configuracion::recurso('js/validaciones.js') ?>"></script>
<script src="<?= Configuracion::recurso('js/menu.js') ?>"></script>
<?php if ($navegacion['avisos']): ?>
    <script src="<?= Configuracion::recurso('js/avisos.js') ?>"></script>
<?php endif; ?>
<?php foreach ($scriptsExtra as $script): ?>
    <script src="<?= htmlspecialchars(Configuracion::recurso('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>

</html>