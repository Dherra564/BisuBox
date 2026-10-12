document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-enviar-al-cambiar]').forEach(function (campo) {
        campo.addEventListener('change', function () {
            campo.form.submit();
        });
    });
});