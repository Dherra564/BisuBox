document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-contactos]').forEach(function (lista) {
        var seccion = lista.closest('section');
        var plantilla = seccion.querySelector('[data-contacto-plantilla]');
        var botonAgregar = seccion.querySelector('[data-contacto-agregar]');
        var cuenta = seccion.querySelector('[data-contacto-cuenta]');
        var vacio = seccion.querySelector('[data-contactos-vacio]');
        var maximo = parseInt(lista.getAttribute('data-maximo'), 10) || 10;

        function actualizarCuenta() {
            var total = lista.querySelectorAll('.filaContacto').length;
            cuenta.textContent = total + ' de ' + maximo + ' contactos';
            botonAgregar.disabled = total >= maximo;
            if (vacio) {
                vacio.hidden = total > 0;
            }
        }

        lista.addEventListener('restaurada', actualizarCuenta);

        botonAgregar.addEventListener('click', function () {
            if (lista.querySelectorAll('.filaContacto').length >= maximo) {
                return;
            }
            lista.appendChild(plantilla.content.cloneNode(true));
            actualizarCuenta();
            lista.lastElementChild.querySelector('input').focus();
        });

        lista.addEventListener('click', function (evento) {
            var boton = evento.target.closest('[data-contacto-quitar]');
            if (!boton) {
                return;
            }
            boton.closest('.filaContacto').remove();
            actualizarCuenta();
            botonAgregar.focus();
        });

        // Al cambiar el tipo, el ejemplo del campo cambia y se quita el error viejo
        lista.addEventListener('change', function (evento) {
            if (!evento.target.matches('[data-contacto-tipo]')) {
                return;
            }
            var fila = evento.target.closest('.filaContacto');
            var opcion = evento.target.options[evento.target.selectedIndex];
            fila.querySelector('input').placeholder = opcion.getAttribute('data-ejemplo') || '';
            fila.classList.remove('campoConError');
            var error = fila.querySelector('.errorCampo');
            if (error) {
                error.remove();
            }
        });

        actualizarCuenta();
    });

    
    document.querySelectorAll('[data-copiar]').forEach(function (boton) {
        var campo = document.getElementById(boton.getAttribute('data-copiar'));
        var textoOriginal = boton.textContent;
        boton.addEventListener('click', function () {
            campo.select();
            var listo = function () {
                boton.textContent = '¡Copiada!';
                setTimeout(function () { boton.textContent = textoOriginal; }, 2000);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(campo.value).then(listo, function () { document.execCommand('copy'); listo(); });
            } else {
                document.execCommand('copy');
                listo();
            }
        });
    });
});