<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Componente;
use Aplicacion\Modelos\TipoComponente;
use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosComponente;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\TipoComponenteRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class ComponenteControlador
{
    private ComponenteRepositorio $componenteRepositorio;
    private TipoComponenteRepositorio $tipoComponenteRepositorio;
    private Vendedor $vendedor;

    public function __construct()
    {
        Permiso::exigir('inventario.gestionar');
        $this->componenteRepositorio = new ComponenteRepositorio();
        $this->tipoComponenteRepositorio = new TipoComponenteRepositorio();

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->vendedor = $vendedor;
    }

    public function nuevo(): void
    {
        $tiposActivos = $this->tipoComponenteRepositorio->listarPorVendedor($this->idVendedor(), true);
        if ($tiposActivos === []) {
            Mensaje::advertencia('Primero cree un tipo de componente, por ejemplo Perlas o Hilos.');
            $this->redirigir('/tipos-componente');
        }

        $tipo = $this->buscarTipoActivo($_GET['tipo'] ?? null);
        if ($tipo === null) {
            $this->mostrarVista('Componente/elegirTipo', ['tipos' => $tiposActivos]);
            return;
        }

        $this->mostrarFormulario(DatosComponente::vacio($tipo), [], $tipo, null);
    }

    public function insertar(): void
    {
        $this->verificarCsrf('/componentes/nuevo');
        $tipo = $this->buscarTipoActivo($_POST['tipo'] ?? null);
        if ($tipo === null) {
            Mensaje::error('Ese tipo de componente no existe o está desactivado.');
            $this->redirigir('/componentes/nuevo');
        }

        $datos = DatosComponente::leer($tipo);
        $validador = new Validador();
        DatosComponente::validar($validador, $datos, $tipo, $this->idVendedor());
        if (!$validador->esValido()) {
            $this->mostrarFormulario($datos, $validador->errores(), $tipo, null);
            return;
        }

        try {
            $this->componenteRepositorio->insertar(DatosComponente::crearComponente($datos, $tipo, $this->idVendedor()));
        } catch (Throwable $error) {
            error_log('Error al crear el componente: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/componentes/nuevo?tipo=' . $tipo->getIdTipoComponente());
        }

        Mensaje::exito('Componente "' . $datos['nombre'] . '" agregado al inventario');
        $this->redirigir(($_POST['siguiente'] ?? '') === 'otro'
            ? '/componentes/nuevo?tipo=' . $tipo->getIdTipoComponente()
            : '/inventario');
    }

    public function editar(): void
    {
        $componente = $this->buscarComponente($_GET['id'] ?? null);
        $tipo = $this->tipoDe($componente);
        $this->mostrarFormulario(DatosComponente::paraFormulario($componente, $tipo), [], $tipo, $componente);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/inventario');
        $componente = $this->buscarComponente($_POST['id'] ?? null);
        $tipo = $this->tipoDe($componente);

        $datos = DatosComponente::leer($tipo);
        $validador = new Validador();
        DatosComponente::validar($validador, $datos, $tipo, $this->idVendedor(), $componente);
        if (!$validador->esValido()) {
            $this->mostrarFormulario($datos, $validador->errores(), $tipo, $componente);
            return;
        }

        try {
            $this->componenteRepositorio->actualizar(
                DatosComponente::crearComponente($datos, $tipo, $this->idVendedor(), $componente),
                array_map(fn($atributo): int => (int) $atributo->getIdAtributo(), $tipo->getAtributosActivos())
            );
        } catch (Throwable $error) {
            error_log('Error al actualizar el componente: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/componentes/editar?id=' . $componente->getIdComponente());
        }

        Mensaje::exito('Componente "' . $datos['nombre'] . '" guardado');
        $this->redirigir('/inventario');
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/inventario');
        $componente = $this->buscarComponente($_POST['id'] ?? null);
        $activo = ($_POST['activo'] ?? '') === '1';

        try {
            $this->componenteRepositorio->cambiarEstado((int) $componente->getIdComponente(), $this->idVendedor(), $activo);
        } catch (Throwable $error) {
            error_log('Error al cambiar el estado del componente: ' . $error->getMessage());
            Mensaje::error('No se pudo cambiar. Intente de nuevo.');
            $this->redirigir('/inventario');
        }

        Mensaje::exito('Componente "' . $componente->getNombre() . '" ' . ($activo ? 'activado' : 'desactivado'));
        $this->redirigir('/inventario' . ($activo ? '' : '?estado=inactivos'));
    }

    private function buscarTipoActivo(mixed $id): ?TipoComponente
    {
        if (!is_string($id) || !ctype_digit($id)) {
            return null;
        }
        $tipo = $this->tipoComponenteRepositorio->buscarPorId((int) $id, $this->idVendedor());

        return $tipo !== null && $tipo->getActivo() ? $tipo : null;
    }

    private function tipoDe(Componente $componente): TipoComponente
    {
        $tipo = $this->tipoComponenteRepositorio->buscarPorId($componente->getIdTipoComponente(), $this->idVendedor());
        if ($tipo === null) {
            error_log('El componente ' . $componente->getIdComponente() . ' tiene un tipo que no existe');
            Mensaje::error('No se encontró el tipo de ese componente.');
            $this->redirigir('/inventario');
        }

        return $tipo;
    }

    private function buscarComponente(mixed $id): Componente
    {
        $componente = is_string($id) && ctype_digit($id)
            ? $this->componenteRepositorio->buscarPorId((int) $id, $this->idVendedor())
            : null;

        if ($componente === null) {
            Mensaje::error('No se encontró ese componente.');
            $this->redirigir('/inventario');
        }

        return $componente;
    }

    private function mostrarFormulario(array $datos, array $errores, TipoComponente $tipo, ?Componente $componente): void
    {
        $this->mostrarVista('Componente/formulario', [
            'tipoComponente' => $tipo,
            'componente' => $componente,
            'datos' => $datos,
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