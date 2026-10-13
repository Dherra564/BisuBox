document.addEventListener('DOMContentLoaded', function () {
    var telefono = document.querySelector('[data-mascara-telefono]');
    if (!telefono) {
        return;
    }

    function darFormato(valor) {
        var numeros = valor.replace(/\D/g, '');
        if (numeros.length > 8 && numeros.indexOf('506') === 0) {
            numeros = numeros.slice(3);
        }
        numeros = numeros.slice(0, 8);
        return numeros.length > 4 ? numeros.slice(0, 4) + '-' + numeros.slice(4) : numeros;
    }

    telefono.addEventListener('input', function () {
        var conFormato = darFormato(telefono.value);
        if (conFormato !== telefono.value) {
            telefono.value = conFormato;
        }
    });

    telefono.value = darFormato(telefono.value);
});