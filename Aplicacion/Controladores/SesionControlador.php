<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\SesionRepositorio;
use Configuracion\Configuracion;


class SesionControlador
{
    public function listar(): void
    {
        Permiso::exigir('sesiones.ver');

        $sesiones = (new SesionRepositorio())->listarPorUsuario((int) UsuarioActual::id(), 100);
        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Sesion/historial.php';
    }
}