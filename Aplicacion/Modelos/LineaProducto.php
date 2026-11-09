<?php

namespace Aplicacion\Modelos;

class LineaProducto
{
    private ?int $idLinea;
    private ?int $idVendedor;
    private string $nombre;
    private ?string $descripcion;
    private bool $activo;
    private int $cantidadProveedores;
    private int $cantidadProductos;

    public function __construct(
        ?int $idLinea = null,
        ?int $idVendedor = null,
        string $nombre = '',
        ?string $descripcion = null,
        bool $activo = true,
        int $cantidadProveedores = 0,
        int $cantidadProductos = 0
    ) {
        $this->idLinea = $idLinea;
        $this->idVendedor = $idVendedor;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->activo = $activo;
        $this->cantidadProveedores = $cantidadProveedores;
        $this->cantidadProductos = $cantidadProductos;
    }

    public function getIdLinea(): ?int
    {
        return $this->idLinea;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    public function getCantidadProveedores(): int
    {
        return $this->cantidadProveedores;
    }

    public function getCantidadProductos(): int
    {
        return $this->cantidadProductos;
    }

    public function setIdLinea(?int $idLinea): void
    {
        $this->idLinea = $idLinea;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }
}