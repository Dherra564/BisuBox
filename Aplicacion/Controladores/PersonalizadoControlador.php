<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Personalizado;
use Aplicacion\Modelos\PersonalizadoExtra;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosProducto;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\PersonalizadoRepositorio;
use Aplicacion\Repositorios\ProductoRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class PersonalizadoControlador
{
    private PersonalizadoRepositorio $personalizadoRepositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('pedidos.gestionar');
        $this->personalizadoRepositorio = new PersonalizadoRepositorio();

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
        $estado = EstadoPersonalizado::existe($_GET['estado'] ?? null) ? $_GET['estado'] : EstadoPersonalizado::EN_REVISION;
        if (($_GET['estado'] ?? null) === 'todos') {
            $estado = '';
        }

        $conteos = ['' => $this->personalizadoRepositorio->contar(['idVendedor' => $this->idVendedor])];
        foreach (EstadoPersonalizado::todos() as $codigo) {
            $conteos[$codigo] = $this->personalizadoRepositorio->contar(['idVendedor' => $this->idVendedor, 'estado' => $codigo]);
        }

        $avisos = $this->personalizadoRepositorio->avisosSinVer($this->idVendedor);
        if ($avisos !== []) {
            $this->personalizadoRepositorio->marcarAvisosVistos($this->idVendedor);
        }

        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / PersonalizadoRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Personalizado/lista', [
            'personalizados' => $this->personalizadoRepositorio->listar(['idVendedor' => $this->idVendedor, 'estado' => $estado], $pagina),
            'estado' => $estado,
            'conteos' => $conteos,
            'avisos' => $avisos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => PersonalizadoRepositorio::POR_PAGINA,
        ]);
    }

    public function detalle(): void
    {
        $personalizado = $this->buscarPersonalizado($_GET['id'] ?? null);

        $extras = [];
        if ($personalizado->estaEnRevision()) {
            $producto = (new ProductoRepositorio())->buscarPorId($personalizado->getIdProducto(), $this->idVendedor);
            foreach ($producto !== null ? $producto->getExtras() : [] as $indice => $extra) {
                $extras[$indice] = ['concepto' => $extra->getConcepto(), 'monto' => Numero::paraCampo($extra->getMonto())];
            }
        }

        $this->mostrarDetalle($personalizado, [
            'extras' => $extras,
            'ganancia' => '',
            'precio' => '',
            'notaTienda' => '',
        ], []);
    }

    public function cotizar(): void
    {
        $this->verificarCsrf('/pedidos/personalizados');
        $personalizado = $this->buscarPersonalizado($_POST['id'] ?? null);
        $volver = '/pedidos/personalizado?id=' . $personalizado->getIdPersonalizado();

        if (!$personalizado->estaEnRevision()) {
            Mensaje::error('Este pedido ya fue cotizado o cancelado.');
            $this->redirigir($volver);
        }

        $datos = $this->leerCotizacion();
        $validador = new Validador();
        $this->validarCotizacion($validador, $datos);
        if (!$personalizado->alcanzaInventario()) {
            $validador->agregarError('inventario', 'Ya no tiene suficiente de: ' . implode(', ', $personalizado->getFaltantes())
                . '. Compre lo que falta antes de cotizar o use «No puedo hacerlo».');
        }
        if (!$validador->esValido()) {
            $this->mostrarDetalle($personalizado, $datos, $validador->errores());
            return;
        }

        $costoComponentes = 0.0;
        foreach ($personalizado->getComponentes() as $linea) {
            $linea->setCostoUnitario($linea->getCostoActual());
            $costoComponentes += $linea->getCantidad() * $linea->getCostoActual();
        }
        $extras = [];
        $costoExtras = 0.0;
        foreach ($datos['extras'] as $extra) {
            $monto = (float) Numero::leer($extra['monto']);
            $extras[] = new PersonalizadoExtra(null, $personalizado->getIdPersonalizado(), $extra['concepto'], $monto);
            $costoExtras += $monto;
        }
        $ganancia = in_array((int) $datos['ganancia'], DatosProducto::GANANCIAS, true) && ctype_digit($datos['ganancia']) ? (int) $datos['ganancia'] : null;
        $personalizado->setCotizacion(
            $costoComponentes,
            $costoExtras,
            $ganancia,
            (float) Numero::leer($datos['precio']),
            $datos['notaTienda'] !== '' ? $datos['notaTienda'] : null,
            $extras
        );

        try {
            $this->personalizadoRepositorio->cotizar($personalizado);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al cotizar el pedido personalizado: ' . $error->getMessage());
            Mensaje::error('No se pudo enviar la cotización. Intente de nuevo.');
            $this->mostrarDetalle($personalizado, $datos, []);
            return;
        }

        Mensaje::exito('Cotización enviada a ' . $personalizado->getNombreCliente() . ' por ' . Numero::moneda((float) $personalizado->getTotal())
            . '. Le avisaremos cuando responda.');
        $this->redirigir($volver);
    }

    public function rechazar(): void
    {
        $this->verificarCsrf('/pedidos/personalizados');
        $personalizado = $this->buscarPersonalizado($_POST['id'] ?? null);
        $volver = '/pedidos/personalizado?id=' . $personalizado->getIdPersonalizado();

        $motivo = is_string($_POST['motivo'] ?? null) ? trim($_POST['motivo']) : '';
        if (mb_strlen($motivo, 'UTF-8') > 300) {
            Mensaje::error('El motivo puede tener como máximo 300 caracteres.');
            $this->redirigir($volver);
        }

        try {
            $this->personalizadoRepositorio->cancelar($personalizado, Rol::VENDEDOR, $motivo !== '' ? $motivo : null);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al rechazar el pedido personalizado: ' . $error->getMessage());
            Mensaje::error('No se pudo cancelar. Intente de nuevo.');
            $this->redirigir($volver);
        }

        Mensaje::exito('Pedido personalizado #' . $personalizado->getIdPersonalizado() . ' cancelado. El cliente lo verá en sus pedidos.');
        $this->redirigir($volver);
    }

    private function leerCotizacion(): array
    {
        $texto = fn(mixed $valor): string => is_string($valor) ? trim($valor) : '';

        $extras = [];
        foreach (is_array($_POST['extra'] ?? null) ? $_POST['extra'] : [] as $clave => $extra) {
            if (!is_array($extra) || !preg_match('/^[0-9]{1,3}$/', (string) $clave)) {
                continue;
            }
            $leido = [
                'concepto' => UsuarioRepositorio::limpiarEspacios($texto($extra['concepto'] ?? null)),
                'monto' => $texto($extra['monto'] ?? null),
            ];
            if ($leido['concepto'] !== '' || $leido['monto'] !== '') {
                $extras[(int) $clave] = $leido;
            }
        }

        return [
            'extras' => $extras,
            'ganancia' => $texto($_POST['ganancia'] ?? null),
            'precio' => $texto($_POST['precio'] ?? null),
            'notaTienda' => $texto($_POST['notaTienda'] ?? null),
        ];
    }

    private function validarCotizacion(Validador $validador, array $datos): void
    {
        if (count($datos['extras']) > DatosProducto::MAXIMO_EXTRAS) {
            $validador->agregarError('extras', 'Puede agregar como máximo ' . DatosProducto::MAXIMO_EXTRAS . ' tarifas extra');
        } else {
            foreach ($datos['extras'] as $clave => $extra) {
                $campo = 'extra.' . $clave . '.';
                $validador->requerido($campo . 'concepto', $extra['concepto'], 'Escriba para qué es, por ejemplo Empaque')
                    ->longitud($campo . 'concepto', $extra['concepto'], 2, 60, 'El concepto debe tener entre 2 y 60 caracteres')
                    ->nombreComercial($campo . 'concepto', $extra['concepto'], 'El concepto solo puede tener letras, números, espacios y los signos . , & \' -');

                $monto = Numero::leer($extra['monto']);
                if ($extra['monto'] === '') {
                    $validador->agregarError($campo . 'monto', 'Escriba cuánto es');
                } elseif ($monto === null || $monto > Numero::MAXIMO) {
                    $validador->agregarError($campo . 'monto', 'Escriba un monto, por ejemplo 150 o 150,50');
                } elseif ($monto <= 0) {
                    $validador->agregarError($campo . 'monto', 'El monto debe ser mayor que 0');
                }
            }
        }

        $precio = Numero::leer($datos['precio']);
        if ($datos['precio'] === '') {
            $validador->agregarError('precio', 'Escriba el precio de cada pieza o escoja cuánto quiere ganar');
        } elseif ($precio === null || $precio > Numero::MAXIMO) {
            $validador->agregarError('precio', 'Escriba un precio, por ejemplo 8500 o 8500,50');
        } elseif ($precio <= 0) {
            $validador->agregarError('precio', 'El precio debe ser mayor que 0');
        }

        $validador->longitud('notaTienda', $datos['notaTienda'], 0, 300, 'El mensaje puede tener como máximo 300 caracteres');
    }

    private function mostrarDetalle(Personalizado $personalizado, array $datos, array $errores): void
    {
        $this->mostrarVista('Personalizado/detalle', [
            'personalizado' => $personalizado,
            'datos' => $datos,
            'errores' => $errores,
        ]);
    }

    private function buscarPersonalizado(mixed $id): Personalizado
    {
        $personalizado = is_string($id) && ctype_digit($id)
            ? $this->personalizadoRepositorio->buscarParaVendedor((int) $id, $this->idVendedor)
            : null;

        if ($personalizado === null) {
            Mensaje::error('No se encontró ese pedido personalizado.');
            $this->redirigir('/pedidos/personalizados');
        }
        return $personalizado;
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