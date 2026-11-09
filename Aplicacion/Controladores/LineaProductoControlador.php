<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\LineaProducto;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosProducto;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\LineaProductoRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class LineaProductoControlador
{
    private LineaProductoRepositorio $lineaRepositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('productos.gestionar');
        $this->lineaRepositorio = new LineaProductoRepositorio();

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
        $this->mostrarVista('Linea/lista', [
            'lineas' => $this->lineaRepositorio->listar($this->idVendedor),
        ]);
    }

    public function nueva(): void
    {
        $this->mostrarFormulario(null, ['nombre' => '', 'descripcion' => ''], []);
    }

    public function crear(): void
    {
        $this->verificarCsrf('/lineas/nueva');
        $datos = $this->leerDatos();
        $validador = $this->validar($datos, null);
        if (!$validador->esValido()) {
            $this->mostrarFormulario(null, $datos, $validador->errores());
            return;
        }

        $linea = new LineaProducto(null, $this->idVendedor, $datos['nombre'], $datos['descripcion'] !== '' ? $datos['descripcion'] : null);
        try {
            $this->lineaRepositorio->insertar($linea);
        } catch (Throwable $error) {
            error_log('Error al crear la línea: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario(null, $datos, []);
            return;
        }

        Mensaje::exito('Línea «' . $linea->getNombre() . '» creada. Ya puede asignarla a sus proveedores y productos.');
        $this->redirigir('/lineas');
    }

    public function editar(): void
    {
        $linea = $this->buscarLinea($_GET['id'] ?? null);
        $this->mostrarFormulario($linea, ['nombre' => $linea->getNombre(), 'descripcion' => (string) $linea->getDescripcion()], []);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/lineas');
        $linea = $this->buscarLinea($_POST['id'] ?? null);
        $datos = $this->leerDatos();
        $validador = $this->validar($datos, $linea->getIdLinea());
        if (!$validador->esValido()) {
            $this->mostrarFormulario($linea, $datos, $validador->errores());
            return;
        }

        $linea->setNombre($datos['nombre']);
        $linea->setDescripcion($datos['descripcion'] !== '' ? $datos['descripcion'] : null);
        try {
            $this->lineaRepositorio->actualizar($linea);
        } catch (Throwable $error) {
            error_log('Error al actualizar la línea: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario($linea, $datos, []);
            return;
        }

        Mensaje::exito('Cambios de la línea «' . $linea->getNombre() . '» guardados');
        $this->redirigir('/lineas');
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/lineas');
        $linea = $this->buscarLinea($_POST['id'] ?? null);
        $estado = $_POST['activo'] ?? null;
        if (!in_array($estado, ['1', '0'], true)) {
            Mensaje::error('Estado no válido');
            $this->redirigir('/lineas');
        }

        try {
            $this->lineaRepositorio->cambiarEstado($linea, $estado === '1');
        } catch (Throwable $error) {
            error_log('Error al cambiar el estado de la línea: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/lineas');
        }

        Mensaje::exito($estado === '1'
            ? 'La línea «' . $linea->getNombre() . '» se activó'
            : 'La línea «' . $linea->getNombre() . '» se desactivó. Sus productos siguen a la venta.');
        $this->redirigir('/lineas');
    }

    private function leerDatos(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($leer('nombre')),
            'descripcion' => (string) preg_replace('/\s+/u', ' ', $leer('descripcion')),
        ];
    }

    private function validar(array $datos, ?int $idLinea): Validador
    {
        $validador = new Validador();
        $validador->requerido('nombre', $datos['nombre'], 'Escriba el nombre de la línea, por ejemplo Perlas')
            ->longitud('nombre', $datos['nombre'], 3, 50, 'El nombre debe tener entre 3 y 50 caracteres')
            ->nombreComercial('nombre', $datos['nombre'])
            ->sinOfensas('nombre', $datos['nombre']);
        if ($validador->error('nombre') === null && $this->lineaRepositorio->existeNombre($this->idVendedor, $datos['nombre'], $idLinea)) {
            $validador->agregarError('nombre', 'Ya tiene una línea con ese nombre');
        }

        $validador->longitud('descripcion', $datos['descripcion'], 1, 300, 'La descripción puede tener hasta 300 caracteres');
        if ($validador->error('descripcion') === null && !preg_match(DatosProducto::PATRON_DESCRIPCION, $datos['descripcion'])) {
            $validador->agregarError('descripcion', DatosProducto::MENSAJE_DESCRIPCION);
        }
        $validador->sinOfensas('descripcion', $datos['descripcion']);

        return $validador;
    }

    private function buscarLinea(mixed $id): LineaProducto
    {
        $linea = is_string($id) && ctype_digit($id) ? $this->lineaRepositorio->buscarPorId((int) $id, $this->idVendedor) : null;
        if ($linea === null) {
            Mensaje::error('No se encontró esa línea.');
            $this->redirigir('/lineas');
        }
        return $linea;
    }

    private function mostrarFormulario(?LineaProducto $linea, array $datos, array $errores): void
    {
        $this->mostrarVista('Linea/formulario', ['linea' => $linea, 'datos' => $datos, 'errores' => $errores]);
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