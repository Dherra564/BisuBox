<?php

namespace Aplicacion\Modelos;

use Aplicacion\Modelos\ProductoComponente;
use Aplicacion\Modelos\ProductoExtra;
use DateTime;

class Producto
{
    private ?int $idProducto;
    private ?int $idVendedor;
    private string $nombre;
    private ?string $descripcion;
    private ?string $foto;
    private int $minutos;
    private float $tarifaHora;
    private float $costoComponentes;
    private float $costoManoObra;
    private float $costoExtras;
    private float $costoTotal;
    private float $precioVenta;
    private int $existencia;
    private bool $activo;
    private ?DateTime $fechaRegistro;
    /** @var ProductoComponente[] */
    private array $componentes;
    /** @var ProductoExtra[] */
    private array $extras;

    /**
     * @param ProductoComponente[] $componentes
     * @param ProductoExtra[] $extras
     */
    public function __construct(
        ?int $idProducto,
        ?int $idVendedor,
        string $nombre,
        ?string $descripcion,
        ?string $foto,
        int $minutos,
        float $tarifaHora,
        float $costoComponentes,
        float $costoManoObra,
        float $costoExtras,
        float $costoTotal,
        float $precioVenta,
        int $existencia = 0,
        bool $activo = true,
        ?DateTime $fechaRegistro = null,
        array $componentes = [],
        array $extras = []
    ) {
        $this->idProducto = $idProducto;
        $this->idVendedor = $idVendedor;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->foto = $foto;
        $this->minutos = $minutos;
        $this->tarifaHora = $tarifaHora;
        $this->costoComponentes = $costoComponentes;
        $this->costoManoObra = $costoManoObra;
        $this->costoExtras = $costoExtras;
        $this->costoTotal = $costoTotal;
        $this->precioVenta = $precioVenta;
        $this->existencia = $existencia;
        $this->activo = $activo;
        $this->fechaRegistro = $fechaRegistro;
        $this->componentes = $componentes;
        $this->extras = $extras;
    }

    public function getIdProducto(): ?int
    {
        return $this->idProducto;
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

    public function getFoto(): ?string
    {
        return $this->foto;
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

    public function getCostoTotal(): float
    {
        return $this->costoTotal;
    }

    public function getPrecioVenta(): float
    {
        return $this->precioVenta;
    }

    public function getGanancia(): float
    {
        return round($this->precioVenta - $this->costoTotal, 2);
    }

    public function getPorcentajeGanancia(): ?float
    {
        return $this->costoTotal > 0 ? round($this->getGanancia() / $this->costoTotal * 100, 1) : null;
    }

    public function getExistencia(): int
    {
        return $this->existencia;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    public function getFechaRegistro(): ?DateTime
    {
        return $this->fechaRegistro;
    }

    /** @return ProductoComponente[] */
    public function getComponentes(): array
    {
        return $this->componentes;
    }

    /** @return ProductoExtra[] */
    public function getExtras(): array
    {
        return $this->extras;
    }

    public function setIdProducto(?int $idProducto): void
    {
        $this->idProducto = $idProducto;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setFoto(?string $foto): void
    {
        $this->foto = $foto;
    }

    public function setPrecioVenta(float $precioVenta): void
    {
        $this->precioVenta = $precioVenta;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }
}