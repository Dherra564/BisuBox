<?php

namespace Aplicacion\Modelos;

class TipoComponente
{
    private ?int $idTipoComponente;
    private ?int $idVendedor;
    private string $nombre;
    private bool $activo;
    /** @var TipoComponenteAtributo[] */
    private array $atributos;
    private int $cantidadComponentes;

    /** @param TipoComponenteAtributo[] $atributos */
    public function __construct(
        ?int $idTipoComponente,
        ?int $idVendedor,
        string $nombre,
        bool $activo = true,
        array $atributos = [],
        int $cantidadComponentes = 0
    ) {
        $this->idTipoComponente = $idTipoComponente;
        $this->idVendedor = $idVendedor;
        $this->nombre = $nombre;
        $this->activo = $activo;
        $this->atributos = $atributos;
        $this->cantidadComponentes = $cantidadComponentes;
    }

    public function getIdTipoComponente(): ?int
    {
        return $this->idTipoComponente;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    /** @return TipoComponenteAtributo[] */
    public function getAtributos(): array
    {
        return $this->atributos;
    }

    /** @return TipoComponenteAtributo[]*/
    public function getAtributosActivos(): array
    {
        return array_values(array_filter($this->atributos, fn(TipoComponenteAtributo $atributo): bool => $atributo->getActivo()));
    }

    public function getCantidadComponentes(): int
    {
        return $this->cantidadComponentes;
    }

    public function setIdTipoComponente(?int $idTipoComponente): void
    {
        $this->idTipoComponente = $idTipoComponente;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    /** @param TipoComponenteAtributo[] $atributos */
    public function setAtributos(array $atributos): void
    {
        $this->atributos = $atributos;
    }
}