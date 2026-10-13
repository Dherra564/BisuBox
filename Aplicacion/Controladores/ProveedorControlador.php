<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Proveedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\CompraProveedorRepositorio;
use Aplicacion\Repositorios\ProveedorRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;

class ProveedorControlador
{
    private ProveedorRepositorio $proveedorRepositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('proveedores.gestionar');
        $this->proveedorRepositorio = new ProveedorRepositorio();

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

        $total = $this->proveedorRepositorio->contar($this->idVendedor, $busqueda, $activo);
        $totalPaginas = max(1, (int) ceil($total / ProveedorRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Proveedor/lista', [
            'proveedores' => $this->proveedorRepositorio->listar($this->idVendedor, $busqueda, $activo, $pagina),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => ProveedorRepositorio::POR_PAGINA,
            'hayProveedores' => $total > 0 || $this->proveedorRepositorio->contar($this->idVendedor) > 0,
        ]);
    }

    public function detalle(): void
    {
        $proveedor = $this->cargarProveedor($_GET['id'] ?? null);

        $filtros = ['proveedor' => (int) $proveedor->getIdProveedor(), 'estado' => 'todas'];
        $compraRepositorio = new CompraProveedorRepositorio();

        $this->mostrarVista('Proveedor/detalle', [
            'proveedor' => $proveedor,
            'compras' => $compraRepositorio->listar($this->idVendedor, $filtros),
            'totalCompras' => $compraRepositorio->contar($this->idVendedor, $filtros),
            'resumen' => $compraRepositorio->resumen($this->idVendedor, $filtros),
        ]);
    }

    public function nuevo(): void
    {
        $this->mostrarFormulario(null, ['nombre' => '', 'telefono' => '', 'correo' => ''], []);
    }

    public function crear(): void
    {
        $this->verificarCsrf('/proveedores/nuevo');

        $datos = $this->leerDatos();
        $validador = $this->validarDatos($datos, null);
        if (!$validador->esValido()) {
            $this->mostrarFormulario(null, $datos, $validador->errores());
            return;
        }

        $proveedor = new Proveedor(
            idVendedor: $this->idVendedor,
            nombre: $datos['nombre'],
            telefono: Validador::limpiarTelefono($datos['telefono']),
            correo: $datos['correo'],
            fechaRegistro: new DateTime(),
            estado: true
        );

        try {
            $this->proveedorRepositorio->insertar($proveedor);
        } catch (Throwable $error) {
            error_log('Error al registrar proveedor: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario(null, $datos, []);
            return;
        }

        Mensaje::exito('Proveedor registrado correctamente');
        $this->redirigir('/proveedores/detalle?id=' . $proveedor->getIdProveedor());
    }

    public function editar(): void
    {
        $proveedor = $this->cargarProveedor($_GET['id'] ?? null);

        $this->mostrarFormulario($proveedor, [
            'nombre' => $proveedor->getNombre(),
            'telefono' => $proveedor->getTelefono(),
            'correo' => $proveedor->getCorreo(),
        ], []);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/proveedores');
        $proveedor = $this->cargarProveedor($_POST['id'] ?? null);

        $datos = $this->leerDatos();
        $validador = $this->validarDatos($datos, $proveedor->getIdProveedor());
        if (!$validador->esValido()) {
            $this->mostrarFormulario($proveedor, $datos, $validador->errores());
            return;
        }

        $proveedor->setNombre($datos['nombre']);
        $proveedor->setTelefono(Validador::limpiarTelefono($datos['telefono']));
        $proveedor->setCorreo($datos['correo']);

        try {
            $this->proveedorRepositorio->actualizar($proveedor);
        } catch (Throwable $error) {
            error_log('Error al actualizar proveedor: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario($proveedor, $datos, []);
            return;
        }

        Mensaje::exito('Cambios guardados correctamente');
        $this->redirigir('/proveedores/detalle?id=' . $proveedor->getIdProveedor());
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/proveedores');
        $proveedor = $this->cargarProveedor($_POST['id'] ?? null);

        $estado = $_POST['activo'] ?? null;
        if (!in_array($estado, ['1', '0'], true)) {
            Mensaje::error('Estado no válido');
            $this->redirigir('/proveedores');
        }
        $activo = $estado === '1';

        try {
            $this->proveedorRepositorio->cambiarEstado($proveedor, $activo);
        } catch (Throwable $error) {
            error_log('Error al cambiar estado de proveedor: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/proveedores');
        }

        Mensaje::exito($activo
            ? $proveedor->getNombre() . ' fue activado'
            : $proveedor->getNombre() . ' fue desactivado');
        $this->redirigir('/proveedores');
    }

    private function cargarProveedor(mixed $id): Proveedor
    {
        $idProveedor = is_string($id) && ctype_digit($id) ? (int) $id : 0;
        $proveedor = $idProveedor > 0 ? $this->proveedorRepositorio->buscarPorId($idProveedor, $this->idVendedor) : null;

        if ($proveedor === null) {
            Mensaje::error('El proveedor no existe.');
            $this->redirigir('/proveedores');
        }

        return $proveedor;
    }

    private function leerDatos(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'nombre' => UsuarioRepositorio::limpiarEspacios($leer('nombre')),
            'telefono' => $leer('telefono'),
            'correo' => mb_strtolower($leer('correo'), 'UTF-8'),
        ];
    }

    private function validarDatos(array $datos, ?int $idProveedor): Validador
    {
        $validador = new Validador();

        $validador->requerido('nombre', $datos['nombre'], 'Ingrese el nombre del proveedor')
            ->longitud('nombre', $datos['nombre'], 3, 50, 'El nombre debe tener entre 3 y 50 caracteres')
            ->nombreComercial('nombre', $datos['nombre']);

        $validador->requerido('telefono', $datos['telefono'], 'Ingrese el teléfono')
            ->telefono('telefono', $datos['telefono']);

        $validador->requerido('correo', $datos['correo'], 'Ingrese el correo')
            ->correo('correo', $datos['correo']);

        $unicos = [
            'nombre' => ['valor' => $datos['nombre'], 'texto' => 'nombre'],
            'telefono' => ['valor' => Validador::limpiarTelefono($datos['telefono']), 'texto' => 'teléfono'],
            'correo' => ['valor' => $datos['correo'], 'texto' => 'correo'],
        ];
        foreach ($unicos as $campo => $unico) {
            if ($validador->error($campo) !== null) {
                continue;
            }
            $repetido = $this->proveedorRepositorio->buscarRepetido($this->idVendedor, $campo, $unico['valor'], $idProveedor);
            if ($repetido === null) {
                continue;
            }
            $mensaje = 'Ya tiene un proveedor con este ' . $unico['texto'];
            if ($campo !== 'nombre') {
                $mensaje .= ': ' . $repetido->getNombre();
            }
            if (!$repetido->getEstado()) {
                $mensaje .= ' (está desactivado; puede activarlo desde la lista de proveedores)';
            }
            $validador->agregarError($campo, $mensaje);
        }

        return $validador;
    }

    private function mostrarFormulario(?Proveedor $proveedor, array $datos, array $errores): void
    {
        $this->mostrarVista('Proveedor/formulario', [
            'proveedor' => $proveedor,
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