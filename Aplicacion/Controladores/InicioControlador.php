<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;

/**
 * Pagina de inicio (panel). Por ahora sirve de prueba de que todo funciona junto:
 * ruta -> controlador -> repositorio (base de datos) -> vista con plantilla -> mensajes.
 * Damian la completa con el panel segun el rol cuando haga el inicio de sesion.
 */
class InicioControlador
{
    public function panel(): void
    {
        $repositorio = new UsuarioRepositorio();
        $totalUsuarios = $repositorio->contar();
        $mensajes = Mensaje::obtener();

        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Inicio/panel.php';
    }

    // Prueba temporal: agrega un mensaje de cada tipo y vuelve al inicio. Se borra despues.
    public function probarMensajes(): void
    {
        Mensaje::exito('Así se ve un mensaje de éxito.');
        Mensaje::error('Así se ve un mensaje de error.');
        Mensaje::advertencia('Así se ve una advertencia.');

        header('Location: ' . Configuracion::obtener('appUrl') . '/');
        exit;
    }
}