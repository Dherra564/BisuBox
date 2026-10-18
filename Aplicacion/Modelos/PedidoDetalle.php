<?php

namespace Aplicacion\Modelos;

class PedidoDetalle
{
    private ?int $idPedidoDetalle;
    private ?int $idPedido;
    private int $idProducto;
    private string $nombreProducto;
    private int $cantidad;
    private float $precio;
    private float $subtotal;
    private ?int $idPersonalizado;

    public function __construct(
        ?int $idPedidoDetalle,
        ?int $idPedido,
        int $idProducto,
        string $nombreProducto,
        int $cantidad,
        float $precio,
        ?float $subtotal = null,
        ?int $idPersonalizado = null
    ) {
        $this->idPedidoDetalle = $idPedidoDetalle;
        $this->idPedido = $idPedido;
        $this->idProducto = $idProducto;
        $this->nombreProducto = $nombreProducto;
        $this->cantidad = $cantidad;
        $this->precio = $precio;
        $this->subtotal = $subtotal ?? round($cantidad * $precio, 2);
        $this->idPersonalizado = $idPersonalizado;
    }

    public function getIdPedidoDetalle(): ?int
    {
        return $this->idPedidoDetalle;
    }

    public function getIdPedido(): ?int
    {
        return $this->idPedido;
    }

    public function getIdProducto(): int
    {
        return $this->idProducto;
    }

    public function getNombreProducto(): string
    {
        return $this->nombreProducto;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getPrecio(): float
    {
        return $this->precio;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getIdPersonalizado(): ?int
    {
        return $this->idPersonalizado;
    }

    public function esPersonalizado(): bool
    {
        return $this->idPersonalizado !== null;
    }

    public function setIdPedidoDetalle(?int $idPedidoDetalle): void
    {
        $this->idPedidoDetalle = $idPedidoDetalle;
    }

    public function setIdPedido(?int $idPedido): void
    {
        $this->idPedido = $idPedido;
    }
}