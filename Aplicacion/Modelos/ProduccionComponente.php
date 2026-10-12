<?php

namespace Aplicacion\Modelos;

class ProduccionComponente
{
    private ?int $idProduccionComponente;
    private ?int $idProduccion;
    private int $idComponente;
    private float $cantidad;
    private float $costoUnitario;

    // La cantidad es todo lo que se descontó del inventario en esa producción
    public function __construct(
        ?int $idProduccionComponente,
        ?int $idProduccion,
        int $idComponente,
        float $cantidad,
        float $costoUnitario
    ) {
        $this->idProduccionComponente = $idProduccionComponente;
        $this->idProduccion = $idProduccion;
        $this->idComponente = $idComponente;
        $this->cantidad = $cantidad;
        $this->costoUnitario = $costoUnitario;
    }

    public function getIdProduccionComponente(): ?int
    {
        return $this->idProduccionComponente;
    }

    public function getIdProduccion(): ?int
    {
        return $this->idProduccion;
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

    public function setIdProduccionComponente(?int $idProduccionComponente): void
    {
        $this->idProduccionComponente = $idProduccionComponente;
    }

    public function setIdProduccion(?int $idProduccion): void
    {
        $this->idProduccion = $idProduccion;
    }
}