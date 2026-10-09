<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Usuario;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\Rol;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\TipoIdentificacion;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;


class UsuarioControlador
{
    private UsuarioRepositorio $usuarioRepositorio;

    public function __construct()
    {
        Permiso::exigir('usuarios.gestionar');
        $this->usuarioRepositorio = new UsuarioRepositorio();
    }

    public function listar(): void
    {
        $busqueda = is_string($_GET['busqueda'] ?? null) ? mb_substr(trim($_GET['busqueda']), 0, 100, 'UTF-8') : '';
        $rol = Rol::existe($_GET['rol'] ?? null) ? $_GET['rol'] : '';
        $estado = in_array($_GET['estado'] ?? '', ['1', '0'], true) ? $_GET['estado'] : '';
        $activo = $estado === '' ? null : $estado === '1';
        $filtroRol = $rol === '' ? null : $rol;

        $total = $this->usuarioRepositorio->contar($busqueda, $filtroRol, $activo);
        $totalPaginas = max(1, (int) ceil($total / UsuarioRepositorio::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->mostrarVista('Usuario/lista', [
            'usuarios' => $this->usuarioRepositorio->listar($busqueda, $filtroRol, $activo, $pagina),
            'busqueda' => $busqueda,
            'rol' => $rol,
            'estado' => $estado,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'porPagina' => UsuarioRepositorio::POR_PAGINA,
            'idUsuarioActual' => UsuarioActual::id(),
        ]);
    }

    public function detalle(): void
    {
        $this->mostrarVista('Usuario/detalle', [
            'usuario' => $this->cargarUsuario($_GET['id'] ?? null),
            'idUsuarioActual' => UsuarioActual::id(),
        ]);
    }

    public function nuevo(): void
    {
        $this->mostrarFormulario(null, [
            'rol' => Rol::VENDEDOR,
            'tipoIdentificacion' => TipoIdentificacion::CEDULA,
            'numeroIdentificacion' => '',
            'nombreCompleto' => '',
            'correoUsuario' => '',
            'numeroTelefonico' => '',
        ], []);
    }

    public function crear(): void
    {
        $this->verificarCsrf('/usuarios/nuevo');

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

        $usuario = new Usuario(
            tipoIdentificacion: $datos['tipoIdentificacion'],
            numeroIdentificacion: $datos['numeroIdentificacion'],
            nombreCompleto: UsuarioRepositorio::limpiarEspacios($datos['nombreCompleto']),
            fotoPerfil: $nombreFoto,
            correoUsuario: $datos['correoUsuario'],
            numeroTelefonico: Validador::limpiarTelefono($datos['numeroTelefonico']),
            contrasena: $contrasena,
            fechaRegistro: new DateTime(),
            estado: true,
            rol: $datos['rol']
        );

        try {
            $this->usuarioRepositorio->insertar($usuario);
        } catch (Throwable $error) {
            error_log('Error al registrar usuario: ' . $error->getMessage());
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario(null, $datos, []);
            return;
        }

        Mensaje::exito(Rol::nombre($usuario->getRol()) . ' registrado correctamente');
        $this->redirigir('/usuarios');
    }

    public function editar(): void
    {
        $usuario = $this->cargarUsuario($_GET['id'] ?? null);

        $this->mostrarFormulario($usuario, [
            'rol' => $usuario->getRol(),
            'tipoIdentificacion' => $usuario->getTipoIdentificacion(),
            'numeroIdentificacion' => $usuario->getNumeroIdentificacion(),
            'nombreCompleto' => $usuario->getNombreCompleto(),
            'correoUsuario' => $usuario->getCorreoUsuario(),
            'numeroTelefonico' => $usuario->getNumeroTelefonico(),
        ], []);
    }

    public function actualizar(): void
    {
        $this->verificarCsrf('/usuarios');
        $usuario = $this->cargarUsuario($_POST['id'] ?? null);

        $datos = $this->leerDatos();
        // Su propio rol no se puede cambiar: se toma el que ya tiene aunque el formulario mande otro
        if ($this->esUsuarioActual($usuario)) {
            $datos['rol'] = $usuario->getRol();
        }
        $contrasena = $this->leerContrasena('contrasena');
        $confirmar = $this->leerContrasena('confirmarContrasena');

        $validador = $this->validarDatos($datos, $usuario->getIdUsuario());

        $cambiaRol = $validador->error('rol') === null && $datos['rol'] !== $usuario->getRol();
        if ($cambiaRol && $usuario->esAdministrador() && $usuario->getEstado()
            && $this->usuarioRepositorio->contarAdministradoresActivos($usuario->getIdUsuario()) === 0) {
            $validador->agregarError('rol', 'Es el único administrador activo; registre o active otro antes de cambiarle el rol');
        }

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
            $this->mostrarFormulario($usuario, $datos, $validador->errores());
            return;
        }

        $fotoAnterior = $usuario->getFotoPerfil();
        $usuario->setRol($datos['rol']);
        $usuario->setTipoIdentificacion($datos['tipoIdentificacion']);
        $usuario->setNumeroIdentificacion($datos['numeroIdentificacion']);
        $usuario->setNombreCompleto(UsuarioRepositorio::limpiarEspacios($datos['nombreCompleto']));
        $usuario->setCorreoUsuario($datos['correoUsuario']);
        $usuario->setNumeroTelefonico(Validador::limpiarTelefono($datos['numeroTelefonico']));
        if ($nombreFoto !== null) {
            $usuario->setFotoPerfil($nombreFoto);
        }

        // Si cambia el rol o la contrasena, se cierran sus sesiones para que ingrese de nuevo
        $cerrarSesiones = !$this->esUsuarioActual($usuario) && ($cambiaRol || $restablecer);

        try {
            $this->usuarioRepositorio->actualizar($usuario, $restablecer ? $contrasena : null, $cerrarSesiones);
        } catch (Throwable $error) {
            error_log('Error al actualizar usuario: ' . $error->getMessage());
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->mostrarFormulario($usuario, $datos, []);
            return;
        }

        if ($nombreFoto !== null && $fotoAnterior !== null) {
            SubidaArchivo::eliminarFotoPerfil($fotoAnterior);
        }
        if ($this->esUsuarioActual($usuario)) {
            UsuarioActual::actualizarNombre((string) $usuario->getNombreCompleto());
            UsuarioActual::actualizarFoto($usuario->getFotoPerfil());
        }

        Mensaje::exito($cambiaRol
            ? 'Cambios guardados. Ahora ' . $usuario->getNombreCompleto() . ' es ' . Rol::nombre($usuario->getRol())
            : 'Cambios guardados correctamente');
        $this->redirigir('/usuarios/detalle?id=' . $usuario->getIdUsuario());
    }

    public function cambiarEstado(): void
    {
        $this->verificarCsrf('/usuarios');
        $usuario = $this->cargarUsuario($_POST['id'] ?? null);

        $estado = $_POST['activo'] ?? null;
        if (!in_array($estado, ['1', '0'], true)) {
            Mensaje::error('Estado no válido');
            $this->redirigir('/usuarios');
        }
        $activo = $estado === '1';

        if (!$activo && $this->esUsuarioActual($usuario)) {
            Mensaje::error('No puede desactivar su propia cuenta.');
            $this->redirigir($this->rutaDeRegreso($usuario));
        }
        if (!$activo && $usuario->esAdministrador()
            && $this->usuarioRepositorio->contarAdministradoresActivos($usuario->getIdUsuario()) === 0) {
            Mensaje::error('No se puede desactivar al único administrador activo.');
            $this->redirigir($this->rutaDeRegreso($usuario));
        }

        try {
            $this->usuarioRepositorio->cambiarEstado($usuario, $activo);
        } catch (Throwable $error) {
            error_log('Error al cambiar estado de usuario: ' . $error->getMessage());
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/usuarios');
        }

        Mensaje::exito($activo
            ? $usuario->getNombreCompleto() . ' fue activado'
            : $usuario->getNombreCompleto() . ' fue desactivado');
        $this->redirigir($this->rutaDeRegreso($usuario));
    }

    private function cargarUsuario(mixed $id): Usuario
    {
        $idUsuario = is_string($id) && ctype_digit($id) ? (int) $id : 0;
        $usuario = $idUsuario > 0 ? $this->usuarioRepositorio->buscarPorId($idUsuario) : null;

        if ($usuario === null) {
            Mensaje::error('El usuario no existe.');
            $this->redirigir('/usuarios');
        }

        return $usuario;
    }

    private function esUsuarioActual(Usuario $usuario): bool
    {
        return $usuario->getIdUsuario() === UsuarioActual::id();
    }

    private function leerDatos(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'rol' => $leer('rol'),
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

        if (!Rol::existe($datos['rol'])) {
            $validador->agregarError('rol', 'Seleccione el rol');
        }

        $validador->tipoIdentificacion('tipoIdentificacion', $datos['tipoIdentificacion']);
        $validador->requerido('numeroIdentificacion', $datos['numeroIdentificacion'], 'Ingrese la identificación')
            ->identificacion('numeroIdentificacion', $datos['tipoIdentificacion'], $datos['numeroIdentificacion']);

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
        if (
            $validador->error('numeroTelefonico') === null
            && $this->usuarioRepositorio->existeTelefono(Validador::limpiarTelefono($datos['numeroTelefonico']), $idUsuario)
        ) {
            $validador->agregarError('numeroTelefonico', 'Este teléfono ya lo usa otro usuario');
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

    private function rutaDeRegreso(Usuario $usuario): string
    {
        return ($_POST['volver'] ?? '') === 'detalle'
            ? '/usuarios/detalle?id=' . $usuario->getIdUsuario()
            : '/usuarios';
    }

    private function mostrarFormulario(?Usuario $usuario, array $datos, array $errores): void
    {
        $this->mostrarVista('Usuario/formulario', [
            'usuario' => $usuario,
            'datos' => $datos,
            'errores' => $errores,
            'esUsuarioActual' => $usuario !== null && $this->esUsuarioActual($usuario),
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