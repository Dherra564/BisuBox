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

    document.querySelectorAll('.formularioEditable').forEach(function (formulario) {
        var campos = formulario.querySelectorAll('[data-editable]');
        var archivo = formulario.querySelector('.campoArchivo');
        var imagen = formulario.querySelector('.fotoPerfilGrande');
        var nombreArchivo = formulario.querySelector('.nombreArchivo');
        var imagenOriginal = imagen ? imagen.src : '';
        var textoArchivoOriginal = nombreArchivo ? nombreArchivo.textContent : '';

        function cambiarModo(editando) {
            formulario.classList.toggle('editando', editando);
            campos.forEach(function (campo) {
                if (campo.tagName === 'SELECT') {
                    campo.disabled = !editando;
                } else {
                    campo.readOnly = !editando;
                }
            });
        }

        formulario.querySelector('.formularioEditar').addEventListener('click', function () {
            cambiarModo(true);
            if (campos.length > 0) {
                campos[0].focus();
            }
        });

        formulario.querySelector('.formularioCancelar').addEventListener('click', function () {
            campos.forEach(function (campo) {
                campo.value = campo.getAttribute('data-original');
                campo.dispatchEvent(new Event(campo.tagName === 'SELECT' ? 'change' : 'input'));
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
    });
});