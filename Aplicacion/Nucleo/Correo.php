<?php

namespace Aplicacion\Nucleo;

use Configuracion\Configuracion;
use RuntimeException;
use Throwable;

class Correo
{
    private const SEGUNDOS_ESPERA = 15;

    public static function enviar(string $para, string $nombrePara, string $asunto, string $html, string $texto): bool
    {
        $servidor = (string) Configuracion::obtener('correoServidor');
        $remitente = (string) Configuracion::obtener('correoRemitente');
        $usuario = (string) Configuracion::obtener('correoUsuario', $remitente);
        $clave = str_replace(' ', '', (string) Configuracion::obtener('correoClave'));
        if ($servidor === '' || $remitente === '' || $usuario === '' || $clave === '') {
            error_log('Faltan correoServidor, correoRemitente o correoClave en el archivo .env');
            return false;
        }
        if (!extension_loaded('openssl')) {
            error_log('Para enviar correos hay que activar la extensión openssl de PHP');
            return false;
        }

        $puerto = (int) Configuracion::obtener('correoPuerto', '587');
        $nombreRemitente = (string) Configuracion::obtener('correoNombre', 'BisuBox');
        $mensaje = self::armarMensaje($remitente, $nombreRemitente, $para, $nombrePara, $asunto, $html, $texto);

        $conexion = null;
        try {
            $conexion = self::conectar($servidor, $puerto);
            self::esperar($conexion, [220]);
            self::saludar($conexion);
            if ($puerto !== 465) {
                self::ordenar($conexion, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($conexion, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('No se pudo activar la conexión segura con el servidor de correo');
                }
                self::saludar($conexion);
            }
            self::ordenar($conexion, 'AUTH LOGIN', [334]);
            self::ordenar($conexion, base64_encode($usuario), [334]);
            self::ordenar($conexion, base64_encode($clave), [235]);
            self::ordenar($conexion, 'MAIL FROM:<' . $remitente . '>', [250]);
            self::ordenar($conexion, 'RCPT TO:<' . $para . '>', [250, 251]);
            self::ordenar($conexion, 'DATA', [354]);
            self::ordenar($conexion, $mensaje . "\r\n.", [250]);
            self::ordenar($conexion, 'QUIT', [221]);
            return true;
        } catch (Throwable $error) {
            error_log('No se pudo enviar el correo: ' . $error->getMessage());
            return false;
        } finally {
            if (is_resource($conexion)) {
                fclose($conexion);
            }
        }
    }

    private static function conectar(string $servidor, int $puerto)
    {
        $contexto = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $servidor,
            ]
        ]);
        $esquema = $puerto === 465 ? 'ssl://' : 'tcp://';
        $conexion = @stream_socket_client(
            $esquema . $servidor . ':' . $puerto,
            $numeroError,
            $textoError,
            self::SEGUNDOS_ESPERA,
            STREAM_CLIENT_CONNECT,
            $contexto
        );
        if ($conexion === false) {
            throw new RuntimeException('No se pudo conectar con ' . $servidor . ':' . $puerto . ' (' . $textoError . ')');
        }
        stream_set_timeout($conexion, self::SEGUNDOS_ESPERA);
        return $conexion;
    }

    private static function saludar($conexion): void
    {
        $equipo = preg_replace('/[^A-Za-z0-9.-]/', '', (string) gethostname());
        self::ordenar($conexion, 'EHLO ' . ($equipo !== '' ? $equipo : 'localhost'), [250]);
    }

    private static function ordenar($conexion, string $orden, array $esperados): string
    {
        if (fwrite($conexion, $orden . "\r\n") === false) {
            throw new RuntimeException('Se perdió la conexión con el servidor de correo');
        }
        return self::esperar($conexion, $esperados);
    }

    private static function esperar($conexion, array $esperados): string
    {
        $respuesta = '';
        while (($linea = fgets($conexion, 1024)) !== false) {
            $respuesta .= $linea;
            if (strlen($linea) < 4 || $linea[3] === ' ') {
                break;
            }
        }
        if ($respuesta === '') {
            throw new RuntimeException('El servidor de correo no respondió o cerró la conexión');
        }
        $codigo = (int) substr($respuesta, 0, 3);
        if (!in_array($codigo, $esperados, true)) {
            throw new RuntimeException('El servidor de correo respondió: ' . trim($respuesta));
        }
        return $respuesta;
    }

    private static function armarMensaje(string $remitente, string $nombreRemitente, string $para, string $nombrePara, string $asunto, string $html, string $texto): string
    {
        $separador = 'bisubox' . bin2hex(random_bytes(12));
        $dominio = substr((string) strrchr($remitente, '@'), 1);
        $encabezados = [
            'Date: ' . date('r'),
            'From: ' . self::nombreCodificado($nombreRemitente) . ' <' . $remitente . '>',
            'To: ' . self::nombreCodificado($nombrePara) . ' <' . $para . '>',
            'Subject: ' . self::nombreCodificado($asunto),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . ($dominio !== '' ? $dominio : 'bisubox') . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $separador . '"',
        ];
        $partes = [
            '--' . $separador,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($texto), 76, "\r\n")),
            '--' . $separador,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($html), 76, "\r\n")),
            '--' . $separador . '--',
        ];

        return implode("\r\n", $encabezados) . "\r\n\r\n" . implode("\r\n", $partes);
    }

    private static function nombreCodificado(string $texto): string
    {
        $texto = str_replace(["\r", "\n"], ' ', $texto);
        return preg_match('/^[A-Za-z0-9 .,_-]*$/', $texto) === 1 ? $texto : '=?UTF-8?B?' . base64_encode($texto) . '?=';
    }
}