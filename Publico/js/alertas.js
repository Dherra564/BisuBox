
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.alertaCerrar').forEach(function (boton) {
        boton.addEventListener('click', function () {
            boton.closest('.alerta').remove();
        });
    });

    var botonMenu = document.getElementById('botonMenu');
    var menuLateral = document.getElementById('menuLateral');
    if (botonMenu && menuLateral) {
        botonMenu.addEventListener('click', function () {
            var abierto = menuLateral.classList.toggle('abierto');
            botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
    }

    // Confirmacion antes de activar, desactivar, etc.
    document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!window.confirm(formulario.getAttribute('data-confirmar'))) {
                evento.preventDefault();
            }
        });
    });

    // Nombre de la foto elegida y vista previa
    document.querySelectorAll('.campoArchivo').forEach(function (campo) {
        var zona = campo.closest('.zonaFoto');
        var texto = zona ? zona.querySelector('.nombreArchivo') : null;
        var imagen = zona ? zona.querySelector('.fotoPerfilGrande') : null;
        var textoOriginal = texto ? texto.textContent : '';

        campo.addEventListener('change', function () {
            var archivo = campo.files && campo.files[0];
            if (texto) {
                texto.textContent = archivo ? archivo.name : textoOriginal;
            }
            if (imagen && archivo && archivo.type.indexOf('image/') === 0) {
                imagen.src = URL.createObjectURL(archivo);
            }
        });
    });

    
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
        }
    };

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

    // Devuelve el mensaje de error del campo, o '' si esta bien
    function revisarCampo(campo, formulario) {
        var valor = campo.type === 'password' ? campo.value : campo.value.trim();

        if (valor === '') {
            return campo.required ? 'Este campo es obligatorio' : '';
        }

        var regla = reglas[campo.getAttribute('data-regla')];
        if (regla && !regla.patron.test(valor)) {
            return regla.mensaje;
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
        var campos = formulario.querySelectorAll('input[required], input[data-regla], input[data-igual-a], input[data-distinto-de]');

        // Si confirmar contrasena tiene valor, se exige aunque la contrasena sea opcional (al editar)
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
                    mensaje = 'Confirme la contraseña';
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
});