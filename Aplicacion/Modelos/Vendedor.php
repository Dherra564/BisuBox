<?php

namespace Aplicacion\Modelos;

use DateTime;

class Vendedor extends Usuario
{
    private ?int $idVendedor;
    private ?string $numeroTelefonico;
    private DateTime $registroFechaVendedor;
    private bool $estadoVendedor;

    public function __construct(
        ?int $idUsuario = null,
        ?string $numeroIdentificacion = null,
        ?string $nombreCompleto = null,
        ?string $fotoPerfil = null,
        ?string $correoUsuario = null,
        ?string $contrasena = null,
        ?DateTime $fechaRegistro = null,
        bool $estado = true,
        ?int $idVendedor = null,
        ?string $numeroTelefonico = null,
        ?DateTime $registroFechaVendedor = null,
        bool $estadoVendedor = true
    ) {

        parent::__construct(
            $idUsuario,
            $numeroIdentificacion,
            $nombreCompleto,
            $fotoPerfil,
            $correoUsuario,
            $contrasena,
            $fechaRegistro,
            $estado
        );

        $this->idVendedor = $idVendedor;
        $this->numeroTelefonico = $numeroTelefonico;
        $this->registroFechaVendedor = $registroFechaVendedor ?? new DateTime();
        $this->estadoVendedor = $estadoVendedor;
    }

    // Getters
    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getNumeroTelefonico(): ?string
    {
        return $this->numeroTelefonico;
    }

    public function getRegistroFechaVendedor(): DateTime
    {
        return $this->registroFechaVendedor;
    }

    public function getEstadoVendedor(): bool
    {
        return $this->estadoVendedor;
    }

    public function setIdVendedor(?int $idVendedor): void
    {
        $this->idVendedor = $idVendedor;
    }

    public function setNumeroTelefonico(?string $numeroTelefonico): void
    {
        $this->numeroTelefonico = $numeroTelefonico;
    }

    public function setEstadoVendedor(bool $estadoVendedor): void
    {
        $this->estadoVendedor = $estadoVendedor;
    }

}