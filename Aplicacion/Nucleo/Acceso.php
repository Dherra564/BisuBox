<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Sesion;
use Aplicacion\Modelos\Usuario;
use Aplicacion\Repositorios\SesionRepositorio;

class Acceso
{
    // Página a la que vuelve la persona después de ingresar o crear su cuenta
    // (por ejemplo la tienda donde quería hacer un pedido)
    private const CLAVE_DESTINO = 'destinoDespuesDeIngresar';

    public static function guardarDestino(mixed $ruta): void
    {
        // Solo rutas internas del sistema, nunca otra página de internet
        if (is_string($ruta) && preg_match('#^/[A-Za-z0-9/_-]*(\?[A-Za-z0-9=&_-]*)?$#', $ruta) === 1 && !str_starts_with($ruta, '//')) {
            $_SESSION[self::CLAVE_DESTINO] = $ruta;
        }
    }

    public static function tomarDestino(): ?string
    {
        $destino = $_SESSION[self::CLAVE_DESTINO] ?? null;
        unset($_SESSION[self::CLAVE_DESTINO]);
        return is_string($destino) ? $destino : null;
    }

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