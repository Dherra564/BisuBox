<?php

namespace Aplicacion\Modelos;

class ProcesoComponente
{
    private ?int $idProcesoComponente;
    private ?int $idProceso;
    private int $idComponente;
    private float $cantidad;
    private float $costoUnitario;
    private float $subtotal;
    private string $nombreComponente;
    private string $nombreTipo;
    private string $unidad;

    public function __construct(
        ?int $idProcesoComponente,
        ?int $idProceso,
        int $idComponente,
        float $cantidad,
        float $costoUnitario,
        float $subtotal,
        string $nombreComponente = '',
        string $nombreTipo = '',
        string $unidad = ''
    ) {
        $this->idProcesoComponente = $idProcesoComponente;
        $this->idProceso = $idProceso;
        $this->idComponente = $idComponente;
        $this->cantidad = $cantidad;
        $this->costoUnitario = $costoUnitario;
        $this->subtotal = $subtotal;
        $this->nombreComponente = $nombreComponente;
        $this->nombreTipo = $nombreTipo;
        $this->unidad = $unidad;
    }

    public function getIdProcesoComponente(): ?int
    {
        return $this->idProcesoComponente;
    }

    public function getIdProceso(): ?int
    {
        return $this->idProceso;
    }

    public function getIdComponente(): int
    {
        return $this->idComponente;
    }

    public function getCantidad(): float
    {
        return $this->cantidad;
    }

    public function getCostoUnitario(): float
    {
        return $this->costoUnitario;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getNombreComponente(): string
    {
        return $this->nombreComponente;
    }

    public function getNombreTipo(): string
    {
        return $this->nombreTipo;
    }

    public function getUnidad(): string
    {
        return $this->unidad;
    }

    public function setIdProcesoComponente(?int $idProcesoComponente): void
    {
        $this->idProcesoComponente = $idProcesoComponente;
    }

    public function setIdProceso(?int $idProceso): void
    {
        $this->idProceso = $idProceso;
    }
}