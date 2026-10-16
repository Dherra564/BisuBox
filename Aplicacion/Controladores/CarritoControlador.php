<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Cliente;
use Aplicacion\Modelos\Pedido;
use Aplicacion\Modelos\PedidoDetalle;
use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\FormaEntrega;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\MetodoPago;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\PedidoRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;

// Carrito del cliente y envío del pedido. El carrito va separado por tienda:
// cada tienda se pide por aparte
class CarritoControlador
{
    private Cliente $cliente;
    private VendedorRepositorio $vendedorRepositorio;
    private ProductoRepositorio $productoRepositorio;

    public function __construct()
    {
        Permiso::exigir('carrito.usar');
        $this->vendedorRepositorio = new VendedorRepositorio();
        $this->productoRepositorio = new ProductoRepositorio();

        $cliente = (new ClienteRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($cliente === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Cliente pero no tiene registro de cliente');
            Mensaje::error('No se encontró su cuenta de cliente.');
            $this->redirigir('/');
        }
        $this->cliente = $cliente;
    }

    // Muestra solo el carrito de la tienda donde está comprando
    public function ver(): void
    {
        $tienda = Carrito::tiendaActual();
        $tiendas = $tienda !== null ? $this->armarCarrito($tienda['id']) : [];
        $this->mostrarVista('Carrito/carrito', ['tiendas' => $tiendas, 'tiendaActual' => $tienda]);
    }

    public function agregar(): void
    {
        $idVendedor = $this->leerId($_POST['tienda'] ?? null);
        $vendedor = $idVendedor > 0 ? $this->tiendaVisible($idVendedor) : null;
        if ($vendedor === null) {
            Mensaje::error('Esa tienda ya no está disponible.');
            $this->redirigir('/');
        }
        $volver = '/tienda/' . $vendedor->getTiendaEnlace();
        $this->verificarCsrf($volver);

        $idProducto = $this->leerId($_POST['producto'] ?? null);
        $producto = $this->productoRepositorio->buscarVarios($idVendedor, [$idProducto])[$idProducto] ?? null;
        if ($producto === null || !$producto->getActivo()) {
            Mensaje::error('Ese producto ya no está disponible.');
            $this->redirigir($volver);
        }

        $cantidad = Carrito::leerCantidad($_POST['cantidad'] ?? null);
        if ($cantidad === null) {
            Mensaje::error('Escriba cuántos quiere de «' . $producto->getNombre() . '», con números enteros desde 1.');
            $this->redirigir($volver);
        }

        $yaTiene = Carrito::cantidadDe($idVendedor, $idProducto);
        $hay = $producto->getExistencia();
        if ($hay <= 0) {
            Mensaje::error('«' . $producto->getNombre() . '» está agotado.');
            $this->redirigir($volver);
        }
        if ($yaTiene + $cantidad > $hay) {
            Mensaje::error('Solo quedan ' . $hay . ' de «' . $producto->getNombre() . '»'
                . ($yaTiene > 0 ? ' y ya tiene ' . $yaTiene . ' en el carrito.' : '.'));
            $this->redirigir($volver);
        }

        Carrito::agregar($idVendedor, $idProducto, $cantidad);
        Mensaje::exito('Se agregó «' . $producto->getNombre() . '» al carrito'
            . ($cantidad > 1 ? ' (' . $cantidad . ')' : '') . '.');
        $this->redirigir($volver);
    }

    // Cambia las cantidades de una tienda o quita un producto
    public function actualizar(): void
    {
        $this->verificarCsrf('/carrito');
        $idVendedor = $this->leerId($_POST['tienda'] ?? null);
        $enCarrito = Carrito::deTienda($idVendedor);
        if ($enCarrito === []) {
            $this->redirigir('/carrito');
        }

        $quitar = $this->leerId($_POST['quitar'] ?? null);
        if ($quitar > 0) {
            Carrito::cambiar($idVendedor, $quitar, 0);
            Mensaje::exito('Producto quitado del carrito.');
            $this->redirigir('/carrito');
        }

        $productos = $this->productoRepositorio->buscarVarios($idVendedor, array_keys($enCarrito));
        $cantidades = is_array($_POST['cantidad'] ?? null) ? $_POST['cantidad'] : [];
        $avisos = [];
        foreach ($enCarrito as $idProducto => $actual) {
            if (!array_key_exists($idProducto, $cantidades)) {
                continue;
            }
            $texto = is_string($cantidades[$idProducto]) ? trim($cantidades[$idProducto]) : '';
            if ($texto === '0') {
                Carrito::cambiar($idVendedor, $idProducto, 0);
                continue;
            }
            $cantidad = Carrito::leerCantidad($texto);
            $nombre = isset($productos[$idProducto]) ? $productos[$idProducto]->getNombre() : 'un producto';
            if ($cantidad === null) {
                $avisos[] = 'La cantidad de «' . $nombre . '» debe ser un número entero de 1 a ' . Carrito::MAXIMO_POR_PRODUCTO . '; se dejó en ' . $actual . '.';
                continue;
            }
            $hay = isset($productos[$idProducto]) ? $productos[$idProducto]->getExistencia() : 0;
            if ($cantidad > $hay && $hay > 0) {
                $avisos[] = 'Solo quedan ' . $hay . ' de «' . $nombre . '»; se dejó en ' . $hay . '.';
                $cantidad = $hay;
            }
            Carrito::cambiar($idVendedor, $idProducto, $cantidad);
        }

        if ($avisos !== []) {
            Mensaje::advertencia(implode(' ', $avisos));
        } else {
            Mensaje::exito('Carrito actualizado.');
        }
        $this->redirigir('/carrito');
    }

    // Formulario para enviar el pedido de una tienda
    public function confirmar(): void
    {
        $carrito = $this->carritoListoParaPedir($this->leerId($_GET['tienda'] ?? null));

        $this->mostrarConfirmacion($carrito, [
            'entrega' => '',
            'direccion' => '',
            'metodoPago' => '',
            'nota' => '',
        ], []);
    }

    public function hacerPedido(): void
    {
        $idVendedor = $this->leerId($_POST['tienda'] ?? null);
        $this->verificarCsrf('/carrito/confirmar?tienda=' . $idVendedor);
        $carrito = $this->carritoListoParaPedir($idVendedor);

        $datos = $this->leerDatos();
        $validador = $this->validarDatos($datos);
        if (!$validador->esValido()) {
            $this->mostrarConfirmacion($carrito, $datos, $validador->errores());
            return;
        }

        $detalles = [];
        $total = 0.0;
        foreach ($carrito['lineas'] as $linea) {
            $producto = $linea['producto'];
            $detalle = new PedidoDetalle(null, null, (int) $producto->getIdProducto(), $producto->getNombre(), $linea['cantidad'], $producto->getPrecioVenta());
            $detalles[] = $detalle;
            $total += $detalle->getSubtotal();
        }

        $pedido = new Pedido(
            null,
            $idVendedor,
            (int) $this->cliente->getIdCliente(),
            new DateTime(),
            $datos['entrega'],
            $datos['entrega'] === FormaEntrega::ENVIO ? $datos['direccion'] : null,
            $datos['metodoPago'],
            $datos['nota'] !== '' ? $datos['nota'] : null,
            round($total, 2),
            EstadoPedido::PENDIENTE,
            null,
            null,
            $detalles
        );

        try {
            $idPedido = (new PedidoRepositorio())->crear($pedido);
        } catch (Throwable $error) {
            error_log('Error al crear el pedido: ' . $error->getMessage());
            Mensaje::error('No se pudo enviar el pedido. Intente de nuevo.');
            $this->mostrarConfirmacion($carrito, $datos, []);
            return;
        }

        Carrito::vaciarTienda($idVendedor);
        Mensaje::exito('Pedido enviado a ' . $carrito['vendedor']->getTiendaNombre()
            . '. Cuando la tienda lo confirme, lo verá aquí.');
        $this->redirigir('/mis-pedidos/detalle?id=' . $idPedido);
    }

    /**
     * Arma lo que hay en el carrito con los datos actuales de cada producto.
     * Quita lo que ya no existe y marca lo que no alcanza
     * @return array<int, array{vendedor: Vendedor, lineas: array, total: float, problemas: bool}>
     */
    private function armarCarrito(?int $soloTienda = null): array
    {
        $tiendas = [];
        foreach (Carrito::todo() as $idVendedor => $productosEnCarrito) {
            $idVendedor = (int) $idVendedor;
            if ($soloTienda !== null && $idVendedor !== $soloTienda) {
                continue;
            }

            $vendedor = $this->tiendaVisible($idVendedor);
            if ($vendedor === null) {
                Carrito::vaciarTienda($idVendedor);
                Mensaje::advertencia('Se quitaron del carrito los productos de una tienda que ya no está disponible.');
                continue;
            }

            $productos = $this->productoRepositorio->buscarVarios($idVendedor, array_keys($productosEnCarrito));
            $lineas = [];
            $total = 0.0;
            $problemas = false;
            foreach ($productosEnCarrito as $idProducto => $cantidad) {
                $producto = $productos[(int) $idProducto] ?? null;
                if ($producto === null || !$producto->getActivo()) {
                    Carrito::cambiar($idVendedor, (int) $idProducto, 0);
                    Mensaje::advertencia('Se quitó del carrito un producto que la tienda ya no vende.');
                    continue;
                }

                $hay = $producto->getExistencia();
                $aviso = null;
                if ($hay <= 0) {
                    $aviso = 'Se agotó. Quítelo para poder pedir.';
                } elseif ($cantidad > $hay) {
                    $aviso = 'Solo quedan ' . $hay . '. Baje la cantidad para poder pedir.';
                }
                $problemas = $problemas || $aviso !== null;

                $subtotal = round($cantidad * $producto->getPrecioVenta(), 2);
                $total += $subtotal;
                $lineas[] = ['producto' => $producto, 'cantidad' => (int) $cantidad, 'subtotal' => $subtotal, 'aviso' => $aviso];
            }

            if ($lineas !== []) {
                $tiendas[$idVendedor] = ['vendedor' => $vendedor, 'lineas' => $lineas, 'total' => round($total, 2), 'problemas' => $problemas];
            }
        }
        return $tiendas;
    }

    private function carritoListoParaPedir(int $idVendedor): array
    {
        $carrito = $this->armarCarrito($idVendedor)[$idVendedor] ?? null;
        if ($carrito === null) {
            Mensaje::advertencia('No tiene productos de esa tienda en el carrito.');
            $this->redirigir('/carrito');
        }
        if ($carrito['problemas']) {
            Mensaje::error('Algunos productos ya no tienen suficiente existencia. Revise las cantidades marcadas antes de pedir.');
            $this->redirigir('/carrito');
        }
        return $carrito;
    }

    private function tiendaVisible(int $idVendedor): ?Vendedor
    {
        $vendedor = $this->vendedorRepositorio->buscarPorId($idVendedor);
        return $vendedor !== null && $vendedor->getTiendaActiva() && $vendedor->getEstado() ? $vendedor : null;
    }

    private function leerDatos(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'entrega' => $leer('entrega'),
            'direccion' => (string) preg_replace('/[ \t]+/', ' ', $leer('direccion')),
            'metodoPago' => $leer('metodoPago'),
            'nota' => $leer('nota'),
        ];
    }

    private function validarDatos(array $datos): Validador
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
        if (!MetodoPago::existe($datos['metodoPago'])) {
            $validador->agregarError('metodoPago', 'Escoja cómo va a pagar');
        }
        $validador->longitud('nota', $datos['nota'], 0, 300, 'La nota puede tener como máximo 300 caracteres');

        return $validador;
    }

    private function mostrarConfirmacion(array $carrito, array $datos, array $errores): void
    {
        $this->mostrarVista('Carrito/confirmar', [
            'carrito' => $carrito,
            'cliente' => $this->cliente,
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    private function leerId(mixed $valor): int
    {
        return is_string($valor) && ctype_digit($valor) ? (int) $valor : 0;
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
