<?php
/**
 * @var int $codigo
 * @var string $titulo
 * @var string $mensaje
 * @var string $urlBase
 * @var bool $haySesion
 * @var ?string $detalle
 */
use Configuracion\Configuracion;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (int) $codigo ?> <?= htmlspecialchars($titulo) ?> | BisuBox</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= htmlspecialchars(Configuracion::recurso('css/estilos.css')) ?>">
</head>
<body>
    <div class="paginaAcceso">
        <div class="tarjetaAcceso">
            <p class="logo">BisuBox</p>
            <p class="tarjetaNumero"><?= (int) $codigo ?></p>
            <h1 class="tituloPagina"><?= htmlspecialchars($titulo) ?></h1>
            <p class="subtitulo"><?= htmlspecialchars($mensaje) ?></p>

            <?php if ($detalle !== null): ?>
                <div class="cajaInformativa">
                    <strong>Detalle (solo en desarrollo):</strong> <?= htmlspecialchars($detalle) ?>
                </div>
            <?php endif; ?>

            <p style="margin-top: 20px;">
                <a href="<?= htmlspecialchars($urlBase) ?><?= $haySesion ? '/' : '/ingresar' ?>" class="boton">
                    <?= $haySesion ? 'Volver al inicio' : 'Ir a iniciar sesión' ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html>