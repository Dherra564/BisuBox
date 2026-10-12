document.addEventListener('DOMContentLoaded', function () {
    var unidad = document.querySelector('[data-unidad]');
    if (!unidad) {
        return;
    }
    var existencia = document.getElementById('existencia');
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

    function actualizar() {
        var opcion = unidad.options[unidad.selectedIndex];
        document.querySelectorAll('[data-unidad-plural]').forEach(function (texto) {
            texto.textContent = opcion.getAttribute('data-plural');
        });
        document.querySelectorAll('[data-unidad-singular]').forEach(function (texto) {
            texto.textContent = opcion.getAttribute('data-singular');
        });
        document.querySelectorAll('[data-unidad-ayuda]').forEach(function (texto) {
            texto.textContent = opcion.getAttribute('data-ayuda');
        });

        if (costoCalculado) {
            var cantidad = leerNumero(existencia.value) || 0;
            var total = leerNumero(costoTotal.value);
            costoCalculado.textContent = cantidad > 0 && total !== null
                ? opcion.getAttribute('data-costo') + ': ' + moneda(Math.round(total / cantidad * 100) / 100)
                : '';
        }
    }

    unidad.addEventListener('change', actualizar);
    if (existencia && costoTotal) {
        existencia.addEventListener('input', actualizar);
        costoTotal.addEventListener('input', actualizar);
    }
    actualizar();
});