<?php
namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Usuario;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\ManejadorSesion;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\Permiso;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\SesionRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;
use Throwable;

class PerfilControlador
{
    private UsuarioRepositorio $usuarioRepositorio;
    private Usuario $usuario;

    public function __construct()
    {
        $this->usuarioRepositorio = new UsuarioRepositorio();
        $this->usuario            = $this->cargarUsuarioActual();
    }

    // GET /perfil
    public function mostrar(): void
    {
        $this->mostrarPerfil([
            'numeroIdentificacion' => $this->usuario->getNumeroIdentificacion(),
            'nombreCompleto'       => $this->usuario->getNombreCompleto(),
            'correoUsuario'        => $this->usuario->getCorreoUsuario(),
        ], []);
    }

    // POST /perfil/actualizar
    public function actualizar(): void
    {
        $this->verificarCsrf('/perfil');

        $leer              = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
        $puedeEditarAcceso = UsuarioActual::esSuperAdmin();

        // El Vendedor solo cambia nombre y foto; su identificacion y correo los cambia el SuperAdmin
        $datos = [
            'numeroIdentificacion' => $puedeEditarAcceso
                ? strtoupper($leer('numeroIdentificacion'))
                : $this->usuario->getNumeroIdentificacion(),
            'nombreCompleto'       => $leer('nombreCompleto'),
            'correoUsuario'        => $puedeEditarAcceso
                ? mb_strtolower($leer('correoUsuario'), 'UTF-8')
                : $this->usuario->getCorreoUsuario(),
        ];

        $validador  = $this->validarDatos($datos, $puedeEditarAcceso);
        $nombreFoto = $this->subirFoto($validador);
        if (! $validador->esValido()) {
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            $this->mostrarPerfil($datos, $validador->errores());
            return;
        }

        $fotoAnterior = $this->usuario->getFotoPerfil();
        $this->usuario->setNumeroIdentificacion($datos['numeroIdentificacion']);
        $this->usuario->setNombreCompleto(UsuarioRepositorio::limpiarEspacios($datos['nombreCompleto']));
        $this->usuario->setCorreoUsuario($datos['correoUsuario']);
        if ($nombreFoto !== null) {
            $this->usuario->setFotoPerfil($nombreFoto);
        }

        try {
            $this->usuarioRepositorio->actualizar($this->usuario);
        } catch (Throwable $error) {
            error_log('Error al actualizar perfil: ' . $error->getMessage());
            SubidaArchivo::eliminarFotoPerfil($nombreFoto);
            Mensaje::error('No se pudo guardar. Intente de nuevo.');
            $this->redirigir('/perfil');
        }

        if ($nombreFoto !== null && $fotoAnterior !== null) {
            SubidaArchivo::eliminarFotoPerfil($fotoAnterior);
        }
        UsuarioActual::actualizarNombre($this->usuario->getNombreCompleto());
        UsuarioActual::actualizarFoto($this->usuario->getFotoPerfil());
        
        Mensaje::exito('Perfil actualizado correctamente');
        $this->redirigir('/perfil');
    }

    public function formularioContrasena(): void
    {
        $this->mostrarVista('Perfil/cambiarContrasena', ['errores' => []]);
    }

    public function cambiarContrasena(): void
    {
        $this->verificarCsrf('/perfil/contrasena');

        // Las contrasenas no se recortan: los espacios pueden ser parte de ellas
        $leer      = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        $actual    = $leer('contrasenaActual');
        $nueva     = $leer('contrasenaNueva');
        $confirmar = $leer('confirmarContrasena');

        $validador = new Validador();
        $validador->requerido('contrasenaActual', $actual, 'Ingrese su contraseña actual');
        $validador->requerido('contrasenaNueva', $nueva, 'Ingrese la contraseña nueva')
            ->contrasenaSegura('contrasenaNueva', $nueva);
        $validador->requerido('confirmarContrasena', $confirmar, 'Confirme la contraseña nueva')
            ->coinciden('confirmarContrasena', $confirmar, $nueva, 'Las contraseñas no coinciden');

        if ($validador->error('contrasenaActual') === null
            && ! password_verify($actual, (string) $this->usuario->getContrasena())) {
            $validador->agregarError('contrasenaActual', 'La contraseña actual no es correcta');
        }
        if ($validador->error('contrasenaNueva') === null && $actual === $nueva) {
            $validador->agregarError('contrasenaNueva', 'La contraseña nueva debe ser diferente a la actual');
        }

        if (! $validador->esValido()) {
            $this->mostrarVista('Perfil/cambiarContrasena', ['errores' => $validador->errores()]);
            return;
        }

        try {
            $this->usuarioRepositorio->cambiarContrasena($this->usuario->getIdUsuario(), $nueva);
        } catch (Throwable $error) {
            error_log('Error al cambiar contraseña: ' . $error->getMessage());
            Mensaje::error('No se pudo cambiar la contraseña. Intente de nuevo.');
            $this->redirigir('/perfil/contrasena');
        }

        Mensaje::exito('Contraseña cambiada correctamente');
        $this->redirigir('/perfil');
    }

