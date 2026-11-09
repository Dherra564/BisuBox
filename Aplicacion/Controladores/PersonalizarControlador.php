<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Pedido;
use Aplicacion\Modelos\PedidoDetalle;
use Aplicacion\Modelos\Personalizado;
use Aplicacion\Modelos\PersonalizadoComponente;
use Aplicacion\Modelos\Producto;
use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Acceso;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosPago;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\PersonalizadoRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;

class PersonalizarControlador
{
    private PersonalizadoRepositorio $personalizadoRepositorio;
    private int $idCliente;

    public function __construct()
    {
        if (!UsuarioActual::haySesion()) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $consulta = http_build_query(array_filter([
                    'tienda' => is_string($_GET['tienda'] ?? null) && ctype_digit($_GET['tienda']) ? $_GET['tienda'] : null,
                    'producto' => is_string($_GET['producto'] ?? null) && ctype_digit($_GET['producto']) ? $_GET['producto'] : null,
                ]));
                Acceso::guardarDestino('/personalizar' . ($consulta !== '' ? '?' . $consulta : ''));
            }
            Mensaje::advertencia('Para personalizar una pieza, ingrese o cree su cuenta.');
            $this->redirigir('/ingresar');
        }
        Permiso::exigir('personalizar');
        $this->personalizadoRepositorio = new PersonalizadoRepositorio();
        $this->idCliente = (new ClienteRepositorio())->asegurar((int) UsuarioActual::id());
    }

    public function mostrar(): void
    {
        [$tienda, $producto] = $this->buscarProducto($_GET['tienda'] ?? null, $_GET['producto'] ?? null);
        $lineas = $this->armarLineas($tienda, $producto);

        $elegidos = [];
        foreach ($lineas as $id => $linea) {
            $elegidos[$id] = $linea['originalDisponible'] ? $linea['idOriginal'] : null;
        }

        $this->mostrarFormulario($tienda, $producto, $lineas, ['piezas' => '1', 'elegidos' => $elegidos, 'nota' => ''], []);
    }

    public function enviar(): void
    {
        [$tienda, $producto] = $this->buscarProducto($_POST['tienda'] ?? null, $_POST['producto'] ?? null);
        $volver = '/personalizar?tienda=' . $tienda->getIdVendedor() . '&producto=' . $producto->getIdProducto();
        $this->verificarCsrf($volver);
        $lineas = $this->armarLineas($tienda, $producto);

        $piezasTexto = is_string($_POST['piezas'] ?? null) ? trim($_POST['piezas']) : '';
        $nota = is_string($_POST['nota'] ?? null) ? trim($_POST['nota']) : '';
        $enviados = is_array($_POST['componente'] ?? null) ? $_POST['componente'] : [];
        $elegidos = [];
        foreach ($lineas as $id => $linea) {
            $valor = $enviados[$id] ?? null;
            $elegidos[$id] = is_string($valor) && ctype_digit($valor) ? (int) $valor : null;
        }
        $datos = ['piezas' => $piezasTexto, 'elegidos' => $elegidos, 'nota' => $nota];

        $validador = new Validador();
        $piezas = Carrito::leerCantidad($piezasTexto);
        if ($piezas === null) {
            $validador->agregarError('piezas', 'Escriba cuántas piezas quiere, con números enteros de 1 a ' . Carrito::MAXIMO_POR_PRODUCTO);
        }
        $validador->longitud('nota', $nota, 0, 300, 'La nota puede tener como máximo 300 caracteres');

        $necesidades = [];
        $componentesElegidos = [];
        foreach ($lineas as $id => $linea) {
            $elegido = $elegidos[$id] !== null ? ($linea['opciones'][$elegidos[$id]] ?? null) : null;
            if ($elegido === null || $elegido->getExistencia() <= 0) {
                $validador->agregarError('componente.' . $id, 'Escoja ' . mb_strtolower($linea['tipo'], 'UTF-8') . ' entre los que hay en la tienda');
                continue;
            }
            $componentesElegidos[$id] = $elegido;
            $necesidades[$elegido->getIdComponente()] = ($necesidades[$elegido->getIdComponente()] ?? 0) + $linea['cantidad'] * ($piezas ?? 1);
        }
        $hayCambios = false;
        foreach ($componentesElegidos as $id => $elegido) {
            $hayCambios = $hayCambios || (int) $elegido->getIdComponente() !== $lineas[$id]['idOriginal'];
        }
        if (count($componentesElegidos) === count($lineas) && !$hayCambios) {
            $validador->agregarError('cambios', 'Cambie al menos una pieza para poder enviarla a cotizar. Si la quiere igual al original, puede comprarla directo en la tienda.');
        }
        foreach ($componentesElegidos as $id => $elegido) {
            $necesita = $necesidades[$elegido->getIdComponente()];
            if ($necesita > $elegido->getExistencia()) {
                $validador->agregarError('componente.' . $id, 'Necesita ' . Numero::formatear($necesita) . ' de «' . $elegido->getNombre()
                    . '» y en la tienda solo quedan ' . Numero::formatear($elegido->getExistencia()) . '. Escoja otro o pida menos piezas.');
            }
        }

        if (!$validador->esValido()) {
            $this->mostrarFormulario($tienda, $producto, $lineas, $datos, $validador->errores());
            return;
        }

        $componentes = [];
        $costoComponentes = 0.0;
        foreach ($lineas as $id => $linea) {
            $elegido = $componentesElegidos[$id];
            $componentes[] = new PersonalizadoComponente(
                null,
                null,
                (int) $elegido->getIdComponente(),
                $linea['idOriginal'],
                $linea['cantidad'],
                $elegido->getCostoUnitario()
            );
            $costoComponentes += $linea['cantidad'] * $elegido->getCostoUnitario();
        }

        $personalizado = new Personalizado(
            null,
            (int) $tienda->getIdVendedor(),
            $this->idCliente,
            (int) $producto->getIdProducto(),
            $producto->getNombre(),
            (int) $piezas,
            $nota !== '' ? $nota : null,
            EstadoPersonalizado::EN_REVISION,
            $producto->getMinutos(),
            $producto->getTarifaHora(),
            round($costoComponentes, 2),
            $producto->getCostoManoObra(),
            new DateTime(),
            $componentes
        );

        try {
            $idPersonalizado = $this->personalizadoRepositorio->crear($personalizado);
        } catch (Throwable $error) {
            error_log('Error al enviar el pedido personalizado: ' . $error->getMessage());
            Mensaje::error('No se pudo enviar su pedido. Intente de nuevo.');
            $this->mostrarFormulario($tienda, $producto, $lineas, $datos, []);
            return;
        }

        Mensaje::exito('Su pedido personalizado se envió a ' . $tienda->getTiendaNombre() . '. Le avisamos aquí cuando le manden el precio.');
        $this->redirigir('/mis-pedidos/personalizado?id=' . $idPersonalizado);
    }

    public function listar(): void
    {
        $estado = EstadoPersonalizado::existe($_GET['estado'] ?? null) ? $_GET['estado'] : '';
        $conteos = ['' => $this->personalizadoRepositorio->contar(['idCliente' => $this->idCliente])];
        foreach (EstadoPersonalizado::todos() as $codigo) {
            $conteos[$codigo] = $this->personalizadoRepositorio->contar(['idCliente' => $this->idCliente, 'estado' => $codigo]);
        }

        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / PersonalizadoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Personalizar/lista', [
            'personalizados' => $this->personalizadoRepositorio->listar(['idCliente' => $this->idCliente, 'estado' => $estado], $pagina),
            'estado' => $estado,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => PersonalizadoRepositorio::POR_PAGINA,
        ]);
    }

    public function detalle(): void
    {
        $personalizado = $this->buscarPersonalizado($_GET['id'] ?? null);
        $this->mostrarVista('Personalizar/detalle', ['personalizado' => $personalizado]);
    }

    public function cancelar(): void
    {
        $this->verificarCsrf('/mis-pedidos/personalizados');
        $personalizado = $this->buscarPersonalizado($_POST['id'] ?? null);
        $volver = '/mis-pedidos/personalizado?id=' . $personalizado->getIdPersonalizado();

        try {
            $this->personalizadoRepositorio->cancelar($personalizado, Rol::CLIENTE);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al cancelar el pedido personalizado: ' . $error->getMessage());
            Mensaje::error('No se pudo cancelar. Intente de nuevo.');
            $this->redirigir($volver);
        }

        Mensaje::exito('Pedido personalizado cancelado. Le avisamos a la tienda.');
        $this->redirigir($volver);
    }

    public function comprar(): void
    {
        $personalizado = $this->personalizadoListoParaComprar($_GET['id'] ?? null);
        $this->mostrarCompra($personalizado, ['entrega' => '', 'direccion' => '', 'metodoPago' => '', 'nota' => '', 'referencia' => '', 'comprobantePendiente' => null], []);
    }

    public function hacerCompra(): void
    {
        $this->verificarCsrf('/mis-pedidos/personalizados');
        $personalizado = $this->personalizadoListoParaComprar($_POST['id'] ?? null);

        $tienda = $this->tiendaDe($personalizado);
        $datos = $this->leerDatosEntrega();
        $validador = $this->validarDatosEntrega($datos, $tienda);
        $leidoPago = null;
        if ($datos['metodoPago'] === MetodoPago::SINPE && $validador->error('metodoPago') === null) {
            $leidoPago = DatosPago::recibir($tienda, $validador);
            $datos['referencia'] = $leidoPago['referencia'];
            $datos['comprobantePendiente'] = $leidoPago['comprobante'];
        }
        if (!$validador->esValido()) {
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COMPROBANTE, $datos['comprobantePendiente']);
            $this->mostrarCompra($personalizado, $datos, $validador->errores());
            return;
        }

        $detalle = new PedidoDetalle(
            null,
            null,
            $personalizado->getIdProducto(),
            mb_substr($personalizado->getNombre() . ' (personalizado)', 0, 60, 'UTF-8'),
            $personalizado->getPiezas(),
            (float) $personalizado->getPrecio(),
            null,
            (int) $personalizado->getIdPersonalizado()
        );
        $pedido = new Pedido(
            null,
            $personalizado->getIdVendedor(),
            $this->idCliente,
            new DateTime(),
            $datos['entrega'],
            $datos['entrega'] === FormaEntrega::ENVIO ? $datos['direccion'] : null,
            $datos['metodoPago'],
            $datos['nota'] !== '' ? $datos['nota'] : null,
            (float) $personalizado->getTotal(),
            EstadoPedido::CONFIRMADO,
            null,
            null,
            [$detalle]
        );

        $pago = $leidoPago !== null ? DatosPago::crearPago($tienda, $pedido->getTotal(), $leidoPago) : null;

        try {
            $idPedido = $this->personalizadoRepositorio->comprar($personalizado, $pedido, $pago);
        } catch (OperacionNoPermitida $error) {
            SubidaArchivo::eliminarComprobante($datos['comprobantePendiente']);
            Mensaje::error($error->getMessage());
            $this->redirigir('/mis-pedidos/personalizado?id=' . $personalizado->getIdPersonalizado());
        } catch (Throwable $error) {
            error_log('Error al comprar el pedido personalizado: ' . $error->getMessage());
            SubidaArchivo::recordarPendiente(SubidaArchivo::FOTO_COMPROBANTE, $datos['comprobantePendiente']);
            Mensaje::error('No se pudo enviar el pedido. Intente de nuevo.');
            $this->mostrarCompra($personalizado, $datos, []);
            return;
        }

        SubidaArchivo::olvidarPendiente(SubidaArchivo::FOTO_COMPROBANTE, $datos['comprobantePendiente']);
        DatosPago::olvidarCodigoPago($personalizado->getIdVendedor());
        Mensaje::exito('Su compra a ' . $personalizado->getNombreTienda() . ' quedó confirmada.'
            . ($pago !== null ? ' La tienda va a revisar que le llegó su SINPE.' : ' La tienda ya está preparando su pieza.'));
        $this->redirigir('/mis-pedidos/detalle?id=' . $idPedido);
    }

    private function armarLineas(Vendedor $tienda, Producto $producto): array
    {
        $componenteRepositorio = new ComponenteRepositorio();
        $porTipo = [];
        foreach ($componenteRepositorio->listarActivos((int) $tienda->getIdVendedor()) as $componente) {
            $porTipo[$componente->getIdTipoComponente()][(int) $componente->getIdComponente()] = $componente;
        }

        $lineas = [];
        foreach ($producto->getComponentes() as $productoComponente) {
            $original = $componenteRepositorio->buscarPorId($productoComponente->getIdComponente(), (int) $tienda->getIdVendedor());
            if ($original === null) {
                continue;
            }
            $opciones = $porTipo[$original->getIdTipoComponente()] ?? [];
            $lineas[(int) $productoComponente->getIdProductoComponente()] = [
                'tipo' => $original->getNombreTipo(),
                'cantidad' => $productoComponente->getCantidad(),
                'unidad' => $original->getUnidad(),
                'idOriginal' => (int) $original->getIdComponente(),
                'original' => $original,
                'originalDisponible' => isset($opciones[(int) $original->getIdComponente()]) && $original->getExistencia() > 0,
                'opciones' => $opciones,
            ];
        }
        return $lineas;
    }

    private function buscarProducto(mixed $idTienda, mixed $idProducto): array
    {
        $idTienda = is_string($idTienda) && ctype_digit($idTienda) ? (int) $idTienda : 0;
        $idProducto = is_string($idProducto) && ctype_digit($idProducto) ? (int) $idProducto : 0;

        $tienda = $idTienda > 0 ? (new VendedorRepositorio())->buscarPorId($idTienda) : null;
        if ($tienda === null || !$tienda->getTiendaActiva() || !$tienda->getEstado()) {
            Mensaje::error('Esa tienda ya no está disponible.');
            $this->redirigir('/');
        }
        $volver = '/tienda/' . $tienda->getTiendaEnlace();
        if (Carrito::esPropia($tienda)) {
            Mensaje::error('No puede pedir piezas personalizadas en su propia tienda.');
            $this->redirigir($volver);
        }

        $producto = $idProducto > 0 ? (new ProductoRepositorio())->buscarPorId($idProducto, $idTienda) : null;
        if ($producto === null || !$producto->getActivo()) {
            Mensaje::error('Ese producto ya no está disponible.');
            $this->redirigir($volver);
        }
        if ($producto->getComponentes() === []) {
            Mensaje::advertencia('Este producto no se puede personalizar.');
            $this->redirigir($volver);
        }

        return [$tienda, $producto];
    }

    private function buscarPersonalizado(mixed $id): Personalizado
    {
        $personalizado = is_string($id) && ctype_digit($id)
            ? $this->personalizadoRepositorio->buscarParaCliente((int) $id, $this->idCliente)
            : null;

        if ($personalizado === null) {
            Mensaje::error('No se encontró ese pedido personalizado.');
            $this->redirigir('/mis-pedidos/personalizados');
        }
        return $personalizado;
    }

    private function personalizadoListoParaComprar(mixed $id): Personalizado
    {
        $personalizado = $this->buscarPersonalizado($id);
        $volver = '/mis-pedidos/personalizado?id=' . $personalizado->getIdPersonalizado();

        if (!$personalizado->estaCotizado()) {
            Mensaje::error('Este pedido ya no está esperando su respuesta.');
            $this->redirigir($volver);
        }
        if (!$personalizado->alcanzaInventario()) {
            Mensaje::error('La tienda ya no tiene suficiente de: ' . implode(', ', $personalizado->getFaltantes())
                . '. Escríbale a la tienda o personalice otra pieza.');
            $this->redirigir($volver);
        }
        return $personalizado;
    }

    private function mostrarFormulario(Vendedor $tienda, Producto $producto, array $lineas, array $datos, array $errores): void
    {
        $this->mostrarVista('Personalizar/formulario', [
            'tiendaVista' => $tienda,
            'producto' => $producto,
            'lineas' => $lineas,
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    private function tiendaDe(Personalizado $personalizado): Vendedor
    {
        $tienda = (new VendedorRepositorio())->buscarPorId($personalizado->getIdVendedor());
        if ($tienda === null || !$tienda->getTiendaActiva() || !$tienda->getEstado()) {
            Mensaje::error('Esa tienda ya no está disponible.');
            $this->redirigir('/mis-pedidos/personalizados');
        }
        return $tienda;
    }

    private function mostrarCompra(Personalizado $personalizado, array $datos, array $errores): void
    {
        $tienda = $this->tiendaDe($personalizado);
        $this->mostrarVista('Personalizar/comprar', [
            'personalizado' => $personalizado,
            'tiendaVista' => $tienda,
            'tiendaPago' => $tienda,
            'metodos' => DatosPago::metodosPara($tienda),
            'codigoPago' => $tienda->tieneSinpe() ? DatosPago::codigoPago((int) $tienda->getIdVendedor()) : null,
            'cliente' => (new ClienteRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id()),
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    private function leerDatosEntrega(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'entrega' => $leer('entrega'),
            'direccion' => (string) preg_replace('/[ \t]+/', ' ', $leer('direccion')),
            'metodoPago' => $leer('metodoPago'),
            'nota' => $leer('nota'),
            'referencia' => '',
            'comprobantePendiente' => null,
        ];
    }

    private function validarDatosEntrega(array $datos, Vendedor $tienda): Validador
    {
        $validador = new Validador();

        if (!FormaEntrega::existe($datos['entrega'])) {
            $validador->agregarError('entrega', 'Escoja cómo quiere recibir el pedido');
        }
        if ($datos['entrega'] === FormaEntrega::ENVIO) {
            $validador->requerido('direccion', $datos['direccion'], 'Escriba la dirección de entrega')
                ->longitud('direccion', $datos['direccion'], 10, 250, 'La dirección debe tener entre 10 y 250 caracteres')
                ->direccion('direccion', $datos['direccion']);
        }
        if (!isset(DatosPago::metodosPara($tienda)[$datos['metodoPago']])) {
            $validador->agregarError('metodoPago', 'Escoja cómo va a pagar');
        }
        $validador->longitud('nota', $datos['nota'], 0, 300, 'La nota puede tener como máximo 300 caracteres');

        return $validador;
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