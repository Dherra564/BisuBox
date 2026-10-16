<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;


class ClienteControlador
{
    public function listar(): void
    {
        Permiso::exigir('clientes.ver');

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            Mensaje::error('No se encontró su tienda.');
            header('Location: ' . Configuracion::urlBase() . '/');
            exit;
        }
        $idVendedor = (int) $vendedor->getIdVendedor();
        $pedidoRepositorio = new PedidoRepositorio();

        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';
        $total = $pedidoRepositorio->contarClientes($idVendedor, $busqueda);
        $totalPaginas = max(1, (int) ceil($total / PedidoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $variablesVista = [
            'clientes' => $pedidoRepositorio->listarClientes($idVendedor, $busqueda, $pagina),
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => PedidoRepositorio::POR_PAGINA,
            'hayClientes' => $total > 0 || $pedidoRepositorio->contarClientes($idVendedor) > 0,
            'mensajes' => Mensaje::obtener(),
        ];
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/Cliente/lista.php';
    }
}
