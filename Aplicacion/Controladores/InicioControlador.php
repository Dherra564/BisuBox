<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;


class InicioControlador
{
    
    public function panel(): void
    {
        Permiso::exigir('panel.ver');

        $totalUsuarios = (new UsuarioRepositorio())->contar();
        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Inicio/panel.php';
    }
}