document.addEventListener('DOMContentLoaded', function () {
    var opciones = document.querySelectorAll('[data-unidad]');
    if (opciones.length === 0) {
        return;
    }
    var existencia = document.getElementById('existencia');
    var medida = document.querySelector('[data-medida]');
    var unidadExistencia = document.querySelector('[data-unidad-existencia]');
    var costoTotal = document.querySelector('[data-costo-total]');
    var costoCalculado = document.querySelector('[data-costo-calculado]');

    function leerNumero(texto) {
        texto = texto.replace(/\s/g, '');
        return /^[0-9]{1,10}([.,][0-9]{1,2})?$/.test(texto) ? parseFloat(texto.replace(',', '.')) : null;
    }

    function moneda(monto) {
        var decimales = Math.round(monto * 100) % 100 === 0 ? 0 : 2;
        var partes = monto.toFixed(decimales).split('.');
        return '₡' + partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (partes.length > 1 ? ',' + partes[1] : '');
    }

    function elegida() {
        return document.querySelector('[data-unidad]:checked') || opciones[0];
    }

    function cantidadEnUnidad(opcion) {
        var cantidad = leerNumero(existencia.value) || 0;
        return opcion.value === 'Metro' && medida && medida.value === 'cm' ? cantidad / 100 : cantidad;
    }

    function actualizar() {
        var opcion = elegida();
        var esMetro = opcion.value === 'Metro';
        document.querySelectorAll('[data-unidad-plural]').forEach(function (texto) {
            texto.textContent = opcion.getAttribute('data-plural');
        });
        document.querySelectorAll('[data-unidad-ayuda]').forEach(function (texto) {
            texto.textContent = opcion.getAttribute('data-ayuda');
        });
        if (medida && unidadExistencia) {
            medida.hidden = !esMetro;
            unidadExistencia.hidden = esMetro;
        }

        if (costoCalculado) {
            var cantidad = cantidadEnUnidad(opcion);
            var total = leerNumero(costoTotal.value);
            costoCalculado.textContent = cantidad > 0 && total !== null
                ? opcion.getAttribute('data-costo') + ': ' + moneda(Math.round(total / cantidad * 100) / 100)
                : '';
        }
    }

    opciones.forEach(function (opcion) {
        opcion.addEventListener('change', actualizar);
    });
    if (existencia && costoTotal) {
        existencia.addEventListener('input', actualizar);
        costoTotal.addEventListener('input', actualizar);
        if (medida) {
            medida.addEventListener('change', actualizar);
        }
    }
    actualizar();
});