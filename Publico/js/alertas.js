document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.alertaCerrar').forEach(function (boton) {
        boton.addEventListener('click', function () {
            boton.closest('.alerta').remove();
        });
    });

    var modal = document.getElementById('modalConfirmacion');
    var formularioPendiente = null;

    function abrirModal(formulario) {
        var botonConfirmar = modal.querySelector('.modalConfirmar');

        formularioPendiente = formulario;
        modal.querySelector('.modalTitulo').textContent = formulario.getAttribute('data-titulo') || 'Confirmar acción';
        modal.querySelector('.modalMensaje').textContent = formulario.getAttribute('data-confirmar');
        botonConfirmar.textContent = formulario.getAttribute('data-boton') || 'Confirmar';
        botonConfirmar.classList.toggle('botonPeligro', formulario.hasAttribute('data-peligro'));
        modal.showModal();
    }

    document.addEventListener('submit', function (evento) {
        var formulario = evento.target;
        if (!formulario.hasAttribute('data-confirmar') || evento.defaultPrevented) {
            return;
        }

        evento.preventDefault();
        if (modal && typeof modal.showModal === 'function') {
            abrirModal(formulario);
        } else if (window.confirm(formulario.getAttribute('data-confirmar'))) {
            formulario.submit();
        }
    });

    if (modal) {
        modal.querySelector('.modalCancelar').addEventListener('click', function () {
            modal.close();
        });

        modal.querySelector('.modalConfirmar').addEventListener('click', function () {
            var formulario = formularioPendiente;
            modal.close();

            if (formulario) {
                formulario.submit();
            }
        });

        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) {
                modal.close();
            }
        });

        modal.addEventListener('close', function () {
            formularioPendiente = null;
        });
    }

    document.querySelectorAll('.campoArchivo').forEach(function (campo) {
        var zona = campo.closest('.zonaFoto');
        var contenedor = campo.closest('.campo');
        var texto = zona ? zona.querySelector('.nombreArchivo') : null;
        var imagen = zona ? zona.querySelector('.fotoPerfilGrande') : null;
        var textoOriginal = texto ? texto.textContent : '';
        var imagenOriginal = imagen ? imagen.src : '';
        var maximo = parseInt(campo.getAttribute('data-tamano-maximo'), 10) || 0;
        var mensajeTipo = campo.getAttribute('data-mensaje-tipo');
        var mensajeTamano = campo.getAttribute('data-mensaje-tamano');

        function mostrarError(mensaje) {
            if (!contenedor) {
                return;
            }
            quitarError();
            contenedor.classList.add('campoConError');
            var error = document.createElement('p');
            error.className = 'errorCampo';
            error.textContent = mensaje;
            contenedor.appendChild(error);
        }

        function quitarError() {
            if (!contenedor) {
                return;
            }
            contenedor.classList.remove('campoConError');
            contenedor.querySelectorAll('.errorCampo').forEach(function (error) {
                error.remove();
            });
        }

        function esImagenReal(bytes) {
            var b = new Uint8Array(bytes);
            var jpeg = b[0] === 0xFF && b[1] === 0xD8 && b[2] === 0xFF;
            var png = b[0] === 0x89 && b[1] === 0x50 && b[2] === 0x4E && b[3] === 0x47;
            var webp = b[0] === 0x52 && b[1] === 0x49 && b[2] === 0x46 && b[3] === 0x46
                && b[8] === 0x57 && b[9] === 0x45 && b[10] === 0x42 && b[11] === 0x50;
            return jpeg || png || webp;
        }

        function rechazar(mensaje) {
            campo.value = '';
            if (texto) {
                texto.textContent = textoOriginal;
            }
            if (imagen) {
                imagen.src = imagenOriginal;
            }
            mostrarError(mensaje);
        }

        function aceptar(archivo) {
            quitarError();
            if (texto) {
                texto.textContent = archivo.name;
            }
            if (imagen) {
                imagen.src = URL.createObjectURL(archivo);
            }
        }

        campo.addEventListener('change', function () {
            var archivo = campo.files && campo.files[0];
            if (!archivo) {
                quitarError();
                if (texto) {
                    texto.textContent = textoOriginal;
                }
                if (imagen) {
                    imagen.src = imagenOriginal;
                }
                return;
            }

            if (!mensajeTipo || !mensajeTamano) {
                if (texto) {
                    texto.textContent = archivo.name;
                }
                if (imagen && archivo.type.indexOf('image/') === 0) {
                    imagen.src = URL.createObjectURL(archivo);
                }
                return;
            }

            if (maximo > 0 && archivo.size > maximo) {
                rechazar(mensajeTamano);
                return;
            }

            var lector = new FileReader();
            lector.onload = function () {
                if (esImagenReal(lector.result)) {
                    aceptar(archivo);
                } else {
                    rechazar(mensajeTipo);
                }
            };
            lector.onerror = function () {
                rechazar(mensajeTipo);
            };
            lector.readAsArrayBuffer(archivo.slice(0, 12));
        });
    });

    document.querySelectorAll('.formularioEditable').forEach(function (formulario) {
        var archivo = formulario.querySelector('.campoArchivo');
        var imagen = formulario.querySelector('.fotoPerfilGrande');
        var nombreArchivo = formulario.querySelector('.nombreArchivo');
        var imagenOriginal = imagen ? imagen.src : '';
        var textoArchivoOriginal = nombreArchivo ? nombreArchivo.textContent : '';

        function camposEditables() {
            return formulario.querySelectorAll('[data-editable], [data-restaurar-desde] input, [data-restaurar-desde] select');
        }

        function cambiarModo(editando) {
            formulario.classList.toggle('editando', editando);
            camposEditables().forEach(function (campo) {
                if (campo.tagName === 'SELECT' || campo.type === 'checkbox') {
                    campo.disabled = !editando;
                } else {
                    campo.readOnly = !editando;
                }
            });
            if (archivo) {
                archivo.disabled = !editando;
            }

            formulario.querySelectorAll('[data-solo-edicion]').forEach(function (elemento) {
                elemento.hidden = !editando;
            });
        }

        formulario.querySelector('.formularioEditar').addEventListener('click', function () {
            cambiarModo(true);
            var primero = formulario.querySelector('[data-editable]');
            if (primero) {
                primero.focus();
            }
        });

        formulario.querySelector('.formularioCancelar').addEventListener('click', function () {
            formulario.querySelectorAll('[data-editable]').forEach(function (campo) {
                if (campo.type === 'checkbox') {
                    campo.checked = campo.getAttribute('data-original') === '1';
                    return;
                }
                campo.value = campo.getAttribute('data-original');
                campo.dispatchEvent(new Event(campo.tagName === 'SELECT' ? 'change' : 'input'));
            });

            formulario.querySelectorAll('[data-restaurar-desde]').forEach(function (lista) {
                var original = document.getElementById(lista.getAttribute('data-restaurar-desde'));
                if (original) {
                    lista.innerHTML = original.innerHTML;
                    lista.dispatchEvent(new Event('restaurada'));
                }
            });

            if (archivo) {
                archivo.value = '';
            }
            if (imagen) {
                imagen.src = imagenOriginal;
            }
            if (nombreArchivo) {
                nombreArchivo.textContent = textoArchivoOriginal;
            }

            formulario.querySelectorAll('.errorCampo').forEach(function (error) {
                error.remove();
            });
            formulario.querySelectorAll('.campoConError').forEach(function (campo) {
                campo.classList.remove('campoConError');
            });
            document.querySelectorAll('.alertaFormulario').forEach(function (alerta) {
                alerta.remove();
            });

            cambiarModo(false);
        });

        formulario.addEventListener('submit', function (evento) {
            if (!formulario.classList.contains('editando')) {
                evento.preventDefault();
            }
        });

        cambiarModo(formulario.classList.contains('editando'));
    });
});