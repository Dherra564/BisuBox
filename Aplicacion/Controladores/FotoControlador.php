<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\CotizacionRepositorio;
use Aplicacion\Repositorios\PedidoPagoRepositorio;
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

    public function mostrarComponente(): void
    {
        Permiso::exigirSesion();

        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        $foto = SubidaArchivo::obtenerFotoComponente($nombre);

        header('X-Content-Type-Options: nosniff');

        if ($foto === null) {
            http_response_code(404);
            return;
        }

        header('Cache-Control: private, max-age=86400');
        $this->enviar($foto);
    }

    public function mostrarPieza(): void
    {
        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        $this->mostrarPublica(SubidaArchivo::obtenerFotoPieza($nombre));
    }

    public function mostrarComprobante(): void
    {
        Permiso::exigirSesion();

        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        $foto = (new PedidoPagoRepositorio())->puedeVerComprobante($nombre, (int) UsuarioActual::id())
            ? SubidaArchivo::obtenerComprobante($nombre)
            : null;

        header('X-Content-Type-Options: nosniff');

        if ($foto === null) {
            http_response_code(404);
            return;
        }

        header('Cache-Control: private, max-age=86400');
        $this->enviar($foto);
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

        public function mostrarCotizacion(): void
    {
        Permiso::exigirSesion();

        $nombre = is_string($_GET['archivo'] ?? null) ? $_GET['archivo'] : '';
        header('X-Content-Type-Options: nosniff');

        $foto = $this->puedeVerFotoCotizacion($nombre) ? SubidaArchivo::obtenerFotoCotizacion($nombre) : null;
        if ($foto === null) {
            http_response_code(404);
            return;
        }

        header('Cache-Control: private, max-age=86400');
        $this->enviar($foto);
    }

    private function puedeVerFotoCotizacion(string $nombre): bool
    {
        if (SubidaArchivo::esPendiente(SubidaArchivo::FOTO_COTIZACION, $nombre)) {
            return true;
        }
        $duenios = (new CotizacionRepositorio())->buscarPorFoto($nombre);

        return $duenios !== null
            && in_array((int) UsuarioActual::id(), [$duenios['idUsuarioCliente'], $duenios['idUsuarioVendedor']], true);
    }
}