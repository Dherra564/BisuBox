document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('[data-cotizar]');
    if (!formulario) {
        return;
    }

    var componentes = parseFloat(formulario.getAttribute('data-componentes')) || 0;
    var manoObra = parseFloat(formulario.getAttribute('data-mano-obra')) || 0;
    var piezas = parseInt(formulario.getAttribute('data-piezas'), 10) || 1;
    var lista = formulario.querySelector('[data-lista-extras]');
    var plantilla = formulario.querySelector('[data-plantilla-extra]');
    var maximo = parseInt(lista.getAttribute('data-maximo'), 10) || 20;
    var botonAgregar = formulario.querySelector('[data-extra-agregar]');
    var campoPrecio = formulario.querySelector('[data-precio]');
    var resultado = formulario.querySelector('[data-resultado-ganancia]');
    var siguienteClave = 1000;

    function conMiles(entero) {
        return String(entero).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function moneda(valor) {
        var negativo = valor < 0;
        var redondo = Math.round(Math.abs(valor) * 100) / 100;
        var entero = Math.floor(redondo);
        var decimales = Math.round((redondo - entero) * 100);
        return (negativo ? '-' : '') + '₡' + conMiles(entero) + (decimales > 0 ? ',' + String(decimales).padStart(2, '0') : '');
    }

    function leerNumero(texto) {
        var limpio = String(texto).replace(/\s/g, '');
        if (!/^[0-9]{1,10}([.,][0-9]{1,2})?$/.test(limpio)) {
            return null;
        }
        return parseFloat(limpio.replace(',', '.'));
    }

    function totalExtras() {
        var suma = 0;
        lista.querySelectorAll('[data-extra-monto]').forEach(function (campo) {
            var monto = leerNumero(campo.value);
            if (monto !== null) {
                suma += monto;
            }
        });
        return suma;
    }

    function costo() {
        return componentes + manoObra + totalExtras();
    }

    function gananciaEscogida() {
        var escogida = formulario.querySelector('[data-ganancia]:checked');
        return escogida ? parseInt(escogida.value, 10) : null;
    }

    function aplicarGanancia() {
        var porcentaje = gananciaEscogida();
        if (porcentaje !== null && costo() > 0) {
            campoPrecio.value = String(Math.ceil(costo() * (1 + porcentaje / 100) / 10) * 10);
        }
    }

    function mostrar() {
        var costoPieza = costo();
        formulario.querySelector('[data-total-extras]').textContent = moneda(totalExtras());
        formulario.querySelector('[data-costo]').textContent = moneda(costoPieza);

        var precio = leerNumero(campoPrecio.value);
        formulario.querySelector('[data-total]').textContent = precio !== null ? moneda(precio * piezas) : '₡0';

        if (precio === null || precio <= 0 || costoPieza <= 0) {
            resultado.hidden = true;
            return;
        }
        var diferencia = Math.round((precio - costoPieza) * 100) / 100;
        var porcentaje = Math.round(diferencia / costoPieza * 100);
        resultado.textContent = diferencia >= 0
            ? 'Gana ' + moneda(diferencia) + ' por pieza (' + porcentaje + '%)'
            : 'Pierde ' + moneda(-diferencia) + ' por pieza: el precio no cubre el costo';
        resultado.classList.toggle('gananciaNegativa', diferencia < 0);
        resultado.hidden = false;
    }

    function revisarMaximo() {
        botonAgregar.disabled = lista.querySelectorAll('[data-extra]').length >= maximo;
    }

    botonAgregar.addEventListener('click', function () {
        var contenedor = document.createElement('div');
        contenedor.innerHTML = plantilla.innerHTML.replace(/__CLAVE__/g, String(siguienteClave++));
        var fila = contenedor.firstElementChild;
        lista.appendChild(fila);
        fila.querySelector('input').focus();
        revisarMaximo();
    });

    lista.addEventListener('click', function (evento) {
        var quitar = evento.target.closest('[data-extra-quitar]');
        if (quitar) {
            quitar.closest('[data-extra]').remove();
            if (gananciaEscogida() !== null) {
                aplicarGanancia();
            }
            mostrar();
            revisarMaximo();
        }
    });

    lista.addEventListener('input', function (evento) {
        if (evento.target.hasAttribute('data-extra-monto')) {
            if (gananciaEscogida() !== null) {
                aplicarGanancia();
            }
            mostrar();
        }
    });

    formulario.querySelectorAll('[data-ganancia]').forEach(function (opcion) {
        opcion.addEventListener('change', function () {
            aplicarGanancia();
            mostrar();
        });
    });

    campoPrecio.addEventListener('input', function () {
        formulario.querySelectorAll('[data-ganancia]').forEach(function (opcion) {
            opcion.checked = false;
        });
        mostrar();
    });

    if (campoPrecio.value === '' && gananciaEscogida() === null) {
        var sugerida = formulario.querySelector('[data-ganancia][value="50"]');
        if (sugerida) {
            sugerida.checked = true;
            aplicarGanancia();
        }
    }
    revisarMaximo();
    mostrar();
});