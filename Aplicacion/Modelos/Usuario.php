<?php

namespace Aplicacion\Modelos;

use DateTime;

class Usuario
{
    protected ?int $idUsuario;
    protected ?string $tipoIdentificacion;
    protected ?string $numeroIdentificacion;
    protected ?string $nombreCompleto;
    protected ?string $fotoPerfil;
    protected ?string $correoUsuario;
    protected ?string $numeroTelefonico;
    protected ?string $contrasena;
    protected DateTime $fechaRegistro;
    protected bool $estado;

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
        bool $estado = true
    ) {
        $this->idUsuario = $idUsuario;
        $this->tipoIdentificacion = $tipoIdentificacion;
        $this->numeroIdentificacion = $numeroIdentificacion;
        $this->nombreCompleto = $nombreCompleto;
        $this->fotoPerfil = $fotoPerfil;
        $this->correoUsuario = $correoUsuario;
        $this->numeroTelefonico = $numeroTelefonico;
        $this->contrasena = $contrasena;
        $this->fechaRegistro = $fechaRegistro ?? new DateTime();
        $this->estado = $estado;
    }

    public function getIdUsuario(): ?int
    {
        return $this->idUsuario;
    }

    public function getTipoIdentificacion(): ?string
    {
        return $this->tipoIdentificacion;
    }

    public function getNumeroIdentificacion(): ?string
    {
        return $this->numeroIdentificacion;
    }

    public function getNombreCompleto(): ?string
    {
        return $this->nombreCompleto;
    }

    public function getFotoPerfil(): ?string
    {
        return $this->fotoPerfil;
    }

    public function getCorreoUsuario(): ?string
    {
        return $this->correoUsuario;
    }

    public function getNumeroTelefonico(): ?string
    {
        return $this->numeroTelefonico;
    }

    public function getContrasena(): ?string
    {
        return $this->contrasena;
    }

    public function getFechaRegistro(): DateTime
    {
        return $this->fechaRegistro;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }

    public function setIdUsuario(?int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
    }

    public function setTipoIdentificacion(?string $tipoIdentificacion): void
    {
        $this->tipoIdentificacion = $tipoIdentificacion;
    }

    public function setNumeroIdentificacion(?string $numeroIdentificacion): void
    {
        $this->numeroIdentificacion = $numeroIdentificacion;
    }

    public function setNombreCompleto(?string $nombreCompleto): void
    {
        $this->nombreCompleto = $nombreCompleto;
    }

    public function setFotoPerfil(?string $fotoPerfil): void
    {
        $this->fotoPerfil = $fotoPerfil;
    }

    public function setCorreoUsuario(?string $correoUsuario): void
    {
        $this->correoUsuario = $correoUsuario;
    }

    public function setNumeroTelefonico(?string $numeroTelefonico): void
    {
        $this->numeroTelefonico = $numeroTelefonico;
    }

    public function setContrasena(?string $contrasena): void
    {
        $this->contrasena = $contrasena;
    }

    public function setEstado(bool $estado): void
    {
        $this->estado = $estado;
    }
}