
document.addEventListener('DOMContentLoaded', function () {

    
    document.querySelectorAll('[data-selector-cantidad]').forEach(function (selector) {
        var campo = selector.querySelector('input');
        var maximo = parseInt(selector.getAttribute('data-maximo'), 10) || 99;
        var enviarAlCambiar = selector.hasAttribute('data-enviar-al-cambiar');
        var valorAnterior = campo.value;

        function leer() {
            var numero = parseInt(campo.value.trim(), 10);
            return /^[0-9]{1,2}$/.test(campo.value.trim()) ? numero : NaN;
        }

        function guardar() {
            if (enviarAlCambiar && campo.value !== valorAnterior && campo.form) {
                campo.form.requestSubmit();
            }
        }

        function cambiar(paso) {
            var actual = isNaN(leer()) ? 1 : leer();
            campo.value = String(Math.min(maximo, Math.max(1, actual + paso)));
            guardar();
        }

        selector.querySelector('[data-menos]').addEventListener('click', function () { cambiar(-1); });
        selector.querySelector('[data-mas]').addEventListener('click', function () { cambiar(1); });

        
        campo.addEventListener('input', function () {
            campo.value = campo.value.replace(/\D/g, '').slice(0, 2);
            if (!isNaN(leer()) && leer() > maximo) {
                campo.value = String(maximo);
            }
        });
        campo.addEventListener('change', function () {
            if (campo.value === '' || leer() === 0) {
                campo.value = enviarAlCambiar ? valorAnterior : '1';
            }
            guardar();
        });
    });

    
    var opcionesEntrega = document.querySelectorAll('[data-entrega]');
    var campoDireccion = document.querySelector('[data-campo-direccion]');
    if (opcionesEntrega.length > 0 && campoDireccion) {
        var direccion = campoDireccion.querySelector('textarea');

        function mostrarDireccion() {
            var escogida = document.querySelector('[data-entrega]:checked');
            var esEnvio = escogida !== null && escogida.value === 'Envio';
            campoDireccion.hidden = !esEnvio;
            direccion.required = esEnvio;
            if (!esEnvio) {
                campoDireccion.classList.remove('campoConError');
                var error = campoDireccion.querySelector('.errorCampo');
                if (error) {
                    error.remove();
                }
            }
        }

        opcionesEntrega.forEach(function (opcion) {
            opcion.addEventListener('change', mostrarDireccion);
        });
        mostrarDireccion();
    }
});
