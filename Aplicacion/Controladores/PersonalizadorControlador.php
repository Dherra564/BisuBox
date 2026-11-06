<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosPersonalizador;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Numero;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Repositorios\ComponenteVistaRepositorio;
use Aplicacion\Repositorios\DisenoRepositorio;
use Aplicacion\Repositorios\PersonalizadorRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use Throwable;

class PersonalizadorControlador
{
    private ComponenteVistaRepositorio $vistaRepositorio;
    private PersonalizadorRepositorio $personalizadorRepositorio;
    private Vendedor $vendedor;

    public function __construct()
    {
        Permiso::exigir('personalizador.gestionar');
        $this->vistaRepositorio = new ComponenteVistaRepositorio();
        $this->personalizadorRepositorio = new PersonalizadorRepositorio();

        $vendedor = (new VendedorRepositorio())->buscarPorIdUsuario((int) UsuarioActual::id());
        if ($vendedor === null) {
            error_log('El usuario ' . UsuarioActual::id() . ' tiene rol Vendedor pero no tiene tienda');
            Mensaje::error('No se encontró su tienda.');
            $this->redirigir('/');
        }
        $this->vendedor = $vendedor;
    }

    public function mostrar(): void
    {
        $configuracion = $this->personalizadorRepositorio->buscar($this->idVendedor());
        $this->mostrarPanel([
            'hilo' => $configuracion !== null && $configuracion['idHilo'] !== null ? (string) $configuracion['idHilo'] : '',
            'manoObra' => $configuracion !== null ? Numero::paraCampo($configuracion['manoObra']) : '1500',
            'ganancia' => $configuracion !== null ? (string) (int) $configuracion['ganancia'] : '50',
            'activo' => $configuracion !== null && $configuracion['activo'],
        ], []);
    }

