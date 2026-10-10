<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Modelos\VendedorContacto;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorContactoRepositorio;
use Aplicacion\Repositorios\VendedorRepositorio;

// Lee y valida los datos de la tienda; lo usan el registro de vendedor y Mi tienda
class DatosTienda
{
    /** @return array{tiendaNombre: string, tiendaEnlace: string, tiendaDescripcion: string, contactos: array} */
    public static function leer(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'tiendaNombre' => UsuarioRepositorio::limpiarEspacios($leer('tiendaNombre')),
            'tiendaEnlace' => VendedorRepositorio::normalizarEnlace($leer('tiendaEnlace')),
            'tiendaDescripcion' => $leer('tiendaDescripcion'),
            'contactos' => TipoContacto::desdeFormulario($_POST['contactoTipo'] ?? null, $_POST['contactoValor'] ?? null),
        ];
    }

    // $idVendedor: el de la tienda que se edita, para no compararla consigo misma (null en el registro)
    public static function validar(Validador $validador, array $datos, ?int $idVendedor = null): void
    {
        $validador->requerido('tiendaNombre', $datos['tiendaNombre'], 'Ingrese el nombre de la tienda')
            ->longitud('tiendaNombre', $datos['tiendaNombre'], 3, 100, 'El nombre debe tener entre 3 y 100 caracteres')
            ->nombreTienda('tiendaNombre', $datos['tiendaNombre']);

        $validador->requerido('tiendaEnlace', $datos['tiendaEnlace'], 'Ingrese el enlace de la tienda')
            ->longitud('tiendaEnlace', $datos['tiendaEnlace'], 3, 60, 'El enlace debe tener entre 3 y 60 caracteres')
            ->enlaceTienda('tiendaEnlace', $datos['tiendaEnlace']);
        if (
            $validador->error('tiendaEnlace') === null
            && (new VendedorRepositorio())->existeEnlace($datos['tiendaEnlace'], $idVendedor)
        ) {
            $validador->agregarError('tiendaEnlace', 'Ese enlace ya lo usa otra tienda');
        }

        $validador->longitud('tiendaDescripcion', $datos['tiendaDescripcion'], 1, 300, 'La descripción puede tener como máximo 300 caracteres');

        // Primero las reglas de cada contacto; luego, que ningún WhatsApp lo tenga otra tienda
        $validador->contactos($datos['contactos']);
        $contactoRepositorio = new VendedorContactoRepositorio();
        foreach ($datos['contactos'] as $indice => $contacto) {
            if (
                $contacto['tipo'] === TipoContacto::WHATSAPP
                && $validador->error('contacto' . $indice) === null
                && $contactoRepositorio->existeWhatsApp($contacto['valor'], $idVendedor)
            ) {
                $validador->agregarError('contacto' . $indice, 'Este WhatsApp ya lo usa otra tienda');
            }
        }
    }

    /** @return VendedorContacto[] */
    public static function crearContactos(array $contactos): array
    {
        return array_map(
            fn(array $contacto): VendedorContacto => new VendedorContacto(null, null, $contacto['tipo'], $contacto['valor']),
            $contactos
        );
    }

    // Los contactos del vendedor como los usa el formulario: ['tipo' => ..., 'valor' => ...]
    public static function contactosParaFormulario(Vendedor $vendedor): array
    {
        return array_map(
            fn(VendedorContacto $contacto): array => ['tipo' => $contacto->getTipo(), 'valor' => $contacto->getValor()],
            $vendedor->getContactos()
        );
    }
}