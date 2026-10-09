<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Proceso;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Formato;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ProcesoRepositorio;
use Configuracion\Configuracion;
use Throwable;

/**
 * Catálogo de procesos de elaboración. Solo para quien tiene el permiso 'procesos.gestionar'.
 */
class ProcesoControlador
{
    private const TIEMPO_MAXIMO = 10080; // minutos (7 días)

    private ProcesoRepositorio $procesoRepositorio;

    public function __construct()
    {
        Permiso::exigir('procesos.gestionar');
        $this->procesoRepositorio = new ProcesoRepositorio();
    }

    // GET /procesos
    public function listar(): void
    {
        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';
        $estado = in_array($_GET['estado'] ?? '', ['1', '0'], true) ? $_GET['estado'] : '';
        $activo = $estado === '' ? null : $estado === '1';

        $procesos = $this->procesoRepositorio->listar($busqueda, $activo);
        $total = count($procesos);
        $totalPaginas = max(1, (int) ceil($total / ProcesoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Proceso/lista', [
            'procesos' => array_slice($procesos, ($pagina - 1) * ProcesoRepositorio::POR_PAGINA, ProcesoRepositorio::POR_PAGINA),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => ProcesoRepositorio::POR_PAGINA,
        ]);
    }

    // GET /procesos/detalle?id=
    public function detalle(): void
    {
        $this->mostrarVista('Proceso/detalle', [
            'proceso' => $this->cargarProceso($_GET['id'] ?? null),
        ]);
    }

    // GET /procesos/nuevo
    public function nuevo(): void
    {
        $this->mostrarFormulario(null, ['nombre' => '', 'descripcion' => '', 'tiempoEstimado' => '', 'costoManoObra' => ''], []);
    }

    // POST /procesos/crear
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
            null,
            $datos['nombre'],
            $datos['descripcion'],
            (int) $datos['tiempoEstimado'],
            (string) Formato::leerMonto($datos['costoManoObra']),
            true
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
        $this->redirigir('/procesos');
    }

    // GET /procesos/editar?id=
    public function editar(): void
    {
        $proceso = $this->cargarProceso($_GET['id'] ?? null);

        $this->mostrarFormulario($proceso, [
            'nombre' => $proceso->getNombre(),
            'descripcion' => $proceso->getDescripcion(),
            'tiempoEstimado' => (string) $proceso->getTiempoEstimado(),
            'costoManoObra' => str_replace('.', ',', $proceso->getCostoManoObra()),
        ], []);
    }

    // POST /procesos/actualizar
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
        $proceso->setDescripcion($datos['descripcion']);
        $proceso->setTiempoEstimado((int) $datos['tiempoEstimado']);
        $proceso->setCostoManoObra((string) Formato::leerMonto($datos['costoManoObra']));

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

    // POST /procesos/estado
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

        Mensaje::exito($activo ? 'El proceso fue activado' : 'El proceso fue desactivado');
        $this->redirigir(
            ($_POST['volver'] ?? '') === 'detalle'
                ? '/procesos/detalle?id=' . $proceso->getIdProceso()
                : '/procesos'
        );
    }

    private function cargarProceso(mixed $id): Proceso
    {
        $idProceso = is_string($id) && ctype_digit($id) ? (int) $id : 0;
        $proceso = $idProceso > 0 ? $this->procesoRepositorio->buscarPorId($idProceso) : null;

        if ($proceso === null) {
            Mensaje::error('El proceso no existe.');
            $this->redirigir('/procesos');
        }

        return $proceso;
    }

    private function leerDatos(): array
    {
        $leer = fn (string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            // Espacios repetidos se juntan en uno: "Armar   joya" y "Armar joya" son el mismo nombre
            'nombre' => trim((string) preg_replace('/\s+/u', ' ', $leer('nombre'))),
            'descripcion' => trim((string) preg_replace('/\s+/u', ' ', $leer('descripcion'))),
            'tiempoEstimado' => $leer('tiempoEstimado'),
            'costoManoObra' => $leer('costoManoObra'),
        ];
    }

    private function validarDatos(array $datos, ?int $idProceso): Validador
    {
        $validador = new Validador();

        $validador->requerido('nombre', $datos['nombre'], 'Ingrese el nombre del proceso')
            ->longitud('nombre', $datos['nombre'], 3, 60, 'El nombre debe tener entre 3 y 60 caracteres')
            ->soloLetras('nombre', $datos['nombre'], 'El nombre solo puede tener letras y espacios');

        if (
            $validador->error('nombre') === null
            && $this->procesoRepositorio->existeNombre($datos['nombre'], $idProceso)
        ) {
            $validador->agregarError('nombre', 'Ya existe un proceso con este nombre');
        }

        // La descripción es opcional, pero si se escribe tiene tope de largo
        $validador->longitud('descripcion', $datos['descripcion'], 1, 200, 'La descripción no puede pasar de 200 caracteres');

        $validador->requerido('tiempoEstimado', $datos['tiempoEstimado'], 'Ingrese el tiempo estimado en minutos');
        if ($validador->error('tiempoEstimado') === null) {
            $tiempo = $datos['tiempoEstimado'];
            if (!ctype_digit($tiempo) || (int) $tiempo < 1 || (int) $tiempo > self::TIEMPO_MAXIMO) {
                $validador->agregarError(
                    'tiempoEstimado',
                    'El tiempo debe ser un número entero de minutos, de 1 a ' . self::TIEMPO_MAXIMO
                );
            }
        }

        $validador->requerido('costoManoObra', $datos['costoManoObra'], 'Ingrese el costo de mano de obra');
        if ($validador->error('costoManoObra') === null && Formato::leerMonto($datos['costoManoObra']) === null) {
            $validador->agregarError(
                'costoManoObra',
                'Ingrese un monto en colones válido, con máximo 2 decimales (por ejemplo 1250 o 1250,50)'
            );
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