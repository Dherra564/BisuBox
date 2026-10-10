<?php

namespace Aplicacion\Modelos;

use Aplicacion\Nucleo\Rol;
use DateTime;

class Cliente extends Usuario
{
    private ?int $idCliente;

    public function __construct(
        ?int $idUsuario = null,
        ?string $nombreCompleto = null,
        ?string $fotoPerfil = null,
        ?string $correoUsuario = null,
        ?string $numeroTelefonico = null,
        ?string $contrasena = null,
        ?DateTime $fechaRegistro = null,
        bool $estado = true,
        ?int $idCliente = null
    ) {
        parent::__construct(
            $idUsuario,
            null,
            null,
            $nombreCompleto,
            $fotoPerfil,
            $correoUsuario,
            $numeroTelefonico,
            $contrasena,
            $fechaRegistro,
            $estado,
            Rol::CLIENTE
        );

        $this->idCliente = $idCliente;
    }

    public function getIdCliente(): ?int
    {
        return $this->idCliente;
    }

    public function setIdCliente(?int $idCliente): void
    {
        $this->idCliente = $idCliente;
    }
}