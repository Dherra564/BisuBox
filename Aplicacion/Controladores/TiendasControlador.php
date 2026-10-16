<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;


class TiendasControlador
{
    public function listar(): void
    {
        Permiso::exigir('tiendas.ver');

        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';

        $variablesVista = [
            'tiendas' => (new VendedorRepositorio())->listarTiendasVisibles($busqueda),
            'busqueda' => $busqueda,
            'mensajes' => Mensaje::obtener(),
        ];
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Tienda/directorio.php';
    }
}
