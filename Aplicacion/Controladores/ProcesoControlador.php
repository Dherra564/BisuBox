<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Proceso;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ProcesoRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;


class ProcesoControlador
{
    private const MENSAJE_MINUTOS = 'Escriba los minutos como número entero, por ejemplo 45';
    private const MENSAJE_COSTO = 'Escriba un monto válido, por ejemplo 1500 o 1500,50';

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
        $estado = in_array($_GET['estado'] ?? '', ['1', '0'], true) ? $_GET['estado'] : '';
        $activo = $estado === '' ? null : $estado === '1';

        $total = $this->procesoRepositorio->contar($this->idVendedor, $busqueda, $activo);
        $totalPaginas = max(1, (int) ceil($total / ProcesoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Proceso/lista', [
            'procesos' => $this->procesoRepositorio->listar($this->idVendedor, $busqueda, $activo, $pagina),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => ProcesoRepositorio::POR_PAGINA,
            'hayProcesos' => $total > 0 || $this->procesoRepositorio->contar($this->idVendedor) > 0,
        ]);
    }

    public function detalle(): void
    {
        $this->mostrarVista('Proceso/detalle', [
            'proceso' => $this->cargarProceso($_GET['id'] ?? null),
        ]);
    }

    public function nuevo(): void
    {
        $this->mostrarFormulario(null, [
            'nombre' => '',
            'descripcion' => '',
            'tiempoEstimado' => '',
            'costoManoObra' => '',
        ], []);
    }

    public function crear(): void
    {
        $this->verificarCsrf('/procesos/nuevo');

        $datos = $this->leerDatos();
        $validador = $this->validarDatos($datos, null);
        if (!$validador->esValido()) {
            $this->mostrarFormulario(null, $datos, $validador->errores());
            return;
        }

        $proceso = new Proceso(
            idVendedor: $this->idVendedor,
            nombre: $datos['nombre'],
            descripcion: $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
            tiempoEstimado: (int) $datos['tiempoEstimado'],
            costoManoObra: Numero::leer($datos['costoManoObra']),
            estado: true
        );

        try {
            $this->procesoRepositorio->insertar($proceso);
        } catch (Throwable $error) {
            error_log('Error al registrar proceso: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario(null, $datos, []);
            return;
        }

        Mensaje::exito('Proceso registrado correctamente');
        $this->redirigir('/procesos/detalle?id=' . $proceso->getIdProceso());
    }

    public function editar(): void
    {
        $proceso = $this->cargarProceso($_GET['id'] ?? null);

        $this->mostrarFormulario($proceso, [
            'nombre' => $proceso->getNombre(),
            'descripcion' => $proceso->getDescripcion() ?? '',
            'tiempoEstimado' => (string) $proceso->getTiempoEstimado(),
            'costoManoObra' => Numero::paraCampo($proceso->getCostoManoObra()),
        ], []);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/procesos');

        $proceso = $this->cargarProceso($_POST['id'] ?? null);

        $datos = $this->leerDatos();
        $validador = $this->validarDatos($datos, $proceso->getIdProceso());
        if (!$validador->esValido()) {
            $this->mostrarFormulario($proceso, $datos, $validador->errores());
            return;
        }

        $proceso->setNombre($datos['nombre']);
        $proceso->setDescripcion($datos['descripcion'] !== '' ? $datos['descripcion'] : null);
        $proceso->setTiempoEstimado((int) $datos['tiempoEstimado']);
        $proceso->setCostoManoObra(Numero::leer($datos['costoManoObra']));

        try {
            $this->procesoRepositorio->actualizar($proceso);
        } catch (Throwable $error) {
            error_log('Error al actualizar proceso: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario($proceso, $datos, []);
            return;
        }

        Mensaje::exito('Cambios guardados correctamente');
        $this->redirigir('/procesos/detalle?id=' . $proceso->getIdProceso());
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/procesos');
        $proceso = $this->cargarProceso($_POST['id'] ?? null);

        $estado = $_POST['activo'] ?? null;
        if (!in_array($estado, ['1', '0'], true)) {
            Mensaje::error('Estado no válido');
            $this->redirigir('/procesos');
        }
        $activo = $estado === '1';

        try {
            $this->procesoRepositorio->cambiarEstado($proceso, $activo);
        } catch (Throwable $error) {
            error_log('Error al cambiar estado de proceso: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/procesos');
        }

        Mensaje::exito($activo
            ? $proceso->getNombre() . ' fue activado'
            : $proceso->getNombre() . ' fue desactivado');
        $this->redirigir(($_POST['volver'] ?? '') === 'detalle'
            ? '/procesos/detalle?id=' . $proceso->getIdProceso()
            : '/procesos');
    }


    private function cargarProceso(mixed $id): Proceso
    {
        $idProceso = is_string($id) && ctype_digit($id) ? (int) $id : 0;
        $proceso = $idProceso > 0 ? $this->procesoRepositorio->buscarPorId($idProceso, $this->idVendedor) : null;

        if ($proceso === null) {
            Mensaje::error('El proceso no existe.');
            $this->redirigir('/procesos');
        }

        return $proceso;
    }

    private function leerDatos(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($leer('nombre')),
            'descripcion' => (string) preg_replace('/\s+/u', ' ', $leer('descripcion')),
            'tiempoEstimado' => $leer('tiempoEstimado'),
            'costoManoObra' => $leer('costoManoObra'),
        ];
    }

    private function validarDatos(array $datos, ?int $idProceso): Validador
    {
        $validador = new Validador();

        $validador->requerido('nombre', $datos['nombre'], 'Ingrese el nombre del proceso')
            ->longitud('nombre', $datos['nombre'], 3, 60, 'El nombre debe tener entre 3 y 60 caracteres')
            ->nombreComercial('nombre', $datos['nombre']);

        $validador->longitud('descripcion', $datos['descripcion'], 1, 200, 'La descripción puede tener hasta 200 caracteres');

        $validador->requerido('tiempoEstimado', $datos['tiempoEstimado'], 'Ingrese el tiempo estimado en minutos')
            ->soloNumeros('tiempoEstimado', $datos['tiempoEstimado'], self::MENSAJE_MINUTOS);
        if (
            $validador->error('tiempoEstimado') === null
            && ((int) $datos['tiempoEstimado'] < 1 || (int) $datos['tiempoEstimado'] > 9999)
        ) {
            $validador->agregarError('tiempoEstimado', self::MENSAJE_MINUTOS);
        }

        $validador->requerido('costoManoObra', $datos['costoManoObra'], 'Ingrese el costo de mano de obra');
        if ($validador->error('costoManoObra') === null && Numero::leer($datos['costoManoObra']) === null) {
            $validador->agregarError('costoManoObra', self::MENSAJE_COSTO);
        }

        if (
            $validador->error('nombre') === null
            && $this->procesoRepositorio->existeNombre($this->idVendedor, $datos['nombre'], $idProceso)
        ) {
            $validador->agregarError('nombre', 'Ya tiene un proceso con este nombre');
        }

        return $validador;
    }

    private function mostrarFormulario(?Proceso $proceso, array $datos, array $errores): void
    {
        $this->mostrarVista('Proceso/formulario', [
            'proceso' => $proceso,
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