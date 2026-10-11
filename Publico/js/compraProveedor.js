document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('.formularioCompra');
    if (!formulario) {
        return;
    }

    var lista = formulario.querySelector('[data-lineas]');
    var plantilla = document.querySelector('[data-linea-plantilla]');
    var botonAgregar = formulario.querySelector('[data-linea-agregar]');
    var cuenta = formulario.querySelector('[data-lineas-cuenta]');
    var campoEnvio = formulario.querySelector('[data-costo-envio]');
    var maximo = parseInt(formulario.getAttribute('data-maximo-lineas'), 10) || 50;
    var patronNumero = /^[0-9]{1,10}([.,][0-9]{1,2})?$/;

    var siguienteClave = 0;
    lista.querySelectorAll('[data-linea]').forEach(function (linea) {
        siguienteClave = Math.max(siguienteClave, parseInt(linea.getAttribute('data-clave'), 10) + 1);
    });

    function leerNumero(texto) {
        texto = texto.replace(/\s/g, '');
        return patronNumero.test(texto) ? parseFloat(texto.replace(',', '.')) : null;
    }

    function formatear(numero, decimales) {
        var partes = numero.toFixed(decimales).split('.');
        var enteros = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return partes.length > 1 ? enteros + ',' + partes[1] : enteros;
    }

    function moneda(monto) {
        return '₡' + formatear(monto, Math.round(monto * 100) % 100 === 0 ? 0 : 2);
    }

    function cantidadTexto(numero) {
        return formatear(numero, 2).replace(/,?0+$/, '');
    }

    function lineas() {
        return Array.prototype.slice.call(lista.querySelectorAll('[data-linea]'));
    }

    function datosUnidad(linea) {
        var select = linea.querySelector('[data-linea-componente]');
        var opcion = select.options[select.selectedIndex];
        if (select.value === 'nuevo') {
            var unidad = linea.querySelector('[data-linea-unidad-nueva]');
            opcion = unidad.options[unidad.selectedIndex];
        }
        return opcion && opcion.getAttribute('data-plural')
            ? { plural: opcion.getAttribute('data-plural'), entera: opcion.getAttribute('data-entera') === '1' }
            : { plural: '', entera: false };
    }

    function actualizarLinea(linea) {
        var select = linea.querySelector('[data-linea-componente]');
        var esNuevo = select.value === 'nuevo';
        linea.querySelector('[data-linea-nuevo]').hidden = !esNuevo;

        var unidad = datosUnidad(linea);
        linea.querySelector('[data-linea-unidad]').textContent = unidad.plural;

        var cantidad = leerNumero(linea.querySelector('[data-linea-cantidad]').value) || 0;
        var costo = leerNumero(linea.querySelector('[data-linea-costo]').value) || 0;
        var subtotal = Math.round(cantidad * costo * 100) / 100;
        linea.querySelector('[data-linea-subtotal]').textContent = moneda(subtotal);

        var aviso = linea.querySelector('[data-linea-aviso]');
        var opcion = select.options[select.selectedIndex];
        if (esNuevo) {
            aviso.textContent = cantidad > 0 ? 'Entrará al inventario con ' + cantidadTexto(cantidad) + ' ' + unidad.plural : '';
        } else if (opcion && opcion.hasAttribute('data-existencia')) {
            var existencia = parseFloat(opcion.getAttribute('data-existencia')) || 0;
            aviso.textContent = 'Tiene ' + cantidadTexto(existencia) + ' ' + unidad.plural
                + (cantidad > 0 ? ' → quedará con ' + cantidadTexto(existencia + cantidad) : '');
        } else {
            aviso.textContent = '';
        }
        aviso.hidden = aviso.textContent === '';

        return subtotal;
    }

    function actualizar() {
        var subtotal = 0;
        lineas().forEach(function (linea) {
            subtotal += actualizarLinea(linea);
        });
        var envio = leerNumero(campoEnvio.value) || 0;
        formulario.querySelector('[data-total-subtotal]').textContent = moneda(subtotal);
        formulario.querySelector('[data-total-envio]').textContent = moneda(envio);
        formulario.querySelector('[data-total-compra]').textContent = moneda(subtotal + envio);

        var total = lineas().length;
        cuenta.textContent = total === 1 ? '1 componente' : total + ' componentes';
        botonAgregar.disabled = total >= maximo;
        lineas().forEach(function (linea) {
            linea.querySelector('[data-linea-quitar]').hidden = total === 1;
        });
    }

    function quitarErrores(contenedor) {
        contenedor.querySelectorAll('.campoConError').forEach(function (campo) {
            campo.classList.remove('campoConError');
        });
        contenedor.querySelectorAll('.errorCampo').forEach(function (error) {
            error.remove();
        });
    }

    function ponerError(campo, mensaje) {
        var contenedor = campo.closest('.campo');
        contenedor.classList.add('campoConError');
        var error = document.createElement('p');
        error.className = 'errorCampo';
        error.textContent = mensaje;
        contenedor.appendChild(error);
    }

    botonAgregar.addEventListener('click', function () {
        if (lineas().length >= maximo) {
            return;
        }
        var contenedor = document.createElement('div');
        contenedor.innerHTML = plantilla.innerHTML.replace(/__n__/g, String(siguienteClave++)).trim();
        var linea = contenedor.firstElementChild;
        lista.appendChild(linea);
        actualizar();
        linea.querySelector('[data-linea-componente]').focus();
    });

    lista.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-linea-quitar]');
        if (boton && lineas().length > 1) {
            boton.closest('[data-linea]').remove();
            actualizar();
            botonAgregar.focus();
        }
    });

    formulario.addEventListener('change', function (evento) {
        var linea = evento.target.closest('[data-linea]');
        if (linea) {
            quitarErrores(linea);
            if (evento.target.matches('[data-linea-componente]') && evento.target.value === 'nuevo') {
                linea.querySelector('[data-linea-nuevo] select').focus();
            }
        }
        actualizar();
    });
    formulario.addEventListener('input', actualizar);

    formulario.addEventListener('submit', function (evento) {
        var primero = null;
        var usados = [];

        lineas().forEach(function (linea) {
            quitarErrores(linea);
            var select = linea.querySelector('[data-linea-componente]');
            var cantidad = linea.querySelector('[data-linea-cantidad]');
            var costo = linea.querySelector('[data-linea-costo]');
            var errores = [];

            if (select.value === '') {
                errores.push([select, 'Escoja el componente o cree uno nuevo']);
            } else if (select.value === 'nuevo') {
                var tipo = linea.querySelector('[data-linea-nuevo] select');
                var nombre = linea.querySelector('[data-linea-nuevo] input');
                if (tipo.value === '') {
                    errores.push([tipo, 'Escoja el tipo del componente nuevo']);
                }
                if (nombre.value.trim().length < 2) {
                    errores.push([nombre, 'Escriba el nombre del componente nuevo']);
                }
            } else if (usados.indexOf(select.value) !== -1) {
                errores.push([select, 'Este componente ya está en otra línea; sume las cantidades en una sola']);
            } else {
                usados.push(select.value);
            }

            var numeroCantidad = leerNumero(cantidad.value);
            if (cantidad.value.trim() === '') {
                errores.push([cantidad, 'Escriba cuántos compró']);
            } else if (numeroCantidad === null) {
                errores.push([cantidad, 'Escriba un número, por ejemplo 200 o 1,5']);
            } else if (numeroCantidad <= 0) {
                errores.push([cantidad, 'La cantidad debe ser mayor que 0']);
            } else if (datosUnidad(linea).entera && Math.floor(numeroCantidad) !== numeroCantidad) {
                errores.push([cantidad, 'Se cuenta por unidades: use números enteros']);
            }

            if (costo.value.trim() === '') {
                errores.push([costo, 'Escriba cuánto costó cada uno']);
            } else if (leerNumero(costo.value) === null) {
                errores.push([costo, 'Escriba un monto, por ejemplo 12 o 12,50']);
            }

            errores.forEach(function (error) {
                ponerError(error[0], error[1]);
                primero = primero || error[0];
            });
        });

        if (primero) {
            evento.preventDefault();
            primero.focus();
        }
    });

    actualizar();
});