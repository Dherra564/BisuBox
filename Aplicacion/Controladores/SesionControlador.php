<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Repositorios\SesionRepositorio;
use Configuracion\Configuracion;

/**
 * Historial de sesiones: quién ingresó al sistema y cuándo. Solo para el SuperAdmin.
 */
class SesionControlador
{
    // GET /sesiones
    public function listar(): void
    {
        Permiso::exigir('sesiones.ver');

        $sesiones = (new SesionRepositorio())->listarConUsuario(100);
        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Sesion/historial.php';
    }
}