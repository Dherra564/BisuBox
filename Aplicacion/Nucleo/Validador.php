<?php

namespace Aplicacion\Nucleo;


class Validador
{
    private array $errores = [];

    public function requerido(string $campo, ?string $valor, string $mensaje = 'Este campo es obligatorio'): self
    {
        if (trim($valor ?? '') === '') {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }

    public function longitud(string $campo, ?string $valor, int $minimo, int $maximo, ?string $mensaje = null): self
    {
        if ($this->debeRevisar($campo, $valor)) {
            $largo = mb_strlen($valor, 'UTF-8');
            if ($largo < $minimo || $largo > $maximo) {
                $this->agregarError($campo, $mensaje ?? "Debe tener entre {$minimo} y {$maximo} caracteres");
            }
        }
        return $this;
    }

    
    public function soloLetras(string $campo, ?string $valor, string $mensaje = 'Solo se permiten letras y espacios'): self
    {
        if ($this->debeRevisar($campo, $valor) && !preg_match('/^\p{L}+(\s+\p{L}+)*$/u', trim($valor))) {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }

    public function soloNumeros(string $campo, ?string $valor, string $mensaje = 'Solo se permiten números'): self
    {
        if ($this->debeRevisar($campo, $valor) && !preg_match('/^[0-9]+$/', $valor)) {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }
    
    public function alfanumerico(string $campo, ?string $valor, string $mensaje = 'Solo se permiten letras y números, sin espacios ni guiones'): self
    {
        if ($this->debeRevisar($campo, $valor) && !preg_match('/^[A-Za-z0-9]+$/', $valor)) {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }
    public function tipoIdentificacion(string $campo, ?string $tipo, string $mensaje = 'Seleccione el tipo de identificación'): self
    {
        if (!isset($this->errores[$campo]) && !TipoIdentificacion::existe($tipo)) {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }

    public function identificacion(string $campo, ?string $tipo, ?string $numero): self
    {
        if ($this->debeRevisar($campo, $numero) && TipoIdentificacion::existe($tipo)
            && !TipoIdentificacion::esValida($tipo, $numero)) {
            $this->agregarError($campo, TipoIdentificacion::mensaje($tipo));
        }
        return $this;
    }

    public function correo(string $campo, ?string $valor, string $mensaje = 'Ingrese un correo válido, por ejemplo nombre@correo.com'): self
    {
        if ($this->debeRevisar($campo, $valor)
            && (filter_var($valor, FILTER_VALIDATE_EMAIL) === false || mb_strlen($valor, 'UTF-8') > 150)) {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }

    
    public function telefono(string $campo, ?string $valor, string $mensaje = 'El teléfono debe tener 8 dígitos, se aceptan unicamente números'): self
    {
        if ($this->debeRevisar($campo, $valor) && !preg_match('/^[0-9]{8}$/', self::limpiarTelefono($valor))) {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }
    
    public function contrasenaSegura(string $campo, ?string $valor, string $mensaje = 'La contraseña debe tener entre 8 y 20 caracteres, una mayúscula, una minúscula y un número, sin espacios'): self
    {
        if ($this->debeRevisar($campo, $valor)) {
            $largo = strlen($valor);
            $segura = $largo >= 8 && $largo <= 20
                && preg_match('/[A-Z]/', $valor)
                && preg_match('/[a-z]/', $valor)
                && preg_match('/[0-9]/', $valor)
                && !preg_match('/\s/u', $valor);   
            if (!$segura) {
                $this->agregarError($campo, $mensaje);
            }
        }
        return $this;
    }

    public function coinciden(string $campo, ?string $valor, ?string $otroValor, string $mensaje = 'Los valores no coinciden'): self
    {
        if ($this->debeRevisar($campo, $valor) && $valor !== $otroValor) {
            $this->agregarError($campo, $mensaje);
        }
        return $this;
    }

    
    public function agregarError(string $campo, string $mensaje): self
    {
        if (!isset($this->errores[$campo])) {
            $this->errores[$campo] = $mensaje;
        }
        return $this;
    }

    public function esValido(): bool
    {
        return $this->errores === [];
    }

    
    public function errores(): array
    {
        return $this->errores;
    }

    
    public function error(string $campo): ?string
    {
        return $this->errores[$campo] ?? null;
    }

    
    public static function limpiarTelefono(string $telefono): string
    {
        $soloNumeros = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($soloNumeros) === 11 && str_starts_with($soloNumeros, '506')) {
            $soloNumeros = substr($soloNumeros, 3);
        }
        return $soloNumeros;
    }

    private function debeRevisar(string $campo, ?string $valor): bool
    {
        return !isset($this->errores[$campo]) && trim($valor ?? '') !== '';
    }
}