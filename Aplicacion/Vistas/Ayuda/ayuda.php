<?php
/**
  * @var \Aplicacion\Modelos\Usuario[] $administradores
 * @var string $urlBase 
 */

$titulo = 'Ayuda';
$paginaActual = 'ayuda';
require __DIR__ . '/../Plantilla/encabezado.php';

$formatoTelefono = fn(?string $telefono): string => strlen((string) $telefono) === 8
    ? substr($telefono, 0, 4) . '-' . substr($telefono, 4)
    : (string) $telefono;
?>

<div class="cajaInformativa">
    <p><strong>¿Necesita cambiar su identificación o su correo?</strong>
        Solo un administrador puede hacerlo. Comuníquese con cualquiera de ellos.</p>
    <p class="textoAyuda">Su nombre, su foto y su contraseña los puede cambiar usted en
        <a href="<?= $urlBase ?>/perfil">Mi perfil</a>.
    </p>
</div>

<h2 class="tituloSeccion">Administradores</h2>

<?php if ($administradores === []): ?>
    <div class="estadoVacio">
        <p>No hay administradores activos registrados.</p>
    </div>
<?php else: ?>
    <div class="tarjetas">
        <?php foreach ($administradores as $administrador): ?>
            <section class="tarjeta">
                <h2><?= htmlspecialchars((string) $administrador->getNombreCompleto()) ?></h2>
                <dl class="listaDatos listaContacto">
                    <dt>Correo</dt>
                    <dd><?= htmlspecialchars((string) $administrador->getCorreoUsuario()) ?></dd>

                    <dt>Teléfono</dt>
                    <dd>
                        <?php if ($administrador->getNumeroTelefonico() !== null && $administrador->getNumeroTelefonico() !== ''): ?>
                            <?= htmlspecialchars($formatoTelefono($administrador->getNumeroTelefonico())) ?>
                        <?php else: ?>
                            <span class="textoAyuda">Sin teléfono registrado</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../Plantilla/pie.php'; ?>