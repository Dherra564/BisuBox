
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

    // Confirmación antes de activar, desactivar, etc., con el modal de la plantilla (modalConfirmacion.php).
    // Si el navegador no soporta <dialog>, se usa la ventana del navegador.
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

    document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();
            if (modal && typeof modal.showModal === 'function') {
                abrirModal(formulario);
            } else if (window.confirm(formulario.getAttribute('data-confirmar'))) {
                formulario.submit();
            }
        });
    });

    if (modal) {
        modal.querySelector('.modalCancelar').addEventListener('click', function () {
            modal.close();
        });

        modal.querySelector('.modalConfirmar').addEventListener('click', function () {
            var formulario = formularioPendiente;
            modal.close();
            // submit() no vuelve a pasar por el evento, así que el modal no se abre otra vez
            if (formulario) {
                formulario.submit();
            }
        });

        // Un clic en el fondo oscuro también cancela
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) {
                modal.close();
            }
        });

        modal.addEventListener('close', function () {
            formularioPendiente = null;
        });
    }

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
});