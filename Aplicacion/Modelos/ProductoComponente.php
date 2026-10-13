<?php

namespace Aplicacion\Modelos;

class ProductoComponente
{
    private ?int $idProductoComponente;
    private ?int $idProducto;
    private int $idComponente;
    private float $cantidad;
    private float $costoUnitario;
    private float $subtotal;
    private string $nombreComponente;
    private string $nombreTipo;
    private string $unidad;

    public function __construct(
        ?int $idProductoComponente,
        ?int $idProducto,
        int $idComponente,
        float $cantidad,
        float $costoUnitario,
        float $subtotal,
        string $nombreComponente = '',
        string $nombreTipo = '',
        string $unidad = ''
    ) {
        $this->idProductoComponente = $idProductoComponente;
        $this->idProducto = $idProducto;
        $this->idComponente = $idComponente;
        $this->cantidad = $cantidad;
        $this->costoUnitario = $costoUnitario;
        $this->subtotal = $subtotal;
        $this->nombreComponente = $nombreComponente;
        $this->nombreTipo = $nombreTipo;
        $this->unidad = $unidad;
    }

    public function getIdProductoComponente(): ?int
    {
        return $this->idProductoComponente;
    }

    public function getIdProducto(): ?int
    {
        return $this->idProducto;
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

    public function setIdProductoComponente(?int $idProductoComponente): void
    {
        $this->idProductoComponente = $idProductoComponente;
    }

    public function setIdProducto(?int $idProducto): void
    {
        $this->idProducto = $idProducto;
    }
}