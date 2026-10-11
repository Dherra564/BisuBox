<?php

namespace Aplicacion\Modelos;

class TipoComponenteAtributoOpcion
{
    private ?int $idOpcion;
    private ?int $idAtributo;
    private string $valor;
    private bool $activo;

    public function __construct(?int $idOpcion, ?int $idAtributo, string $valor, bool $activo = true)
    {
        $this->idOpcion = $idOpcion;
        $this->idAtributo = $idAtributo;
        $this->valor = $valor;
        $this->activo = $activo;
    }

    public function getIdOpcion(): ?int
    {
        return $this->idOpcion;
    }

    public function getIdAtributo(): ?int
    {
        return $this->idAtributo;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    public function setIdOpcion(?int $idOpcion): void
    {
        $this->idOpcion = $idOpcion;
    }

    public function setIdAtributo(?int $idAtributo): void
    {
        $this->idAtributo = $idAtributo;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }
}