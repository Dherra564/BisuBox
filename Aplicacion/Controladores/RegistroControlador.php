<?php

namespace Aplicacion\Controladores;

use Aplicacion\Modelos\Cliente;
use Aplicacion\Modelos\Vendedor;
use Aplicacion\Nucleo\Acceso;
use Aplicacion\Nucleo\Csrf;
use Aplicacion\Nucleo\DatosTienda;
use Aplicacion\Nucleo\Mensaje;
use Aplicacion\Nucleo\SubidaArchivo;
use Aplicacion\Nucleo\TipoIdentificacion;
use Aplicacion\Nucleo\UsuarioActual;
use Aplicacion\Nucleo\Validador;
use Aplicacion\Repositorios\ClienteRepositorio;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;
use Configuracion\Configuracion;
use DateTime;
use Throwable;

// Registro público de vendedores y clientes
class RegistroControlador
{
    private UsuarioRepositorio $usuarioRepositorio;

    public function __construct()
    {
        // Quien ya inició sesión no necesita registrarse
        if (UsuarioActual::haySesion()) {
            $this->redirigir('/');
        }
        $this->usuarioRepositorio = new UsuarioRepositorio();
    }

    public function mostrarVendedor(): void
    {
        $this->mostrarVista('Registro/vendedor', [
            'datos' => [
                'tipoIdentificacion' => TipoIdentificacion::CEDULA,
                'contactos' => [],
            ],
            'errores' => [],
        ]);
    }

    public function registrarVendedor(): void
    {
        $this->verificarCsrf('/registro/vendedor');

        $datos = $this->leerDatosPersonales() + DatosTienda::leer();
        $datos['tipoIdentificacion'] = $this->leer('tipoIdentificacion');
        $datos['numeroIdentificacion'] = TipoIdentificacion::limpiar($this->leer('numeroIdentificacion'));
        $contrasena = $this->leerContrasena('contrasena');
        $confirmar = $this->leerContrasena('confirmarContrasena');

        $validador = $this->validarDatosPersonales($datos);

        $validador->tipoIdentificacion('tipoIdentificacion', $datos['tipoIdentificacion']);
        $validador->requerido('numeroIdentificacion', $datos['numeroIdentificacion'], 'Ingrese la identificación')
            ->identificacion('numeroIdentificacion', $datos['tipoIdentificacion'], $datos['numeroIdentificacion']);
        if (
            $validador->error('numeroIdentificacion') === null
            && $this->usuarioRepositorio->existeIdentificacion($datos['numeroIdentificacion'])
        ) {
            $validador->agregarError('numeroIdentificacion', 'Ya existe una cuenta con esta identificación');
        }

        DatosTienda::validar($validador, $datos);
        $this->validarContrasena($validador, $contrasena, $confirmar);

        $nombreLogo = $this->subirLogo($validador);
        if (!$validador->esValido()) {
            SubidaArchivo::eliminarLogo($nombreLogo);
            $this->mostrarVista('Registro/vendedor', ['datos' => $datos, 'errores' => $validador->errores()]);
            return;
        }

         $vendedorRepositorio = new VendedorRepositorio();
        $vendedor = new Vendedor(
            tipoIdentificacion: $datos['tipoIdentificacion'],
            numeroIdentificacion: $datos['numeroIdentificacion'],
            nombreCompleto: UsuarioRepositorio::limpiarEspacios($datos['nombreCompleto']),
            correoUsuario: $datos['correoUsuario'],
            numeroTelefonico: Validador::limpiarTelefono($datos['numeroTelefonico']),
            contrasena: $contrasena,
            fechaRegistro: new DateTime(),
            tiendaNombre: $datos['tiendaNombre'],
            tiendaEnlace: $vendedorRepositorio->generarEnlace($datos['tiendaNombre']),
            tiendaDescripcion: $datos['tiendaDescripcion'],
            tiendaLogo: $nombreLogo,
            contactos: DatosTienda::crearContactos($datos['contactos'])
        );

        try {
            $vendedorRepositorio->insertar($vendedor);
        } catch (Throwable $error) {
            error_log('Error al registrar vendedor: ' . $error->getMessage());
            SubidaArchivo::eliminarLogo($nombreLogo);
            Mensaje::error('No se pudo crear la cuenta. Intente de nuevo.');
            $this->mostrarVista('Registro/vendedor', ['datos' => $datos, 'errores' => []]);
            return;
        }

        Acceso::abrirSesion($vendedor);
        Mensaje::exito('Bienvenido a BisuBox. Su cuenta y su tienda quedaron creadas');
        $this->redirigir('/');
    }

    public function mostrarCliente(): void
    {
        $this->mostrarVista('Registro/cliente', ['datos' => [], 'errores' => []]);
    }

    public function registrarCliente(): void
    {
        $this->verificarCsrf('/registro/cliente');

        $datos = $this->leerDatosPersonales();
        $contrasena = $this->leerContrasena('contrasena');
        $confirmar = $this->leerContrasena('confirmarContrasena');

        $validador = $this->validarDatosPersonales($datos);
        $this->validarContrasena($validador, $contrasena, $confirmar);

        if (!$validador->esValido()) {
            $this->mostrarVista('Registro/cliente', ['datos' => $datos, 'errores' => $validador->errores()]);
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
            $this->mostrarVista('Registro/cliente', ['datos' => $datos, 'errores' => []]);
            return;
        }

        Acceso::abrirSesion($cliente);
        Mensaje::exito('Bienvenido a BisuBox. Su cuenta quedó creada');
        $this->redirigir('/');
    }

    // Nombre, correo y teléfono: los piden los dos registros
    private function leerDatosPersonales(): array
    {
        return [
            'nombreCompleto' => $this->leer('nombreCompleto'),
            'correoUsuario' => mb_strtolower($this->leer('correoUsuario'), 'UTF-8'),
            'numeroTelefonico' => $this->leer('numeroTelefonico'),
        ];
    }

    private function validarDatosPersonales(array $datos): Validador
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

        return $validador;
    }

    private function validarContrasena(Validador $validador, string $contrasena, string $confirmar): void
    {
        $validador->requerido('contrasena', $contrasena, 'Ingrese una contraseña')
            ->contrasenaSegura('contrasena', $contrasena);
        $validador->requerido('confirmarContrasena', $confirmar, 'Confirme la contraseña')
            ->coinciden('confirmarContrasena', $confirmar, $contrasena, 'Las contraseñas no coinciden');
    }

    private function subirLogo(Validador $validador): ?string
    {
        if (!SubidaArchivo::seEnvio($_FILES['tiendaLogo'] ?? null)) {
            return null;
        }
        $subida = new SubidaArchivo();
        $nombre = $subida->guardarLogo($_FILES['tiendaLogo']);
        if ($nombre === null) {
            $validador->agregarError('tiendaLogo', $subida->obtenerError());
        }
        return $nombre;
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
        header('Location: ' . rtrim((string) Configuracion::obtener('appUrl'), '/') . $ruta);
        exit;
    }
}