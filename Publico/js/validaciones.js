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
            patron: /^[a-z0-9](\.?[a-z0-9])*@gmail\.com$/i,
            mensaje: 'Ingrese un correo de Gmail válido, por ejemplo nombre@gmail.com'
        },
        contrasena: {
            patron: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)\S{8,20}$/,
            mensaje: 'La contraseña debe tener entre 8 y 20 caracteres, una mayúscula, una minúscula y un número, sin espacios'
        },
        telefono: {
            prueba: function (valor) {
                return /^[0-9]{8}$/.test(limpiarTelefono(valor));
            },
            mensaje: 'El teléfono debe tener 8 dígitos, solo números'
        },
        nombreTienda: {
            patron: /^[\p{L}0-9][\p{L}0-9 .&'-]*$/u,
            mensaje: 'El nombre solo puede tener letras, números, espacios y los signos . & \' -'
        },
        enlaceTienda: {
            patron: /^[a-z0-9]+(-[a-z0-9]+)*$/,
            mensaje: 'Use solo minúsculas, números y guiones, por ejemplo mi-tienda'
        }
    };

    // Igual que Validador::limpiarTelefono en PHP
    function limpiarTelefono(valor) {
        var numeros = valor.replace(/\D/g, '');
        if (numeros.length === 11 && numeros.indexOf('506') === 0) {
            numeros = numeros.slice(3);
        }
        return numeros;
    }

    // Igual que TipoContacto::esValido en PHP; las reglas de cada tipo vienen en la opción elegida
    function esContactoValido(campo, valor) {
        var fila = campo.closest('.filaContacto');
        var tipo = fila ? fila.querySelector('[data-contacto-tipo]') : null;
        if (!tipo || tipo.selectedIndex < 0) {
            return false;
        }
        var opcion = tipo.options[tipo.selectedIndex];

        if (opcion.hasAttribute('data-telefono')) {
            return /^[0-9]{8}$/.test(limpiarTelefono(valor));
        }

        var esEnlace = /^https?:\/\//i.test(valor) || /^(www\.)?[a-z0-9-]+(\.[a-z0-9-]+)+\//i.test(valor);
        if (!esEnlace) {
            var patronUsuario = opcion.getAttribute('data-usuario');
            return patronUsuario !== '' && new RegExp(patronUsuario).test(valor.replace(/^@+/, ''));
        }

        var direccion;
        try {
            direccion = new URL(/^https?:\/\//i.test(valor) ? valor : 'https://' + valor);
        } catch (error) {
            return false;
        }
        var dominios = (opcion.getAttribute('data-dominios') || '').split(',').filter(Boolean);
        if (dominios.length === 0) {
            return opcion.value === 'Otro';
        }
        var servidor = direccion.hostname.toLowerCase();
        return dominios.some(function (dominio) {
            return servidor === dominio || servidor.slice(-(dominio.length + 1)) === '.' + dominio;
        });
    }

    function cumpleRegla(regla, valor) {
        return regla.prueba ? regla.prueba(valor) : regla.patron.test(valor);
    }

    function limpiarIdentificacion(valor) {
        return valor.replace(/[\s-]+/g, '').toUpperCase();
    }

    function opcionTipoIdentificacion(campo) {
        var tipo = document.getElementById(campo.getAttribute('data-tipo'));
        if (!tipo || tipo.value === '') {
            return null;
        }
        return tipo.options[tipo.selectedIndex];
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

    function revisarCampo(campo, formulario) {
        var valor = campo.type === 'password' ? campo.value : campo.value.trim();

        if (valor === '') {
            return campo.required ? (campo.getAttribute('data-mensaje-requerido') || 'Este campo es obligatorio') : '';
        }

        if (campo.getAttribute('data-regla') === 'contacto') {
            if (!esContactoValido(campo, valor)) {
                var tipoContacto = campo.closest('.filaContacto').querySelector('[data-contacto-tipo]');
                return tipoContacto.options[tipoContacto.selectedIndex].getAttribute('data-mensaje');
            }
            return '';
        }

        if (campo.getAttribute('data-regla') === 'identificacion') {
            var opcion = opcionTipoIdentificacion(campo);
            if (opcion && opcion.getAttribute('data-patron')
                && !new RegExp(opcion.getAttribute('data-patron')).test(limpiarIdentificacion(valor))) {
                return opcion.getAttribute('data-mensaje');
            }
            return '';
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

    var selectorCampos = 'input[required], input[data-regla], input[minlength], input[data-igual-a], '
        + 'input[data-distinto-de], select[required], textarea[required]';

    document.querySelectorAll('form.validarFormulario').forEach(function (formulario) {
        // Se buscan al enviar, así también se revisan las filas que se agregaron después (contactos)
        function camposPorRevisar() {
            var campos = formulario.querySelectorAll(selectorCampos);
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

        formulario.addEventListener('input', function (evento) {
            var campo = evento.target;
            if (campo.matches(selectorCampos) && campo.closest('.campoConError') && revisarCampo(campo, formulario) === '') {
                quitarError(campo);
            }
        });
    });

    document.querySelectorAll('[data-regla="identificacion"]').forEach(function (campo) {
        var tipo = document.getElementById(campo.getAttribute('data-tipo'));
        var ayuda = document.getElementById(campo.getAttribute('data-campo-ayuda'));
        if (!tipo) {
            return;
        }

        tipo.addEventListener('change', function () {
            var opcion = opcionTipoIdentificacion(campo);
            if (ayuda) {
                ayuda.textContent = opcion ? opcion.getAttribute('data-ayuda') : 'Primero seleccione el tipo';
            }
            if (campo.closest('.campoConError') && campo.value !== '') {
                var mensaje = revisarCampo(campo, campo.form);
                if (mensaje === '') {
                    quitarError(campo);
                } else {
                    ponerError(campo, mensaje);
                }
            }
        });
    });

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