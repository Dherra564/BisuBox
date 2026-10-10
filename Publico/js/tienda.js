// Formulario de la tienda: lista de contactos y enlace sugerido a partir del nombre
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

    // El enlace se llena solo mientras la persona no lo haya escrito a mano
    document.querySelectorAll('[data-sugerir-desde]').forEach(function (enlace) {
        var origen = document.getElementById(enlace.getAttribute('data-sugerir-desde'));
        if (!origen) {
            return;
        }
        var escritoAMano = enlace.value !== '';

        function convertirEnEnlace(texto) {
            return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 60)
                .replace(/-+$/g, '');
        }

        enlace.addEventListener('input', function () {
            escritoAMano = enlace.value !== '';
        });

        origen.addEventListener('input', function () {
            if (!escritoAMano) {
                enlace.value = convertirEnEnlace(origen.value);
            }
        });
    });
});