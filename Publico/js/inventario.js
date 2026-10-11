document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('[data-filtros]');
    if (!formulario) {
        return;
    }

    document.querySelectorAll('[data-enviar-al-cambiar]').forEach(function (lista) {
        lista.addEventListener('change', function () {
            if (lista.hasAttribute('data-quita-datos')) {
                var datos = formulario.querySelector('[data-filtros-datos]');
                if (datos) {
                    datos.remove();
                }
            }
            if (formulario.requestSubmit) {
                formulario.requestSubmit();
            } else {
                formulario.submit();
            }
        });
    });

    formulario.addEventListener('submit', function () {
        Array.prototype.forEach.call(formulario.elements, function (campo) {
            if (campo.name && (campo.value === '' || campo.value === campo.getAttribute('data-por-defecto'))) {
                campo.disabled = true;
            }
        });
    });

    window.addEventListener('pageshow', function () {
        Array.prototype.forEach.call(formulario.elements, function (campo) {
            campo.disabled = false;
        });
    });
});