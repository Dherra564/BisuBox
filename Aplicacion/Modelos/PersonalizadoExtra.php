<?php

namespace Aplicacion\Modelos;

class PersonalizadoExtra
{
    private ?int $idPersonalizadoExtra;
    private ?int $idPersonalizado;
    private string $concepto;
    private float $monto;

    public function __construct(?int $idPersonalizadoExtra, ?int $idPersonalizado, string $concepto, float $monto)
    {
        $this->idPersonalizadoExtra = $idPersonalizadoExtra;
        $this->idPersonalizado = $idPersonalizado;
        $this->concepto = $concepto;
        $this->monto = $monto;
    }

    public function getIdPersonalizadoExtra(): ?int
    {
        return $this->idPersonalizadoExtra;
    }

    public function getIdPersonalizado(): ?int
    {
        return $this->idPersonalizado;
    }

    public function getConcepto(): string
    {
        return $this->concepto;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }
}