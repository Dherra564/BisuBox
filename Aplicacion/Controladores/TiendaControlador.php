<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosPago;
use Aplicacion\Nucleo\DatosTienda;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class TiendaControlador
{
    private VendedorRepositorio $vendedorRepositorio;
    private Vendedor $vendedor;

    public function __construct()
    {
        Permiso::exigir('tienda.gestionar');
        $this->vendedorRepositorio = new VendedorRepositorio();

        $vendedor = $this->vendedorRepositorio->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->vendedor = $vendedor;
    }

    public function mostrar(): void
    {
        $this->mostrarTienda([
            'tiendaNombre' => $this->vendedor->getTiendaNombre(),
            'tiendaDescripcion' => $this->vendedor->getTiendaDescripcion(),
            'tiendaActiva' => $this->vendedor->getTiendaActiva(),
            'tiendaSinpe' => $this->vendedor->getSinpeBonito(),
            'contactos' => DatosTienda::contactosParaFormulario($this->vendedor),
        ], []);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/tienda');

        $datos = DatosTienda::leer();
        $datos['tiendaActiva'] = ($_POST['tiendaActiva'] ?? '') === '1';
        $quitarLogo = ($_POST['quitarLogo'] ?? '') === '1';
        $datos['quitarLogo'] = $quitarLogo;

        $datos['tiendaSinpe'] = is_string($_POST['tiendaSinpe'] ?? null) ? trim($_POST['tiendaSinpe']) : '';
        $sinpe = DatosPago::limpiarSinpe($datos['tiendaSinpe']);

        $validador = new Validador();
        DatosTienda::validar($validador, $datos, $this->vendedor->getIdVendedor());
        if ($datos['tiendaSinpe'] !== '' && !DatosPago::esSinpe($sinpe)) {
            $validador->agregarError('tiendaSinpe', 'El número SINPE Móvil debe tener 8 dígitos y empezar con 5, 6, 7 u 8');
        }

        $nombreLogo = (new SubidaArchivo())->recibir('tiendaLogo', SubidaArchivo::LOGO, $validador);
        $datos['tiendaLogoPendiente'] = $nombreLogo;
        if (!$validador->esValido()) {
            SubidaArchivo::recordarPendiente(SubidaArchivo::LOGO, $nombreLogo);
            $this->mostrarTienda($datos, $validador->errores());
            return;
        }

        $logoAnterior = $this->vendedor->getTiendaLogo();
        $this->vendedor->setTiendaNombre($datos['tiendaNombre']);
        $this->vendedor->setTiendaDescripcion($datos['tiendaDescripcion']);
        $this->vendedor->setTiendaActiva($datos['tiendaActiva']);
        $this->vendedor->setSinpe($datos['tiendaSinpe'] !== '' ? $sinpe : null);
        $this->vendedor->setContactos(DatosTienda::crearContactos($datos['contactos']));
        if ($nombreLogo !== null) {
            $this->vendedor->setTiendaLogo($nombreLogo);
        } elseif ($quitarLogo) {
            $this->vendedor->setTiendaLogo(null);
        }

        try {
            $this->vendedorRepositorio->actualizarTienda($this->vendedor);
        } catch (Throwable $error) {
            error_log('Error al actualizar la tienda: ' . $error->getMessage());
            SubidaArchivo::recordarPendiente(SubidaArchivo::LOGO, $nombreLogo);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->vendedor = $this->vendedorRepositorio->buscarPorIdUsuario((int) UsuarioActual::id()) ?? $this->vendedor;
            $this->mostrarTienda($datos, [], true);
            return;
        }
        SubidaArchivo::olvidarPendiente(SubidaArchivo::LOGO, $nombreLogo);

        if ($logoAnterior !== null && $logoAnterior !== $this->vendedor->getTiendaLogo()) {
            SubidaArchivo::eliminarLogo($logoAnterior);
        }

        Mensaje::exito('Datos de la tienda guardados correctamente');
        $this->redirigir('/tienda');
    }

    private function mostrarTienda(array $datos, array $errores, bool $seguirEditando = false): void
    {
        $this->mostrarVista('Tienda/miTienda', [
            'seguirEditando' => $seguirEditando,
            'vendedor' => $this->vendedor,
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    private function verificarCsrf(string $rutaSiFalla): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir($rutaSiFalla);
        }
    }

    private function mostrarVista(string $vista, array $variablesVista): void
    {
        $variablesVista['mensajes'] = Mensaje::obtener();
        extract($variablesVista, EXTR_SKIP);
        require Configuracion::rutaBase() . '/Aplicacion/Vistas/' . $vista . '.php';
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . Configuracion::urlBase() . $ruta);
        exit;
    }
}