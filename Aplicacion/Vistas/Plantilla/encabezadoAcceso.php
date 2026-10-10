<?php
/**
 * Plantilla de las páginas sin sesión: inicio de sesión y registro.
 * @var string $titulo
 * @var string|null $claseTarjeta  'tarjetaRegistro' para los formularios largos
 */

use Configuracion\Configuracion;

$urlBase = rtrim((string) Configuracion::obtener('appUrl', ''), '/');
$claseTarjeta = $claseTarjeta ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?> | BisuBox</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= Configuracion::recurso('css/estilos.css') ?>">
</head>

<body>
    <div class="paginaAcceso">
        <main class="tarjetaAcceso <?= $claseTarjeta ?>">
            <p class="logo">BisuBox</p>