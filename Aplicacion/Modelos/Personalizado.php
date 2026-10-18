<?php

namespace Aplicacion\Modelos;

use Aplicacion\Modelos\PersonalizadoComponente;
use Aplicacion\Nucleo\EstadoPersonalizado;
use Aplicacion\Nucleo\Numero;
use DateTime;

class Personalizado
{
    private ?int $idPersonalizado;
    private int $idVendedor;
    private int $idCliente;
    private int $idProducto;
    private string $nombre;
    private int $piezas;
    private ?string $notaCliente;
    private string $estado;
    private int $minutos;
    private float $tarifaHora;
    private float $costoComponentes;
    private float $costoManoObra;
    private float $costoExtras = 0;
    private float $costoTotal = 0;
    private ?int $ganancia = null;
    private ?float $precio = null;
    private ?float $total = null;
    private ?string $notaTienda = null;
    private ?string $canceladoPor = null;
    private bool $avisoVisto = true;
    private ?int $idPedido = null;
    private DateTime $fechaRegistro;
    private ?DateTime $fechaCotizado = null;
    private ?DateTime $fechaRespuesta = null;
    private array $componentes;
    private array $extras = [];

    private string $nombreTienda = '';
    private string $enlaceTienda = '';
    private string $nombreCliente = '';
    private string $telefonoCliente = '';
    private string $correoCliente = '';
    private ?string $fotoProducto = null;

    public function __construct(
        ?int $idPersonalizado,
        int $idVendedor,
        int $idCliente,
        int $idProducto,
        string $nombre,
        int $piezas,
        ?string $notaCliente,
        string $estado,
        int $minutos,
        float $tarifaHora,
        float $costoComponentes,
        float $costoManoObra,
        DateTime $fechaRegistro,
        array $componentes = []
    ) {
        $this->idPersonalizado = $idPersonalizado;
        $this->idVendedor = $idVendedor;
        $this->idCliente = $idCliente;
        $this->idProducto = $idProducto;
        $this->nombre = $nombre;
        $this->piezas = $piezas;
        $this->notaCliente = $notaCliente;
        $this->estado = $estado;
        $this->minutos = $minutos;
        $this->tarifaHora = $tarifaHora;
        $this->costoComponentes = $costoComponentes;
        $this->costoManoObra = $costoManoObra;
        $this->fechaRegistro = $fechaRegistro;
        $this->componentes = $componentes;
    }

    public function getIdPersonalizado(): ?int
    {
        return $this->idPersonalizado;
    }

    public function getIdVendedor(): int
    {
        return $this->idVendedor;
    }

    public function getIdCliente(): int
    {
        return $this->idCliente;
    }

