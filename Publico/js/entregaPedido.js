document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.getElementById('formularioEntrega');
    if (!formulario) {
        return;
    }
    var campoVencimiento = document.getElementById('campoVencimiento');
    var vencimiento = document.getElementById('vencimiento');

    function actualizar() {
        var elegido = formulario.querySelector('input[name="pago"]:checked');
        var debe = elegido !== null && elegido.value === 'pendiente';
        campoVencimiento.hidden = !debe;
        vencimiento.required = debe;
    }

    formulario.querySelectorAll('input[name="pago"]').forEach(function (opcion) {
        opcion.addEventListener('change', actualizar);
    });
    actualizar();
});
