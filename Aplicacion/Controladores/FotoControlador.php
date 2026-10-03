<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\SubidaArchivo;
use Configuracion\Configuracion;
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

    // Imagen para quien no tiene foto: Publico/imagenes/avatarPorDefecto.jpg
    private function mostrarAvatarPorDefecto(): void
    {
        $ruta = Configuracion::rutaBase() . '/Publico/imagenes/avatarPorDefecto.jpg';
        if (!is_file($ruta)) {
            error_log("No existe el avatar por defecto: {$ruta}");
            http_response_code(404);
            return;
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
    }
}