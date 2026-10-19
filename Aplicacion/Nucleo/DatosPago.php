<?php

namespace Aplicacion\Nucleo;

use Aplicacion\Modelos\PedidoPago;
use Aplicacion\Modelos\Vendedor;

class DatosPago
{
    public const LARGO_CODIGO_PAGO = 5;
    public const LARGO_CODIGO_RETIRO = 6;

    private const CARACTERES = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const CLAVE_SESION = 'pagosSinpe';

    public static function metodosPara(Vendedor $tienda): array
    {
        $metodos = MetodoPago::todos();
        if (!$tienda->tieneSinpe()) {
            unset($metodos[MetodoPago::SINPE]);
        }
        return $metodos;
    }

    public static function codigoPago(int $idVendedor): string
    {
        $codigo = $_SESSION[self::CLAVE_SESION][$idVendedor] ?? null;
        if (!is_string($codigo) || !self::esCodigo($codigo, self::LARGO_CODIGO_PAGO)) {
            $codigo = self::generar(self::LARGO_CODIGO_PAGO);
            $_SESSION[self::CLAVE_SESION][$idVendedor] = $codigo;
        }
        return $codigo;
    }

    public static function olvidarCodigoPago(int $idVendedor): void
    {
        unset($_SESSION[self::CLAVE_SESION][$idVendedor]);
    }

    public static function generarCodigoRetiro(): string
    {
        return self::generar(self::LARGO_CODIGO_RETIRO);
    }

    public static function limpiarCodigo(string $codigo): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $codigo));
    }

    public static function limpiarSinpe(string $numero): string
    {
        return Validador::limpiarTelefono($numero);
    }

    public static function esSinpe(string $numero): bool
    {
        return preg_match('/^[5-8][0-9]{7}$/', $numero) === 1;
    }

    public static function validarComprobante(Validador $validador, string $referencia, ?string $comprobante): void
    {
        if ($referencia === '') {
            $validador->agregarError('referencia', 'Escriba los últimos 4 dígitos de la referencia del SINPE');
        } elseif (preg_match('/^[0-9]{4}$/', $referencia) !== 1) {
            $validador->agregarError('referencia', 'La referencia debe ser de 4 números, los últimos del comprobante');
        }
        if ($comprobante === null && $validador->error('comprobante') === null) {
            $validador->agregarError('comprobante', 'Suba la foto del comprobante del SINPE');
        }
    }

    public static function recibir(Vendedor $tienda, Validador $validador): array
    {
        $idVendedor = (int) $tienda->getIdVendedor();
        $enviado = is_string($_POST['codigoPago'] ?? null) ? self::limpiarCodigo($_POST['codigoPago']) : '';
        $guardado = $_SESSION[self::CLAVE_SESION][$idVendedor] ?? null;
        if (!is_string($guardado) || !hash_equals($guardado, $enviado)) {
            $validador->agregarError('codigoPago', 'El código del SINPE cambió porque la página estuvo abierta mucho tiempo. Haga el SINPE con el código nuevo.');
        }

        $referencia = is_string($_POST['referencia'] ?? null) ? trim($_POST['referencia']) : '';
        $comprobante = (new SubidaArchivo())->recibir('comprobante', SubidaArchivo::FOTO_COMPROBANTE, $validador);
        self::validarComprobante($validador, $referencia, $comprobante);

        return [
            'codigo' => is_string($guardado) ? $guardado : '',
            'referencia' => $referencia,
            'comprobante' => $comprobante,
        ];
    }

    public static function crearPago(Vendedor $tienda, float $monto, array $leido): PedidoPago
    {
        return new PedidoPago(
            null,
            null,
            (string) $tienda->getSinpe(),
            $leido['codigo'],
            $leido['referencia'],
            (string) $leido['comprobante'],
            $monto
        );
    }

    private static function esCodigo(string $codigo, int $largo): bool
    {
        return strlen($codigo) === $largo && strspn($codigo, self::CARACTERES) === $largo;
    }

    private static function generar(int $largo): string
    {
        $maximo = strlen(self::CARACTERES) - 1;
        $codigo = '';
        for ($i = 0; $i < $largo; $i++) {
            $codigo .= self::CARACTERES[random_int(0, $maximo)];
        }
        return $codigo;
    }
}