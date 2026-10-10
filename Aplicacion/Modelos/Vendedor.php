<?php

namespace Aplicacion\Modelos;

use Aplicacion\Nucleo\Rol;
use DateTime;

class Vendedor extends Usuario
{
    private ?int $idVendedor;
    private ?string $tiendaNombre;
    private ?string $tiendaEnlace;
    private ?string $tiendaDescripcion;
    private ?string $tiendaLogo;
    private bool $tiendaActiva;
    /** @var VendedorContacto[] */
    private array $contactos;

    /** @param VendedorContacto[] $contactos */
    public function __construct(
        ?int $idUsuario = null,
        ?string $tipoIdentificacion = null,
        ?string $numeroIdentificacion = null,
        ?string $nombreCompleto = null,
        ?string $fotoPerfil = null,
        ?string $correoUsuario = null,
        ?string $numeroTelefonico = null,
        ?string $contrasena = null,
        ?DateTime $fechaRegistro = null,
        bool $estado = true,
        ?int $idVendedor = null,
        ?string $tiendaNombre = null,
        ?string $tiendaEnlace = null,
        ?string $tiendaDescripcion = null,
        ?string $tiendaLogo = null,
        bool $tiendaActiva = true,
        array $contactos = []
    ) {
        parent::__construct(
            $idUsuario,
            $tipoIdentificacion,
            $numeroIdentificacion,
            $nombreCompleto,
            $fotoPerfil,
            $correoUsuario,
            $numeroTelefonico,
            $contrasena,
            $fechaRegistro,
            $estado,
            Rol::VENDEDOR
        );

        $this->idVendedor = $idVendedor;
        $this->tiendaNombre = $tiendaNombre;
        $this->tiendaEnlace = $tiendaEnlace;
        $this->tiendaDescripcion = $tiendaDescripcion;
        $this->tiendaLogo = $tiendaLogo;
        $this->tiendaActiva = $tiendaActiva;
        $this->contactos = $contactos;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getTiendaNombre(): ?string
    {
        return $this->tiendaNombre;
    }

    public function getTiendaEnlace(): ?string
    {
        return $this->tiendaEnlace;
    }

    public function getTiendaDescripcion(): ?string
    {
        return $this->tiendaDescripcion;
    }

    public function getTiendaLogo(): ?string
    {
        return $this->tiendaLogo;
    }

    public function getTiendaActiva(): bool
    {
        return $this->tiendaActiva;
    }

    /** @return VendedorContacto[] */
    public function getContactos(): array
    {
        return $this->contactos;
    }

    public function setIdVendedor(?int $idVendedor): void
    {
        $this->idVendedor = $idVendedor;
    }

    public function setTiendaNombre(?string $tiendaNombre): void
    {
        $this->tiendaNombre = $tiendaNombre;
    }

    public function setTiendaEnlace(?string $tiendaEnlace): void
    {
        $this->tiendaEnlace = $tiendaEnlace;
    }

    public function setTiendaDescripcion(?string $tiendaDescripcion): void
    {
        $this->tiendaDescripcion = $tiendaDescripcion;
    }

    public function setTiendaLogo(?string $tiendaLogo): void
    {
        $this->tiendaLogo = $tiendaLogo;
    }

    public function setTiendaActiva(bool $tiendaActiva): void
    {
        $this->tiendaActiva = $tiendaActiva;
    }

    /** @param VendedorContacto[] $contactos */
    public function setContactos(array $contactos): void
    {
        $this->contactos = $contactos;
    }
}