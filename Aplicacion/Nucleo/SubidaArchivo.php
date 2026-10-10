<?php

namespace Aplicacion\Nucleo;

use Configuracion\Configuracion;
use finfo;

// Guarda las imágenes subidas: fotos de perfil y logos de tienda
class SubidaArchivo
{
    public const TAMANO_MAXIMO = 5 * 1024 * 1024;

    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const CARPETA_PERFILES = '/Almacenamiento/Fotos/Perfiles';
    private const CARPETA_LOGOS = '/Almacenamiento/Fotos/Logos';

    private string $error = '';

    public static function seEnvio(?array $archivo): bool
    {
        return $archivo !== null && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    public function guardarFotoPerfil(array $archivo): ?string
    {
        return $this->guardarImagen($archivo, self::CARPETA_PERFILES, 'La foto');
    }

    public function guardarLogo(array $archivo): ?string
    {
        return $this->guardarImagen($archivo, self::CARPETA_LOGOS, 'El logo');
    }

    public function obtenerError(): string
    {
        return $this->error;
    }

    public static function eliminarFotoPerfil(?string $nombre): void
    {
        self::eliminar($nombre, self::CARPETA_PERFILES);
    }

    public static function eliminarLogo(?string $nombre): void
    {
        self::eliminar($nombre, self::CARPETA_LOGOS);
    }

    public static function rutaFotoPerfil(string $nombre): ?string
    {
        return self::ruta($nombre, self::CARPETA_PERFILES);
    }

    public static function rutaLogo(string $nombre): ?string
    {
        return self::ruta($nombre, self::CARPETA_LOGOS);
    }

    // $queEs: "La foto" o "El logo", para que los mensajes digan de qué imagen se habla
    private function guardarImagen(array $archivo, string $carpetaRelativa, string $queEs): ?string
    {
        $this->error = '';
        $mensajeFormato = $queEs . ' debe ser JPG o PNG y pesar como máximo 5 MB';

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->fallar($this->mensajeErrorSubida($archivo['error'] ?? UPLOAD_ERR_NO_FILE, $mensajeFormato));
        }

        $temporal = $archivo['tmp_name'] ?? '';
        if (!is_uploaded_file($temporal)) {
            return $this->fallar('No se recibió la imagen. Intente de nuevo.');
        }

        if (filesize($temporal) > self::TAMANO_MAXIMO) {
            return $this->fallar($mensajeFormato);
        }

        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        if (!isset(self::TIPOS_PERMITIDOS[$tipo]) || @getimagesize($temporal) === false) {
            return $this->fallar($mensajeFormato);
        }

        $carpeta = Configuracion::rutaBase() . $carpetaRelativa;
        if (!is_dir($carpeta) || !is_writable($carpeta)) {
            error_log("La carpeta de imágenes no existe o no tiene permiso de escritura: {$carpeta}");
            return $this->fallar('No se pudo guardar la imagen. Intente de nuevo más tarde.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS_PERMITIDOS[$tipo];
        if (!move_uploaded_file($temporal, $carpeta . '/' . $nombre)) {
            error_log("No se pudo mover la imagen subida a {$carpeta}/{$nombre}");
            return $this->fallar('No se pudo guardar la imagen. Intente de nuevo más tarde.');
        }

        @chmod($carpeta . '/' . $nombre, 0644);

        return $nombre;
    }

    private static function eliminar(?string $nombre, string $carpetaRelativa): void
    {
        $ruta = $nombre !== null ? self::ruta($nombre, $carpetaRelativa) : null;
        if ($ruta !== null) {
            @unlink($ruta);
        }
    }

    // Solo acepta nombres generados por esta clase, así nadie puede pedir otro archivo del servidor
    private static function ruta(string $nombre, string $carpetaRelativa): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $nombre)) {
            return null;
        }
        $ruta = Configuracion::rutaBase() . $carpetaRelativa . '/' . $nombre;
        return is_file($ruta) ? $ruta : null;
    }

    private function fallar(string $mensaje): ?string
    {
        $this->error = $mensaje;
        return null;
    }

    private function mensajeErrorSubida(int $codigo, string $mensajeFormato): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $mensajeFormato,
            UPLOAD_ERR_PARTIAL => 'La imagen no se subió completa. Intente de nuevo.',
            UPLOAD_ERR_NO_FILE => 'Seleccione una imagen',
            default => 'No se pudo subir la imagen. Intente de nuevo más tarde.',
        };
    }
}