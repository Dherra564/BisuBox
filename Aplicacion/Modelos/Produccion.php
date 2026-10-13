<?php

namespace Aplicacion\Modelos;

use Aplicacion\Modelos\ProduccionComponente;
use DateTime;

class Produccion
{
    public const ACTIVA = 'Activa';
    public const ANULADA = 'Anulada';

    private ?int $idProduccion;
    private ?int $idVendedor;
    private int $idProducto;
    private int $cantidad;
    private DateTime $fecha;
    private string $estado;
    private ?DateTime $fechaRegistro;
    /** @var ProduccionComponente[] */
    private array $componentes;

    /** @param ProduccionComponente[] $componentes*/
    public function __construct(
        ?int $idProduccion,
        ?int $idVendedor,
        int $idProducto,
        int $cantidad,
        DateTime $fecha,
        string $estado = self::ACTIVA,
        ?DateTime $fechaRegistro = null,
        array $componentes = []
    ) {
        $this->idProduccion = $idProduccion;
        $this->idVendedor = $idVendedor;
        $this->idProducto = $idProducto;
        $this->cantidad = $cantidad;
        $this->fecha = $fecha;
        $this->estado = $estado;
        $this->fechaRegistro = $fechaRegistro;
        $this->componentes = $componentes;
    }

    public function getIdProduccion(): ?int
    {
        return $this->idProduccion;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getIdProducto(): int
    {
        return $this->idProducto;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
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

    /** @return ProduccionComponente[] */
    public function getComponentes(): array
    {
        return $this->componentes;
    }

    public function setIdProduccion(?int $idProduccion): void
    {
        $this->idProduccion = $idProduccion;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }
}