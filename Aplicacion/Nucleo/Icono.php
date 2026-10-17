<?php

namespace Aplicacion\Nucleo;

class Icono
{
    private const TRAZOS = [
        'inicio' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'carrito' => '<path d="M3 4h2l2.4 11h10.2L20 7H6.2"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>',
        'bolsa' => '<path d="M6 7h12l-1 13H7z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
        'recibo' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
        'perfil' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
        'historial' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'tienda' => '<path d="M4 9l1.5-5h13L20 9"/><path d="M5 9v11h14V9"/><path d="M4 9c0 1.7 1.3 3 2.7 3s2.6-1.3 2.6-3c0 1.7 1.2 3 2.7 3s2.7-1.3 2.7-3c0 1.7 1.2 3 2.6 3S20 10.7 20 9"/><path d="M10 20v-4h4v4"/>',
        'emprender' => '<path d="M12 3v3"/><path d="M5.6 5.6l2.1 2.1"/><path d="M18.4 5.6l-2.1 2.1"/><path d="M5 13h14l-1.5 7h-11z"/><path d="M9 13l3-4 3 4"/>',
        'clientes' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 3.6-5 6.5-5s5.3 1.5 6.5 5"/><circle cx="17" cy="9" r="2.8"/><path d="M16.5 14.2c2.4.2 4 1.7 5 4.3"/>',
        'productos' => '<path d="M6 4h12l3 5-9 11L3 9z"/><path d="M3 9h18"/><path d="M9 4l3 16 3-16"/>',
        'inventario' => '<path d="M3 7l9-4 9 4-9 4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        'proveedores' => '<path d="M2 6h12v10H2z"/><path d="M14 9h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'panel' => '<path d="M4 4h7v7H4z"/><path d="M13 4h7v7h-7z"/><path d="M4 13h7v7H4z"/><path d="M13 13h7v7h-7z"/>',
        'ingresar' => '<path d="M14 4h5v16h-5"/><path d="M3 12h11"/><path d="M10 8l4 4-4 4"/>',
        'salir' => '<path d="M10 4H5v16h5"/><path d="M21 12H10"/><path d="M17 8l4 4-4 4"/>',
        'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
        'enlace' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 12 20a8 8 0 0 1-4-1.1z"/><path d="M9 9.5c0 2.5 2.5 5 5 5l1.2-1.2-1.8-1-1 .7c-.9-.4-1.5-1-1.9-1.9l.7-1-1-1.8z"/>',
        'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="16.8" cy="7.2" r="0.6"/>',
        'facebook' => '<path d="M14 21v-7h3l.5-4H14V8.2c0-1.1.4-1.9 1.9-1.9h1.7V2.9a20 20 0 0 0-2.6-.2c-2.6 0-4.4 1.6-4.4 4.5V10H8v4h2.6v7"/>',
        'tiktok' => '<path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 4c.5 2.5 2.5 4 5 4"/>',
        'web' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c2.5 2.6 3.5 5.4 3.5 8.5s-1 5.9-3.5 8.5c-2.5-2.6-3.5-5.4-3.5-8.5s1-5.9 3.5-8.5z"/>',
    ];

    public static function svg(string $nombre): string
    {
        $trazos = self::TRAZOS[$nombre] ?? self::TRAZOS['web'];
        return '<svg class="icono" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $trazos . '</svg>';
    }
}