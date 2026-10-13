<?php
namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Producto;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosProducto;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\ProduccionRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class ProductoControlador
{
    private const ESTADOS = ['activos' => 'Activos', 'agotados' => 'Agotados', 'inactivos' => 'Desactivados', 'todos' => 'Todos'];

    private ProductoRepositorio $productoRepositorio;
    private ProduccionRepositorio $produccionRepositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('productos.gestionar');
        $this->productoRepositorio = new ProductoRepositorio();
        $this->produccionRepositorio = new ProduccionRepositorio();

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
        $estado = is_string($_GET['estado'] ?? null) && isset(self::ESTADOS[$_GET['estado']]) ? $_GET['estado'] : 'activos';

        $conteos = [];
        foreach (array_keys(self::ESTADOS) as $codigo) {
            $conteos[$codigo] = $this->productoRepositorio->contar($this->idVendedor, ['estado' => $codigo, 'busqueda' => $busqueda]);
        }

        $filtros = ['estado' => $estado, 'busqueda' => $busqueda];
        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / ProductoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Producto/lista', [
            'productos' => $this->productoRepositorio->listar($this->idVendedor, $filtros, $pagina),
            'resumen' => $this->productoRepositorio->resumen($this->idVendedor),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'estados' => self::ESTADOS,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => ProductoRepositorio::POR_PAGINA,
            'hayProductos' => $this->productoRepositorio->contar($this->idVendedor) > 0,
        ]);
    }

    public function nuevo(): void
    {
        $componentes = $this->componentesDisponibles();
        $this->mostrarFormulario(DatosProducto::vacio($this->productoRepositorio->ultimaTarifaHora($this->idVendedor)), [], $componentes);
    }

    public function insertar(): void
    {
        $this->verificarCsrf('/productos/nuevo');
        $componentes = $this->componentesDisponibles();

        $datos = DatosProducto::leer();
        $validador = new Validador();
        DatosProducto::validar($validador, $datos, $componentes, $this->idVendedor);
        $foto = $this->subirFoto($validador);
        if (!$validador->esValido()) {
            SubidaArchivo::eliminarFotoProducto($foto);
            $this->mostrarFormulario($datos, $validador->errores(), $componentes);
            return;
        }

        $producto = DatosProducto::crearProducto($datos, $this->idVendedor, $componentes);
        $producto->setFoto($foto);
        $cantidad = (int) $datos['cantidad'];
        try {
            $idProducto = $this->produccionRepositorio->crearProducto($producto, $cantidad);
        } catch (OperacionNoPermitida $error) {
            SubidaArchivo::eliminarFotoProducto($foto);
            Mensaje::error($error->getMessage());
            $this->mostrarFormulario($datos, [], $this->componentesDisponibles());
            return;
        } catch (Throwable $error) {
            error_log('Error al crear el producto: ' . $error->getMessage());
            SubidaArchivo::eliminarFotoProducto($foto);
            Mensaje::error('No se pudo guardar. No se cambió nada en el inventario; intente de nuevo.');
            $this->mostrarFormulario($datos, [], $componentes);
            return;
        }

        Mensaje::exito('«' . $producto->getNombre() . '» listo: ' . ($cantidad === 1 ? 'hizo 1' : 'hizo ' . $cantidad)
            . ' y se descontó del inventario lo que llevan.');
        $this->redirigir('/productos/detalle?id=' . $idProducto);
    }

    public function detalle(): void
    {
        $producto = $this->buscarProducto($_GET['id'] ?? null);

        $existencias = [];
        foreach ((new ComponenteRepositorio())->listarActivos($this->idVendedor) as $componente) {
            $existencias[(int) $componente->getIdComponente()] = $componente->getExistencia();
        }
        $desactivados = [];
        foreach ($producto->getComponentes() as $linea) {
            if (!isset($existencias[$linea->getIdComponente()])) {
                $desactivados[] = $linea->getNombreComponente();
            }
        }

        $this->mostrarVista('Producto/detalle', [
            'producto' => $producto,
            'producciones' => $this->produccionRepositorio->listarPorProducto((int) $producto->getIdProducto(), $this->idVendedor),
            'alcanzaPara' => $this->alcanzaPara($producto, $existencias),
            'desactivados' => $desactivados,
        ]);
    }

    public function editar(): void
    {
        $producto = $this->buscarProducto($_GET['id'] ?? null);
        $this->mostrarEdicion(DatosProducto::paraEditar($producto), [], $producto);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/productos');
        $producto = $this->buscarProducto($_POST['id'] ?? null);

        $datos = DatosProducto::leerDatosComunes();
        $validador = new Validador();
        DatosProducto::validarDatosComunes($validador, $datos, $this->idVendedor, $producto);
        $foto = $this->subirFoto($validador);
        if (!$validador->esValido()) {
            SubidaArchivo::eliminarFotoProducto($foto);
            $this->mostrarEdicion($datos, $validador->errores(), $producto);
            return;
        }

        $fotoAnterior = $producto->getFoto();
        $producto->setNombre($datos['nombre']);
        $producto->setDescripcion($datos['descripcion'] !== '' ? $datos['descripcion'] : null);
        $producto->setPrecioVenta((float) Numero::leer($datos['precioVenta']));
        if ($foto !== null) {
            $producto->setFoto($foto);
        } elseif (($_POST['quitarFoto'] ?? '') === '1') {
            $producto->setFoto(null);
        }

        try {
            $this->productoRepositorio->actualizar($producto);
        } catch (Throwable $error) {
            error_log('Error al actualizar el producto: ' . $error->getMessage());
            SubidaArchivo::eliminarFotoProducto($foto);
            $producto->setFoto($fotoAnterior);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarEdicion($datos, [], $producto);
            return;
        }

        if ($fotoAnterior !== null && $fotoAnterior !== $producto->getFoto()) {
            SubidaArchivo::eliminarFotoProducto($fotoAnterior);
        }

        Mensaje::exito('Cambios de «' . $producto->getNombre() . '» guardados');
        $this->redirigir('/productos/detalle?id=' . $producto->getIdProducto());
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/productos');
        $producto = $this->buscarProducto($_POST['id'] ?? null);
        $estado = $_POST['activo'] ?? null;
        if (!in_array($estado, ['1', '0'], true)) {
            Mensaje::error('Estado no válido');
            $this->redirigir('/productos');
        }
        $activo = $estado === '1';

        try {
            $this->productoRepositorio->cambiarEstado($producto, $activo);
        } catch (Throwable $error) {
            error_log('Error al cambiar el estado del producto: ' . $error->getMessage());
            Mensaje::error('No se pudo cambiar. Intente de nuevo.');
            $this->redirigir('/productos/detalle?id=' . $producto->getIdProducto());
        }

        Mensaje::exito('«' . $producto->getNombre() . '» ' . ($activo ? 'activado' : 'desactivado'));
        $this->redirigir('/productos/detalle?id=' . $producto->getIdProducto());
    }

    public function producir(): void
    {
        $this->verificarCsrf('/productos');
        $producto = $this->buscarProducto($_POST['id'] ?? null);
        $volver = ($_POST['volver'] ?? '') === 'lista' ? '/productos' : '/productos/detalle?id=' . $producto->getIdProducto();

        $cantidad = is_string($_POST['cantidad'] ?? null) ? trim($_POST['cantidad']) : '';
        if (!DatosProducto::esCantidadValida($cantidad)) {
            Mensaje::error(str_starts_with($cantidad, '-') ? 'No puede ser un número negativo.' : 'Escriba cuántos hizo, con números enteros desde 1.');
            $this->redirigir($volver);
        }

        try {
            $this->produccionRepositorio->producir($producto, (int) $cantidad);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al registrar la producción: ' . $error->getMessage());
            Mensaje::error('No se pudo registrar. No se cambió nada en el inventario; intente de nuevo.');
            $this->redirigir($volver);
        }

        Mensaje::exito(((int) $cantidad === 1 ? 'Se sumó 1' : 'Se sumaron ' . (int) $cantidad) . ' a «' . $producto->getNombre() . '» y se descontó del inventario lo que llevan.');
        $this->redirigir($volver);
    }

    public function anularProduccion(): void
    {
        $this->verificarCsrf('/productos');
        $idProduccion = $_POST['id'] ?? null;
        $produccion = is_string($idProduccion) && ctype_digit($idProduccion)
            ? $this->produccionRepositorio->buscarPorId((int) $idProduccion, $this->idVendedor)
            : null;
        if ($produccion === null) {
            Mensaje::error('No se encontró esa producción.');
            $this->redirigir('/productos');
        }
        $volver = '/productos/detalle?id=' . $produccion->getIdProducto();

        try {
            $this->produccionRepositorio->anular($produccion);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al anular la producción: ' . $error->getMessage());
            Mensaje::error('No se pudo anular. Intente de nuevo.');
            $this->redirigir($volver);
        }

        Mensaje::exito('Producción anulada: ' . ($produccion->getCantidad() === 1 ? 'se quitó 1' : 'se quitaron ' . $produccion->getCantidad())
            . ' del producto y los componentes volvieron al inventario.');
        $this->redirigir($volver);
    }

    /** @return array<int, \Aplicacion\Modelos\Componente>*/
    private function componentesDisponibles(): array
    {
        $componentes = [];
        foreach ((new ComponenteRepositorio())->listarActivos($this->idVendedor) as $componente) {
            $componentes[(int) $componente->getIdComponente()] = $componente;
        }

        $hayExistencia = array_filter($componentes, fn($componente): bool => $componente->getExistencia() > 0) !== [];
        if (!$hayExistencia) {
            Mensaje::advertencia('Para hacer un producto necesita componentes en el inventario. Regístrelos con una compra a proveedores.');
            $this->redirigir('/compras-proveedor/nueva');
        }

        return $componentes;
    }

    private function alcanzaPara(Producto $producto, array $existencias): int
    {

        $alcanza = null;
        foreach ($producto->getComponentes() as $linea) {
            $tiene = $existencias[$linea->getIdComponente()] ?? 0.0;
            $paraEste = $linea->getCantidad() > 0 ? (int) floor(($tiene + 0.001) / $linea->getCantidad()) : 0;
            $alcanza = $alcanza === null ? $paraEste : min($alcanza, $paraEste);
        }

        return $alcanza ?? 0;
    }

    private function subirFoto(Validador $validador): ?string
    {
        if (!SubidaArchivo::seEnvio($_FILES['foto'] ?? null)) {
            return null;
        }
        $subida = new SubidaArchivo();
        $nombre = $subida->guardarFotoProducto($_FILES['foto']);
        if ($nombre === null) {
            $validador->agregarError('foto', $subida->obtenerError());
        }
        return $nombre;
    }

    private function buscarProducto(mixed $id): Producto
    {
        $producto = is_string($id) && ctype_digit($id)
            ? $this->productoRepositorio->buscarPorId((int) $id, $this->idVendedor)
            : null;

        if ($producto === null) {
            Mensaje::error('No se encontró ese producto.');
            $this->redirigir('/productos');
        }

        return $producto;
    }

    private function mostrarFormulario(array $datos, array $errores, array $componentes): void
    {
        $this->mostrarVista('Producto/formulario', ['datos' => $datos, 'errores' => $errores, 'componentes' => $componentes]);
    }

    private function mostrarEdicion(array $datos, array $errores, Producto $producto): void
    {
        $this->mostrarVista('Producto/editar', ['datos' => $datos, 'errores' => $errores, 'producto' => $producto]);
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
