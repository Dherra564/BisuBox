document.addEventListener('DOMContentLoaded', function () {
    var unidad = document.querySelector('[data-unidad]');
    if (!unidad) {
        return;
    }

    function actualizarUnidad() {
        var opcion = unidad.options[unidad.selectedIndex];
        document.querySelectorAll('[data-unidad-plural]').forEach(function (texto) {
            texto.textContent = opcion.getAttribute('data-plural');
        });
        document.querySelectorAll('[data-unidad-singular]').forEach(function (texto) {
            texto.textContent = opcion.getAttribute('data-singular');
        });
    }

    unidad.addEventListener('change', actualizarUnidad);
    actualizarUnidad();
});