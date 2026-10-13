<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Configuracion\Configuracion;

class FotoControlador
{
    public function mostrarPerfil(): void
    {
        Permiso::exigirSesion();

        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        $foto = SubidaArchivo::obtenerFotoPerfil($nombre);

        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=86400');

        if ($foto === null) {
            $this->mostrarAvatarPorDefecto();
            return;
        }

        $this->enviar($foto);
    }

    public function mostrarLogo(): void
    {
        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        $this->mostrarPublica(SubidaArchivo::obtenerLogo($nombre));
    }

    public function mostrarProducto(): void
    {
        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        $this->mostrarPublica(SubidaArchivo::obtenerFotoProducto($nombre));
    }

    private function mostrarPublica(?array $foto): void
    {
        header('X-Content-Type-Options: nosniff');

        if ($foto === null) {
            http_response_code(404);
            return;
        }

        header('Cache-Control: public, max-age=86400');
        $this->enviar($foto);
    }

    private function enviar(array $foto): void
    {
        header('Content-Type: ' . $foto['tipo']);
        header('Content-Length: ' . strlen($foto['datos']));
        echo $foto['datos'];
    }

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