    private function cargarUsuarioActual(): Usuario
    {
        // Exige sesión iniciada, no expirada y con permiso para ver el perfil
        Permiso::exigir('perfil.ver');

        $usuario = $this->usuarioRepositorio->buscarPorId((int) UsuarioActual::id());
        if ($usuario === null || ! $usuario->getEstado()) {
            // La cuenta se borró o se desactivó mientras estaba conectado
            $idSesionBd = ManejadorSesion::obtenerIdSesionBd();
            if ($idSesionBd !== null) {
                (new SesionRepositorio())->cerrarPorId($idSesionBd);
            }

            UsuarioActual::cerrar();
            Mensaje::error('Su cuenta no está activa. Comuníquese con un administrador.');
            $this->redirigir('/ingresar');
        }

        return $usuario;
    }

    private function validarDatos(array $datos, bool $puedeEditarAcceso): Validador
    {
        $validador = new Validador();
        $idUsuario = $this->usuario->getIdUsuario();

        $validador->requerido('nombreCompleto', $datos['nombreCompleto'], 'Ingrese su nombre completo')
            ->longitud('nombreCompleto', $datos['nombreCompleto'], 3, 100, 'El nombre debe tener entre 3 y 100 caracteres')
            ->soloLetras('nombreCompleto', $datos['nombreCompleto'], 'El nombre solo puede tener letras y espacios');

        if (! $puedeEditarAcceso) {
            return $validador;
        }

        $validador->requerido('numeroIdentificacion', $datos['numeroIdentificacion'], 'Ingrese la identificación')
            ->alfanumerico('numeroIdentificacion', $datos['numeroIdentificacion'])
            ->longitud('numeroIdentificacion', $datos['numeroIdentificacion'], 6, 20,
                'La identificación debe tener entre 6 y 20 caracteres');

        $validador->requerido('correoUsuario', $datos['correoUsuario'], 'Ingrese el correo')
            ->correo('correoUsuario', $datos['correoUsuario']);

        if ($validador->error('correoUsuario') === null
            && $this->usuarioRepositorio->existeCorreo($datos['correoUsuario'], $idUsuario)) {
            $validador->agregarError('correoUsuario', 'Este correo ya lo usa otro usuario');
        }
        if ($validador->error('numeroIdentificacion') === null
            && $this->usuarioRepositorio->existeIdentificacion($datos['numeroIdentificacion'], $idUsuario)) {
            $validador->agregarError('numeroIdentificacion', 'Ya existe otro usuario con esta identificación');
        }

        return $validador;
    }

    private function subirFoto(Validador $validador): ?string
    {
        if (! SubidaArchivo::seEnvio($_FILES['fotoPerfil'] ?? null)) {
            return null;
        }
        $subida = new SubidaArchivo();
        $nombre = $subida->guardarFotoPerfil($_FILES['fotoPerfil']);
        if ($nombre === null) {
            $validador->agregarError('fotoPerfil', $subida->obtenerError());
        }
        return $nombre;
    }

    private function mostrarPerfil(array $datos, array $errores): void
    {
        $this->mostrarVista('Perfil/miPerfil', [
            'usuario'           => $this->usuario,
            'rol'               => (string) UsuarioActual::tipo(),
            'puedeEditarAcceso' => UsuarioActual::esSuperAdmin(),
            'datos'             => $datos,
            'errores'           => $errores,
        ]);
    }

    private function verificarCsrf(string $rutaSiFalla): void
    {
        if (! Csrf::esValido()) {
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
