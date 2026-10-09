<?php

namespace Aplicacion\Modelos;

use Aplicacion\Nucleo\Rol;
use DateTime;
use InvalidArgumentException;

class Sesion
{
    private ?int $idSesion;
    private ?int $idUsuario;
    private ?string $tipoUsuario;
    private DateTime $fechaInicioSesion;
    private ?DateTime $fechaCierreSesion;
    private bool $estado;

    public function __construct(
        ?int $idSesion = null,
        ?int $idUsuario = null,
        ?string $tipoUsuario = null,
        ?DateTime $fechaInicioSesion = null,
        ?DateTime $fechaCierreSesion = null,
        bool $estado = true
    ) {
        $this->idSesion = $idSesion;
        $this->idUsuario = $idUsuario;
        $this->setTipoUsuario($tipoUsuario);
        $this->fechaInicioSesion = $fechaInicioSesion ?? new DateTime();
        $this->fechaCierreSesion = $fechaCierreSesion;
        $this->estado = $estado;
    }

    public function getIdSesion(): ?int
    {
        return $this->idSesion;
    }

    public function getIdUsuario(): ?int
    {
        return $this->idUsuario;
    }

    public function getTipoUsuario(): ?string
    {
        return $this->tipoUsuario;
    }

    public function getFechaInicioSesion(): DateTime
    {
        return $this->fechaInicioSesion;
    }

    public function getFechaCierreSesion(): ?DateTime
    {
        return $this->fechaCierreSesion;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }

    public function setIdSesion(?int $idSesion): void
    {
        $this->idSesion = $idSesion;
    }

    public function setIdUsuario(?int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
    }

    public function setTipoUsuario(?string $tipoUsuario): void
    {
        if ($tipoUsuario !== null && !Rol::existe($tipoUsuario)) {
            throw new InvalidArgumentException('Tipo de usuario no válido: ' . $tipoUsuario);
        }
        $this->tipoUsuario = $tipoUsuario;
    }

    public function estaActiva(): bool
    {
        return $this->estado && $this->fechaCierreSesion === null;
    }

    public function cerrar(): void
    {
        $this->fechaCierreSesion = new DateTime();
        $this->estado = false;
    }
}