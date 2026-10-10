<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosTienda;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

// Mi tienda: el vendedor edita los datos de su tienda y sus contactos
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
            'tiendaEnlace' => $this->vendedor->getTiendaEnlace(),
            'tiendaDescripcion' => $this->vendedor->getTiendaDescripcion(),
            'tiendaActiva' => $this->vendedor->getTiendaActiva(),
            'contactos' => DatosTienda::contactosParaFormulario($this->vendedor),
        ], []);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/tienda');

        $datos = DatosTienda::leer();
        $datos['tiendaActiva'] = ($_POST['tiendaActiva'] ?? '') === '1';
        $quitarLogo = ($_POST['quitarLogo'] ?? '') === '1';

        $validador = new Validador();
        DatosTienda::validar($validador, $datos, $this->vendedor->getIdVendedor());

        $nombreLogo = $this->subirLogo($validador);
        if (!$validador->esValido()) {
            SubidaArchivo::eliminarLogo($nombreLogo);
            $this->mostrarTienda($datos, $validador->errores());
            return;
        }

        $logoAnterior = $this->vendedor->getTiendaLogo();
        $this->vendedor->setTiendaNombre($datos['tiendaNombre']);
        $this->vendedor->setTiendaEnlace($datos['tiendaEnlace']);
        $this->vendedor->setTiendaDescripcion($datos['tiendaDescripcion']);
        $this->vendedor->setTiendaActiva($datos['tiendaActiva']);
        $this->vendedor->setContactos(DatosTienda::crearContactos($datos['contactos']));
        // Un logo nuevo reemplaza al anterior; "Quitar logo" solo cuenta si no se subió otro
        if ($nombreLogo !== null) {
            $this->vendedor->setTiendaLogo($nombreLogo);
        } elseif ($quitarLogo) {
            $this->vendedor->setTiendaLogo(null);
        }

        try {
            $this->vendedorRepositorio->actualizarTienda($this->vendedor);
        } catch (Throwable $error) {
            error_log('Error al actualizar la tienda: ' . $error->getMessage());
            SubidaArchivo::eliminarLogo($nombreLogo);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/tienda');
        }

        if ($logoAnterior !== null && $logoAnterior !== $this->vendedor->getTiendaLogo()) {
            SubidaArchivo::eliminarLogo($logoAnterior);
        }

        Mensaje::exito('Datos de la tienda guardados correctamente');
        $this->redirigir('/tienda');
    }

    private function subirLogo(Validador $validador): ?string
    {
        if (!SubidaArchivo::seEnvio($_FILES['tiendaLogo'] ?? null)) {
            return null;
        }
        $subida = new SubidaArchivo();
        $nombre = $subida->guardarLogo($_FILES['tiendaLogo']);
        if ($nombre === null) {
            $validador->agregarError('tiendaLogo', $subida->obtenerError());
        }
        return $nombre;
    }

    private function mostrarTienda(array $datos, array $errores): void
    {
        $this->mostrarVista('Tienda/miTienda', [
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
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl'), '/') . $ruta);
        exit;
    }
}