<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Repositorios\FotoRepositorio;
use Configuracion\Configuracion;
use finfo;
use GdImage;
use Throwable;

class SubidaArchivo
{
    public const TAMANO_MAXIMO = 5 * 1024 * 1024;

    private const TAMANO_GUARDADO = 900 * 1024;
    private const LADO_MAXIMO = 1200;

    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const CARPETA_PERFILES = 'Perfiles';
    private const CARPETA_LOGOS = 'Logos';
    private const CARPETA_PRODUCTOS = 'Productos';

    // Fotos ya subidas en un intento que tuvo errores; se guardan para no pedirlas otra vez
    private const CLAVE_PENDIENTES = 'fotosPendientes';
    public const FOTO_PERFIL = 'perfil';
    public const LOGO = 'logo';
    public const FOTO_PRODUCTO = 'producto';
    private const CARPETAS = [
        self::FOTO_PERFIL => self::CARPETA_PERFILES,
        self::LOGO => self::CARPETA_LOGOS,
        self::FOTO_PRODUCTO => self::CARPETA_PRODUCTOS,
    ];

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

    public function guardarFotoProducto(array $archivo): ?string
    {
        return $this->guardarImagen($archivo, self::CARPETA_PRODUCTOS, 'La foto');
    }

    /**
     * Toma la foto de un formulario. Si eligió una nueva, la guarda; si no, usa la que ya
     * había subido en un intento anterior que tuvo errores ($campo . 'Pendiente').
     * Si la nueva no sirve, agrega el error y conserva la anterior.
     */
    public function recibir(string $campo, string $tipo, Validador $validador): ?string
    {
        $pendiente = self::pendienteValida($tipo, $_POST[$campo . 'Pendiente'] ?? null);

        if (!self::seEnvio($_FILES[$campo] ?? null)) {
            return $pendiente;
        }

        $queEs = $tipo === self::LOGO ? 'El logo' : 'La foto';
        $nombre = $this->guardarImagen($_FILES[$campo], self::CARPETAS[$tipo], $queEs);
        if ($nombre === null) {
            $validador->agregarError($campo, $this->error);
            return $pendiente;
        }

        if ($pendiente !== null) {
            self::olvidarPendiente($tipo, $pendiente);
            self::eliminar($pendiente, self::CARPETAS[$tipo]);
        }
        return $nombre;
    }

    // El formulario tuvo errores: la foto ya subida queda guardada para el próximo intento
    public static function recordarPendiente(string $tipo, ?string $nombre): void
    {
        if ($nombre === null || !isset(self::CARPETAS[$tipo]) || !self::esNombreValido($nombre)) {
            return;
        }
        $pendientes = $_SESSION[self::CLAVE_PENDIENTES][$tipo] ?? [];
        if (!in_array($nombre, $pendientes, true)) {
            $pendientes[] = $nombre;
        }
        $_SESSION[self::CLAVE_PENDIENTES][$tipo] = array_slice($pendientes, -5);
    }

    // La foto ya se guardó con el registro: deja de estar pendiente
    public static function olvidarPendiente(string $tipo, ?string $nombre): void
    {
        if ($nombre === null || !isset($_SESSION[self::CLAVE_PENDIENTES][$tipo])) {
            return;
        }
        $_SESSION[self::CLAVE_PENDIENTES][$tipo] = array_values(array_filter(
            $_SESSION[self::CLAVE_PENDIENTES][$tipo],
            fn(string $pendiente): bool => $pendiente !== $nombre
        ));
    }

