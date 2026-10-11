<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosTipoComponente;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\TipoComponenteSugerido;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\TipoComponenteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class TipoComponenteControlador
{
    private TipoComponenteRepositorio $tipoComponenteRepositorio;
    private Vendedor $vendedor;

    public function __construct()
    {
        Permiso::exigir('inventario.gestionar');
        $this->tipoComponenteRepositorio = new TipoComponenteRepositorio();

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->vendedor = $vendedor;
    }

    public function listar(): void
    {
        $this->mostrarVista('TipoComponente/listar', [
            'tiposComponente' => $this->tipoComponenteRepositorio->listarPorVendedor($this->idVendedor()),
            'sugeridos' => TipoComponenteSugerido::todos(),
        ]);
    }

    public function agregarSugeridos(): void
    {
        $this->verificarCsrf('/tipos-componente');

        $elegidos = is_array($_POST['sugeridos'] ?? null) ? $_POST['sugeridos'] : [];
        $tipos = [];
        foreach ($elegidos as $nombre) {
            if (
                is_string($nombre) && TipoComponenteSugerido::existe($nombre)
                && !$this->tipoComponenteRepositorio->existeNombre($this->idVendedor(), $nombre)
            ) {
                $tipos[] = TipoComponenteSugerido::crear($nombre, $this->idVendedor());
            }
        }

        if ($tipos === []) {
            Mensaje::advertencia('Marque al menos un tipo para agregarlo.');
            $this->redirigir('/tipos-componente');
        }

        try {
            $this->tipoComponenteRepositorio->insertarVarios($tipos);
        } catch (Throwable $error) {
            error_log('Error al agregar los tipos sugeridos: ' . $error->getMessage());
            Mensaje::error('No se pudieron agregar los tipos. Intente de nuevo.');
            $this->redirigir('/tipos-componente');
        }

        Mensaje::exito(count($tipos) === 1
            ? 'Se agregó 1 tipo de componente. Puede cambiarlo cuando quiera.'
            : 'Se agregaron ' . count($tipos) . ' tipos de componente. Puede cambiarlos cuando quiera.');
        $this->redirigir('/tipos-componente');
    }

    public function nuevo(): void
    {
        $this->mostrarFormulario(['nombre' => '', 'datos' => []], [], null);
    }

    public function insertar(): void
    {
        $this->verificarCsrf('/tipos-componente/nuevo');

        $datos = DatosTipoComponente::leer();
        $validador = new Validador();
        DatosTipoComponente::validar($validador, $datos, $this->idVendedor());
        if (!$validador->esValido()) {
            $this->mostrarFormulario($datos, $validador->errores(), null);
            return;
        }

        try {
            $this->tipoComponenteRepositorio->insertar(DatosTipoComponente::crearTipo($datos, $this->idVendedor()));
        } catch (Throwable $error) {
            error_log('Error al crear el tipo de componente: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/tipos-componente/nuevo');
        }

        Mensaje::exito('Tipo de componente "' . $datos['nombre'] . '" creado');
        $this->redirigir('/tipos-componente');
    }

    public function editar(): void
    {
        $tipoComponente = $this->buscarTipo($_GET['id'] ?? null);
        $this->mostrarFormulario(DatosTipoComponente::paraFormulario($tipoComponente), [], $tipoComponente);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/tipos-componente');
        $tipoComponente = $this->buscarTipo($_POST['id'] ?? null);

        $datos = DatosTipoComponente::leer();
        $validador = new Validador();
        DatosTipoComponente::validar($validador, $datos, $this->idVendedor(), $tipoComponente);
        if (!$validador->esValido()) {
            $this->mostrarFormulario($datos, $validador->errores(), $tipoComponente);
            return;
        }

        try {
            $this->tipoComponenteRepositorio->actualizar(DatosTipoComponente::crearTipo($datos, $this->idVendedor(), $tipoComponente));
        } catch (Throwable $error) {
            error_log('Error al actualizar el tipo de componente: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/tipos-componente/editar?id=' . $tipoComponente->getIdTipoComponente());
        }

        Mensaje::exito('Tipo de componente "' . $datos['nombre'] . '" guardado');
        $this->redirigir('/tipos-componente');
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/tipos-componente');
        $tipoComponente = $this->buscarTipo($_POST['id'] ?? null);
        $activo = ($_POST['activo'] ?? '') === '1';

        try {
            $this->tipoComponenteRepositorio->cambiarEstado((int) $tipoComponente->getIdTipoComponente(), $this->idVendedor(), $activo);
        } catch (Throwable $error) {
            error_log('Error al cambiar el estado del tipo de componente: ' . $error->getMessage());
            Mensaje::error('No se pudo cambiar. Intente de nuevo.');
            $this->redirigir('/tipos-componente');
        }

        Mensaje::exito('Tipo de componente "' . $tipoComponente->getNombre() . '" ' . ($activo ? 'activado' : 'desactivado'));
        $this->redirigir('/tipos-componente');
    }

    private function buscarTipo(mixed $id): TipoComponente
    {
        $tipoComponente = is_string($id) && ctype_digit($id)
            ? $this->tipoComponenteRepositorio->buscarPorId((int) $id, $this->idVendedor())
            : null;

        if ($tipoComponente === null) {
            Mensaje::error('No se encontró ese tipo de componente.');
            $this->redirigir('/tipos-componente');
        }

        return $tipoComponente;
    }

    private function mostrarFormulario(array $datos, array $errores, ?TipoComponente $tipoComponente): void
    {
        $this->mostrarVista('TipoComponente/formulario', [
            'tipoComponente' => $tipoComponente,
            'datos' => $datos,
            'ocultos' => DatosTipoComponente::ocultosParaFormulario($tipoComponente, $datos),
            'errores' => $errores,
        ]);
    }

    private function idVendedor(): int
    {
        return (int) $this->vendedor->getIdVendedor();
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