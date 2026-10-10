<?php

namespace Aplicacion\Modelos;

// Un contacto de la tienda: un WhatsApp, una red social u otro enlace
class VendedorContacto
{
    private ?int $idContacto;
    private ?int $idVendedor;
    private string $tipo;
    private string $valor;

    public function __construct(?int $idContacto, ?int $idVendedor, string $tipo, string $valor)
    {
        $this->idContacto = $idContacto;
        $this->idVendedor = $idVendedor;
        $this->tipo = $tipo;
        $this->valor = $valor;
    }

    public function getIdContacto(): ?int
    {
        return $this->idContacto;
    }

    public function getIdVendedor(): ?int
    {
        return $this->idVendedor;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function setIdContacto(?int $idContacto): void
    {
        $this->idContacto = $idContacto;
    }

    public function setIdVendedor(?int $idVendedor): void
    {
        $this->idVendedor = $idVendedor;
    }
}