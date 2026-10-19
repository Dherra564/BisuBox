<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\AvisosTienda;
use Aplicacion\Nucleo\ManejadorSesion;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\SesionRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Throwable;

class AvisoControlador
{
    public function revisar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!UsuarioActual::haySesion() || !UsuarioActual::esVendedor() || ManejadorSesion::haExpirado()) {
            $this->responder(['sesion' => false]);
            return;
        }

        try {
            $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
            if ($idSesionBd !== null && !(new SesionRepositorio())->estaAbierta($idSesionBd)) {
                $this->responder(['sesion' => false]);
                return;
            }

            $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
            if ($vendedor === null) {
                $this->responder(['sesion' => false]);
                return;
            }

            $idVendedor = (int) $vendedor->getIdVendedor();
            $this->responder([
                'sesion' => true,
                'avisos' => AvisosTienda::revisar($idVendedor),
                'numeros' => AvisosTienda::numeros($idVendedor),
            ]);
        } catch (Throwable $error) {
            error_log('No se pudieron revisar los avisos de la tienda: ' . $error->getMessage());
            $this->responder(['sesion' => true, 'avisos' => [], 'numeros' => null]);
        }
    }

    private function responder(array $datos): void
    {
        session_write_close();
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}