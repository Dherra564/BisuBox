document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('.formularioProceso');
    if (!formulario) {
        return;
    }

    var listaComponentes = formulario.querySelector('[data-lineas]');
    var plantillaComponente = document.querySelector('[data-linea-plantilla]');
    var botonAgregarComponente = formulario.querySelector('[data-linea-agregar]');
    var cuentaComponentes = formulario.querySelector('[data-lineas-cuenta]');
    var listaExtras = formulario.querySelector('[data-extras]');
    var plantillaExtra = document.querySelector('[data-extra-plantilla]');
    var botonAgregarExtra = formulario.querySelector('[data-extra-agregar]');
    var extrasVacio = formulario.querySelector('[data-extras-vacio]');
    var campoHoras = formulario.querySelector('[data-horas]');
    var campoMinutos = formulario.querySelector('[data-minutos]');
    var campoTarifa = formulario.querySelector('[data-tarifa]');
    var maximoComponentes = parseInt(formulario.getAttribute('data-maximo-componentes'), 10) || 50;
    var maximoExtras = parseInt(formulario.getAttribute('data-maximo-extras'), 10) || 20;
    var patronNumero = /^[0-9]{1,10}([.,][0-9]{1,2})?$/;

    function siguienteClave(lista, selector) {
        var siguiente = 0;
        lista.querySelectorAll(selector).forEach(function (fila) {
            siguiente = Math.max(siguiente, parseInt(fila.getAttribute('data-clave'), 10) + 1);
        });
        return siguiente;
    }

    var claveComponente = siguienteClave(listaComponentes, '[data-linea]');
    var claveExtra = siguienteClave(listaExtras, '[data-extra]');

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

    // El costo del inventario puede tener hasta 4 decimales
    function costoTexto(costo) {
        return '₡' + formatear(costo, 4).replace(/(,\d*?)0+$/, '$1').replace(/,$/, '');
    }

    function cantidadTexto(numero) {
        return formatear(numero, 2).replace(/,?0+$/, '');
    }

    function duracionTexto(total) {
        var horas = Math.floor(total / 60);
        var minutos = total % 60;
        if (horas === 0) {
            return minutos + ' min';
        }
        return minutos === 0 ? horas + ' h' : horas + ' h ' + minutos + ' min';
    }

    function componentes() {
        return Array.prototype.slice.call(listaComponentes.querySelectorAll('[data-linea]'));
    }

    function extras() {
        return Array.prototype.slice.call(listaExtras.querySelectorAll('[data-extra]'));
    }

    // Devuelve los minutos escritos, o 0 si lo escrito no es válido
    function minutosTotales() {
        var horas = campoHoras.value.trim();
        var minutos = campoMinutos.value.trim();
        if (!/^[0-9]{0,3}$/.test(horas) || !/^[0-9]{0,2}$/.test(minutos) || parseInt(minutos || '0', 10) > 59) {
            return 0;
        }
        return parseInt(horas || '0', 10) * 60 + parseInt(minutos || '0', 10);
    }

    // Si el componente va por metro, la cantidad puede venir en centímetros (75 cm = 0,75 m)
    function enCentimetros(linea) {
        var select = linea.querySelector('[data-linea-componente]');
        var opcion = select.options[select.selectedIndex];
        return opcion && opcion.getAttribute('data-metro') === '1' && linea.querySelector('[data-linea-medida]').value === 'cm';
    }

    function cantidadEnUnidad(linea) {
        var cantidad = leerNumero(linea.querySelector('[data-linea-cantidad]').value) || 0;
        return enCentimetros(linea) ? cantidad / 100 : cantidad;
    }

    function actualizarComponente(linea) {
        var select = linea.querySelector('[data-linea-componente]');
        var opcion = select.options[select.selectedIndex];
        var hayComponente = select.value !== '' && opcion && opcion.hasAttribute('data-costo');
        var esMetro = hayComponente && opcion.getAttribute('data-metro') === '1';

        var plural = hayComponente ? opcion.getAttribute('data-plural') : '';
        var costo = hayComponente ? parseFloat(opcion.getAttribute('data-costo')) || 0 : 0;
        var existencia = hayComponente ? parseFloat(opcion.getAttribute('data-existencia')) || 0 : 0;
        var cantidad = cantidadEnUnidad(linea);
        var subtotal = Math.round(cantidad * costo * 100) / 100;

        linea.querySelector('[data-linea-medida]').hidden = !esMetro;
        linea.querySelector('[data-linea-unidad]').hidden = esMetro;
        linea.querySelector('[data-linea-unidad]').textContent = plural;
        linea.querySelector('[data-linea-etiqueta-costo]').textContent = hayComponente ? opcion.getAttribute('data-etiqueta-costo') : 'Costo de cada uno';
        linea.querySelector('[data-linea-costo]').textContent = costoTexto(costo);
        linea.querySelector('[data-linea-subtotal]').textContent = moneda(subtotal);

        var aviso = linea.querySelector('[data-linea-aviso]');
        if (hayComponente) {
            var sobra = cantidad > existencia + 0.001;
            aviso.textContent = sobra
                ? 'No alcanza: solo tiene ' + cantidadTexto(existencia) + ' ' + plural
                : 'Tiene ' + cantidadTexto(existencia) + ' ' + plural
                    + (cantidad > 0 ? ' → le quedarán ' + cantidadTexto(existencia - cantidad) : '');
            aviso.classList.toggle('errorCampo', sobra);
        } else {
            aviso.textContent = '';
        }
        aviso.hidden = aviso.textContent === '';

        return subtotal;
    }

    function actualizar() {
        var totalComponentes = 0;
        componentes().forEach(function (linea) {
            totalComponentes += actualizarComponente(linea);
        });

        var totalExtras = 0;
        extras().forEach(function (extra) {
            totalExtras += leerNumero(extra.querySelector('[data-extra-monto]').value) || 0;
        });

        var minutos = minutosTotales();
        var tarifa = leerNumero(campoTarifa.value) || 0;
        var manoObra = Math.round(minutos / 60 * tarifa * 100) / 100;

        formulario.querySelector('[data-total-componentes]').textContent = moneda(totalComponentes);
        formulario.querySelector('[data-total-mano-obra]').textContent = moneda(manoObra);
        formulario.querySelector('[data-total-extras]').textContent = moneda(totalExtras);
        formulario.querySelector('[data-total-precio]').textContent = moneda(totalComponentes + manoObra + totalExtras);

        formulario.querySelector('[data-mano-obra-detalle]').textContent = minutos > 0
            ? 'Mano de obra: ' + duracionTexto(minutos) + ' × ' + moneda(tarifa) + ' por hora = ' + moneda(manoObra)
            : '';

        var cantidadComponentes = componentes().length;
        cuentaComponentes.textContent = cantidadComponentes === 1 ? '1 componente' : cantidadComponentes + ' componentes';
        botonAgregarComponente.disabled = cantidadComponentes >= maximoComponentes;
        componentes().forEach(function (linea) {
            linea.querySelector('[data-linea-quitar]').hidden = cantidadComponentes === 1;
        });

        botonAgregarExtra.disabled = extras().length >= maximoExtras;
        extrasVacio.hidden = extras().length > 0;
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
        contenedor.classList.add('campoConError');
        var error = document.createElement('p');
        error.className = 'errorCampo';
        error.textContent = mensaje;
        contenedor.appendChild(error);
    }

    function agregarFila(lista, plantilla, clave) {
        var contenedor = document.createElement('div');
        contenedor.innerHTML = plantilla.innerHTML.replace(/__n__/g, String(clave)).trim();
        var fila = contenedor.firstElementChild;
        lista.appendChild(fila);
        actualizar();
        return fila;
    }

    botonAgregarComponente.addEventListener('click', function () {
        if (componentes().length >= maximoComponentes) {
            return;
        }
        var linea = agregarFila(listaComponentes, plantillaComponente, claveComponente++);
        linea.querySelector('[data-linea-componente]').focus();
    });

    botonAgregarExtra.addEventListener('click', function () {
        if (extras().length >= maximoExtras) {
            return;
        }
        var extra = agregarFila(listaExtras, plantillaExtra, claveExtra++);
        extra.querySelector('[data-extra-concepto]').focus();
    });

    listaComponentes.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-linea-quitar]');
        if (boton && componentes().length > 1) {
            boton.closest('[data-linea]').remove();
            actualizar();
            botonAgregarComponente.focus();
        }
    });

    listaExtras.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-extra-quitar]');
        if (boton) {
            boton.closest('[data-extra]').remove();
            actualizar();
            botonAgregarExtra.focus();
        }
    });

    formulario.addEventListener('change', function (evento) {
        var fila = evento.target.closest('[data-linea], [data-extra]');
        if (fila) {
            quitarErrores(fila);
        }
        actualizar();
    });
    formulario.addEventListener('input', actualizar);

    formulario.addEventListener('submit', function (evento) {
        var primero = null;
        var usados = [];

        function marcar(campo, mensaje) {
            ponerError(campo, mensaje);
            primero = primero || campo;
        }

        componentes().forEach(function (linea) {
            quitarErrores(linea);
            var select = linea.querySelector('[data-linea-componente]');
            var cantidad = linea.querySelector('[data-linea-cantidad]');
            var opcion = select.options[select.selectedIndex];

            if (select.value === '') {
                marcar(select, 'Escoja el componente que usó');
            } else if (usados.indexOf(select.value) !== -1) {
                marcar(select, 'Este componente ya está en otra línea; sume las cantidades en una sola');
            } else {
                usados.push(select.value);
            }

            var numero = leerNumero(cantidad.value);
            if (cantidad.value.trim() === '') {
                marcar(cantidad, 'Escriba cuántos usó');
            } else if (numero === null) {
                marcar(cantidad, 'Escriba un número, por ejemplo 20 o 1,5');
            } else if (numero <= 0) {
                marcar(cantidad, 'La cantidad debe ser mayor que 0');
            } else if (opcion && opcion.getAttribute('data-entera') === '1' && Math.floor(numero) !== numero) {
                marcar(cantidad, 'Se cuenta por unidades: use números enteros');
            } else if (enCentimetros(linea) && Math.floor(numero) !== numero) {
                marcar(cantidad, 'Los centímetros van sin decimales');
            } else if (opcion && opcion.hasAttribute('data-existencia')
                && cantidadEnUnidad(linea) > (parseFloat(opcion.getAttribute('data-existencia')) || 0) + 0.001) {
                marcar(cantidad, 'No alcanza: solo tiene ' + cantidadTexto(parseFloat(opcion.getAttribute('data-existencia'))));
            }
        });

        var contenedorTiempo = campoHoras.closest('.campo');
        quitarErrores(contenedorTiempo);
        if (minutosTotales() < 1) {
            marcar(campoHoras, 'Escriba cuánto tardó, por ejemplo 1 hora y 30 minutos (los minutos van de 0 a 59)');
        }

        extras().forEach(function (extra) {
            quitarErrores(extra);
            var concepto = extra.querySelector('[data-extra-concepto]');
            var monto = extra.querySelector('[data-extra-monto]');

            if (concepto.value.trim() === '' && monto.value.trim() === '') {
                return;
            }
            if (concepto.value.trim().length < 2) {
                marcar(concepto, 'Escriba para qué fue, por ejemplo Luz o Transporte');
            }
            var numero = leerNumero(monto.value);
            if (monto.value.trim() === '') {
                marcar(monto, 'Escriba cuánto fue');
            } else if (numero === null) {
                marcar(monto, 'Escriba un monto, por ejemplo 1500 o 1500,50');
            } else if (numero <= 0) {
                marcar(monto, 'El monto debe ser mayor que 0');
            }
        });

        if (primero) {
            evento.preventDefault();
            primero.focus();
        }
    });

    actualizar();
});