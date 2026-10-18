document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('[data-personalizar]');
    if (!formulario) {
        return;
    }

    var campoPiezas = formulario.querySelector('[data-piezas]');
    var galerias = Array.prototype.slice.call(formulario.querySelectorAll('[data-galeria]'));
    var tarjetas = Array.prototype.slice.call(formulario.querySelectorAll('[data-pieza]'));
    var total = formulario.querySelector('[data-total]');
    var listaCambios = formulario.querySelector('[data-lista-cambios]');
    var sinCambios = formulario.querySelector('[data-sin-cambios]');
    var textoCambios = formulario.querySelector('[data-texto-cambios]');
    var resumenPiezas = formulario.querySelector('[data-resumen-piezas]');
    var resumenPalabra = formulario.querySelector('[data-resumen-palabra]');
    var alerta = formulario.querySelector('[data-alerta-inventario]');
    var botonEnviar = formulario.querySelector('[data-enviar]');

    function conMiles(entero) {
        return String(entero).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function numero(valor) {
        var redondo = Math.round(valor * 100) / 100;
        var entero = Math.floor(redondo);
        var decimales = Math.round((redondo - entero) * 100);
        var texto = conMiles(entero);
        if (decimales > 0) {
            texto += ',' + String(decimales).padStart(2, '0').replace(/0$/, '');
        }
        return texto;
    }

    function moneda(valor) {
        var redondo = Math.round(valor * 100) / 100;
        var entero = Math.floor(redondo);
        var decimales = Math.round((redondo - entero) * 100);
        return '₡' + conMiles(entero) + (decimales > 0 ? ',' + String(decimales).padStart(2, '0') : '');
    }

    function piezas() {
        var cantidad = parseInt(campoPiezas.value, 10);
        return cantidad >= 1 ? cantidad : 1;
    }

    function elegido(galeria) {
        return galeria.querySelector('input[type="radio"]:checked');
    }

    function tarjetaDe(id) {
        return formulario.querySelector('[data-pieza="' + id + '"]');
    }

    function activar(id, desplazar) {
        galerias.forEach(function (galeria) {
            galeria.hidden = galeria.getAttribute('data-galeria') !== id;
        });
        tarjetas.forEach(function (tarjeta) {
            var activa = tarjeta.getAttribute('data-pieza') === id;
            tarjeta.classList.toggle('tarjetaPiezaActiva', activa);
            tarjeta.setAttribute('aria-pressed', activa ? 'true' : 'false');
        });
        if (desplazar) {
            var galeria = document.getElementById('galeria-' + id);
            if (galeria) {
                galeria.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }

    function actualizar() {
        var cantidadPiezas = piezas();
        var necesidades = {};
        var suma = 0;

        galerias.forEach(function (galeria) {
            var opcion = elegido(galeria);
            if (!opcion) {
                return;
            }
            var cantidad = parseFloat(galeria.getAttribute('data-cantidad')) * cantidadPiezas;
            necesidades[opcion.value] = (necesidades[opcion.value] || 0) + cantidad;
            suma += parseFloat(opcion.getAttribute('data-costo')) * cantidad;
        });

        var hayErrores = false;
        var cambios = [];

        galerias.forEach(function (galeria) {
            var id = galeria.getAttribute('data-galeria');
            var tarjeta = tarjetaDe(id);
            var opcion = elegido(galeria);
            var error = galeria.querySelector('[data-error-galeria]');
            var volver = galeria.querySelector('[data-volver-original]');
            var mensaje = '';
            var cambiado = false;

            if (!opcion) {
                mensaje = 'Escoja una para poder enviar su pedido.';
            } else {
                var necesita = necesidades[opcion.value];
                var hay = parseFloat(opcion.getAttribute('data-existencia'));
                if (necesita > hay) {
                    mensaje = 'Para ' + cantidadPiezas + (cantidadPiezas === 1 ? ' pieza' : ' piezas') + ' necesita ' + numero(necesita)
                        + ' de «' + opcion.getAttribute('data-nombre') + '» y en la tienda solo quedan ' + numero(hay)
                        + '. Escoja otra o pida menos piezas.';
                }
                cambiado = opcion.value !== galeria.getAttribute('data-original');
                tarjeta.querySelector('[data-pieza-foto]').src = opcion.getAttribute('data-foto');
                tarjeta.querySelector('[data-pieza-nombre]').textContent = opcion.getAttribute('data-nombre');
                if (cambiado) {
                    cambios.push(galeria.getAttribute('data-nombre-original') + ' → ' + opcion.getAttribute('data-nombre'));
                }
            }

            error.textContent = mensaje;
            error.hidden = mensaje === '';
            tarjeta.querySelector('[data-pieza-cambiado]').hidden = !cambiado;
            tarjeta.querySelector('[data-pieza-falta]').hidden = mensaje === '';
            tarjeta.classList.toggle('tarjetaPiezaError', mensaje !== '');
            if (volver) {
                volver.hidden = !cambiado;
            }
            if (mensaje !== '') {
                hayErrores = true;
            }
        });

        total.textContent = moneda(suma);
        resumenPiezas.textContent = String(cantidadPiezas);
        resumenPalabra.textContent = cantidadPiezas === 1 ? 'pieza' : 'piezas';

        listaCambios.innerHTML = '';
        cambios.forEach(function (cambio) {
            var item = document.createElement('li');
            item.textContent = cambio;
            listaCambios.appendChild(item);
        });
        sinCambios.hidden = cambios.length > 0;
        textoCambios.textContent = cambios.length === 0 ? 'Sin cambios' : (cambios.length === 1 ? '1 cambio' : cambios.length + ' cambios');

        alerta.hidden = !hayErrores;
        botonEnviar.disabled = hayErrores;
    }

    tarjetas.forEach(function (tarjeta) {
        tarjeta.addEventListener('click', function () {
            activar(tarjeta.getAttribute('data-pieza'), true);
        });
    });

    galerias.forEach(function (galeria) {
        var volver = galeria.querySelector('[data-volver-original]');
        if (volver) {
            volver.addEventListener('click', function () {
                var original = galeria.querySelector('input[value="' + galeria.getAttribute('data-original') + '"]');
                if (original && !original.disabled) {
                    original.checked = true;
                    actualizar();
                }
            });
        }
        galeria.addEventListener('change', function () {
            galeria.querySelectorAll('[data-error-servidor]').forEach(function (error) {
                error.remove();
            });
        });
    });

    formulario.addEventListener('change', actualizar);
    formulario.addEventListener('click', function (evento) {
        if (evento.target.closest('[data-selector-cantidad] button')) {
            actualizar();
        }
    });
    campoPiezas.addEventListener('input', actualizar);

    activar(formulario.getAttribute('data-primera') || (tarjetas[0] && tarjetas[0].getAttribute('data-pieza')), false);
    actualizar();
});