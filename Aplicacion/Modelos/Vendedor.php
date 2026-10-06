<?php

namespace Aplicacion\Modelos;

use DateTime;

class Vendedor extends Usuario
{
    private ?int $idVendedor;
    private DateTime $registroFechaVendedor;
    private bool $estadoVendedor;

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
        ?DateTime $registroFechaVendedor = null,
        bool $estadoVendedor = true
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
            $estado
        );

        $this->idVendedor = $idVendedor;
        $this->registroFechaVendedor = $registroFechaVendedor ?? new DateTime();
        $this->estadoVendedor = $estadoVendedor;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
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

    public function setEstadoVendedor(bool $estadoVendedor): void
    {
        $this->estadoVendedor = $estadoVendedor;
    }
}