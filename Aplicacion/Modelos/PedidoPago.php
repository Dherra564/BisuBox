<?php

namespace Aplicacion\Modelos;

use Aplicacion\Nucleo\EstadoPago;
use DateTime;

class PedidoPago
{
    private ?int $idPedidoPago;
    private ?int $idPedido;
    private string $numeroSinpe;
    private string $codigo;
    private string $referencia;
    private string $comprobante;
    private float $monto;
    private string $estado;
    private ?string $motivo;
    private int $intentos;
    private DateTime $fechaRegistro;
    private ?DateTime $fechaRevision;

    public function __construct(
        ?int $idPedidoPago,
        ?int $idPedido,
        string $numeroSinpe,
        string $codigo,
        string $referencia,
        string $comprobante,
        float $monto,
        string $estado = EstadoPago::POR_VERIFICAR,
        ?string $motivo = null,
        int $intentos = 1,
        ?DateTime $fechaRegistro = null,
        ?DateTime $fechaRevision = null
    ) {
        $this->idPedidoPago = $idPedidoPago;
        $this->idPedido = $idPedido;
        $this->numeroSinpe = $numeroSinpe;
        $this->codigo = $codigo;
        $this->referencia = $referencia;
        $this->comprobante = $comprobante;
        $this->monto = round($monto, 2);
        $this->estado = $estado;
        $this->motivo = $motivo;
        $this->intentos = $intentos;
        $this->fechaRegistro = $fechaRegistro ?? new DateTime();
        $this->fechaRevision = $fechaRevision;
    }

    public function getIdPedidoPago(): ?int
    {
        return $this->idPedidoPago;
    }

    public function getIdPedido(): ?int
    {
        return $this->idPedido;
    }

    public function getNumeroSinpe(): string
    {
        return $this->numeroSinpe;
    }

    public function getNumeroSinpeBonito(): string
    {
        return strlen($this->numeroSinpe) === 8 ? substr($this->numeroSinpe, 0, 4) . '-' . substr($this->numeroSinpe, 4) : $this->numeroSinpe;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getReferencia(): string
    {
        return $this->referencia;
    }

    public function getComprobante(): string
    {
        return $this->comprobante;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function getIntentos(): int
    {
        return $this->intentos;
    }

    public function getFechaRegistro(): DateTime
    {
        return $this->fechaRegistro;
    }

    public function getFechaRevision(): ?DateTime
    {
        return $this->fechaRevision;
    }

    public function estaPorVerificar(): bool
    {
        return $this->estado === EstadoPago::POR_VERIFICAR;
    }

    public function estaRecibido(): bool
    {
        return $this->estado === EstadoPago::RECIBIDO;
    }

    public function tieneProblema(): bool
    {
        return $this->estado === EstadoPago::CON_PROBLEMA;
    }

    public function setIdPedidoPago(?int $idPedidoPago): void
    {
        $this->idPedidoPago = $idPedidoPago;
    }

    public function setIdPedido(?int $idPedido): void
    {
        $this->idPedido = $idPedido;
    }
}