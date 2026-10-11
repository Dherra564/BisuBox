<?php

namespace Aplicacion\Modelos;

use DateTime;


class Proveedor
{
    private ?int $idProveedor;
    private ?int $idVendedor;
    private ?string $tipoIdentificacion;
    private ?string $numeroIdentificacion;
    private ?string $nombre;
    private ?string $telefono;
    private ?string $correo;
    private DateTime $fechaRegistro;
    private bool $estado;

    public function __construct(
        ?int $idProveedor = null,
        ?int $idVendedor = null,
        ?string $tipoIdentificacion = null,
        ?string $numeroIdentificacion = null,
        ?string $nombre = null,
        ?string $telefono = null,
        ?string $correo = null,
        ?DateTime $fechaRegistro = null,
        bool $estado = true
    ) {
        $this->idProveedor = $idProveedor;
        $this->idVendedor = $idVendedor;
        $this->tipoIdentificacion = $tipoIdentificacion;
        $this->numeroIdentificacion = $numeroIdentificacion;
        $this->nombre = $nombre;
        $this->telefono = $telefono;
        $this->correo = $correo;
        $this->fechaRegistro = $fechaRegistro ?? new DateTime();
        $this->estado = $estado;
    }

    public function getIdProveedor(): ?int
    {
        return $this->idProveedor;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getTipoIdentificacion(): ?string
    {
        return $this->tipoIdentificacion;
    }

    public function getNumeroIdentificacion(): ?string
    {
        return $this->numeroIdentificacion;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function getCorreo(): ?string
    {
        return $this->correo;
    }

    public function getFechaRegistro(): DateTime
    {
        return $this->fechaRegistro;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }

    public function setIdProveedor(?int $idProveedor): void
    {
        $this->idProveedor = $idProveedor;
    }

    public function setTipoIdentificacion(?string $tipoIdentificacion): void
    {
        $this->tipoIdentificacion = $tipoIdentificacion;
    }

    public function setNumeroIdentificacion(?string $numeroIdentificacion): void
    {
        $this->numeroIdentificacion = $numeroIdentificacion;
    }

    public function setNombre(?string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono;
    }

    public function setCorreo(?string $correo): void
    {
        $this->correo = $correo;
    }

    public function setEstado(bool $estado): void
    {
        $this->estado = $estado;
    }
}