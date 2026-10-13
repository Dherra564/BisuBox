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

    var formulario = document.querySelector('.formularioComponente');
    var patronNombre = /^[\p{L}0-9][\p{L}0-9 .,()#\/&'-]*$/u;
    var signos = 'puede tener letras, números, espacios y los signos . , ( ) # / & \' -';

    function ponerError(campo, mensaje) {
        var contenedor = campo.closest('.campo');
        var anterior = contenedor.querySelector('.errorCampo');
        if (anterior) {
            anterior.remove();
        }
        contenedor.classList.add('campoConError');
        var error = document.createElement('p');
        error.className = 'errorCampo';
        error.textContent = mensaje;
        contenedor.appendChild(error);
    }

    function revisarCantidad(campo, entera, enCentimetros) {
        var valor = campo.value.trim();
        if (valor === '') {
            return '';
        }
        if (valor.charAt(0) === '-') {
            return 'No puede ser un número negativo';
        }
        var numero = leerNumero(valor);
        if (numero === null) {
            return '';
        }
        if (entera && Math.floor(numero) !== numero) {
            return 'Si se mide por unidades, use números enteros';
        }
        if (enCentimetros && Math.floor(numero) !== numero) {
            return 'Los centímetros van sin decimales';
        }
        return '';
    }

    function limpiar(texto) {
        return texto.replace(/\s+/g, ' ').trim();
    }

    formulario.addEventListener('submit', function (evento) {
        var yaHabiaErrores = evento.defaultPrevented;
        var opcion = elegida();
        var enUnidades = opcion.value === 'Unidad';
        var revisiones = [];

        var nombre = formulario.querySelector('#nombre');
        var textoNombre = limpiar(nombre.value);
        if (textoNombre.length >= 2 && textoNombre.length <= 100 && !patronNombre.test(textoNombre)) {
            revisiones.push([nombre, 'El nombre solo ' + signos]);
        }

        if (existencia && costoTotal) {
            var enCentimetros = opcion.value === 'Metro' && medida && medida.value === 'cm';
            var mensajeExistencia = revisarCantidad(existencia, enUnidades, enCentimetros);
            revisiones.push([existencia, mensajeExistencia]);
            var tiene = mensajeExistencia === '' && !existencia.closest('.campoConError') ? leerNumero(existencia.value) || 0 : 0;
            revisiones.push([costoTotal, costoTotal.value.trim() === '' && tiene > 0
                ? 'Escriba cuánto pagó en total por lo que tiene. Si no lo sabe, ponga 0'
                : revisarCantidad(costoTotal, false, false)]);
        }

        var minima = formulario.querySelector('#existenciaMinima');
        revisiones.push([minima, revisarCantidad(minima, enUnidades, false)]);

        formulario.querySelectorAll('[data-atributo-numero]').forEach(function (campo) {
            revisiones.push([campo, campo.value.trim().charAt(0) === '-' ? 'No puede ser un número negativo' : '']);
        });
        formulario.querySelectorAll('[data-atributo-texto]').forEach(function (campo) {
            var texto = limpiar(campo.value);
            revisiones.push([campo, texto !== '' && !patronNombre.test(texto) ? 'Solo ' + signos : '']);
        });

        var primero = null;
        revisiones.forEach(function (revision) {
            if (revision[1] !== '') {
                ponerError(revision[0], revision[1]);
                primero = primero || revision[0];
            }
        });

        if (primero) {
            evento.preventDefault();
            if (!yaHabiaErrores) {
                primero.focus();
            }
        }
    });
});