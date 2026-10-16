<?php

namespace Aplicacion\Nucleo;


class WhatsApp
{
    public static function enlace(?string $telefono, string $mensaje = ''): ?string
    {
        $numero = Validador::limpiarTelefono((string) $telefono);
        if (strlen($numero) !== 8) {
            return null;
        }
        return 'https://wa.me/506' . $numero . ($mensaje !== '' ? '?text=' . rawurlencode($mensaje) : '');
    }
}
