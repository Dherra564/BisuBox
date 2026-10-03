document.addEventListener('DOMContentLoaded', function () {

    var reglas = {
        alfanumerico: {
            patron: /^[A-Za-z0-9]+$/,
            mensaje: 'Solo se permiten letras y números, sin espacios ni guiones'
        },
        soloLetras: {
            patron: /^\p{L}+(\s+\p{L}+)*$/u,
            mensaje: 'Solo se permiten letras y espacios'
        },
        correo: {
            patron: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            mensaje: 'Ingrese un correo válido, por ejemplo nombre@correo.com'
        },
        contrasena: {
            patron: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)\S{8,20}$/,
            mensaje: 'La contraseña debe tener entre 8 y 20 caracteres, una mayúscula, una minúscula y un número, sin espacios'
        },
        // Igual que Validador::limpiarTelefono: acepta +506, espacios y guiones
        telefono: {
            prueba: function (valor) {
                var numeros = valor.replace(/\D/g, '');
                if (numeros.length === 11 && numeros.indexOf('506') === 0) {
                    numeros = numeros.slice(3);
                }
                return /^[0-9]{8}$/.test(numeros);
            },
            mensaje: 'El teléfono debe tener 8 dígitos'
        }
    };

    function cumpleRegla(regla, valor) {
        return regla.prueba ? regla.prueba(valor) : regla.patron.test(valor);
    }

    function quitarError(campo) {
        var contenedor = campo.closest('.campo');
        if (!contenedor) {
            return;
        }
        contenedor.classList.remove('campoConError');
        var error = contenedor.querySelector('.errorCampo');
        if (error) {
            error.remove();
        }
    }

    function ponerError(campo, mensaje) {
        quitarError(campo);
        var contenedor = campo.closest('.campo');
        if (!contenedor) {
            return;
        }
        contenedor.classList.add('campoConError');
        var error = document.createElement('p');
        error.className = 'errorCampo';
        error.textContent = mensaje;
        contenedor.appendChild(error);
    }

    // Devuelve el mensaje de error del campo, o '' si está bien
    function revisarCampo(campo, formulario) {
        var valor = campo.type === 'password' ? campo.value : campo.value.trim();

        if (valor === '') {
            return campo.required ? (campo.getAttribute('data-mensaje-requerido') || 'Este campo es obligatorio') : '';
        }

        var regla = reglas[campo.getAttribute('data-regla')];
        if (regla && !cumpleRegla(regla, valor)) {
            return campo.getAttribute('data-mensaje-regla') || regla.mensaje;
        }

        var minimo = campo.minLength;
        var maximo = campo.maxLength;
        if ((minimo > 0 && valor.length < minimo) || (maximo > 0 && valor.length > maximo)) {
            return campo.getAttribute('data-mensaje-largo') || 'Debe tener entre ' + minimo + ' y ' + maximo + ' caracteres';
        }

        var idOtro = campo.getAttribute('data-igual-a');
        if (idOtro) {
            var otro = formulario.querySelector('#' + idOtro);
            if (otro && otro.value !== campo.value) {
                return 'Las contraseñas no coinciden';
            }
        }

        var idDistinto = campo.getAttribute('data-distinto-de');
        if (idDistinto) {
            var anterior = formulario.querySelector('#' + idDistinto);
            if (anterior && anterior.value === campo.value) {
                return 'La contraseña nueva debe ser diferente a la actual';
            }
        }

        return '';
    }

    document.querySelectorAll('form.validarFormulario').forEach(function (formulario) {
        var campos = formulario.querySelectorAll(
            'input[required], input[data-regla], input[minlength], input[data-igual-a], input[data-distinto-de], select[required], textarea[required]'
        );

        // Si confirmar contraseña tiene valor, se exige aunque la contraseña sea opcional (al editar)
        function camposPorRevisar() {
            return Array.prototype.filter.call(campos, function (campo) {
                var idOtro = campo.getAttribute('data-igual-a');
                if (idOtro && campo.value === '' && !campo.required) {
                    var otro = formulario.querySelector('#' + idOtro);
                    return otro && otro.value !== '';
                }
                return true;
            });
        }

        formulario.addEventListener('submit', function (evento) {
            var primero = null;

            camposPorRevisar().forEach(function (campo) {
                var mensaje = revisarCampo(campo, formulario);
                if (mensaje === '' && campo.getAttribute('data-igual-a') && campo.value === '') {
                    mensaje = campo.getAttribute('data-mensaje-requerido') || 'Confirme la contraseña';
                }
                if (mensaje !== '') {
                    ponerError(campo, mensaje);
                    primero = primero || campo;
                } else {
                    quitarError(campo);
                }
            });

            if (primero) {
                evento.preventDefault();
                primero.focus();
            }
        });

        // Al corregir un campo, se quita su error
        campos.forEach(function (campo) {
            campo.addEventListener('input', function () {
                if (campo.closest('.campoConError') && revisarCampo(campo, formulario) === '') {
                    quitarError(campo);
                }
            });
        });
    });

    // Contador de caracteres debajo de los campos con data-contador y maxlength
    document.querySelectorAll('[data-contador]').forEach(function (campo) {
        var maximo = campo.maxLength;
        if (maximo <= 0) {
            return;
        }

        var contador = document.createElement('p');
        contador.className = 'contadorCaracteres';
        campo.insertAdjacentElement('afterend', contador);

        function actualizar() {
            contador.textContent = campo.value.length + '/' + maximo;
            contador.classList.toggle('alLimite', campo.value.length >= maximo);
        }

        campo.addEventListener('input', actualizar);
        actualizar();
    });

    // Evita que un formulario POST se envíe dos veces (doble clic o Enter repetido).
    // Va en document para correr después de la validación y del modal: si alguno detuvo el envío, no hace nada.
    document.addEventListener('submit', function (evento) {
        var formulario = evento.target;
        if (evento.defaultPrevented || formulario.method.toLowerCase() !== 'post') {
            return;
        }
        if (formulario.getAttribute('data-enviando') === 'si') {
            evento.preventDefault();
            return;
        }
        formulario.setAttribute('data-enviando', 'si');
        formulario.querySelectorAll('button[type="submit"]').forEach(function (boton) {
            boton.disabled = true;
        });
    });

    // Si la persona vuelve con el botón Atrás, los formularios quedan usables otra vez
    window.addEventListener('pageshow', function (evento) {
        if (!evento.persisted) {
            return;
        }
        document.querySelectorAll('form[data-enviando]').forEach(function (formulario) {
            formulario.removeAttribute('data-enviando');
            formulario.querySelectorAll('button[type="submit"]').forEach(function (boton) {
                boton.disabled = false;
            });
        });
    });
});