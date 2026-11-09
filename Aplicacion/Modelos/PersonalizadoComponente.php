<?php

namespace Aplicacion\Modelos;

class PersonalizadoComponente
{
    private ?int $idPersonalizadoComponente;
    private ?int $idPersonalizado;
    private int $idComponente;
    private int $idComponenteOriginal;
    private float $cantidad;
    private float $costoUnitario;

    private string $nombre = '';
    private string $nombreOriginal = '';
    private string $nombreTipo = '';
    private string $unidad = '';
    private ?string $foto = null;
    private ?string $fotoOriginal = null;
    private float $existencia = 0;
    private float $costoActual = 0;

    public function __construct(
        ?int $idPersonalizadoComponente,
        ?int $idPersonalizado,
        int $idComponente,
        int $idComponenteOriginal,
        float $cantidad,
        float $costoUnitario
    ) {
        $this->idPersonalizadoComponente = $idPersonalizadoComponente;
        $this->idPersonalizado = $idPersonalizado;
        $this->idComponente = $idComponente;
        $this->idComponenteOriginal = $idComponenteOriginal;
        $this->cantidad = $cantidad;
        $this->costoUnitario = $costoUnitario;
    }

    public function getIdPersonalizadoComponente(): ?int
    {
        return $this->idPersonalizadoComponente;
    }

    public function getIdPersonalizado(): ?int
    {
        return $this->idPersonalizado;
    }

    public function getIdComponente(): int
    {
        return $this->idComponente;
    }

    public function getIdComponenteOriginal(): int
    {
        return $this->idComponenteOriginal;
    }

    public function fueCambiado(): bool
    {
        return $this->idComponente !== $this->idComponenteOriginal;
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
        return round($this->cantidad * $this->costoUnitario, 2);
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getNombreOriginal(): string
    {
        return $this->nombreOriginal;
    }

    public function getNombreTipo(): string
    {
        return $this->nombreTipo;
    }

    public function getUnidad(): string
    {
        return $this->unidad;
    }

    public function getFoto(): ?string
    {
        return $this->foto;
    }

    public function getFotoOriginal(): ?string
    {
        return $this->fotoOriginal;
    }

    public function getExistencia(): float
    {
        return $this->existencia;
    }

    public function getCostoActual(): float
    {
        return $this->costoActual;
    }

    public function setIdPersonalizadoComponente(?int $idPersonalizadoComponente): void
    {
        $this->idPersonalizadoComponente = $idPersonalizadoComponente;
    }

    public function setIdPersonalizado(?int $idPersonalizado): void
    {
        $this->idPersonalizado = $idPersonalizado;
    }

    public function setCostoUnitario(float $costoUnitario): void
    {
        $this->costoUnitario = $costoUnitario;
    }

    public function setDatosComponente(
        string $nombre,
        string $nombreOriginal,
        string $nombreTipo,
        string $unidad,
        ?string $foto,
        ?string $fotoOriginal,
        float $existencia,
        float $costoActual
    ): void {
        $this->nombre = $nombre;
        $this->nombreOriginal = $nombreOriginal;
        $this->nombreTipo = $nombreTipo;
        $this->unidad = $unidad;
        $this->foto = $foto;
        $this->fotoOriginal = $fotoOriginal;
        $this->existencia = $existencia;
        $this->costoActual = $costoActual;
    }
}