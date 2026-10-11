<?php
class Proceso
{
    private $id;
    private $vendedorId;
    private $nombre;
    private $descripcion;
    private $tiempoEstimado;
    private $costoManoObra;
    private $activo;

    public function getId()
    {return $this->id;}
    public function setId($id)
    {$this->id = $id;}

    public function getVendedorId()
    {return $this->vendedorId;}
    public function setVendedorId($vendedorId)
    {$this->vendedorId = $vendedorId;}

    public function getNombre()
    {return $this->nombre;}
    public function setNombre($nombre)
    {$this->nombre = $nombre;}

    public function getDescripcion()
    {return $this->descripcion;}
    public function setDescripcion($descripcion)
    {$this->descripcion = $descripcion;}

    public function getTiempoEstimado()
    {return $this->tiempoEstimado;}
    public function setTiempoEstimado($tiempoEstimado)
    {$this->tiempoEstimado = $tiempoEstimado;}

    public function getCostoManoObra()
    {return $this->costoManoObra;}
    public function setCostoManoObra($costoManoObra)
    {$this->costoManoObra = $costoManoObra;}

    public function getActivo()
    {return $this->activo;}
    public function setActivo($activo)
    {$this->activo = $activo;}
}
