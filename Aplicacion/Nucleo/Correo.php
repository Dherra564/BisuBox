<?php

namespace Aplicacion\Nucleo;

use Configuracion\Configuracion;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class Correo
{
    public static function enviar(string $para, string $nombrePara, string $asunto, string $html, string $texto): bool
    {
        if (!class_exists(PHPMailer::class)) {
            error_log('PHPMailer no está instalado. Ejecute: composer require phpmailer/phpmailer');
            return false;
        }

        $servidor = (string) Configuracion::obtener('correoServidor');
        $remitente = (string) Configuracion::obtener('correoRemitente');
        $usuario = (string) Configuracion::obtener('correoUsuario', $remitente);
        $clave = str_replace(' ', '', (string) Configuracion::obtener('correoClave'));
        if ($servidor === '' || $remitente === '' || $usuario === '' || $clave === '') {
            error_log('Faltan correoServidor, correoRemitente o correoClave en el archivo .env');
            return false;
        }

        $puerto = (int) Configuracion::obtener('correoPuerto', '587');

        try {
            $correo = new PHPMailer(true);
            $correo->isSMTP();
            $correo->Host = $servidor;
            $correo->Port = $puerto;
            $correo->SMTPAuth = true;
            $correo->Username = $usuario;
            $correo->Password = $clave;
            $correo->SMTPSecure = $puerto === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $correo->CharSet = 'UTF-8';
            $correo->Timeout = 15;
            $correo->setFrom($remitente, (string) Configuracion::obtener('correoNombre', 'BisuBox'));
            $correo->addAddress($para, $nombrePara);
            $correo->isHTML(true);
            $correo->Subject = $asunto;
            $correo->Body = $html;
            $correo->AltBody = $texto;
            $correo->send();
            return true;
        } catch (Throwable $error) {
            error_log('No se pudo enviar el correo: ' . $error->getMessage());
            return false;
        }
    }
}