<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\SubidaArchivo;
use finfo;


class FotoControlador
{
    public function mostrarPerfil(): void
    {
        // Pendiente (Damian): cuando exista el inicio de sesion, permitir esto solo a usuarios con sesion

        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        $ruta = SubidaArchivo::rutaFotoPerfil($nombre);

        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=86400');

        if ($ruta === null) {
            $this->mostrarAvatarPorDefecto();
            return;
        }

        header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($ruta));
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
    }

    // Circulo gris con una silueta, dibujado en SVG: no necesita ningun archivo de imagen
    private function mostrarAvatarPorDefecto(): void
    {
        header('Content-Type: image/svg+xml');
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
            . '<circle cx="50" cy="50" r="50" fill="#d9d9de"/>'
            . '<circle cx="50" cy="40" r="17" fill="#ffffff"/>'
            . '<path d="M20 84c4-16 17-25 30-25s26 9 30 25" fill="#ffffff"/>'
            . '</svg>';
    }
}