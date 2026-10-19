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
            prueba: function (valor) {
                var partes = valor.split('@');
                return partes.length === 2
                    && partes[0].length <= 64
                    && valor.length <= 150
                    && /^[a-z0-9_%+-]+(\.[a-z0-9_%+-]+)*@([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,24}$/i.test(valor);
            },
            mensaje: 'Revise el correo: debe ser como nombre@gmail.com, sin espacios ni puntos seguidos'
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
        cantidad: {
            patron: /^[0-9]{1,10}([.,][0-9]{1,2})?$/,
            mensaje: 'Escriba un número, por ejemplo 12 o 12,50'
        },
        nombreComercial: {
            patron: /^[\p{L}0-9][\p{L}0-9 .,&'-]*$/u,
            mensaje: 'El nombre solo puede tener letras, números, espacios y los signos . , & \' -'
        },
        direccion: {
            patron: /^[\p{L}0-9\s.,#\-\/()°º:;]+$/u,
            mensaje: 'La dirección solo puede tener letras, números, espacios y los signos . , # - / ( ) °'
        },
        referencia: {
            patron: /^[0-9]{4}$/,
            mensaje: 'La referencia debe ser de 4 números, los últimos del comprobante'
        },
        sinpe: {
            prueba: function (valor) {
                return /^[5-8][0-9]{7}$/.test(limpiarTelefono(valor));
            },
            mensaje: 'El número SINPE Móvil debe tener 8 dígitos y empezar con 5, 6, 7 u 8'
        },
        codigoRetiro: {
            patron: /^[A-Za-z0-9]{6}$/,
            mensaje: 'El código de retiro tiene 6 letras y números'
        },
        minutos: {
            prueba: function (valor) {
                return /^[0-9]{1,4}$/.test(valor) && parseInt(valor, 10) >= 1;
            },
            mensaje: 'Escriba los minutos como número entero, por ejemplo 45'
        },
    };

    function limpiarTelefono(valor) {
        var numeros = valor.replace(/\D/g, '');
        if (numeros.length === 11 && numeros.indexOf('506') === 0) {
            numeros = numeros.slice(3);
        }
        return numeros;
    }

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
        // Un campo escondido (por ejemplo la dirección cuando escoge retiro) no se revisa
        if (campo.closest('[hidden]')) {
            return '';
        }

        // Opciones de radio: basta con que una del grupo esté marcada
        if (campo.type === 'radio') {
            var grupo = formulario.querySelectorAll('input[type="radio"][name="' + campo.name + '"]');
            var marcada = Array.prototype.some.call(grupo, function (opcion) { return opcion.checked; });
            return !marcada && campo.required ? (campo.getAttribute('data-mensaje-requerido') || 'Escoja una opción') : '';
        }

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
        + 'input[data-distinto-de], select[required], textarea[required], textarea[data-regla]';

    document.querySelectorAll('form.validarFormulario').forEach(function (formulario) {
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

        // Revisa un campo y muestra o quita su error en ese mismo momento
        function revisarYMostrar(campo) {
            if (!campo.matches(selectorCampos)) {
                return;
            }
            var mensaje = revisarCampo(campo, formulario);
            if (mensaje === '' && campo.getAttribute('data-igual-a') && campo.value === '') {
                var otro = formulario.querySelector('#' + campo.getAttribute('data-igual-a'));
                if (otro && otro.value !== '') {
                    mensaje = campo.getAttribute('data-mensaje-requerido') || 'Confirme la contraseña';
                }
            }
            if (mensaje !== '') {
                ponerError(campo, mensaje);
            } else {
                quitarError(campo);
            }
        }

        // Campos que dependen de otro (confirmar contraseña, contraseña nueva distinta a la actual)
        function revisarDependientes(campo) {
            if (!campo.id) {
                return;
            }
            formulario.querySelectorAll('[data-igual-a="' + campo.id + '"], [data-distinto-de="' + campo.id + '"]')
                .forEach(function (dependiente) {
                    if (dependiente.value !== '' || dependiente.getAttribute('data-tocado') === 'si') {
                        revisarYMostrar(dependiente);
                    }
                });
        }

        // Mientras escribe: el error aparece o desaparece con cada tecla
        function alCambiar(evento) {
            var campo = evento.target;
            if (!campo.matches('input, select, textarea') || campo.type === 'hidden') {
                return;
            }
            campo.setAttribute('data-tocado', 'si');

            if (campo.matches(selectorCampos)) {
                revisarYMostrar(campo);
            } else if (campo.closest('.campoConError')) {
                // Campos sin regla del navegador (por ejemplo una foto): al cambiarlos se quita el error del servidor
                quitarError(campo);
            }
            revisarDependientes(campo);
        }

        formulario.addEventListener('input', alCambiar);
        formulario.addEventListener('change', alCambiar);

        // Al salir de un campo que ya tocó, se revisa aunque lo haya dejado vacío
        formulario.addEventListener('focusout', function (evento) {
            var campo = evento.target;
            if (campo.matches && campo.matches(selectorCampos) && campo.getAttribute('data-tocado') === 'si') {
                revisarYMostrar(campo);
            }
        });
    });

    // Lista de requisitos de la contraseña que se marca mientras escribe
    var requisitosContrasena = [
        { texto: 'Entre 8 y 20 caracteres', cumple: function (v) { return v.length >= 8 && v.length <= 20; } },
        { texto: 'Una letra mayúscula', cumple: function (v) { return /[A-Z]/.test(v); } },
        { texto: 'Una letra minúscula', cumple: function (v) { return /[a-z]/.test(v); } },
        { texto: 'Un número', cumple: function (v) { return /\d/.test(v); } },
        { texto: 'Sin espacios', cumple: function (v) { return v !== '' && !/\s/.test(v); } }
    ];

    document.querySelectorAll('input[data-regla="contrasena"]').forEach(function (campo) {
        var lista = document.createElement('ul');
        lista.className = 'requisitosContrasena';
        lista.setAttribute('aria-live', 'polite');

        var elementos = requisitosContrasena.map(function (requisito) {
            var elemento = document.createElement('li');
            elemento.textContent = requisito.texto;
            lista.appendChild(elemento);
            return elemento;
        });

        // La lista reemplaza el texto de ayuda fijo que estaba debajo del campo
        var ayuda = campo.parentNode.querySelector('.textoAyuda');
        if (ayuda) {
            ayuda.hidden = true;
        }
        campo.insertAdjacentElement('afterend', lista);

        function actualizar() {
            requisitosContrasena.forEach(function (requisito, indice) {
                var cumple = requisito.cumple(campo.value);
                elementos[indice].classList.toggle('cumple', cumple);
                elementos[indice].classList.toggle('falta', campo.value !== '' && !cumple);
            });
        }

        campo.addEventListener('input', actualizar);
        actualizar();
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
        guardarRespaldo(formulario);
        var boton = evento.submitter;
        if (boton && boton.name) {
            var copia = document.createElement('input');
            copia.type = 'hidden';
            copia.name = boton.name;
            copia.value = boton.value;
            formulario.appendChild(copia);
        }
        formulario.querySelectorAll('button[type="submit"]').forEach(function (boton) {
            boton.disabled = true;
        });
    });

    // Respaldo de lo escrito: si al enviar la página expiró o algo falló y el sistema la volvió
    // a cargar vacía, lo que la persona escribió se vuelve a poner en los campos
    var CLAVE_RESPALDO = 'bisuboxRespaldoFormulario';

    function camposRespaldables(formulario) {
        return Array.prototype.filter.call(formulario.elements, function (campo) {
            return campo.name && !campo.disabled
                && ['hidden', 'file', 'password', 'submit', 'button'].indexOf(campo.type) === -1
                && campo.name !== '_csrf';
        });
    }

    function guardarRespaldo(formulario) {
        var campos = camposRespaldables(formulario).map(function (campo) {
            var marcable = campo.type === 'checkbox' || campo.type === 'radio';
            return { nombre: campo.name, valor: campo.value, marcable: marcable, marcado: marcable && campo.checked };
        });
        if (campos.length === 0) {
            return;
        }
        try {
            sessionStorage.setItem(CLAVE_RESPALDO, JSON.stringify({ accion: formulario.getAttribute('action'), campos: campos }));
        } catch (error) {
            // Sin almacenamiento disponible: simplemente no hay respaldo
        }
    }

    function restaurarRespaldo() {
        var respaldo = null;
        try {
            respaldo = JSON.parse(sessionStorage.getItem(CLAVE_RESPALDO) || 'null');
            sessionStorage.removeItem(CLAVE_RESPALDO);
        } catch (error) {
            return;
        }
        // Solo cuando la página muestra un error (si se guardó bien, no hay nada que restaurar)
        if (!respaldo || !document.querySelector('.alertaError')) {
            return;
        }
        var formulario = Array.prototype.find.call(document.forms, function (otro) {
            return otro.getAttribute('action') === respaldo.accion;
        });
        if (!formulario) {
            return;
        }

        var campos = camposRespaldables(formulario);
        var escritos = campos.filter(function (campo) {
            return campo.type !== 'checkbox' && campo.type !== 'radio' && campo.tagName !== 'SELECT' && campo.value.trim() !== '';
        });
        // Si el sistema ya devolvió el formulario lleno, se respeta lo que trae
        if (escritos.length > 0) {
            return;
        }

        // Los campos con el mismo nombre (por ejemplo filas repetidas) se llenan en el mismo orden
        var usados = {};
        respaldo.campos.forEach(function (guardado) {
            var mismos = campos.filter(function (campo) { return campo.name === guardado.nombre; });
            if (guardado.marcable) {
                mismos.forEach(function (campo) {
                    if (campo.value === guardado.valor) {
                        campo.checked = guardado.marcado;
                    }
                });
                return;
            }
            var posicion = usados[guardado.nombre] || 0;
            usados[guardado.nombre] = posicion + 1;
            var campo = mismos[posicion];
            if (campo && (campo.value === '' || campo.tagName === 'SELECT')) {
                campo.value = guardado.valor;
            }
        });
    }

    restaurarRespaldo();

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