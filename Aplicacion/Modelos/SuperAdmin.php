<?php

namespace Aplicacion\Modelos;

use DateTime;

class SuperAdmin extends Usuario
{
   
    private ?int $idSuperAdmin;
    private bool $estadoSuperAdmin;

    public function __construct(
        ?int $idUsuario = null,
        ?string $numeroIdentificacion = null,
        ?string $nombreCompleto = null,
        ?string $fotoPerfil = null,
        ?string $correoUsuario = null,
        ?string $contrasena = null,
        ?DateTime $fechaRegistro = null,
        bool $estado = true,
        ?int $idSuperAdmin = null,
        bool $estadoSuperAdmin = true
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

        $this->idSuperAdmin = $idSuperAdmin;
        $this->estadoSuperAdmin = $estadoSuperAdmin;
    }

    // Getters
    public function getIdSuperAdmin(): ?int
    {
        return $this->idSuperAdmin;
    }

    public function getEstadoSuperAdmin(): bool
    {
        return $this->estadoSuperAdmin;
    }

    public function setIdSuperAdmin(?int $idSuperAdmin): void
    {
        $this->idSuperAdmin = $idSuperAdmin;
    }

    public function setEstadoSuperAdmin(bool $estadoSuperAdmin): void
    {
        $this->estadoSuperAdmin = $estadoSuperAdmin;
    }
}
