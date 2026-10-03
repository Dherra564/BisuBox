<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;

/**
 * Página de inicio (panel). Muestra lo que corresponde al rol del usuario conectado.
 */
class InicioControlador
{
    // GET /
    public function panel(): void
    {
        Permiso::exigir('panel.ver');

        $totalUsuarios = (new UsuarioRepositorio())->contar();
        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Inicio/panel.php';
    }
}