    public function getIdProducto(): int
    {
        return $this->idProducto;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getPiezas(): int
    {
        return $this->piezas;
    }

    public function getNotaCliente(): ?string
    {
        return $this->notaCliente;
    }

    public function getEstado(): string
    {
        return $this->estado;
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

    public function getGanancia(): ?int
    {
        return $this->ganancia;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function getTotalComponentes(): float
    {
        return round($this->costoComponentes * $this->piezas, 2);
    }

    public function getNotaTienda(): ?string
    {
        return $this->notaTienda;
    }

    public function getCanceladoPor(): ?string
    {
        return $this->canceladoPor;
    }

    public function getAvisoVisto(): bool
    {
        return $this->avisoVisto;
    }

    public function getIdPedido(): ?int
    {
        return $this->idPedido;
    }

    public function getFechaRegistro(): DateTime
    {
        return $this->fechaRegistro;
    }

    public function getFechaCotizado(): ?DateTime
    {
        return $this->fechaCotizado;
    }

    public function getFechaRespuesta(): ?DateTime
    {
        return $this->fechaRespuesta;
    }

    public function getComponentes(): array
    {
        return $this->componentes;
    }

    public function getCambios(): array
    {
        return array_values(array_filter($this->componentes, fn(PersonalizadoComponente $linea): bool => $linea->fueCambiado()));
    }

    public function getExtras(): array
    {
        return $this->extras;
    }

    public function getNecesidades(): array
    {
        $necesidades = [];
        foreach ($this->componentes as $linea) {
            $id = $linea->getIdComponente();
            $necesidades[$id] = [
                'nombre' => $linea->getNombre(),
                'cantidad' => ($necesidades[$id]['cantidad'] ?? 0) + $linea->getCantidad() * $this->piezas,
                'existencia' => $linea->getExistencia(),
            ];
        }
        return $necesidades;
    }

    public function getFaltantes(): array
    {
        $faltantes = [];
        foreach ($this->getNecesidades() as $necesidad) {
            if ($necesidad['cantidad'] > $necesidad['existencia']) {
                $faltantes[] = '«' . $necesidad['nombre'] . '» (necesita ' . Numero::formatear($necesidad['cantidad'])
                    . ', quedan ' . Numero::formatear(max(0, $necesidad['existencia'])) . ')';
            }
        }
        return $faltantes;
    }

    public function alcanzaInventario(): bool
    {
        return $this->getFaltantes() === [];
    }

    public function estaEnRevision(): bool
    {
        return $this->estado === EstadoPersonalizado::EN_REVISION;
    }

    public function estaCotizado(): bool
    {
        return $this->estado === EstadoPersonalizado::COTIZADO;
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

    public function getFotoProducto(): ?string
    {
        return $this->fotoProducto;
    }

    public function setIdPersonalizado(?int $idPersonalizado): void
    {
        $this->idPersonalizado = $idPersonalizado;
    }

    public function setEstado(string $estado, ?string $canceladoPor = null): void
    {
        $this->estado = $estado;
        $this->canceladoPor = $canceladoPor;
        $this->fechaRespuesta = new DateTime();
    }

    public function setCotizacion(float $costoComponentes, float $costoExtras, ?int $ganancia, float $precio, ?string $notaTienda, array $extras): void
    {
        $this->costoComponentes = round($costoComponentes, 2);
        $this->costoExtras = round($costoExtras, 2);
        $this->costoTotal = round($this->costoComponentes + $this->costoManoObra + $this->costoExtras, 2);
        $this->ganancia = $ganancia;
        $this->precio = round($precio, 2);
        $this->total = round($precio * $this->piezas, 2);
        $this->notaTienda = $notaTienda;
        $this->extras = $extras;
        $this->fechaCotizado = new DateTime();
    }

    public function setNotaTienda(?string $notaTienda): void
    {
        $this->notaTienda = $notaTienda;
    }

    public function setIdPedido(?int $idPedido): void
    {
        $this->idPedido = $idPedido;
    }

    public function setGuardado(
        float $costoExtras,
        float $costoTotal,
        ?int $ganancia,
        ?float $precio,
        ?float $total,
        ?string $notaTienda,
        ?string $canceladoPor,
        bool $avisoVisto,
        ?int $idPedido,
        ?DateTime $fechaCotizado,
        ?DateTime $fechaRespuesta,
        array $extras
    ): void {
        $this->costoExtras = $costoExtras;
        $this->costoTotal = $costoTotal;
        $this->ganancia = $ganancia;
        $this->precio = $precio;
        $this->total = $total;
        $this->notaTienda = $notaTienda;
        $this->canceladoPor = $canceladoPor;
        $this->avisoVisto = $avisoVisto;
        $this->idPedido = $idPedido;
        $this->fechaCotizado = $fechaCotizado;
        $this->fechaRespuesta = $fechaRespuesta;
        $this->extras = $extras;
    }

    public function setComponentes(array $componentes): void
    {
        $this->componentes = $componentes;
    }

    public function setDatosTienda(string $nombreTienda, string $enlaceTienda): void
    {
        $this->nombreTienda = $nombreTienda;
        $this->enlaceTienda = $enlaceTienda;
    }

    public function setDatosCliente(string $nombre, string $telefono, string $correo): void
    {
        $this->nombreCliente = $nombre;
        $this->telefonoCliente = $telefono;
        $this->correoCliente = $correo;
    }

    public function setFotoProducto(?string $fotoProducto): void
    {
        $this->fotoProducto = $fotoProducto;
    }
}