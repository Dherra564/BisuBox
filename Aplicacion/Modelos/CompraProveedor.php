<?php

namespace Aplicacion\Modelos;

use DateTime;

class CompraProveedor
{
    public const REGISTRADA = 'Registrada';
    public const ANULADA = 'Anulada';

    private ?int $idCompraProveedor;
    private ?int $idVendedor;
    private int $idProveedor;
    private DateTime $fecha;
    private float $subtotal;
    private float $costoEnvio;
    private float $total;
    private string $estado;
    private ?DateTime $fechaRegistro;
    /** @var CompraProveedorDetalle[] */
    private array $detalles;
    private string $nombreProveedor;
    private int $cantidadLineas;

    /** @param CompraProveedorDetalle[] $detalles */
    public function __construct(
        ?int $idCompraProveedor,
        ?int $idVendedor,
        int $idProveedor,
        DateTime $fecha,
        float $subtotal,
        float $costoEnvio,
        float $total,
        string $estado = self::REGISTRADA,
        ?DateTime $fechaRegistro = null,
        array $detalles = [],
        string $nombreProveedor = '',
        int $cantidadLineas = 0
    ) {
        $this->idCompraProveedor = $idCompraProveedor;
        $this->idVendedor = $idVendedor;
        $this->idProveedor = $idProveedor;
        $this->fecha = $fecha;
        $this->subtotal = $subtotal;
        $this->costoEnvio = $costoEnvio;
        $this->total = $total;
        $this->estado = $estado;
        $this->fechaRegistro = $fechaRegistro;
        $this->detalles = $detalles;
        $this->nombreProveedor = $nombreProveedor;
        $this->cantidadLineas = $cantidadLineas !== 0 ? $cantidadLineas : count($detalles);
    }

    public function getIdCompraProveedor(): ?int
    {
        return $this->idCompraProveedor;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getIdProveedor(): int
    {
        return $this->idProveedor;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getCostoEnvio(): float
    {
        return $this->costoEnvio;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function estaAnulada(): bool
    {
        return $this->estado === self::ANULADA;
    }

    public function getFechaRegistro(): ?DateTime
    {
        return $this->fechaRegistro;
    }

    /** @return CompraProveedorDetalle[] */
    public function getDetalles(): array
    {
        return $this->detalles;
    }

    public function getNombreProveedor(): string
    {
        return $this->nombreProveedor;
    }

    public function getCantidadLineas(): int
    {
        return $this->cantidadLineas;
    }

    public function setIdCompraProveedor(?int $idCompraProveedor): void
    {
        $this->idCompraProveedor = $idCompraProveedor;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }
}