    public function guardarConfiguracion(): void
    {
        $this->verificarCsrf('/personalizador');
        $datos = [
            'hilo' => is_string($_POST['hilo'] ?? null) ? trim($_POST['hilo']) : '',
            'manoObra' => is_string($_POST['manoObra'] ?? null) ? trim($_POST['manoObra']) : '',
            'ganancia' => is_string($_POST['ganancia'] ?? null) ? trim($_POST['ganancia']) : '',
            'activo' => ($_POST['activo'] ?? '') === '1',
        ];

        $errores = [];
        $hilos = $this->personalizadorRepositorio->listarHilos($this->idVendedor());
        if ($datos['hilo'] === '') {
            $errores['hilo'] = DatosPersonalizador::MENSAJE_HILO;
        } elseif (!ctype_digit($datos['hilo']) || !isset($hilos[(int) $datos['hilo']])) {
            $errores['hilo'] = DatosPersonalizador::MENSAJE_HILO;
        }
        $manoObra = DatosPersonalizador::leerManoObra($datos['manoObra']);
        if ($manoObra === null) {
            $errores['manoObra'] = DatosPersonalizador::MENSAJE_MANO_OBRA;
        }
        $ganancia = DatosPersonalizador::leerGanancia($datos['ganancia']);
        if ($ganancia === null) {
            $errores['ganancia'] = DatosPersonalizador::MENSAJE_GANANCIA;
        }
        if ($datos['activo'] && $this->vistaRepositorio->listarParaTienda($this->idVendedor()) === []) {
            $errores['activo'] = DatosPersonalizador::MENSAJE_SIN_PIEZAS;
        }

        if ($errores !== []) {
            $this->mostrarPanel($datos, $errores);
            return;
        }

        try {
            $this->personalizadorRepositorio->guardar($this->idVendedor(), (int) $datos['hilo'], $manoObra, $ganancia, $datos['activo']);
        } catch (Throwable $error) {
            error_log('Error al guardar el personalizador: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarPanel($datos, []);
            return;
        }

        Mensaje::exito($datos['activo'] ? 'Listo: sus clientes ya pueden armar piezas en su tienda' : 'Configuración guardada');
        $this->redirigir('/personalizador');
    }

    public function fotos(): void
    {
        $this->mostrarHerramienta([], []);
    }

    public function guardarFotos(): void
    {
        $this->verificarCsrf('/personalizador/fotos');
        $enviadas = is_array($_POST['piezas'] ?? null) ? array_slice($_POST['piezas'], 0, DatosPersonalizador::PIEZAS_POR_FOTO, true) : [];
        $componentes = $this->vistaRepositorio->idsComponentesDeUnidad($this->idVendedor());

        $piezas = [];
        $errores = [];
        $elegidos = [];
        foreach ($enviadas as $indice => $enviada) {
            if (!is_array($enviada) || !is_int($indice)) {
                continue;
            }
            $pieza = [
                'usar' => ($enviada['usar'] ?? '') === '1',
                'componente' => is_string($enviada['componente'] ?? null) ? trim($enviada['componente']) : '',
                'tamano' => is_string($enviada['tamano'] ?? null) ? trim($enviada['tamano']) : '',
                'imagen' => is_string($enviada['imagen'] ?? null) ? $enviada['imagen'] : '',
                'avisos' => is_string($enviada['avisos'] ?? null) ? mb_substr($enviada['avisos'], 0, 300) : '',
            ];
            $piezas[$indice] = $pieza;
            if (!$pieza['usar']) {
                continue;
            }

            if ($pieza['componente'] === '') {
                $errores["piezas{$indice}componente"] = DatosPersonalizador::MENSAJE_COMPONENTE;
            } elseif (!ctype_digit($pieza['componente']) || !isset($componentes[(int) $pieza['componente']])) {
                $errores["piezas{$indice}componente"] = DatosPersonalizador::MENSAJE_COMPONENTE_NO_EXISTE;
            } elseif (isset($elegidos[(int) $pieza['componente']])) {
                $errores["piezas{$indice}componente"] = DatosPersonalizador::MENSAJE_COMPONENTE_REPETIDO;
            } else {
                $elegidos[(int) $pieza['componente']] = $indice;
            }

            if (DatosPersonalizador::leerTamano($pieza['tamano']) === null) {
                $errores["piezas{$indice}tamano"] = DatosPersonalizador::MENSAJE_TAMANO;
            }

            $png = SubidaArchivo::revisarPiezaRecortada($pieza['imagen']);
            if ($png === null) {
                $errores["piezas{$indice}imagen"] = DatosPersonalizador::MENSAJE_IMAGEN;
            } else {
                $piezas[$indice]['png'] = $png;
            }
        }

        $conError = [];
        foreach (array_keys($errores) as $clave) {
            if (preg_match('/^piezas(\d+)/', $clave, $coincidencia)) {
                $conError[(int) $coincidencia[1]] = true;
            }
        }
        $listas = array_filter($elegidos, fn(int $indice): bool => !isset($conError[$indice]));
        if ($listas === [] && $errores === []) {
            $errores['piezas'] = DatosPersonalizador::MENSAJE_NINGUNA;
        }

        $subida = new SubidaArchivo();
        $guardadas = 0;
        foreach ($listas as $idComponente => $indice) {
            $pieza = $piezas[$indice];
            $foto = $subida->guardarPiezaRecortada($pieza['png']);
            if ($foto === null) {
                $errores["piezas{$indice}imagen"] = $subida->obtenerError();
                continue;
            }
            try {
                $anterior = $this->vistaRepositorio->guardar(
                    $this->idVendedor(),
                    $idComponente,
                    $foto,
                    (float) DatosPersonalizador::leerTamano($pieza['tamano'])
                );
                $this->eliminarSiNoSeUsa($anterior);
                unset($piezas[$indice]);
                $guardadas++;
            } catch (Throwable $error) {
                error_log('Error al guardar la foto de la pieza: ' . $error->getMessage());
                SubidaArchivo::eliminarFotoPieza($foto);
                $errores["piezas{$indice}imagen"] = 'No se pudo guardar esta pieza. Intente de nuevo.';
            }
        }

        $texto = $guardadas === 1 ? 'Se guardó 1 pieza para el personalizador' : "Se guardaron {$guardadas} piezas para el personalizador";
        if ($errores !== []) {
            if ($guardadas > 0) {
                Mensaje::exito($texto . '. Abajo quedan las que faltan: corríjalas o desmárquelas.');
            }
            $this->mostrarHerramienta($piezas, $errores);
            return;
        }

        Mensaje::exito($texto);
        $this->redirigir('/personalizador');
    }

    public function cambiarTamano(): void
    {
        $this->verificarCsrf('/personalizador');
        $idComponente = $this->leerId($_POST['componente'] ?? null);
        $tamano = DatosPersonalizador::leerTamano($_POST['tamano'] ?? null);
        if ($tamano === null) {
            Mensaje::error(DatosPersonalizador::MENSAJE_TAMANO);
            $this->redirigir('/personalizador');
        }

        try {
            $cambiado = $idComponente !== null && $this->vistaRepositorio->cambiarTamano($this->idVendedor(), $idComponente, $tamano);
        } catch (Throwable $error) {
            error_log('Error al cambiar el tamaño de la pieza: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/personalizador');
        }

        if (!$cambiado) {
            Mensaje::error('Esa pieza no tiene foto todavía.');
        } else {
            Mensaje::exito('Tamaño guardado');
        }
        $this->redirigir('/personalizador');
    }

    public function quitar(): void
    {
        $this->verificarCsrf('/personalizador');
        $idComponente = $this->leerId($_POST['componente'] ?? null);
        if ($idComponente === null) {
            Mensaje::error('No se encontró esa pieza.');
            $this->redirigir('/personalizador');
        }

        try {
            $foto = $this->vistaRepositorio->quitar($this->idVendedor(), $idComponente);
            $this->eliminarSiNoSeUsa($foto);
            $configuracion = $this->personalizadorRepositorio->buscar($this->idVendedor());
            if ($configuracion !== null && $configuracion['activo'] && $this->vistaRepositorio->listarParaTienda($this->idVendedor()) === []) {
                $this->personalizadorRepositorio->guardar(
                    $this->idVendedor(),
                    (int) $configuracion['idHilo'],
                    $configuracion['manoObra'],
                    $configuracion['ganancia'],
                    false
                );
                Mensaje::advertencia('Ya no quedan piezas con foto, así que el personalizador se ocultó de su tienda.');
            }
        } catch (Throwable $error) {
            error_log('Error al quitar la foto de la pieza: ' . $error->getMessage());
            Mensaje::error('No se pudo quitar. Intente de nuevo.');
            $this->redirigir('/personalizador');
        }

        Mensaje::exito('Foto quitada del personalizador');
        $this->redirigir('/personalizador');
    }

    private function mostrarPanel(array $datos, array $errores): void
    {
        $piezas = $this->vistaRepositorio->listarParaVendedor($this->idVendedor());
        $this->mostrarVista('Personalizador/panel', [
            'datos' => $datos,
            'errores' => $errores,
            'hilos' => $this->personalizadorRepositorio->listarHilos($this->idVendedor()),
            'piezas' => $piezas,
            'conFoto' => count(array_filter($piezas, fn(array $pieza): bool => $pieza['foto'] !== null)),
            'tienda' => $this->vendedor,
        ]);
    }

    private function mostrarHerramienta(array $piezas, array $errores): void
    {
        $componentes = array_map(fn(array $pieza): array => [
            'id' => $pieza['idComponente'],
            'nombre' => $pieza['nombre'],
            'tipo' => $pieza['tipo'],
            'tamano' => $pieza['tamano'] ?? $pieza['tamanoSugerido'],
            'tieneFoto' => $pieza['foto'] !== null,
        ], $this->vistaRepositorio->listarParaVendedor($this->idVendedor()));

        $paraVista = [];
        foreach ($piezas as $indice => $pieza) {
            $paraVista[] = [
                'indice' => $indice,
                'usar' => $pieza['usar'],
                'componente' => $pieza['componente'],
                'tamano' => $pieza['tamano'],
                'imagen' => SubidaArchivo::revisarPiezaRecortada($pieza['imagen']) !== null ? $pieza['imagen'] : '',
                'avisos' => $pieza['avisos'],
            ];
        }

        $this->mostrarVista('Personalizador/fotos', [
            'componentes' => $componentes,
            'piezasEnviadas' => $paraVista,
            'errores' => $errores,
        ]);
    }

    private function eliminarSiNoSeUsa(?string $foto): void
    {
        if ($foto !== null && !(new DisenoRepositorio())->fotoEnUso($foto)) {
            SubidaArchivo::eliminarFotoPieza($foto);
        }
    }

    private function leerId(mixed $id): ?int
    {
        return is_string($id) && ctype_digit($id) && strlen($id) <= 10 ? (int) $id : null;
    }

    private function idVendedor(): int
    {
        return (int) $this->vendedor->getIdVendedor();
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