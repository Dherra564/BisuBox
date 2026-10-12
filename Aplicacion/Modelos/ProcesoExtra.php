<?php

namespace Aplicacion\Modelos;

class ProcesoExtra
{
    private ?int $idProcesoExtra;
    private ?int $idProceso;
    private string $concepto;
    private float $monto;

    public function __construct(?int $idProcesoExtra, ?int $idProceso, string $concepto, float $monto)
    {
        $this->idProcesoExtra = $idProcesoExtra;
        $this->idProceso = $idProceso;
        $this->concepto = $concepto;
        $this->monto = $monto;
    }

    public function getIdProcesoExtra(): ?int
    {
        return $this->idProcesoExtra;
    }

    public function getIdProceso(): ?int
    {
        return $this->idProceso;
    }

    public function getConcepto(): string
    {
        return $this->concepto;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }

    public function setIdProcesoExtra(?int $idProcesoExtra): void
    {
        $this->idProcesoExtra = $idProcesoExtra;
    }

    public function setIdProceso(?int $idProceso): void
    {
        $this->idProceso = $idProceso;
    }
}