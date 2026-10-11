<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Proveedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\TipoIdentificacion;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
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
        $this->mostrarVista('Proveedor/detalle', [
            'proveedor' => $this->cargarProveedor($_GET['id'] ?? null),
        ]);
    }

    public function nuevo(): void
    {
        $this->mostrarFormulario(null, [
            'tipoIdentificacion' => TipoIdentificacion::CEDULA_JURIDICA,
            'numeroIdentificacion' => '',
            'nombre' => '',
            'telefono' => '',
            'correo' => '',
        ], []);
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
            tipoIdentificacion: $datos['tipoIdentificacion'],
            numeroIdentificacion: $datos['numeroIdentificacion'],
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
            'tipoIdentificacion' => $proveedor->getTipoIdentificacion(),
            'numeroIdentificacion' => $proveedor->getNumeroIdentificacion(),
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

        $proveedor->setTipoIdentificacion($datos['tipoIdentificacion']);
        $proveedor->setNumeroIdentificacion($datos['numeroIdentificacion']);
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
        $this->redirigir(($_POST['volver'] ?? '') === 'detalle'
            ? '/proveedores/detalle?id=' . $proveedor->getIdProveedor()
            : '/proveedores');
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
            'tipoIdentificacion' => $leer('tipoIdentificacion'),
            'numeroIdentificacion' => TipoIdentificacion::limpiar($leer('numeroIdentificacion')),
            'nombre' => UsuarioRepositorio::limpiarEspacios($leer('nombre')),
            'telefono' => $leer('telefono'),
            'correo' => mb_strtolower($leer('correo'), 'UTF-8'),
        ];
    }

    
    private function validarDatos(array $datos, ?int $idProveedor): Validador
    {
        $validador = new Validador();

        
        if (!TipoIdentificacion::existeParaProveedor($datos['tipoIdentificacion'])) {
            $validador->agregarError('tipoIdentificacion', 'Seleccione el tipo de identificación');
        }
        $validador->requerido('numeroIdentificacion', $datos['numeroIdentificacion'], 'Ingrese la identificación');
        if (
            $validador->error('numeroIdentificacion') === null
            && TipoIdentificacion::existeParaProveedor($datos['tipoIdentificacion'])
            && !TipoIdentificacion::esValida($datos['tipoIdentificacion'], $datos['numeroIdentificacion'])
        ) {
            $validador->agregarError('numeroIdentificacion', TipoIdentificacion::mensaje($datos['tipoIdentificacion']));
        }

        $validador->requerido('nombre', $datos['nombre'], 'Ingrese el nombre del proveedor')
            ->longitud('nombre', $datos['nombre'], 3, 50, 'El nombre debe tener entre 3 y 50 caracteres')
            ->nombreComercial('nombre', $datos['nombre']);

        $validador->requerido('telefono', $datos['telefono'], 'Ingrese el teléfono')
            ->telefono('telefono', $datos['telefono']);

        $validador->requerido('correo', $datos['correo'], 'Ingrese el correo')
            ->correoGeneral('correo', $datos['correo']);

        
        if (
            $validador->error('numeroIdentificacion') === null
            && $this->proveedorRepositorio->existeIdentificacion($this->idVendedor, $datos['numeroIdentificacion'], $idProveedor)
        ) {
            $validador->agregarError('numeroIdentificacion', 'Ya tiene un proveedor con esta identificación');
        }
        if (
            $validador->error('nombre') === null
            && $this->proveedorRepositorio->existeNombre($this->idVendedor, $datos['nombre'], $idProveedor)
        ) {
            $validador->agregarError('nombre', 'Ya tiene un proveedor con este nombre');
        }
        if (
            $validador->error('telefono') === null
            && $this->proveedorRepositorio->existeTelefono($this->idVendedor, Validador::limpiarTelefono($datos['telefono']), $idProveedor)
        ) {
            $validador->agregarError('telefono', 'Ya tiene un proveedor con este teléfono');
        }
        if (
            $validador->error('correo') === null
            && $this->proveedorRepositorio->existeCorreo($this->idVendedor, $datos['correo'], $idProveedor)
        ) {
            $validador->agregarError('correo', 'Ya tiene un proveedor con este correo');
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
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl'), '/') . $ruta);
        exit;
    }
}