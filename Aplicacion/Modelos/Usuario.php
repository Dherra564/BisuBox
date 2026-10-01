<?php

namespace Aplicacion\Modelos;

use DateTime;

class Usuario
{
    protected ?int $idUsuario;
    protected ?string $numeroIdentificacion;
    protected ?string $nombreCompleto;
    protected ?string $fotoPerfil;
    protected ?string $correoUsuario;
    protected ?string $contrasena;        
    protected DateTime $fechaRegistro;
    protected bool $estado;

    public function __construct(
        ?int $idUsuario = null,
        ?string $numeroIdentificacion = null,
        ?string $nombreCompleto = null,
        ?string $fotoPerfil = null,
        ?string $correoUsuario = null,
        ?string $contrasena = null,
        ?DateTime $fechaRegistro = null,
        bool $estado = true
    ) {
        $this->idUsuario = $idUsuario;
        $this->numeroIdentificacion = $numeroIdentificacion;
        $this->nombreCompleto = $nombreCompleto;
        $this->fotoPerfil = $fotoPerfil;
        $this->correoUsuario = $correoUsuario;
        $this->contrasena = $contrasena;
        $this->fechaRegistro = $fechaRegistro ?? new DateTime();
        $this->estado = $estado;
    }

    // Getters
    public function getIdUsuario(): ?int
    {
        return $this->idUsuario;
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

    // Setters
    // El id sí tiene setter porque el repositorio se lo asigna al guardar
    public function setIdUsuario(?int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
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

    public function setContrasena(?string $contrasena): void
    {
        $this->contrasena = $contrasena;
    }

    public function setEstado(bool $estado): void
    {
        $this->estado = $estado;
    }

    // No hay setFechaRegistro: la fecha se asigna al guardar y no debería cambiar
}