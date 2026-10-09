<?php

namespace Aplicacion\Modelos;

/**
 * Una forma de trabajo del taller (ensartar, armar, soldar, pegar, empacar).
 * No tiene existencia: aporta el orden de elaboración y el costo de mano de obra.
 */
class Proceso
{
    public function __construct(
        private ?int $idProceso,
        private string $nombre,
        private string $descripcion,
        private int $tiempoEstimado,
        private string $costoManoObra,
        private bool $activo
    ) {
    }

    public function getIdProceso(): ?int
    {
        return $this->idProceso;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getTiempoEstimado(): int
    {
        return $this->tiempoEstimado;
    }

    public function getCostoManoObra(): string
    {
        return $this->costoManoObra;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    public function setIdProceso(?int $idProceso): void
    {
        $this->idProceso = $idProceso;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setTiempoEstimado(int $tiempoEstimado): void
    {
        $this->tiempoEstimado = $tiempoEstimado;
    }

    public function setCostoManoObra(string $costoManoObra): void
    {
        $this->costoManoObra = $costoManoObra;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }
}