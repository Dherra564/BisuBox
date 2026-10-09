<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;

class AyudaControlador
{
    public function mostrar(): void
    {
        Permiso::exigir('ayuda.ver');

        $this->mostrarVista('Ayuda/ayuda', [
            'administradores' => (new UsuarioRepositorio())->listarAdministradoresActivos(),
        ]);
    }

    private function mostrarVista(string $vista, array $variablesVista): void
    {
        $variablesVista['mensajes'] = Mensaje::obtener();
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/' . $vista . '.php';
    }
}