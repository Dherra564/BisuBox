<?php

namespace Aplicacion\Modelos;

class ProductoExtra
{
    private ?int $idProductoExtra;
    private ?int $idProducto;
    private string $concepto;
    private float $monto;

    public function __construct(?int $idProductoExtra, ?int $idProducto, string $concepto, float $monto)
    {
        $this->idProductoExtra = $idProductoExtra;
        $this->idProducto = $idProducto;
        $this->concepto = $concepto;
        $this->monto = $monto;
    }

    public function getIdProductoExtra(): ?int
    {
        return $this->idProductoExtra;
    }

    public function getIdProducto(): ?int
    {
        return $this->idProducto;
    }

    public function getConcepto(): string
    {
        return $this->concepto;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }

    public function setIdProductoExtra(?int $idProductoExtra): void
    {
        $this->idProductoExtra = $idProductoExtra;
    }

    public function setIdProducto(?int $idProducto): void
    {
        $this->idProducto = $idProducto;
    }
}