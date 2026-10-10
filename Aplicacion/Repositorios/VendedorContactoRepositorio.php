<?php

namespace Aplicacion\Repositorios;

use Aplicacion\Modelos\VendedorContacto;
use Configuracion\BaseDatos;
use PDO;
use Throwable;

class VendedorContactoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /** @return VendedorContacto[] */
    public function listarPorVendedor(int $idVendedor): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM tbvendedorcontacto WHERE tbvendedorcontactovendedorid = ? ORDER BY tbvendedorcontactoid ASC'
        );
        $consulta->execute([$idVendedor]);

        return array_map(fn(array $fila): VendedorContacto => new VendedorContacto(
            (int) $fila['tbvendedorcontactoid'],
            (int) $fila['tbvendedorcontactovendedorid'],
            (string) $fila['tbvendedorcontactotipo'],
            (string) $fila['tbvendedorcontactovalor']
        ), $consulta->fetchAll());
    }

    // Borra los contactos que tenía la tienda y guarda la lista nueva, en el orden recibido
    /** @param VendedorContacto[] $contactos */
    public function reemplazar(int $idVendedor, array $contactos): void
    {
        $transaccionPropia = BaseDatos::iniciarTransaccion();

        try {
            $consulta = $this->conexion->prepare('DELETE FROM tbvendedorcontacto WHERE tbvendedorcontactovendedorid = ?');
            $consulta->execute([$idVendedor]);

            $insertar = $this->conexion->prepare(
                'INSERT INTO tbvendedorcontacto (tbvendedorcontactoid, tbvendedorcontactovendedorid,
                    tbvendedorcontactotipo, tbvendedorcontactovalor)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($contactos as $contacto) {
                $idContacto = BaseDatos::generarId('tbvendedorcontacto', 'tbvendedorcontactoid');
                $insertar->execute([$idContacto, $idVendedor, $contacto->getTipo(), $contacto->getValor()]);
                $contacto->setIdContacto($idContacto);
                $contacto->setIdVendedor($idVendedor);
            }

            if ($transaccionPropia) {
                BaseDatos::confirmarTransaccion();
            }
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                BaseDatos::revertirTransaccion();
            }
            throw $error;
        }
    }
}