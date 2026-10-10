<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Sesion;
use Aplicacion\Modelos\Usuario;
use Aplicacion\Repositorios\SesionRepositorio;

// Abre la sesión de un usuario: la usan el inicio de sesión y el registro
class Acceso
{
    public static function abrirSesion(Usuario $usuario): void
    {
        ManejadorSesion::regenerarId();

        $repositorioSesion = new SesionRepositorio();
        $repositorioSesion->cerrarTodasDeUsuario((int) $usuario->getIdUsuario());

        $sesion = new Sesion(
            idUsuario: $usuario->getIdUsuario(),
            tipoUsuario: $usuario->getRol()
        );
        $repositorioSesion->registrarInicio($sesion);

        ManejadorSesion::guardarIdSesionBd($sesion->getIdSesion());
        ManejadorSesion::registrarActividad();
        UsuarioActual::iniciar(
            (int) $usuario->getIdUsuario(),
            (string) $usuario->getRol(),
            (string) $usuario->getNombreCompleto(),
            $usuario->getFotoPerfil()
        );
    }
}