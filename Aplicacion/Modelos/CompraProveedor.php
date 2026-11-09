<?php

namespace Aplicacion\Modelos;

use DateTime;

class CompraProveedor
{
    public const POR_RECIBIR = 'Por recibir';
    public const RECIBIDA = 'Recibida';
    public const ANULADA = 'Anulada';

    public const CONTADO = 'Contado';
    public const CREDITO = 'Crédito';

    public const PAGADA = 'Pagada';
    public const POR_PAGAR = 'Por pagar';

    private ?int $idCompraProveedor;
    private ?int $idVendedor;
    private int $idProveedor;
    private DateTime $fecha;
    private float $subtotal;
    private float $costoEnvio;
    private float $total;
    private string $estado;
    private ?DateTime $fechaRegistro;
    private array $detalles;
    private string $nombreProveedor;
    private int $cantidadLineas;
    private ?DateTime $fechaRecepcion;
    private string $condicion;
    private ?DateTime $vencimiento;
    private float $saldo;
    private string $estadoPago;

    public function __construct(
        ?int $idCompraProveedor,
        ?int $idVendedor,
        int $idProveedor,
        DateTime $fecha,
        float $subtotal,
        float $costoEnvio,
        float $total,
        string $estado = self::RECIBIDA,
        ?DateTime $fechaRegistro = null,
        array $detalles = [],
        string $nombreProveedor = '',
        int $cantidadLineas = 0,
        ?DateTime $fechaRecepcion = null,
        string $condicion = self::CONTADO,
        ?DateTime $vencimiento = null,
        float $saldo = 0.0,
        string $estadoPago = self::PAGADA
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
        $this->fechaRecepcion = $fechaRecepcion;
        $this->condicion = $condicion;
        $this->vencimiento = $vencimiento;
        $this->saldo = $saldo;
        $this->estadoPago = $estadoPago;
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

    public function estaRecibida(): bool
    {
        return $this->estado === self::RECIBIDA;
    }

    public function estaPorRecibir(): bool
    {
        return $this->estado === self::POR_RECIBIR;
    }

    public function getFechaRegistro(): ?DateTime
    {
        return $this->fechaRegistro;
    }

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

    public function getFechaRecepcion(): ?DateTime
    {
        return $this->fechaRecepcion;
    }

    public function getCondicion(): string
    {
        return $this->condicion;
    }

    public function esCredito(): bool
    {
        return $this->condicion === self::CREDITO;
    }

    public function getVencimiento(): ?DateTime
    {
        return $this->vencimiento;
    }

    public function getSaldo(): float
    {
        return $this->saldo;
    }

    public function getPagado(): float
    {
        return round($this->total - $this->saldo, 2);
    }

    public function getEstadoPago(): string
    {
        return $this->estadoPago;
    }

    public function debe(): bool
    {
        return !$this->estaAnulada() && $this->estadoPago === self::POR_PAGAR && $this->saldo > 0;
    }

    public function estaVencida(): bool
    {
        return $this->debe() && $this->vencimiento !== null && $this->vencimiento < new DateTime('today');
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