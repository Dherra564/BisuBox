<?php

namespace Aplicacion\Modelos;

use Aplicacion\Nucleo\EstadoExistencia;

class Componente
{
    private ?int $idComponente;
    private ?int $idVendedor;
    private int $idTipoComponente;
    private string $nombre;
    private string $unidad;
    private float $existencia;
    private float $costoUnitario;
    private float $existenciaMinima;
    private bool $activo;
    /** @var array<int, string>*/
    private array $valores;
    private string $nombreTipo;

    /** @param array<int, string> $valores */
    public function __construct(
        ?int $idComponente,
        ?int $idVendedor,
        int $idTipoComponente,
        string $nombre,
        string $unidad,
        float $existencia = 0,
        float $costoUnitario = 0,
        float $existenciaMinima = 0,
        bool $activo = true,
        array $valores = [],
        string $nombreTipo = ''
    ) {
        $this->idComponente = $idComponente;
        $this->idVendedor = $idVendedor;
        $this->idTipoComponente = $idTipoComponente;
        $this->nombre = $nombre;
        $this->unidad = $unidad;
        $this->existencia = $existencia;
        $this->costoUnitario = $costoUnitario;
        $this->existenciaMinima = $existenciaMinima;
        $this->activo = $activo;
        $this->valores = $valores;
        $this->nombreTipo = $nombreTipo;
    }

    public function getIdComponente(): ?int
    {
        return $this->idComponente;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getIdTipoComponente(): int
    {
        return $this->idTipoComponente;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getUnidad(): string
    {
        return $this->unidad;
    }

    public function getExistencia(): float
    {
        return $this->existencia;
    }

    public function getCostoUnitario(): float
    {
        return $this->costoUnitario;
    }

    public function getExistenciaMinima(): float
    {
        return $this->existenciaMinima;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    /** @return array<int, string> */
    public function getValores(): array
    {
        return $this->valores;
    }

    public function getValor(int $idAtributo): string
    {
        return $this->valores[$idAtributo] ?? '';
    }

    public function getNombreTipo(): string
    {
        return $this->nombreTipo;
    }

    public function getValorTotal(): float
    {
        return $this->existencia * $this->costoUnitario;
    }

    public function getEstadoExistencia(): string
    {
        return EstadoExistencia::calcular($this->existencia, $this->existenciaMinima);
    }

    public function setIdComponente(?int $idComponente): void
    {
        $this->idComponente = $idComponente;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setUnidad(string $unidad): void
    {
        $this->unidad = $unidad;
    }

    public function setExistenciaMinima(float $existenciaMinima): void
    {
        $this->existenciaMinima = $existenciaMinima;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    /** @param array<int, string> $valores */
    public function setValores(array $valores): void
    {
        $this->valores = $valores;
    }
}