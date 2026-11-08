document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('[data-cotizacion]');
    if (!formulario) {
        return;
    }

    var patronNumero = /^[0-9]{1,10}([.,][0-9]{1,2})?$/;
    var patronMensaje = /^[\p{L}0-9 .,;:¡!¿?()"'\/&%#+-]*$/u;
    var mensajeTexto = 'El mensaje solo puede tener letras, números, espacios y los signos . , ; : ¡ ! ¿ ? ( ) " \' / & % # + -';

    var listaComponentes = formulario.querySelector('[data-lineas]');
    var plantilla = document.querySelector('[data-linea-plantilla]');
    var botonAgregar = formulario.querySelector('[data-linea-agregar]');
    var campoManoObra = formulario.querySelector('[data-mano-obra]');
    var campoPrecio = formulario.querySelector('[data-precio-venta]');
    var campoMensaje = formulario.querySelector('[data-mensaje]');
    var opcionesGanancia = formulario.querySelectorAll('[data-ganancia]');
    var resultado = formulario.querySelector('[data-resultado-ganancia]');
    var maximo = parseInt(formulario.getAttribute('data-maximo-componentes'), 10) || 50;
    var costoActual = 0;

    function esNegativo(campo) {
        return campo.value.trim().charAt(0) === '-';
    }

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
        return Array.prototype.slice.call(listaComponentes.querySelectorAll('[data-linea]'));
    }

    function opcionDe(linea) {
        var select = linea.querySelector('[data-linea-componente]');
        return select.value !== '' ? select.options[select.selectedIndex] : null;
    }

    function enCentimetros(linea) {
        var opcion = opcionDe(linea);
        return opcion !== null && opcion.getAttribute('data-metro') === '1' && linea.querySelector('[data-linea-medida]').value === 'cm';
    }

    function cantidadEnUnidad(linea) {
        var cantidad = leerNumero(linea.querySelector('[data-linea-cantidad]').value) || 0;
        return enCentimetros(linea) ? cantidad / 100 : cantidad;
    }

    function siguienteClave() {
        var siguiente = 0;
        lineas().forEach(function (linea) {
            siguiente = Math.max(siguiente, parseInt(linea.getAttribute('data-clave'), 10) + 1);
        });
        return siguiente;
    }

    var clave = siguienteClave();

    function actualizarLinea(linea) {
        var opcion = opcionDe(linea);
        var esMetro = opcion !== null && opcion.getAttribute('data-metro') === '1';
        var plural = opcion !== null ? opcion.getAttribute('data-plural') : '';
        var costo = opcion !== null ? parseFloat(opcion.getAttribute('data-costo')) || 0 : 0;
        var existencia = opcion !== null ? parseFloat(opcion.getAttribute('data-existencia')) || 0 : 0;
        var subtotal = Math.round(cantidadEnUnidad(linea) * costo * 100) / 100;

        linea.querySelector('[data-linea-medida]').hidden = !esMetro;
        linea.querySelector('[data-linea-unidad]').hidden = esMetro;
        linea.querySelector('[data-linea-unidad]').textContent = plural;
        linea.querySelector('[data-linea-subtotal]').textContent = moneda(subtotal);

        var aviso = linea.querySelector('[data-linea-aviso]');
        aviso.textContent = opcion !== null ? 'Tiene ' + cantidadTexto(existencia) + ' ' + plural : '';
        aviso.hidden = aviso.textContent === '';

        return subtotal;
    }

    function gananciaEscogida() {
        var escogida = formulario.querySelector('[data-ganancia]:checked');
        return escogida ? parseInt(escogida.value, 10) : null;
    }

    function mostrarResultado() {
        var precio = leerNumero(campoPrecio.value);
        if (precio === null || precio <= 0 || costoActual <= 0) {
            resultado.hidden = true;
            return;
        }
        var diferencia = Math.round((precio - costoActual) * 100) / 100;
        var porcentaje = Math.round(diferencia / costoActual * 100);
        resultado.textContent = diferencia >= 0
            ? 'Gana ' + moneda(diferencia) + ' (' + porcentaje + '%)'
            : 'Pierde ' + moneda(-diferencia) + ': el precio no cubre el costo';
        resultado.classList.toggle('gananciaNegativa', diferencia < 0);
        resultado.hidden = false;
    }

    function aplicarGanancia() {
        var porcentaje = gananciaEscogida();
        if (porcentaje !== null && costoActual > 0) {
            campoPrecio.value = String(Math.ceil(costoActual * (1 + porcentaje / 100) / 10) * 10);
        }
        mostrarResultado();
    }

    function actualizar() {
        var totalComponentes = 0;
        lineas().forEach(function (linea) {
            totalComponentes += actualizarLinea(linea);
        });
        totalComponentes = Math.round(totalComponentes * 100) / 100;
        var manoObra = leerNumero(campoManoObra.value) || 0;
        costoActual = Math.round((totalComponentes + manoObra) * 100) / 100;

        formulario.querySelector('[data-total-componentes]').textContent = moneda(totalComponentes);
        formulario.querySelector('[data-total-mano-obra]').textContent = moneda(manoObra);
        formulario.querySelector('[data-costo-total]').textContent = moneda(costoActual);

        if (gananciaEscogida() !== null) {
            aplicarGanancia();
        } else {
            mostrarResultado();
        }

        var cantidad = lineas().length;
        botonAgregar.disabled = cantidad >= maximo;
        lineas().forEach(function (linea) {
            linea.querySelector('[data-linea-quitar]').hidden = cantidad === 1;
        });
    }

    function quitarErrores(contenedor) {
        contenedor.querySelectorAll('.campoConError').forEach(function (campo) {
            campo.classList.remove('campoConError');
        });
        contenedor.querySelectorAll('.errorCampo:not(.avisoExistencia)').forEach(function (error) {
            error.remove();
        });
    }

    function ponerError(campo, mensaje) {
        var contenedor = campo.closest('.campo');
        var anterior = contenedor.querySelector('.errorCampo:not(.avisoExistencia)');
        if (anterior) {
            anterior.remove();
        }
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
        contenedor.innerHTML = plantilla.innerHTML.replace(/__n__/g, String(clave++)).trim();
        var fila = contenedor.firstElementChild;
        listaComponentes.appendChild(fila);
        actualizar();
        fila.querySelector('[data-linea-componente]').focus();
    });

    listaComponentes.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-linea-quitar]');
        if (boton && lineas().length > 1) {
            boton.closest('[data-linea]').remove();
            actualizar();
            botonAgregar.focus();
        }
    });

    formulario.addEventListener('change', function (evento) {
        var fila = evento.target.closest('[data-linea]');
        if (fila) {
            quitarErrores(fila);
        }
        actualizar();
    });

    formulario.addEventListener('input', function (evento) {
        if (evento.target.matches('[data-precio-venta]')) {
            opcionesGanancia.forEach(function (opcion) {
                opcion.checked = false;
            });
            mostrarResultado();
            return;
        }
        if (evento.target.matches('[data-mano-obra]')) {
            quitarErrores(evento.target.closest('.campo'));
        }
        actualizar();
    });

    formulario.addEventListener('submit', function (evento) {
        var primero = null;
        var usados = [];

        function marcar(campo, mensaje) {
            ponerError(campo, mensaje);
            primero = primero || campo;
        }

        lineas().forEach(function (linea) {
            quitarErrores(linea);
            var select = linea.querySelector('[data-linea-componente]');
            var cantidad = linea.querySelector('[data-linea-cantidad]');
            var opcion = opcionDe(linea);

            if (select.value === '') {
                marcar(select, 'Escoja el componente');
            } else if (usados.indexOf(select.value) !== -1) {
                marcar(select, 'Este componente ya está en otra línea; sume las cantidades en una sola');
            } else {
                usados.push(select.value);
            }

            var numero = leerNumero(cantidad.value);
            if (cantidad.value.trim() === '') {
                marcar(cantidad, 'Escriba cuánto lleva');
            } else if (esNegativo(cantidad)) {
                marcar(cantidad, 'No puede ser un número negativo');
            } else if (numero === null) {
                marcar(cantidad, 'Escriba un número, por ejemplo 20 o 1,5');
            } else if (numero <= 0) {
                marcar(cantidad, 'La cantidad debe ser mayor que 0');
            } else if (opcion && opcion.getAttribute('data-entera') === '1' && Math.floor(numero) !== numero) {
                marcar(cantidad, 'Se cuenta por unidades: use números enteros');
            } else if (enCentimetros(linea) && Math.floor(numero) !== numero) {
                marcar(cantidad, 'Los centímetros van sin decimales');
            }
        });

        quitarErrores(campoManoObra.closest('.campo'));
        if (campoManoObra.value.trim() === '') {
            marcar(campoManoObra, 'Escriba la mano de obra, o 0 si no la cobra');
        } else if (esNegativo(campoManoObra)) {
            marcar(campoManoObra, 'No puede ser un número negativo');
        } else if (leerNumero(campoManoObra.value) === null) {
            marcar(campoManoObra, 'Escriba un monto, por ejemplo 2000 o 2000,50');
        }

        quitarErrores(campoPrecio.closest('.campo'));
        if (campoPrecio.value.trim() === '') {
            marcar(campoPrecio, 'Escriba el precio para el cliente');
        } else if (esNegativo(campoPrecio)) {
            marcar(campoPrecio, 'No puede ser un número negativo');
        } else if (leerNumero(campoPrecio.value) === null) {
            marcar(campoPrecio, 'Escriba un monto, por ejemplo 8500 o 8500,50');
        } else if (leerNumero(campoPrecio.value) <= 0) {
            marcar(campoPrecio, 'El precio debe ser mayor que 0');
        }

        quitarErrores(campoMensaje.closest('.campo'));
        var mensaje = campoMensaje.value.replace(/\s+/g, ' ').trim();
        if (mensaje.length > 300) {
            marcar(campoMensaje, 'El mensaje puede tener hasta 300 caracteres');
        } else if (!patronMensaje.test(mensaje)) {
            marcar(campoMensaje, mensajeTexto);
        }

        if (primero !== null) {
            evento.preventDefault();
            primero.focus();
        }
    });

    actualizar();
});