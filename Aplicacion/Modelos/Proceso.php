<?php

namespace Aplicacion\Modelos;

use Aplicacion\Modelos\ProcesoComponente;
use Aplicacion\Modelos\ProcesoExtra;
use DateTime;

class Proceso
{
    public const REGISTRADO = 'Registrado';
    public const ANULADO = 'Anulado';

    private ?int $idProceso;
    private ?int $idVendedor;
    private string $nombre;
    private ?string $descripcion;
    private DateTime $fecha;
    private int $minutos;
    private float $tarifaHora;
    private float $costoComponentes;
    private float $costoManoObra;
    private float $costoExtras;
    private float $precio;
    private string $estado;
    private ?DateTime $fechaRegistro;
    /** @var ProcesoComponente[] */
    private array $componentes;
    /** @var ProcesoExtra[] */
    private array $extras;
    private int $cantidadComponentes;

    /**
     * @param ProcesoComponente[] $componentes
     * @param ProcesoExtra[] $extras
     */
    public function __construct(
        ?int $idProceso,
        ?int $idVendedor,
        string $nombre,
        ?string $descripcion,
        DateTime $fecha,
        int $minutos,
        float $tarifaHora,
        float $costoComponentes,
        float $costoManoObra,
        float $costoExtras,
        float $precio,
        string $estado = self::REGISTRADO,
        ?DateTime $fechaRegistro = null,
        array $componentes = [],
        array $extras = [],
        int $cantidadComponentes = 0
    ) {
        $this->idProceso = $idProceso;
        $this->idVendedor = $idVendedor;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->fecha = $fecha;
        $this->minutos = $minutos;
        $this->tarifaHora = $tarifaHora;
        $this->costoComponentes = $costoComponentes;
        $this->costoManoObra = $costoManoObra;
        $this->costoExtras = $costoExtras;
        $this->precio = $precio;
        $this->estado = $estado;
        $this->fechaRegistro = $fechaRegistro;
        $this->componentes = $componentes;
        $this->extras = $extras;
        $this->cantidadComponentes = $cantidadComponentes !== 0 ? $cantidadComponentes : count($componentes);
    }

    public function getIdProceso(): ?int
    {
        return $this->idProceso;
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

    public function getFecha(): DateTime
    {
        return $this->fecha;
    }

    public function getMinutos(): int
    {
        return $this->minutos;
    }

    public function getTarifaHora(): float
    {
        return $this->tarifaHora;
    }

    public function getCostoComponentes(): float
    {
        return $this->costoComponentes;
    }

    public function getCostoManoObra(): float
    {
        return $this->costoManoObra;
    }

    public function getCostoExtras(): float
    {
        return $this->costoExtras;
    }

    public function getPrecio(): float
    {
        return $this->precio;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function estaAnulado(): bool
    {
        return $this->estado === self::ANULADO;
    }

    public function getFechaRegistro(): ?DateTime
    {
        return $this->fechaRegistro;
    }

    /** @return ProcesoComponente[] */
    public function getComponentes(): array
    {
        return $this->componentes;
    }

    /** @return ProcesoExtra[] */
    public function getExtras(): array
    {
        return $this->extras;
    }

    public function getCantidadComponentes(): int
    {
        return $this->cantidadComponentes;
    }

    public function setIdProceso(?int $idProceso): void
    {
        $this->idProceso = $idProceso;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }
}