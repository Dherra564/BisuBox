<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Usuario;
use Aplicacion\Nucleo\Correo;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\RecuperacionRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;
use Throwable;

class RecuperacionControlador
{
    private const MENSAJE_ENVIO = 'Si el correo está registrado, le enviamos un enlace';
    private const MENSAJE_ENLACE = 'El enlace no es válido o ya venció. Pida uno nuevo.';

    public function mostrarSolicitud(): void
    {
        if (UsuarioActual::haySesion()) {
            $this->redirigir('/');
        }
        $this->mostrarVista('Autenticacion/olvideContrasena', ['datos' => ['correo' => ''], 'errores' => []]);
    }

    public function solicitar(): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Recargue e intente de nuevo.');
            $this->redirigir('/olvide-contrasena');
        }

        $correo = Validador::limpiarCorreo(is_string($_POST['correo'] ?? null) ? trim($_POST['correo']) : '');

        $validador = new Validador();
        $validador->requerido('correo', $correo, 'Ingrese su correo')
            ->correo('correo', $correo);
        if (!$validador->esValido()) {
            $this->mostrarVista('Autenticacion/olvideContrasena', ['datos' => ['correo' => $correo], 'errores' => $validador->errores()]);
            return;
        }

        try {
            $this->enviarEnlace($correo);
        } catch (Throwable $error) {
            error_log('Error al preparar la recuperación de contraseña: ' . $error->getMessage());
        }

        Mensaje::exito(self::MENSAJE_ENVIO);
        $this->redirigir('/olvide-contrasena');
    }

    public function mostrarRestablecer(): void
    {
        $codigo = $this->leerCodigo($_GET['codigo'] ?? null);
        if ($codigo === null || (new RecuperacionRepositorio())->buscarVigente(RecuperacionRepositorio::calcularHash($codigo)) === null) {
            Mensaje::error(self::MENSAJE_ENLACE);
            $this->redirigir('/olvide-contrasena');
        }

        $this->mostrarFormularioNuevaContrasena($codigo, []);
    }

    public function restablecer(): void
    {
        if (!Csrf::esValido()) {
            Mensaje::error('La página expiró. Pida un enlace nuevo.');
            $this->redirigir('/olvide-contrasena');
        }

        $codigo = $this->leerCodigo($_POST['codigo'] ?? null);
        $repositorio = new RecuperacionRepositorio();
        if ($codigo === null || $repositorio->buscarVigente(RecuperacionRepositorio::calcularHash($codigo)) === null) {
            Mensaje::error(self::MENSAJE_ENLACE);
            $this->redirigir('/olvide-contrasena');
        }

        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        $nueva = $leer('contrasenaNueva');
        $confirmar = $leer('confirmarContrasena');

        $validador = new Validador();
        $validador->requerido('contrasenaNueva', $nueva, 'Ingrese la contraseña nueva')
            ->contrasenaSegura('contrasenaNueva', $nueva);
        $validador->requerido('confirmarContrasena', $confirmar, 'Confirme la contraseña nueva')
            ->coinciden('confirmarContrasena', $confirmar, $nueva, 'Las contraseñas no coinciden');
        if (!$validador->esValido()) {
            $this->mostrarFormularioNuevaContrasena($codigo, $validador->errores());
            return;
        }

        try {
            $cambiada = $repositorio->restablecer(RecuperacionRepositorio::calcularHash($codigo), $nueva);
        } catch (Throwable $error) {
            error_log('Error al restablecer la contraseña: ' . $error->getMessage());
            Mensaje::error('No se pudo cambiar la contraseña. Intente de nuevo.');
            $this->mostrarFormularioNuevaContrasena($codigo, []);
            return;
        }

        if (!$cambiada) {
            Mensaje::error(self::MENSAJE_ENLACE);
            $this->redirigir('/olvide-contrasena');
        }

        Mensaje::exito('Contraseña cambiada correctamente. Ya puede ingresar con la nueva.');
        $this->redirigir('/ingresar');
    }

    private function enviarEnlace(string $correo): void
    {
        $usuario = (new UsuarioRepositorio())->buscarPorCorreo($correo);
        if ($usuario === null || !$usuario->getEstado()) {
            return;
        }

        $idUsuario = (int) $usuario->getIdUsuario();
        $repositorio = new RecuperacionRepositorio();
        if ($repositorio->solicitudReciente($idUsuario)) {
            return;
        }

        $codigo = bin2hex(random_bytes(32));
        $repositorio->crear($idUsuario, RecuperacionRepositorio::calcularHash($codigo));
        $this->enviarCorreo($usuario, $this->armarEnlace($codigo));
    }

    private function enviarCorreo(Usuario $usuario, string $enlace): void
    {
        $primerNombre = explode(' ', trim((string) $usuario->getNombreCompleto()))[0];
        $minutos = RecuperacionRepositorio::MINUTOS_VIGENCIA;

        $html = '<p>Hola ' . htmlspecialchars($primerNombre, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Recibimos una solicitud para cambiar la contraseña de su cuenta en BisuBox. '
            . 'Use este enlace para escribir una contraseña nueva:</p>'
            . '<p><a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">Cambiar mi contraseña</a></p>'
            . '<p>El enlace sirve una sola vez y vence en ' . $minutos . ' minutos.</p>'
            . '<p>Si usted no lo pidió, ignore este mensaje: su contraseña no cambia.</p>';

        $texto = "Hola {$primerNombre},\n\n"
            . "Recibimos una solicitud para cambiar la contraseña de su cuenta en BisuBox.\n"
            . "Use este enlace para escribir una contraseña nueva:\n\n{$enlace}\n\n"
            . "El enlace sirve una sola vez y vence en {$minutos} minutos.\n"
            . "Si usted no lo pidió, ignore este mensaje: su contraseña no cambia.\n";

        Correo::enviar(
            (string) $usuario->getCorreoUsuario(),
            (string) $usuario->getNombreCompleto(),
            'Cambie su contraseña de BisuBox',
            $html,
            $texto
        );
    }

    private function armarEnlace(string $codigo): string
    {
        $configurada = trim((string) Configuracion::obtener('appUrl'));
        if ($configurada !== '') {
            $base = rtrim($configurada, '/');
        } else {
            $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $esquema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . Configuracion::urlBase();
        }

        return $base . '/restablecer-contrasena?codigo=' . $codigo;
    }

    private function leerCodigo(mixed $valor): ?string
    {
        return is_string($valor) && preg_match('/^[a-f0-9]{64}$/D', $valor) === 1 ? $valor : null;
    }

    private function mostrarFormularioNuevaContrasena(string $codigo, array $errores): void
    {
        header('Referrer-Policy: no-referrer');
        $this->mostrarVista('Autenticacion/restablecerContrasena', ['codigo' => $codigo, 'errores' => $errores]);
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