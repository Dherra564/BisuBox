<?php

namespace Configuracion;

use RuntimeException;


class Configuracion
{
    private static array $valores = [];
    private static bool $cargada = false;

    // Carpeta raiz del proyecto (bisubox/), sin barra al final
    public static function rutaBase(): string
    {
        return dirname(__DIR__);
    }

    public static function cargar(): void
    {
        if (self::$cargada) {
            return;
        }

        $rutaEnv = self::rutaBase() . '/.env';
        if (!is_file($rutaEnv)) {
            throw new RuntimeException('No existe el archivo .env. Copie .env.ejemplo como .env y complete sus datos.');
        }

        // Lee cada linea "clave=valor"; ignora vacias y comentarios (#)
        $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            self::$valores[trim($clave)] = trim(trim($valor), "\"'");
        }

        self::verificarClaves(['bdServidor', 'bdPuerto', 'bdNombre', 'bdUsuario', 'zonaHoraria']);

        // Zona horaria: si no se define, las fechas salen con horas de diferencia
        date_default_timezone_set(self::obtener('zonaHoraria'));

        // En desarrollo se ven los errores; en produccion solo van al registro
        $esDesarrollo = self::esDesarrollo();
        ini_set('display_errors', $esDesarrollo ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::rutaBase() . '/Almacenamiento/Registros/errores.log');
        error_reporting(E_ALL);

        self::$cargada = true;
    }

    public static function obtener(string $clave, ?string $porDefecto = null): ?string
    {
        return self::$valores[$clave] ?? $porDefecto;
    }

    public static function esDesarrollo(): bool
    {
        return self::obtener('appEntorno', 'produccion') === 'desarrollo';
    }

    private static function verificarClaves(array $claves): void
    {
        foreach ($claves as $clave) {
            if (!array_key_exists($clave, self::$valores)) {
                throw new RuntimeException('Falta la clave "' . $clave . '" en el archivo .env.');
            }
        }
    }
}