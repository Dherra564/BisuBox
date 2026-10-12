<?php

namespace Aplicacion\Modelos;


class Proceso
{
    private ?int $idProceso;
    private ?int $idVendedor;
    private ?string $nombre;
    private ?string $descripcion;
    private ?int $tiempoEstimado;
    private ?float $costoManoObra;
    private bool $estado;

    public function __construct(
        ?int $idProceso = null,
        ?int $idVendedor = null,
        ?string $nombre = null,
        ?string $descripcion = null,
        ?int $tiempoEstimado = null,
        ?float $costoManoObra = null,
        bool $estado = true
    ) {
        $this->idProceso = $idProceso;
        $this->idVendedor = $idVendedor;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->tiempoEstimado = $tiempoEstimado;
        $this->costoManoObra = $costoManoObra;
        $this->estado = $estado;
    }

    public function getIdProceso(): ?int
    {
        return $this->idProceso;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getTiempoEstimado(): ?int
    {
        return $this->tiempoEstimado;
    }

    public function getCostoManoObra(): ?float
    {
        return $this->costoManoObra;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }

    public function setIdProceso(?int $idProceso): void
    {
        $this->idProceso = $idProceso;
    }

    public function setNombre(?string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setTiempoEstimado(?int $tiempoEstimado): void
    {
        $this->tiempoEstimado = $tiempoEstimado;
    }

    public function setCostoManoObra(?float $costoManoObra): void
    {
        $this->costoManoObra = $costoManoObra;
    }

    public function setEstado(bool $estado): void
    {
        $this->estado = $estado;
    }
}