    // Solo se acepta un nombre que esta misma sesión subió, para que nadie use fotos ajenas
    private static function pendienteValida(string $tipo, mixed $nombre): ?string
    {
        if (!is_string($nombre) || !self::esNombreValido($nombre)) {
            return null;
        }
        return in_array($nombre, $_SESSION[self::CLAVE_PENDIENTES][$tipo] ?? [], true) ? $nombre : null;
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

    public static function eliminarFotoProducto(?string $nombre): void
    {
        self::eliminar($nombre, self::CARPETA_PRODUCTOS);
    }

    public static function obtenerFotoPerfil(string $nombre): ?array
    {
        return self::obtener($nombre, self::CARPETA_PERFILES);
    }

    public static function obtenerLogo(string $nombre): ?array
    {
        return self::obtener($nombre, self::CARPETA_LOGOS);
    }

    public static function obtenerFotoProducto(string $nombre): ?array
    {
        return self::obtener($nombre, self::CARPETA_PRODUCTOS);
    }

    private function guardarImagen(array $archivo, string $carpeta, string $queEs): ?string
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

        $imagen = self::reducir($temporal, $tipo);
        if ($imagen === null) {
            return $this->fallar($queEs . ' es muy pesada. Use una imagen más pequeña.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS_PERMITIDOS[$imagen['tipo']];
        try {
            (new FotoRepositorio())->insertar($carpeta, $nombre, $imagen['tipo'], $imagen['datos']);
        } catch (Throwable $error) {
            error_log('No se pudo guardar la imagen en la base de datos: ' . $error->getMessage());
            return $this->fallar('No se pudo guardar la imagen. Intente de nuevo más tarde.');
        }

        return $nombre;
    }

    private static function reducir(string $ruta, string $tipo): ?array
    {
        $original = (string) file_get_contents($ruta);
        if (!function_exists('imagecreatefromstring')) {
            return strlen($original) <= self::TAMANO_GUARDADO ? ['tipo' => $tipo, 'datos' => $original] : null;
        }

        $imagen = @imagecreatefromstring($original);
        if ($imagen === false) {
            return null;
        }
        $imagen = self::enderezar($imagen, $ruta, $tipo);

        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $escala = min(1, self::LADO_MAXIMO / max($ancho, $alto));
        if ($escala === 1 && strlen($original) <= self::TAMANO_GUARDADO && !self::fueGirada($ruta, $tipo)) {
            imagedestroy($imagen);
            return ['tipo' => $tipo, 'datos' => $original];
        }

        for ($intento = 0; $intento < 6; $intento++) {
            $nuevoAncho = max(1, (int) round($ancho * $escala));
            $nuevoAlto = max(1, (int) round($alto * $escala));
            $copia = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
            if ($tipo === 'image/png') {
                imagealphablending($copia, false);
                imagesavealpha($copia, true);
            }
            imagecopyresampled($copia, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

            ob_start();
            $tipo === 'image/png' ? imagepng($copia, null, 9) : imagejpeg($copia, null, 82);
            $datos = (string) ob_get_clean();
            imagedestroy($copia);

            if (strlen($datos) <= self::TAMANO_GUARDADO) {
                imagedestroy($imagen);
                return ['tipo' => $tipo, 'datos' => $datos];
            }
            $escala *= 0.75;
        }

        imagedestroy($imagen);
        return null;
    }

    private static function enderezar(GdImage $imagen, string $ruta, string $tipo): GdImage
    {
        $grados = match (self::orientacion($ruta, $tipo)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($grados === 0) {
            return $imagen;
        }
        $girada = imagerotate($imagen, $grados, 0);
        if ($girada === false) {
            return $imagen;
        }
        imagedestroy($imagen);
        return $girada;
    }

    private static function fueGirada(string $ruta, string $tipo): bool
    {
        return in_array(self::orientacion($ruta, $tipo), [3, 6, 8], true);
    }

    private static function orientacion(string $ruta, string $tipo): int
    {
        if ($tipo !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return 1;
        }
        $exif = @exif_read_data($ruta);
        return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    }

    private static function eliminar(?string $nombre, string $carpeta): void
    {
        if ($nombre === null || !self::esNombreValido($nombre)) {
            return;
        }
        try {
            (new FotoRepositorio())->eliminar($carpeta, $nombre);
        } catch (Throwable $error) {
            error_log('No se pudo borrar la imagen de la base de datos: ' . $error->getMessage());
        }

        $ruta = self::rutaAnterior($nombre, $carpeta);
        if ($ruta !== null) {
            @unlink($ruta);
        }
    }

    private static function obtener(string $nombre, string $carpeta): ?array
    {
        if (!self::esNombreValido($nombre)) {
            return null;
        }

        $foto = (new FotoRepositorio())->buscar($carpeta, $nombre);
        if ($foto !== null) {
            return $foto;
        }

        $ruta = self::rutaAnterior($nombre, $carpeta);
        if ($ruta === null) {
            return null;
        }
        return ['tipo' => (string) (new finfo(FILEINFO_MIME_TYPE))->file($ruta), 'datos' => (string) file_get_contents($ruta)];
    }

    private static function esNombreValido(string $nombre): bool
    {
        return preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $nombre) === 1;
    }

    private static function rutaAnterior(string $nombre, string $carpeta): ?string
    {
        $ruta = Configuracion::rutaBase() . '/Almacenamiento/Fotos/' . $carpeta . '/' . $nombre;
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