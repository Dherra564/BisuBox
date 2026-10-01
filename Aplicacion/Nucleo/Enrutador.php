<?php

namespace Aplicacion\Nucleo;

use Closure;
use RuntimeException;

class Enrutador
{
    // Estructura: ['GET' => ['/vendedores' => accion], 'POST' => [...]]
    private array $rutas = ['GET' => [], 'POST' => []];

    public function get(string $ruta, array|Closure $accion): void
    {
        $this->rutas['GET'][$this->normalizar($ruta)] = $accion;
    }

    public function post(string $ruta, array|Closure $accion): void
    {
        $this->rutas['POST'][$this->normalizar($ruta)] = $accion;
    }

    // Busca la ruta de la peticion actual y ejecuta su accion
    public function despachar(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ruta = $this->rutaActual();

        if (isset($this->rutas[$metodo][$ruta])) {
            $this->ejecutar($this->rutas[$metodo][$ruta]);
            return;
        }

        // La ruta existe pero con otro metodo (por ejemplo, abrir con GET algo que es POST)
        $otroMetodo = $metodo === 'GET' ? 'POST' : 'GET';
        if (isset($this->rutas[$otroMetodo][$ruta])) {
            http_response_code(405);
            echo 'Acción no permitida.';
            return;
        }

        $this->rutaNoEncontrada();
    }

    
    private function rutaActual(): string
    {
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        
        $carpetaBase = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $carpetaBase = rtrim($carpetaBase, '/');

        if ($carpetaBase !== '' && str_starts_with($ruta, $carpetaBase)) {
            $ruta = substr($ruta, strlen($carpetaBase));
        }

        
        $carpetaProyecto = rtrim(dirname($carpetaBase), '/');
        if ($carpetaProyecto !== '' && str_starts_with($ruta, $carpetaProyecto . '/')) {
            $ruta = substr($ruta, strlen($carpetaProyecto));
        }

        return $this->normalizar($ruta);
    }

    
    private function normalizar(string $ruta): string
    {
        $ruta = '/' . trim($ruta, '/');
        return $ruta === '' ? '/' : $ruta;
    }

    private function ejecutar(array|Closure $accion): void
    {
        if ($accion instanceof Closure) {
            $accion();
            return;
        }

        [$clase, $metodo] = $accion;
        if (!class_exists($clase) || !method_exists($clase, $metodo)) {
            throw new RuntimeException("La ruta apunta a {$clase}::{$metodo}, que no existe.");
        }

        (new $clase())->$metodo();
    }

    private function rutaNoEncontrada(): void
    {
        http_response_code(404);
        // Damian reemplaza esto por la vista de error 404 cuando la tenga
        echo 'Página no encontrada.';
    }
}