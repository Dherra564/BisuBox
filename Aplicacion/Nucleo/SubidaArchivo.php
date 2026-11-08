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
        'image/webp' => 'webp',
    ];

    private const CARPETA_PERFILES = 'Perfiles';
    private const CARPETA_LOGOS = 'Logos';
    private const CARPETA_PRODUCTOS = 'Productos';
    private const CARPETA_COMPONENTES = 'Componentes';
    private const CARPETA_PIEZAS = 'Piezas';
    private const TAMANO_MAXIMO_PIEZA = 600 * 1024;
    private const LADO_MAXIMO_PIEZA = 1000;

    private const CLAVE_PENDIENTES = 'fotosPendientes';
    public const FOTO_PERFIL = 'perfil';
    public const LOGO = 'logo';
    public const FOTO_PRODUCTO = 'producto';
    public const FOTO_COMPONENTE = 'componente';
    public const FOTO_PIEZA = 'pieza';
    private const CARPETAS = [
        self::FOTO_PERFIL => self::CARPETA_PERFILES,
        self::LOGO => self::CARPETA_LOGOS,
        self::FOTO_PRODUCTO => self::CARPETA_PRODUCTOS,
        self::FOTO_COMPONENTE => self::CARPETA_COMPONENTES,
        self::FOTO_PIEZA => self::CARPETA_PIEZAS,
    ];

    private string $error = '';

    public static function seEnvio(?array $archivo): bool
    {
        return $archivo !== null && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    public static function tamanoMaximo(): int
    {
        $limites = [self::TAMANO_MAXIMO];
        foreach (['upload_max_filesize', 'post_max_size'] as $clave) {
            $bytes = self::aBytes((string) ini_get($clave));
            if ($bytes > 0) {
                $limites[] = $bytes;
            }
        }
        return min($limites);
    }

    public static function tamanoMaximoTexto(): string
    {
        $megas = self::tamanoMaximo() / (1024 * 1024);
        if ($megas >= 1) {
            $redondeado = floor($megas * 10) / 10;
            return rtrim(rtrim(number_format($redondeado, 1, ',', ''), '0'), ',') . ' MB';
        }
        return max(1, (int) floor(self::tamanoMaximo() / 1024)) . ' KB';
    }

    public static function mensajeTipo(string $queEs): string
    {
        return $queEs . ' debe ser una imagen JPG, PNG o WEBP';
    }

    public static function mensajeTamano(string $queEs): string
    {
        return $queEs . ' pesa más de ' . self::tamanoMaximoTexto() . '. Use una imagen más liviana';
    }

    public static function envioExcedido(): bool
    {
        $largo = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $limite = self::aBytes((string) ini_get('post_max_size'));
        return $largo > 0 && $limite > 0 && $largo > $limite;
    }

    private static function aBytes(string $valor): int
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 0;
        }
        $numero = (float) $valor;
        return (int) match (strtolower(substr($valor, -1))) {
            'g' => $numero * 1024 ** 3,
            'm' => $numero * 1024 ** 2,
            'k' => $numero * 1024,
            default => $numero,
        };
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

    public static function eliminarFotoComponente(?string $nombre): void
    {
        self::eliminar($nombre, self::CARPETA_COMPONENTES);
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

    public static function obtenerFotoComponente(string $nombre): ?array
    {
        return self::obtener($nombre, self::CARPETA_COMPONENTES);
    }

    public static function obtenerFotoPieza(string $nombre): ?array
    {
        return self::obtener($nombre, self::CARPETA_PIEZAS);
    }

    public static function eliminarFotoPieza(?string $nombre): void
    {
        self::eliminar($nombre, self::CARPETA_PIEZAS);
    }

    public static function revisarPiezaRecortada(mixed $texto): ?string
    {
        $prefijo = 'data:image/png;base64,';
        if (!is_string($texto) || !str_starts_with($texto, $prefijo)) {
            return null;
        }
        $datos = base64_decode(substr($texto, strlen($prefijo)), true);
        if ($datos === false || $datos === '' || strlen($datos) > self::TAMANO_MAXIMO_PIEZA) {
            return null;
        }
        if ((new finfo(FILEINFO_MIME_TYPE))->buffer($datos) !== 'image/png') {
            return null;
        }
        $medidas = @getimagesizefromstring($datos);
        if ($medidas === false || $medidas[0] < 4 || $medidas[1] < 4
            || $medidas[0] > self::LADO_MAXIMO_PIEZA || $medidas[1] > self::LADO_MAXIMO_PIEZA) {
            return null;
        }
        if (!function_exists('imagecreatefromstring')) {
            return $datos;
        }
        $imagen = @imagecreatefromstring($datos);
        if ($imagen === false) {
            return null;
        }
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $esquinasTransparentes = 0;
        foreach ([[0, 0], [$ancho - 1, 0], [0, $alto - 1], [$ancho - 1, $alto - 1]] as [$x, $y]) {
            $color = imagecolorsforindex($imagen, imagecolorat($imagen, $x, $y));
            if ($color['alpha'] >= 120) {
                $esquinasTransparentes++;
            }
        }
        if ($esquinasTransparentes === 0) {
            imagedestroy($imagen);
            return null;
        }

        $limpia = imagecreatetruecolor($ancho, $alto);
        imagealphablending($limpia, false);
        imagesavealpha($limpia, true);
        imagefill($limpia, 0, 0, imagecolorallocatealpha($limpia, 0, 0, 0, 127));
        imagecopy($limpia, $imagen, 0, 0, 0, 0, $ancho, $alto);
        ob_start();
        imagepng($limpia, null, 9);
        $resultado = (string) ob_get_clean();
        imagedestroy($imagen);
        imagedestroy($limpia);

        return strlen($resultado) <= self::TAMANO_MAXIMO_PIEZA ? $resultado : null;
    }

    public function guardarPiezaRecortada(string $datosPng): ?string
    {
        $this->error = '';
        $nombre = bin2hex(random_bytes(16)) . '.png';
        try {
            (new FotoRepositorio())->insertar(self::CARPETA_PIEZAS, $nombre, 'image/png', $datosPng);
        } catch (Throwable $error) {
            error_log('No se pudo guardar la foto recortada: ' . $error->getMessage());
            return $this->fallar('No se pudo guardar la imagen. Intente de nuevo más tarde.');
        }
        return $nombre;
    }

    private function guardarImagen(array $archivo, string $carpeta, string $queEs): ?string
    {
        $this->error = '';

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->fallar($this->mensajeErrorSubida($archivo['error'] ?? UPLOAD_ERR_NO_FILE, $queEs));
        }

        $temporal = $archivo['tmp_name'] ?? '';
        if (!is_uploaded_file($temporal)) {
            return $this->fallar('No se recibió la imagen. Intente de nuevo.');
        }

        if (filesize($temporal) > self::tamanoMaximo()) {
            return $this->fallar(self::mensajeTamano($queEs));
        }

        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        if (!isset(self::TIPOS_PERMITIDOS[$tipo]) || @getimagesize($temporal) === false) {
            return $this->fallar(self::mensajeTipo($queEs));
        }

        if ($tipo === 'image/webp' && !function_exists('imagewebp')) {
            return $this->fallar('Este servidor no puede procesar imágenes WEBP. Use JPG o PNG');
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

        $conTransparencia = $tipo === 'image/png' || $tipo === 'image/webp';

        for ($intento = 0; $intento < 6; $intento++) {
            $nuevoAncho = max(1, (int) round($ancho * $escala));
            $nuevoAlto = max(1, (int) round($alto * $escala));
            $copia = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
            if ($conTransparencia) {
                imagealphablending($copia, false);
                imagesavealpha($copia, true);
            }
            imagecopyresampled($copia, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

            ob_start();
            match ($tipo) {
                'image/png' => imagepng($copia, null, 9),
                'image/webp' => imagewebp($copia, null, 82),
                default => imagejpeg($copia, null, 82),
            };
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
        return preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $nombre) === 1;
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

    private function mensajeErrorSubida(int $codigo, string $queEs): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => self::mensajeTamano($queEs),
            UPLOAD_ERR_PARTIAL => 'La imagen no se subió completa. Intente de nuevo.',
            UPLOAD_ERR_NO_FILE => 'Seleccione una imagen',
            default => 'No se pudo subir la imagen. Intente de nuevo más tarde.',
        };
    }
}