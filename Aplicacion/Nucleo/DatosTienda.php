<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\Vendedor;
use Aplicacion\Modelos\VendedorContacto;
use Aplicacion\Repositorios\UsuarioRepositorio;
use Aplicacion\Repositorios\VendedorContactoRepositorio;


class DatosTienda
{
    /** @return array{tiendaNombre: string, tiendaDescripcion: string, contactos: array} */
    public static function leer(): array
    {
        $leer = fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';

        return [
            'tiendaNombre' => UsuarioRepositorio::limpiarEspacios($leer('tiendaNombre')),
            'tiendaDescripcion' => $leer('tiendaDescripcion'),
            'contactos' => TipoContacto::desdeFormulario($_POST['contactoTipo'] ?? null, $_POST['contactoValor'] ?? null),
        ];
    }

    
    public static function validar(Validador $validador, array $datos, ?int $idVendedor = null): void
    {
        $validador->requerido('tiendaNombre', $datos['tiendaNombre'], 'Ingrese el nombre de la tienda')
            ->longitud('tiendaNombre', $datos['tiendaNombre'], 3, 100, 'El nombre debe tener entre 3 y 100 caracteres')
            ->nombreTienda('tiendaNombre', $datos['tiendaNombre']);

        $validador->longitud('tiendaDescripcion', $datos['tiendaDescripcion'], 1, 300, 'La descripción puede tener como máximo 300 caracteres');

        
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

    
    public static function contactosParaFormulario(Vendedor $vendedor): array
    {
        return array_map(
            fn(VendedorContacto $contacto): array => ['tipo' => $contacto->getTipo(), 'valor' => $contacto->getValor()],
            $vendedor->getContactos()
        );
    }
}