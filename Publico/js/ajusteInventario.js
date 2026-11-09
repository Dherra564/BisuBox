document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('[data-ajuste]');
    if (!formulario) {
        return;
    }

    var cantidad = formulario.querySelector('[data-regla="ajuste"]');
    var resultado = formulario.querySelector('[data-ajuste-resultado]');
    var existencia = parseFloat(cantidad.getAttribute('data-existencia'));

    function formatear(numero) {
        return (Math.round(numero * 100) / 100).toLocaleString('es-CR', { maximumFractionDigits: 2 });
    }

    function mostrarResultado() {
        var tipo = formulario.querySelector('input[name="tipo"]:checked');
        var texto = cantidad.value.replace(/ /g, '').replace(',', '.');
        var numero = /^[0-9]{1,10}(\.[0-9]{1,2})?$/.test(texto) ? parseFloat(texto) : NaN;
        if (!tipo || isNaN(numero) || numero <= 0) {
            resultado.textContent = '';
            return;
        }
        var nueva = tipo.value === 'Salida' ? existencia - numero : existencia + numero;
        resultado.textContent = nueva < 0 ? '' : 'Quedará en ' + formatear(nueva) + '.';
    }

    formulario.querySelectorAll('input[name="tipo"]').forEach(function (opcion) {
        opcion.addEventListener('change', function () {
            if (cantidad.value !== '') {
                cantidad.dispatchEvent(new Event('input', { bubbles: true }));
            }
            mostrarResultado();
        });
    });
    cantidad.addEventListener('input', mostrarResultado);
    mostrarResultado();
});