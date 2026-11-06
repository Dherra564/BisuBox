<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Acceso;
use Aplicacion\Nucleo\CalculoDiseno;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\ContextoPersonalizador;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosPersonalizador;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\DisenoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class PersonalizadorTiendaControlador
{
    private const CLAVE_AGREGADO = 'personalizadorAgregado';

    public function mostrar(string $enlace): void
    {
        $vendedor = (new VendedorRepositorio())->buscarPorEnlace($enlace);
        $esDuenio = $vendedor !== null && Carrito::esPropia($vendedor);
        if ($vendedor === null || (!$esDuenio && (!$vendedor->getTiendaActiva() || !$vendedor->getEstado()))) {
            ErrorControlador::noEncontrado();
        }
        $idVendedor = (int) $vendedor->getIdVendedor();
        $contexto = ContextoPersonalizador::cargar($idVendedor, $esDuenio);
        if ($contexto === null) {
            if ($esDuenio) {
                Mensaje::advertencia('Para ver el personalizador necesita elegir el hilo y tener al menos una pieza con foto.');
                $this->redirigir('/personalizador');
            }
            ErrorControlador::noEncontrado();
        }

        if (!$esDuenio) {
            Carrito::elegirTienda($idVendedor, (string) $vendedor->getTiendaEnlace());
        }
        $uso = ['componentes' => [], 'hilo' => 0.0];
        $idCliente = $esDuenio ? null : ContextoPersonalizador::idClienteActual();
        if ($idCliente !== null) {
            $uso = ContextoPersonalizador::usoEnCarrito(
                (new DisenoRepositorio())->listarEnCarrito($idCliente, $idVendedor),
                $contexto['hilo']['idComponente']
            );
        }

        $configuracion = $contexto['configuracion'];
        $factor = 1 + $configuracion['ganancia'] / 100;
        $urlBase = Configuracion::urlBase();
        $piezas = [];
        foreach ($contexto['piezas'] as $pieza) {
            $piezas[] = [
                'id' => $pieza['idComponente'],
                'nombre' => $pieza['nombre'],
                'foto' => $urlBase . '/fotos/pieza?archivo=' . rawurlencode($pieza['foto']),
                'tamano' => DatosPersonalizador::decimas($pieza['tamano']),
                'precio' => round($pieza['costo'] * $factor, 6),
                'existencia' => $pieza['existencia'],
                'enCarrito' => (int) ($uso['componentes'][$pieza['idComponente']] ?? 0),
            ];
        }

        $tipos = [];
        foreach (DatosPersonalizador::TIPOS_PIEZA as $clave => $tipo) {
            $tipos[$clave] = $tipo + [
                'mensajeLargo' => CalculoDiseno::mensajeLargo($clave),
                'nombreMinuscula' => mb_strtolower($tipo['nombre'], 'UTF-8'),
            ];
        }

        $datosPagina = [
            'idTienda' => $idVendedor,
            'piezas' => $piezas,
            'tipos' => $tipos,
            'paso' => DatosPersonalizador::PASO_LARGO,
            'proporcionCerrar' => DatosPersonalizador::PROPORCION_PARA_CERRAR,
            'redondeo' => DatosPersonalizador::REDONDEO_PRECIO,
            'manoObra' => round($configuracion['manoObra'] * $factor, 6),
            'hiloPorMm' => round($contexto['hilo']['costo'] / 1000 * $factor, 6),
            'hiloDisponibleMm' => max(0, ($contexto['hilo']['existencia'] - $uso['hilo']) * 1000),
            'mensajes' => [
                'vacio' => CalculoDiseno::MENSAJE_VACIO,
                'espacio' => CalculoDiseno::MENSAJE_ESPACIO,
                'hilo' => CalculoDiseno::MENSAJE_HILO,
            ],
            'esDuenio' => $esDuenio,
            'haySesion' => UsuarioActual::haySesion(),
            'disenoAgregado' => $this->tomarAgregado($idVendedor),
        ];

        $this->mostrarVista('Tienda/personalizador', [
            'tienda' => $vendedor,
            'tiendaVista' => $vendedor,
            'datosPagina' => $datosPagina,
            'esDuenio' => $esDuenio,
            'nombreHilo' => $contexto['hilo']['nombre'],
        ]);
    }

    public function agregar(): void
    {
        $idVendedor = is_string($_POST['tienda'] ?? null) && ctype_digit($_POST['tienda']) ? (int) $_POST['tienda'] : 0;
        $vendedor = $idVendedor > 0 ? (new VendedorRepositorio())->buscarPorId($idVendedor) : null;
        if ($vendedor === null || !$vendedor->getTiendaActiva() || !$vendedor->getEstado()) {
            Mensaje::error('Esa tienda ya no está disponible.');
            $this->redirigir('/');
        }
        $volver = '/tienda/' . $vendedor->getTiendaEnlace() . '/arma-tu-pieza?recuperar=1';
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir($volver);
        }
        if (!UsuarioActual::haySesion()) {
            Acceso::guardarDestino($volver);
            Mensaje::advertencia('Para agregar su pieza al carrito, ingrese o cree su cuenta. Su diseño se guardó.');
            $this->redirigir('/ingresar');
        }
        Permiso::exigir('carrito.usar');
        if (Carrito::esPropia($vendedor)) {
            Mensaje::error('No puede comprar en su propia tienda.');
            $this->redirigir($volver);
        }

        $contexto = ContextoPersonalizador::cargar($idVendedor);
        if ($contexto === null) {
            Mensaje::error('Esta tienda ya no ofrece piezas personalizadas.');
            $this->redirigir('/tienda/' . $vendedor->getTiendaEnlace());
        }

        $idCliente = (new ClienteRepositorio())->asegurar((int) UsuarioActual::id());
        $disenoRepositorio = new DisenoRepositorio();
        $uso = ContextoPersonalizador::usoEnCarrito(
            $disenoRepositorio->listarEnCarrito($idCliente, $idVendedor),
            $contexto['hilo']['idComponente']
        );
        $resultado = CalculoDiseno::revisar(
            CalculoDiseno::leerEntrada($_POST),
            $contexto['piezas'],
            $contexto['configuracion'],
            $contexto['hilo'],
            $uso
        );
        if ($resultado['errores'] !== []) {
            Mensaje::error(implode(' ', array_unique($resultado['errores'])));
            $this->redirigir($volver);
        }

        try {
            $disenoRepositorio->crear($idCliente, $idVendedor, $resultado['diseno']);
        } catch (Throwable $error) {
            error_log('Error al guardar el diseño: ' . $error->getMessage());
            Mensaje::error('No se pudo agregar al carrito. Intente de nuevo.');
            $this->redirigir($volver);
        }

        Carrito::elegirTienda($idVendedor, (string) $vendedor->getTiendaEnlace());
        $_SESSION[self::CLAVE_AGREGADO][$idVendedor] = true;
        Mensaje::exito('Su ' . mb_strtolower(CalculoDiseno::nombreDiseno($resultado['diseno']['tipo']), 'UTF-8') . ' se agregó al carrito.');
        $this->redirigir('/carrito');
    }

    public function quitar(): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/carrito');
        }
        Permiso::exigir('carrito.usar');
        $idDiseno = is_string($_POST['diseno'] ?? null) && ctype_digit($_POST['diseno']) ? (int) $_POST['diseno'] : 0;
        $idCliente = ContextoPersonalizador::idClienteActual();

        try {
            $quitado = $idCliente !== null && $idDiseno > 0 && (new DisenoRepositorio())->quitar($idCliente, $idDiseno);
        } catch (Throwable $error) {
            error_log('Error al quitar el diseño: ' . $error->getMessage());
            $quitado = false;
        }
        if ($quitado) {
            Mensaje::exito('Pieza personalizada quitada del carrito.');
        } else {
            Mensaje::error('No se encontró esa pieza en su carrito.');
        }
        $this->redirigir('/carrito');
    }

    private function tomarAgregado(int $idVendedor): bool
    {
        $agregado = !empty($_SESSION[self::CLAVE_AGREGADO][$idVendedor]);
        unset($_SESSION[self::CLAVE_AGREGADO][$idVendedor]);
        return $agregado;
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
