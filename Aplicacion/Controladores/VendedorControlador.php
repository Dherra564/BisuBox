<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\TipoIdentificacion;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;

class VendedorControlador
{
    private VendedorRepositorio $vendedorRepositorio;
    private UsuarioRepositorio $usuarioRepositorio;

    public function __construct()
    {
        Permiso::exigir('vendedores.gestionar');
        $this->vendedorRepositorio = new VendedorRepositorio();
        $this->usuarioRepositorio = new UsuarioRepositorio();
    }

    public function listar(): void
    {
        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';
        $estado = in_array($_GET['estado'] ?? '', ['1', '0'], true) ? $_GET['estado'] : '';
        $activo = $estado === '' ? null : $estado === '1';

        $total = $this->vendedorRepositorio->contar($busqueda, $activo);
        $totalPaginas = max(1, (int) ceil($total / VendedorRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Vendedor/lista', [
            'vendedores' => $this->vendedorRepositorio->listar($busqueda, $activo, $pagina),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => VendedorRepositorio::POR_PAGINA,
        ]);
    }

    public function detalle(): void
    {
        $this->mostrarVista('Vendedor/detalle', [
            'vendedor' => $this->cargarVendedor($_GET['id'] ?? null),
        ]);
    }

    public function nuevo(): void
    {
        $this->mostrarFormulario(null, [
            'tipoIdentificacion' => TipoIdentificacion::CEDULA,
            'numeroIdentificacion' => '',
            'nombreCompleto' => '',
            'correoUsuario' => '',
            'numeroTelefonico' => '',
        ], []);
    }

    public function crear(): void
    {
        $this->verificarCsrf('/vendedores/nuevo');

        $datos = $this->leerDatos();
        $contrasena = $this->leerContrasena('contrasena');
        $confirmar = $this->leerContrasena('confirmarContrasena');

        $validador = $this->validarDatos($datos, null);
        $validador->requerido('contrasena', $contrasena, 'Ingrese la contraseña')
            ->contrasenaSegura('contrasena', $contrasena);
        $validador->requerido('confirmarContrasena', $confirmar, 'Confirme la contraseña')
            ->coinciden('confirmarContrasena', $confirmar, $contrasena, 'Las contraseñas no coinciden');

        $nombreFoto = $this->subirFoto($validador);
        if (!$validador->esValido()) {
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            $this->mostrarFormulario(null, $datos, $validador->errores());
            return;
        }

        $ahora = new DateTime();
        $vendedor = new Vendedor(
            tipoIdentificacion: $datos['tipoIdentificacion'],
            numeroIdentificacion: $datos['numeroIdentificacion'],
            nombreCompleto: UsuarioRepositorio::limpiarEspacios($datos['nombreCompleto']),
            fotoPerfil: $nombreFoto,
            correoUsuario: $datos['correoUsuario'],
            contrasena: $contrasena,
            fechaRegistro: $ahora,
            numeroTelefonico: Validador::limpiarTelefono($datos['numeroTelefonico']),
            registroFechaVendedor: clone $ahora
        );

        try {
            $this->vendedorRepositorio->insertar($vendedor);
        } catch (Throwable $error) {
            error_log('Error al registrar vendedor: ' . $error->getMessage());
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario(null, $datos, []);
            return;
        }

        Mensaje::exito('Vendedor registrado correctamente');
        $this->redirigir('/vendedores');
    }

    public function editar(): void
    {
        $vendedor = $this->cargarVendedor($_GET['id'] ?? null);

        $this->mostrarFormulario($vendedor, [
            'tipoIdentificacion' => $vendedor->getTipoIdentificacion(),
            'numeroIdentificacion' => $vendedor->getNumeroIdentificacion(),
            'nombreCompleto' => $vendedor->getNombreCompleto(),
            'correoUsuario' => $vendedor->getCorreoUsuario(),
            'numeroTelefonico' => $vendedor->getNumeroTelefonico(),
        ], []);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/vendedores');
        $vendedor = $this->cargarVendedor($_POST['id'] ?? null);

        $datos = $this->leerDatos();
        $contrasena = $this->leerContrasena('contrasena');
        $confirmar = $this->leerContrasena('confirmarContrasena');

        $validador = $this->validarDatos($datos, $vendedor->getIdUsuario());

        $restablecer = $contrasena !== '' || $confirmar !== '';
        if ($restablecer) {
            $validador->requerido('contrasena', $contrasena, 'Ingrese la contraseña nueva')
                ->contrasenaSegura('contrasena', $contrasena);
            $validador->requerido('confirmarContrasena', $confirmar, 'Confirme la contraseña')
                ->coinciden('confirmarContrasena', $confirmar, $contrasena, 'Las contraseñas no coinciden');
        }

        $nombreFoto = $this->subirFoto($validador);
        if (!$validador->esValido()) {
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            $this->mostrarFormulario($vendedor, $datos, $validador->errores());
            return;
        }

        $fotoAnterior = $vendedor->getFotoPerfil();
        $vendedor->setTipoIdentificacion($datos['tipoIdentificacion']);
        $vendedor->setNumeroIdentificacion($datos['numeroIdentificacion']);
        $vendedor->setNombreCompleto(UsuarioRepositorio::limpiarEspacios($datos['nombreCompleto']));
        $vendedor->setCorreoUsuario($datos['correoUsuario']);
        $vendedor->setNumeroTelefonico(Validador::limpiarTelefono($datos['numeroTelefonico']));
        if ($nombreFoto !== null) {
            $vendedor->setFotoPerfil($nombreFoto);
        }

        try {
            $this->vendedorRepositorio->actualizar($vendedor, $restablecer ? $contrasena : null);
        } catch (Throwable $error) {
            error_log('Error al actualizar vendedor: ' . $error->getMessage());
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario($vendedor, $datos, []);
            return;
        }

        if ($nombreFoto !== null && $fotoAnterior !== null) {
            SubidaArchivo::eliminarFotoPerfil($fotoAnterior);
        }

        Mensaje::exito('Cambios guardados correctamente');
        $this->redirigir('/vendedores/detalle?id=' . $vendedor->getIdVendedor());
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/vendedores');
        $vendedor = $this->cargarVendedor($_POST['id'] ?? null);

        $estado = $_POST['activo'] ?? null;
        if (!in_array($estado, ['1', '0'], true)) {
            Mensaje::error('Estado no válido');
            $this->redirigir('/vendedores');
        }
        $activo = $estado === '1';

        try {
            $this->vendedorRepositorio->cambiarEstado($vendedor, $activo);
        } catch (Throwable $error) {
            error_log('Error al cambiar estado de vendedor: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/vendedores');
        }

        Mensaje::exito($activo ? 'El vendedor fue activado' : 'El vendedor fue desactivado');
        $this->redirigir($this->rutaDeRegreso($vendedor));
    }

