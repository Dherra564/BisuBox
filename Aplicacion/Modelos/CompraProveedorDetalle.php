<?php

namespace Aplicacion\Modelos;

class CompraProveedorDetalle
{
    private ?int $idDetalle;
    private ?int $idCompraProveedor;
    private ?int $idComponente;
    private float $cantidad;
    private float $costoUnitario;
    private float $subtotal;
    private float $costoFinal;
    private string $nombreComponente;
    private string $nombreTipo;
    private string $unidad;

    public function __construct(
        ?int $idDetalle,
        ?int $idCompraProveedor,
        ?int $idComponente,
        float $cantidad,
        float $costoUnitario,
        float $subtotal,
        float $costoFinal,
        string $nombreComponente = '',
        string $nombreTipo = '',
        string $unidad = ''
    ) {
        $this->idDetalle = $idDetalle;
        $this->idCompraProveedor = $idCompraProveedor;
        $this->idComponente = $idComponente;
        $this->cantidad = $cantidad;
        $this->costoUnitario = $costoUnitario;
        $this->subtotal = $subtotal;
        $this->costoFinal = $costoFinal;
        $this->nombreComponente = $nombreComponente;
        $this->nombreTipo = $nombreTipo;
        $this->unidad = $unidad;
    }

    public function getIdDetalle(): ?int
    {
        return $this->idDetalle;
    }

    public function getIdCompraProveedor(): ?int
    {
        return $this->idCompraProveedor;
    }

    public function getIdComponente(): ?int
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

    public function getCostoFinal(): float
    {
        return $this->costoFinal;
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

    public function setIdDetalle(?int $idDetalle): void
    {
        $this->idDetalle = $idDetalle;
    }

    public function setIdCompraProveedor(?int $idCompraProveedor): void
    {
        $this->idCompraProveedor = $idCompraProveedor;
    }

    public function setIdComponente(?int $idComponente): void
    {
        $this->idComponente = $idComponente;
    }
}