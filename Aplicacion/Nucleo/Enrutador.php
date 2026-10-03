<?php
namespace Aplicacion\Nucleo;

use Aplicacion\Controladores\ErrorControlador;
use Closure;
use RuntimeException;

class Enrutador
{
    // Estructura: ['GET' => ['/vendedores' => accion], 'POST' => [...]]
    private array $rutas = ['GET' => [], 'POST' => []];

    public function get(string $ruta, array | Closure $accion): void
    {
        $this->rutas['GET'][$this->normalizar($ruta)] = $accion;
    }

    public function post(string $ruta, array | Closure $accion): void
    {
        $this->rutas['POST'][$this->normalizar($ruta)] = $accion;
    }

    // Busca la ruta de la peticion actual y ejecuta su accion
    public function despachar(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ruta   = $this->rutaActual();

        if (isset($this->rutas[$metodo][$ruta])) {
            $this->ejecutar($this->rutas[$metodo][$ruta]);
            return;
        }

        // La ruta existe pero con otro metodo (por ejemplo, abrir con GET algo que es POST)
        $otroMetodo = $metodo === 'GET' ? 'POST' : 'GET';
        if (isset($this->rutas[$otroMetodo][$ruta])) {
            ErrorControlador::metodoNoPermitido();
        }

        $this->rutaNoEncontrada();
    }

    // Devuelve la ruta sin la carpeta del proyecto: /BisuBox/Publico/vendedores -> /vendedores
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

    private function ejecutar(array | Closure $accion): void
    {
        if ($accion instanceof Closure) {
            $accion();
            return;
        }

        [$clase, $metodo] = $accion;
        if (! class_exists($clase) || ! method_exists($clase, $metodo)) {
            throw new RuntimeException("La ruta apunta a {$clase}::{$metodo}, que no existe.");
        }

        (new $clase())->$metodo();
    }

    private function rutaNoEncontrada(): void
    {
        ErrorControlador::noEncontrado();
    }
}
