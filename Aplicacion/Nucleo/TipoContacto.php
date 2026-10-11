<?php

namespace Aplicacion\Nucleo;

class TipoContacto
{
    public const WHATSAPP = 'WhatsApp';
    public const INSTAGRAM = 'Instagram';
    public const TIKTOK = 'TikTok';
    public const FACEBOOK = 'Facebook';
    public const OTRO = 'Otro';

    public const MAXIMO_POR_TIENDA = 10;

   
    private const TIPOS = [
        self::WHATSAPP => [
            'nombre' => 'WhatsApp',
            'usuario' => null,
            'dominios' => [],
            'mensaje' => 'El WhatsApp debe tener 8 dígitos, solo números',
            'ayuda' => '8 dígitos, por ejemplo 88451290',
            'ejemplo' => '88451290',
        ],
        self::INSTAGRAM => [
            'nombre' => 'Instagram',
            'usuario' => '^(?=.*[A-Za-z])[A-Za-z0-9._]{1,30}$',
            'dominios' => ['instagram.com'],
            'mensaje' => 'Escriba el usuario de Instagram (con al menos una letra) o un enlace de instagram.com. Si es un número de teléfono, elija WhatsApp',
            'ayuda' => 'Usuario o enlace del perfil',
            'ejemplo' => 'mitienda o instagram.com/mitienda',
        ],
        self::TIKTOK => [
            'nombre' => 'TikTok',
            'usuario' => '^(?=.*[A-Za-z])[A-Za-z0-9._]{2,24}$',
            'dominios' => ['tiktok.com'],
            'mensaje' => 'Escriba el usuario de TikTok (con al menos una letra) o un enlace de tiktok.com. Si es un número de teléfono, elija WhatsApp',
            'ayuda' => 'Usuario o enlace del perfil',
            'ejemplo' => 'mitienda o tiktok.com/@mitienda',
        ],
        self::FACEBOOK => [
            'nombre' => 'Facebook',
            'usuario' => '^(?=.*[A-Za-z])[A-Za-z0-9.]{5,50}$',
            'dominios' => ['facebook.com', 'fb.com'],
            'mensaje' => 'Escriba el usuario de Facebook (con al menos una letra) o un enlace de facebook.com. Si es un número de teléfono, elija WhatsApp',
            'ayuda' => 'Usuario o enlace del perfil',
            'ejemplo' => 'mitienda o facebook.com/mitienda',
        ],
        self::OTRO => [
            'nombre' => 'Otro',
            'usuario' => null,
            'dominios' => [],
            'mensaje' => 'Escriba un enlace completo, por ejemplo https://mitienda.com',
            'ayuda' => 'Enlace completo, con https://',
            'ejemplo' => 'https://mitienda.com',
        ],
    ];
    

    /** @return array<string, array{nombre: string, usuario: ?string, dominios: string[], mensaje: string, ayuda: string, ejemplo: string}> */
    public static function todos(): array
    {
        return self::TIPOS;
    }

    public static function existe(?string $tipo): bool
    {
        return $tipo !== null && isset(self::TIPOS[$tipo]);
    }

    public static function nombre(?string $tipo): string
    {
        return self::existe($tipo) ? self::TIPOS[$tipo]['nombre'] : 'Sin tipo';
    }

    public static function mensaje(string $tipo): string
    {
        return self::TIPOS[$tipo]['mensaje'];
    }

    /** @return array<int, array{tipo: string, valor: string}> */
    public static function desdeFormulario(mixed $tipos, mixed $valores): array
    {
        $tipos = is_array($tipos) ? array_values($tipos) : [];
        $valores = is_array($valores) ? array_values($valores) : [];

        $contactos = [];
        foreach ($valores as $indice => $valor) {
            $tipo = is_string($tipos[$indice] ?? null) ? $tipos[$indice] : '';
            $valor = is_string($valor) ? trim($valor) : '';
            if ($valor === '') {
                continue;
            }
            $contactos[] = ['tipo' => $tipo, 'valor' => self::limpiar($tipo, $valor)];
        }
        return $contactos;
    }

    public static function limpiar(?string $tipo, string $valor): string
    {
        $valor = trim($valor);
        if ($tipo === self::WHATSAPP) {
            return Validador::limpiarTelefono($valor);
        }
        if (!self::esEnlace($valor)) {
            return ltrim($valor, '@');
        }
        return $valor;
    }

    public static function esValido(?string $tipo, string $valor): bool
    {
        if (!self::existe($tipo) || $valor === '' || mb_strlen($valor, 'UTF-8') > 300) {
            return false;
        }
        if ($tipo === self::WHATSAPP) {
            return preg_match('/^[0-9]{8}$/', $valor) === 1;
        }
        if (self::esEnlace($valor)) {
            return self::enlaceEsDelTipo($tipo, $valor);
        }

        $patron = self::TIPOS[$tipo]['usuario'];
        return $patron !== null && preg_match('/' . $patron . '/', $valor) === 1;
    }

    public static function formatear(?string $tipo, ?string $valor): string
    {
        $valor = (string) $valor;
        if ($tipo === self::WHATSAPP && strlen($valor) === 8) {
            return substr($valor, 0, 4) . '-' . substr($valor, 4);
        }
        if ($tipo !== self::WHATSAPP && $tipo !== self::OTRO && $valor !== '' && !self::esEnlace($valor)) {
            return '@' . $valor;
        }
        return $valor;
    }

    private static function esEnlace(string $valor): bool
    {
        return preg_match('#^https?://#i', $valor) === 1
            || preg_match('#^(www\.)?[a-z0-9-]+(\.[a-z0-9-]+)+/#i', $valor) === 1;
    }

    private static function enlaceEsDelTipo(string $tipo, string $valor): bool
    {
        $completo = preg_match('#^https?://#i', $valor) === 1 ? $valor : 'https://' . $valor;
        if (filter_var($completo, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $dominios = self::TIPOS[$tipo]['dominios'];
        if ($dominios === []) {
            return $tipo === self::OTRO;
        }

        $servidor = strtolower((string) parse_url($completo, PHP_URL_HOST));
        foreach ($dominios as $dominio) {
            if ($servidor === $dominio || str_ends_with($servidor, '.' . $dominio)) {
                return true;
            }
        }
        return false;
    }
}