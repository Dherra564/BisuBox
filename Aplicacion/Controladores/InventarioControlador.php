<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\FiltrosInventario;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\TipoComponenteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;

class InventarioControlador
{
    private Vendedor $vendedor;

    public function __construct()
    {
        Permiso::exigir('inventario.gestionar');

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->vendedor = $vendedor;
    }

    public function mostrar(): void
    {
        $idVendedor = (int) $this->vendedor->getIdVendedor();
        $componenteRepositorio = new ComponenteRepositorio();
        $tipos = (new TipoComponenteRepositorio())->listarPorVendedor($idVendedor);
        $filtros = FiltrosInventario::leer($_GET, $tipos);

        $this->mostrarVista('Inventario/inventario', [
            'tipos' => $tipos,
            'filtros' => $filtros,
            'componentes' => $componenteRepositorio->listar($idVendedor, $filtros),
            'resumen' => $componenteRepositorio->resumen($idVendedor),
            'hayComponentes' => $componenteRepositorio->contarPorVendedor($idVendedor) > 0,
        ]);
    }

    private function mostrarVista(string $vista, array $variablesVista): void
    {
        $variablesVista['mensajes'] = Mensaje::obtener();
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/' . $vista . '.php';
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl'), '/') . $ruta);
        exit;
    }
}