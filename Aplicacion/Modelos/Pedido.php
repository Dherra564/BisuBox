<?php
namespace Aplicacion\Modelos;

use Aplicacion\Nucleo\EstadoPedido;
use Aplicacion\Nucleo\TipoPedido;
use DateTime;

class Pedido
{
    private ?int $idPedido;
    private int $idVendedor;
    private int $idCliente;
    private DateTime $fecha;
    private string $entrega;
    private ?string $direccion;
    private string $metodoPago;
    private ?string $nota;
    private float $total;
    private string $estado;
    private ?string $canceladoPor;
    private ?DateTime $fechaActualizacion;
    /** @var PedidoDetalle[] */
    private array $detalles;

    private string $nombreTienda    = '';
    private string $enlaceTienda    = '';
    private string $nombreCliente   = '';
    private string $telefonoCliente = '';
    private string $correoCliente   = '';
    private string $tipo            = TipoPedido::NORMAL;

    /** @param PedidoDetalle[] $detalles */
    public function __construct(
        ?int $idPedido,
        int $idVendedor,
        int $idCliente,
        DateTime $fecha,
        string $entrega,
        ?string $direccion,
        string $metodoPago,
        ?string $nota,
        float $total,
        string $estado = EstadoPedido::PENDIENTE,
        ?string $canceladoPor = null,
        ?DateTime $fechaActualizacion = null,
        array $detalles = []
    ) {
        $this->idPedido           = $idPedido;
        $this->idVendedor         = $idVendedor;
        $this->idCliente          = $idCliente;
        $this->fecha              = $fecha;
        $this->entrega            = $entrega;
        $this->direccion          = $direccion;
        $this->metodoPago         = $metodoPago;
        $this->nota               = $nota;
        $this->total              = $total;
        $this->estado             = $estado;
        $this->canceladoPor       = $canceladoPor;
        $this->fechaActualizacion = $fechaActualizacion;
        $this->detalles           = $detalles;
    }

    public function getIdPedido(): ?int
    {
        return $this->idPedido;
    }

    public function getIdVendedor(): int
    {
        return $this->idVendedor;
    }

    public function getIdCliente(): int
    {
        return $this->idCliente;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
    }

    public function getEntrega(): string
    {
        return $this->entrega;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function getMetodoPago(): string
    {
        return $this->metodoPago;
    }

    public function getNota(): ?string
    {
        return $this->nota;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getCanceladoPor(): ?string
    {
        return $this->canceladoPor;
    }

    public function getFechaActualizacion(): ?DateTime
    {
        return $this->fechaActualizacion;
    }

    /** @return PedidoDetalle[] */
    public function getDetalles(): array
    {
        return $this->detalles;
    }

    public function getUnidades(): int
    {
        return array_sum(array_map(fn(PedidoDetalle $detalle): int => $detalle->getCantidad(), $this->detalles));
    }

    public function getNombreTienda(): string
    {
        return $this->nombreTienda;
    }

    public function getEnlaceTienda(): string
    {
        return $this->enlaceTienda;
    }

    public function getNombreCliente(): string
    {
        return $this->nombreCliente;
    }

    public function getTelefonoCliente(): string
    {
        return $this->telefonoCliente;
    }

    public function getCorreoCliente(): string
    {
        return $this->correoCliente;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function esPersonalizado(): bool
    {
        return $this->tipo === TipoPedido::PERSONALIZADO;
    }

    public function estaPendiente(): bool
    {
        return $this->estado === EstadoPedido::PENDIENTE;
    }

    public function estaConfirmado(): bool
    {
        return $this->estado === EstadoPedido::CONFIRMADO;
    }

    public function setIdPedido(?int $idPedido): void
    {
        $this->idPedido = $idPedido;
    }

    public function setEstado(string $estado, ?string $canceladoPor = null): void
    {
        $this->estado             = $estado;
        $this->canceladoPor       = $canceladoPor;
        $this->fechaActualizacion = new DateTime();
    }

    /** @param PedidoDetalle[] $detalles */
    public function setDetalles(array $detalles): void
    {
        $this->detalles = $detalles;
    }

    public function setDatosTienda(string $nombreTienda, string $enlaceTienda): void
    {
        $this->nombreTienda = $nombreTienda;
        $this->enlaceTienda = $enlaceTienda;
    }

    public function setDatosCliente(string $nombre, string $telefono, string $correo): void
    {
        $this->nombreCliente   = $nombre;
        $this->telefonoCliente = $telefono;
        $this->correoCliente   = $correo;
    }

    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }
}