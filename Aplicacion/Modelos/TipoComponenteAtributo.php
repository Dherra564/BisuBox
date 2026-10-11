<?php

namespace Aplicacion\Modelos;

use Aplicacion\Nucleo\ClaseDato;

class TipoComponenteAtributo
{
    private ?int $idAtributo;
    private ?int $idTipoComponente;
    private string $nombre;
    private string $clase;
    private ?string $unidad;
    private bool $obligatorio;
    private int $orden;
    private bool $activo;
    /** @var TipoComponenteAtributoOpcion[] */
    private array $opciones;
    private bool $tieneValores;

    /** @param TipoComponenteAtributoOpcion[] $opciones */
    public function __construct(
        ?int $idAtributo,
        ?int $idTipoComponente,
        string $nombre,
        string $clase,
        ?string $unidad = null,
        bool $obligatorio = false,
        int $orden = 0,
        bool $activo = true,
        array $opciones = [],
        bool $tieneValores = false
    ) {
        $this->idAtributo = $idAtributo;
        $this->idTipoComponente = $idTipoComponente;
        $this->nombre = $nombre;
        $this->clase = $clase;
        $this->unidad = $unidad;
        $this->obligatorio = $obligatorio;
        $this->orden = $orden;
        $this->activo = $activo;
        $this->opciones = $opciones;
        $this->tieneValores = $tieneValores;
    }

    public function getIdAtributo(): ?int
    {
        return $this->idAtributo;
    }

    public function getIdTipoComponente(): ?int
    {
        return $this->idTipoComponente;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getClase(): string
    {
        return $this->clase;
    }

    public function getUnidad(): ?string
    {
        return $this->clase === ClaseDato::NUMERO ? $this->unidad : null;
    }

    public function getObligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    /** @return TipoComponenteAtributoOpcion[]*/
    public function getOpciones(): array
    {
        return $this->clase === ClaseDato::LISTA ? $this->opciones : [];
    }

    /** @return TipoComponenteAtributoOpcion[] */
    public function getOpcionesActivas(): array
    {
        return array_values(array_filter($this->getOpciones(), fn(TipoComponenteAtributoOpcion $opcion): bool => $opcion->getActivo()));
    }

    public function getTieneValores(): bool
    {
        return $this->tieneValores;
    }

    public function setIdAtributo(?int $idAtributo): void
    {
        $this->idAtributo = $idAtributo;
    }

    public function setIdTipoComponente(?int $idTipoComponente): void
    {
        $this->idTipoComponente = $idTipoComponente;
    }

    public function setOrden(int $orden): void
    {
        $this->orden = $orden;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }
}