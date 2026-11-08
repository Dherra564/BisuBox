<?php

namespace Aplicacion\Controladores;

use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosCotizacion;
use Aplicacion\Nucleo\EstadoCotizacion;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\OperacionNoPermitida;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ComponenteRepositorio;
use Aplicacion\Repositorios\CotizacionRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class CotizacionControlador
{
    private CotizacionRepositorio $repositorio;
    private int $idVendedor;

    public function __construct()
    {
        Permiso::exigir('cotizaciones.gestionar');
        $this->repositorio = new CotizacionRepositorio();

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
        $estado = is_string($_GET['estado'] ?? null) && EstadoCotizacion::existe($_GET['estado']) ? $_GET['estado'] : '';
        $base = ['idVendedor' => $this->idVendedor];

        $conteos = $this->repositorio->conteosPorEstado($base);
        $total = $conteos[$estado];
        $totalPaginas = max(1, (int) ceil($total / CotizacionRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Cotizacion/lista', [
            'cotizaciones' => $this->repositorio->listar($base + ['estado' => $estado], $pagina),
            'estado' => $estado,
            'conteos' => $conteos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => CotizacionRepositorio::POR_PAGINA,
        ]);
    }

    public function detalle(): void
    {
        $cotizacion = $this->buscarCotizacion($_GET['id'] ?? null);
        $datos = $cotizacion['precio'] !== null
            ? DatosCotizacion::respuestaParaEditar($cotizacion)
            : DatosCotizacion::respuestaVacia();

        $this->mostrarDetalle($cotizacion, $datos, []);
    }

    public function responder(): void
    {
        $this->verificarCsrf('/cotizaciones');
        $cotizacion = $this->buscarCotizacion($_POST['id'] ?? null);
        $volver = '/cotizaciones/detalle?id=' . $cotizacion['id'];
        $yaRespondida = $cotizacion['estado'] === EstadoCotizacion::RESPONDIDA;

        if (!$yaRespondida && $cotizacion['estado'] !== EstadoCotizacion::ENVIADA) {
            Mensaje::error(DatosCotizacion::MENSAJE_NO_RESPONDER);
            $this->redirigir($volver);
        }

        $componentes = $this->componentesActivos();
        $datos = DatosCotizacion::leerRespuesta();
        $validador = new Validador();
        DatosCotizacion::validarRespuesta($validador, $datos, $componentes);
        if (!$validador->esValido()) {
            $this->mostrarDetalle($cotizacion, $datos, $validador->errores());
            return;
        }

        $respuesta = DatosCotizacion::armarRespuesta($datos, $componentes);
        try {
            $this->repositorio->responder($cotizacion['id'], $this->idVendedor, $respuesta, $datos['respuesta']);
        } catch (OperacionNoPermitida $error) {
            Mensaje::error($error->getMessage());
            $this->redirigir($volver);
        } catch (Throwable $error) {
            error_log('Error al responder la cotización: ' . $error->getMessage());
            Mensaje::error('No se pudo enviar la respuesta. Intente de nuevo.');
            $this->mostrarDetalle($cotizacion, $datos, []);
            return;
        }

        Mensaje::exito($yaRespondida
            ? 'Respuesta ajustada. El cliente verá el precio nuevo.'
            : 'Respuesta enviada a ' . $cotizacion['nombreCliente'] . '. Cuando la acepte, recibirá el pedido.');
        $this->redirigir($volver);
    }

    private function componentesActivos(): array
    {
        $porId = [];
        foreach ((new ComponenteRepositorio())->listarActivos($this->idVendedor) as $componente) {
            $porId[(int) $componente->getIdComponente()] = $componente;
        }
        return $porId;
    }

    private function buscarCotizacion(mixed $id): array
    {
        $cotizacion = is_string($id) && ctype_digit($id) ? $this->repositorio->buscarParaVendedor((int) $id, $this->idVendedor) : null;
        if ($cotizacion === null) {
            ErrorControlador::noEncontrado();
        }
        return $cotizacion;
    }

    private function mostrarDetalle(array $cotizacion, array $datos, array $errores): void
    {
        $this->mostrarVista('Cotizacion/detalle', [
            'cotizacion' => $cotizacion,
            'componentes' => $this->componentesActivos(),
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