    private function cargarVendedor(mixed $id): Vendedor
    {
        $idVendedor = is_string($id) && ctype_digit($id) ? (int) $id : 0;
        $vendedor = $idVendedor > 0 ? $this->vendedorRepositorio->buscarPorId($idVendedor) : null;

        if ($vendedor === null) {
            Mensaje::error('El vendedor no existe.');
            $this->redirigir('/vendedores');
        }

        return $vendedor;
    }

    private function leerDatos(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'tipoIdentificacion' => $leer('tipoIdentificacion'),
            'numeroIdentificacion' => TipoIdentificacion::limpiar($leer('numeroIdentificacion')),
            'nombreCompleto' => $leer('nombreCompleto'),
            'correoUsuario' => mb_strtolower($leer('correoUsuario'), 'UTF-8'),
            'numeroTelefonico' => $leer('numeroTelefonico'),
        ];
    }

    private function leerContrasena(string $campo): string
    {
        return is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
    }

    private function validarDatos(array $datos, ?int $idUsuario): Validador
    {
        $validador = new Validador();

        $validador->tipoIdentificacion('tipoIdentificacion', $datos['tipoIdentificacion']);
        $validador->requerido('numeroIdentificacion', $datos['numeroIdentificacion'], 'Ingrese la identificación') ->identificacion('numeroIdentificacion', $datos['tipoIdentificacion'], $datos['numeroIdentificacion']);

        $validador->requerido('nombreCompleto', $datos['nombreCompleto'], 'Ingrese el nombre completo')
            ->longitud('nombreCompleto', $datos['nombreCompleto'], 3, 100, 'El nombre debe tener entre 3 y 100 caracteres')
            ->soloLetras('nombreCompleto', $datos['nombreCompleto'], 'El nombre solo puede tener letras y espacios');

        $validador->requerido('correoUsuario', $datos['correoUsuario'], 'Ingrese el correo')
            ->correo('correoUsuario', $datos['correoUsuario']);

        $validador->requerido('numeroTelefonico', $datos['numeroTelefonico'], 'Ingrese el teléfono')
            ->telefono('numeroTelefonico', $datos['numeroTelefonico']);

        if (
            $validador->error('correoUsuario') === null
            && $this->usuarioRepositorio->existeCorreo($datos['correoUsuario'], $idUsuario)
        ) {
            $validador->agregarError('correoUsuario', 'Este correo ya lo usa otro usuario');
        }
        if (
            $validador->error('numeroIdentificacion') === null
            && $this->usuarioRepositorio->existeIdentificacion($datos['numeroIdentificacion'], $idUsuario)
        ) {
            $validador->agregarError('numeroIdentificacion', 'Ya existe otro usuario con esta identificación');
        }

        return $validador;
    }

    private function subirFoto(Validador $validador): ?string
    {
        if (!SubidaArchivo::seEnvio($_FILES['fotoPerfil'] ?? null)) {
            return null;
        }
        $subida = new SubidaArchivo();
        $nombre = $subida->guardarFotoPerfil($_FILES['fotoPerfil']);
        if ($nombre === null) {
            $validador->agregarError('fotoPerfil', $subida->obtenerError());
        }
        return $nombre;
    }

    private function rutaDeRegreso(Vendedor $vendedor): string
    {
        return ($_POST['volver'] ?? '') === 'detalle'
            ? '/vendedores/detalle?id=' . $vendedor->getIdVendedor()
            : '/vendedores';
    }

    private function mostrarFormulario(?Vendedor $vendedor, array $datos, array $errores): void
    {
        $this->mostrarVista('Vendedor/formulario', [
            'vendedor' => $vendedor,
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
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl'), '/') . $ruta);
        exit;
    }
}