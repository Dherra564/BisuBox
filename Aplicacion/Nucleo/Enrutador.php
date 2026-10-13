<?php
namespace Aplicacion\Nucleo;

use Aplicacion\Controladores\ErrorControlador;
use Closure;
use RuntimeException;

class Enrutador
{
    private array $rutas = ['GET' => [], 'POST' => []];

    public function get(string $ruta, array | Closure $accion): void
    {
        $this->rutas['GET'][$this->normalizar($ruta)] = $accion;
    }

    public function post(string $ruta, array | Closure $accion): void
    {
        $this->rutas['POST'][$this->normalizar($ruta)] = $accion;
    }

    public function despachar(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ruta   = $this->rutaActual();

        if (isset($this->rutas[$metodo][$ruta])) {
            $this->ejecutar($this->rutas[$metodo][$ruta]);
            return;
        }

        $otroMetodo = $metodo === 'GET' ? 'POST' : 'GET';
        if (isset($this->rutas[$otroMetodo][$ruta])) {
            ErrorControlador::metodoNoPermitido();
        }

        $coincidencia = $this->buscarConParametros($metodo, $ruta);
        if ($coincidencia !== null) {
            $this->ejecutar($coincidencia[0], $coincidencia[1]);
            return;
        }

        $this->rutaNoEncontrada();
    }

    private function rutaActual(): string
    {
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $carpetaBase     = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $carpetaProyecto = rtrim(str_replace('\\', '/', dirname($carpetaBase)), '/.');

        foreach ([$carpetaBase, $carpetaProyecto] as $prefijo) {
            if ($prefijo === '') {
                continue;
            }
            if (strcasecmp($ruta, $prefijo) === 0) {
                return '/';
            }
            if (stripos($ruta, $prefijo . '/') === 0) {
                $ruta = substr($ruta, strlen($prefijo));
                break;
            }
        }

        return $this->normalizar($ruta);
    }

    private function normalizar(string $ruta): string
    {
        $ruta = '/' . trim($ruta, '/');
        return $ruta === '' ? '/' : $ruta;
    }

    private function ejecutar(array | Closure $accion, array $parametros = []): void
    {
        if ($accion instanceof Closure) {
            $accion(...$parametros);
            return;
        }

        [$clase, $metodo] = $accion;
        if (! class_exists($clase) || ! method_exists($clase, $metodo)) {
            throw new RuntimeException("La ruta apunta a {$clase}::{$metodo}, que no existe.");
        }

        (new $clase())->$metodo(...$parametros);
    }

    private function rutaNoEncontrada(): void
    {
        ErrorControlador::noEncontrado();
    }

    private function buscarConParametros(string $metodo, string $ruta): ?array
    {
        foreach ($this->rutas[$metodo] ?? [] as $patron => $accion) {
            if (! str_contains($patron, '{enlace}')) {
                continue;
            }
            $expresion = '#^' . str_replace('\{enlace\}', '(?P<enlace>[A-Za-z0-9-]+)', preg_quote($patron, '#')) . '$#';
            if (preg_match($expresion, $ruta, $coincidencias) === 1) {
                return [$accion, [$coincidencias['enlace']]];
            }
        }

        return null;
    }

}
