<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Cliente;
use Aplicacion\Nucleo\Acceso;
use Aplicacion\Nucleo\Carrito;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;

class RegistroControlador
{
    private UsuarioRepositorio $usuarioRepositorio;

    public function __construct()
    {
        if (UsuarioActual::haySesion()) {
            $this->redirigir('/');
        }
        $this->usuarioRepositorio = new UsuarioRepositorio();
    }

    public function mostrar(): void
    {
        if (isset($_GET['volver'])) {
            Acceso::guardarDestino($_GET['volver']);
        }
        $this->mostrarVista('Registro/crearCuenta', ['datos' => [], 'errores' => []]);
    }

    public function registrar(): void
    {
        $this->verificarCsrf('/registro');

        $datos = [
            'nombreCompleto' => $this->leer('nombreCompleto'),
            'correoUsuario' => mb_strtolower($this->leer('correoUsuario'), 'UTF-8'),
            'numeroTelefonico' => $this->leer('numeroTelefonico'),
        ];
        $contrasena = $this->leerContrasena('contrasena');
        $confirmar = $this->leerContrasena('confirmarContrasena');

        $validador = $this->validarDatos($datos, $contrasena, $confirmar);
        $datos['contrasena'] = $contrasena;
        $datos['confirmarContrasena'] = $confirmar;

        if (!$validador->esValido()) {
            $this->mostrarVista('Registro/crearCuenta', ['datos' => $datos, 'errores' => $validador->errores()]);
            return;
        }

        $cliente = new Cliente(
            nombreCompleto: UsuarioRepositorio::limpiarEspacios($datos['nombreCompleto']),
            correoUsuario: $datos['correoUsuario'],
            numeroTelefonico: Validador::limpiarTelefono($datos['numeroTelefonico']),
            contrasena: $contrasena,
            fechaRegistro: new DateTime()
        );

        try {
            (new ClienteRepositorio())->insertar($cliente);
        } catch (Throwable $error) {
            error_log('Error al registrar cliente: ' . $error->getMessage());
            Mensaje::error('No se pudo crear la cuenta. Intente de nuevo.');
            $this->mostrarVista('Registro/crearCuenta', ['datos' => $datos, 'errores' => []]);
            return;
        }

        Acceso::abrirSesion($cliente);
        Carrito::pasarALaCuenta();
        Mensaje::exito('Bienvenido a BisuBox. Su cuenta quedó creada');
        $this->redirigir(Acceso::tomarDestino() ?? '/');
    }

    private function validarDatos(array $datos, string $contrasena, string $confirmar): Validador
    {
        $validador = new Validador();

        $validador->requerido('nombreCompleto', $datos['nombreCompleto'], 'Ingrese su nombre completo')
            ->longitud('nombreCompleto', $datos['nombreCompleto'], 3, 100, 'El nombre debe tener entre 3 y 100 caracteres')
            ->soloLetras('nombreCompleto', $datos['nombreCompleto'], 'El nombre solo puede tener letras y espacios');

        $validador->requerido('correoUsuario', $datos['correoUsuario'], 'Ingrese su correo')
            ->correo('correoUsuario', $datos['correoUsuario']);
        if ($validador->error('correoUsuario') === null && $this->usuarioRepositorio->existeCorreo($datos['correoUsuario'])) {
            $validador->agregarError('correoUsuario', 'Ya existe una cuenta con este correo');
        }

        $validador->requerido('numeroTelefonico', $datos['numeroTelefonico'], 'Ingrese su teléfono')
            ->telefono('numeroTelefonico', $datos['numeroTelefonico']);
        if (
            $validador->error('numeroTelefonico') === null
            && $this->usuarioRepositorio->existeTelefono(Validador::limpiarTelefono($datos['numeroTelefonico']))
        ) {
            $validador->agregarError('numeroTelefonico', 'Este teléfono ya lo usa otra cuenta');
        }

        $validador->requerido('contrasena', $contrasena, 'Ingrese una contraseña')
            ->contrasenaSegura('contrasena', $contrasena);
        $validador->requerido('confirmarContrasena', $confirmar, 'Confirme la contraseña')
            ->coinciden('confirmarContrasena', $confirmar, $contrasena, 'Las contraseñas no coinciden');

        return $validador;
    }

    private function leer(string $campo): string
    {
        return is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    }

    private function leerContrasena(string $campo): string
    {
        return is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
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