<?php

namespace Aplicacion\Nucleo;

use Configuracion\Configuracion;
use finfo;


class SubidaArchivo
{
    public const TAMANO_MAXIMO = 5 * 1024 * 1024;   // 5 MB

    // Tipo real del archivo => extension con la que se guarda
    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private string $error = '';

    // true si la persona eligio un archivo en el formulario (la foto es opcional)
    public static function seEnvio(?array $archivo): bool
    {
        return $archivo !== null && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    // Guarda la foto y devuelve el nombre generado, o null si no se pudo (ver obtenerError())
    public function guardarFotoPerfil(array $archivo): ?string
    {
        $this->error = '';

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->fallar($this->mensajeErrorSubida($archivo['error'] ?? UPLOAD_ERR_NO_FILE));
        }

        $temporal = $archivo['tmp_name'] ?? '';
        if (!is_uploaded_file($temporal)) {
            return $this->fallar('No se recibió la foto. Intente de nuevo.');
        }

        if (filesize($temporal) > self::TAMANO_MAXIMO) {
            return $this->fallar('La foto debe ser JPG o PNG y pesar como máximo 5 MB');
        }

        // Tipo real segun el contenido: un .exe o un .php renombrado a .jpg se rechaza
        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        if (!isset(self::TIPOS_PERMITIDOS[$tipo]) || @getimagesize($temporal) === false) {
            return $this->fallar('La foto debe ser JPG o PNG y pesar como máximo 5 MB');
        }

        $carpeta = self::carpetaPerfiles();
        if (!is_dir($carpeta) || !is_writable($carpeta)) {
            // En Linux pasa si la carpeta no tiene permiso de escritura para el servidor web (www-data)
            error_log("La carpeta de fotos no existe o no tiene permiso de escritura: {$carpeta}");
            return $this->fallar('No se pudo guardar la foto. Intente de nuevo más tarde.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS_PERMITIDOS[$tipo];
        if (!move_uploaded_file($temporal, $carpeta . '/' . $nombre)) {
            error_log("No se pudo mover la foto subida a {$carpeta}/{$nombre}");
            return $this->fallar('No se pudo guardar la foto. Intente de nuevo más tarde.');
        }

        // Solo lectura y escritura para el dueño, lectura para el resto; nunca ejecutable
        @chmod($carpeta . '/' . $nombre, 0644);

        return $nombre;
    }

    public function obtenerError(): string
    {
        return $this->error;
    }

    // Borra una foto anterior (por ejemplo, al cambiarla). Si no existe, no hace nada.
    public static function eliminarFotoPerfil(?string $nombre): void
    {
        $ruta = $nombre !== null ? self::rutaFotoPerfil($nombre) : null;
        if ($ruta !== null) {
            @unlink($ruta);
        }
    }

    /**
     * Ruta completa de una foto, o null si el nombre no es valido o no existe.
     * El nombre se revisa con un patron estricto: asi nadie puede pedir "../../.env".
     */
    public static function rutaFotoPerfil(string $nombre): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $nombre)) {
            return null;
        }
        $ruta = self::carpetaPerfiles() . '/' . $nombre;
        return is_file($ruta) ? $ruta : null;
    }

    private static function carpetaPerfiles(): string
    {
        return Configuracion::rutaBase() . '/Almacenamiento/Fotos/Perfiles';
    }

    private function fallar(string $mensaje): ?string
    {
        $this->error = $mensaje;
        return null;
    }

    private function mensajeErrorSubida(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La foto debe ser JPG o PNG y pesar como máximo 5 MB',
            UPLOAD_ERR_PARTIAL => 'La foto no se subió completa. Intente de nuevo.',
            UPLOAD_ERR_NO_FILE => 'Seleccione una foto',
            default => 'No se pudo subir la foto. Intente de nuevo más tarde.',
        };
    }
}