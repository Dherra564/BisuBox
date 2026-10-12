<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Proceso;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosProceso;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\PeriodoFechas;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\ProcesoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class ProcesoControlador
{
    private const ESTADOS = ['todos' => 'Todos', 'registrados' => 'Registrados', 'anulados' => 'Anulados'];

    private ProcesoRepositorio $procesoRepositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('procesos.gestionar');
        $this->procesoRepositorio = new ProcesoRepositorio();

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->idVendedor = (int) $vendedor->getIdVendedor();
    }

    public function listar(): void
    {
        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';
        $estado = is_string($_GET['estado'] ?? null) && isset(self::ESTADOS[$_GET['estado']]) ? $_GET['estado'] : 'todos';
        $periodo = PeriodoFechas::leer($_GET);

        $filtros = ['busqueda' => $busqueda, 'estado' => $estado, 'desde' => $periodo['desde'], 'hasta' => $periodo['hasta']];
        $total = $this->procesoRepositorio->contar($this->idVendedor, $filtros);
        $totalPaginas = max(1, (int) ceil($total / ProcesoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Proceso/lista', [
            'procesos' => $this->procesoRepositorio->listar($this->idVendedor, $filtros, $pagina),
            'resumen' => $this->procesoRepositorio->resumen($this->idVendedor, $filtros),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'estados' => self::ESTADOS,
            'periodo' => $periodo,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => ProcesoRepositorio::POR_PAGINA,
            'hayProcesos' => $this->procesoRepositorio->contarPorVendedor($this->idVendedor) > 0,
        ]);
    }

    public function nuevo(): void
    {
        $opciones = $this->opcionesFormulario();

        $this->mostrarFormulario(
            DatosProceso::vacio($this->procesoRepositorio->ultimaTarifaHora($this->idVendedor)),
            [],
            $opciones
        );
    }

    public function registrar(): void
    {
        $this->verificarCsrf('/procesos/nuevo');
        $opciones = $this->opcionesFormulario();

        $datos = DatosProceso::leer();
        $validador = new Validador();
        DatosProceso::validar($validador, $datos, $opciones['componentes']);
        if (!$validador->esValido()) {
            $this->mostrarFormulario($datos, $validador->errores(), $opciones);
            return;
        }

        $proceso = DatosProceso::crearProceso($datos, $this->idVendedor, $opciones['componentes']);
        try {
            $idProceso = $this->procesoRepositorio->registrar($proceso);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->mostrarFormulario($datos, [], $this->opcionesFormulario());
            return;
        } catch (Throwable $error) {
            error_log('Error al registrar el proceso: ' . $error->getMessage());
            Mensaje::error('No se pudo registrar el proceso. No se cambió nada en el inventario; intente de nuevo.');
            $this->mostrarFormulario($datos, [], $opciones);
            return;
        }

        $cantidad = count($proceso->getComponentes());
        Mensaje::exito('Proceso registrado. Se descontó del inventario lo que usó de ' . ($cantidad === 1 ? '1 componente' : $cantidad . ' componentes') . '.');
        $this->redirigir('/procesos/detalle?id=' . $idProceso);
    }

    public function detalle(): void
    {
        $this->mostrarVista('Proceso/detalle', [
            'proceso' => $this->buscarProceso($_GET['id'] ?? null),
        ]);
    }

    public function anular(): void
    {
        $this->verificarCsrf('/procesos');
        $proceso = $this->buscarProceso($_POST['id'] ?? null);

        try {
            $this->procesoRepositorio->anular($proceso);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir('/procesos/detalle?id=' . $proceso->getIdProceso());
        } catch (Throwable $error) {
            error_log('Error al anular el proceso: ' . $error->getMessage());
            Mensaje::error('No se pudo anular el proceso. Intente de nuevo.');
            $this->redirigir('/procesos/detalle?id=' . $proceso->getIdProceso());
        }

        Mensaje::exito('Proceso anulado. Los componentes que usó volvieron al inventario.');
        $this->redirigir('/procesos/detalle?id=' . $proceso->getIdProceso());
    }

    private function opcionesFormulario(): array
    {
        $componentes = [];
        foreach ((new ComponenteRepositorio())->listarActivos($this->idVendedor) as $componente) {
            $componentes[(int) $componente->getIdComponente()] = $componente;
        }

        $hayExistencia = array_filter($componentes, fn($componente): bool => $componente->getExistencia() > 0) !== [];
        if (!$hayExistencia) {
            Mensaje::advertencia('Para registrar un proceso necesita componentes con existencia. Regístrelos con una compra a proveedores.');
            $this->redirigir('/compras-proveedor/nueva');
        }

        return ['componentes' => $componentes];
    }

    private function buscarProceso(mixed $id): Proceso
    {
        $proceso = is_string($id) && ctype_digit($id) ? $this->procesoRepositorio->buscarPorId((int) $id, $this->idVendedor) : null;
        if ($proceso === null) {
            Mensaje::error('No se encontró ese proceso.');
            $this->redirigir('/procesos');
        }

        return $proceso;
    }

    private function mostrarFormulario(array $datos, array $errores, array $opciones): void
    {
        $this->mostrarVista('Proceso/formulario', ['datos' => $datos, 'errores' => $errores] + $opciones);
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