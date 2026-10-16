document.addEventListener('DOMContentLoaded', function () {
    var patronNumero = /^[0-9]{1,10}([.,][0-9]{1,2})?$/;
    var patronDescripcion = /^[\p{L}0-9 .,;:¡!¿?()"'\/&%#+-]*$/u;
    var patronConcepto = /^[\p{L}0-9][\p{L}0-9 .,&'-]*$/u;

    function esNegativo(campo) {
        return campo.value.trim().charAt(0) === '-';
    }

    function reemplazarError(campo, mensaje) {
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

    function leerCantidad(campo) {
        var texto = campo.value.trim();
        return /^[0-9]{1,4}$/.test(texto) && parseInt(texto, 10) >= 1 ? parseInt(texto, 10) : 0;
    }

    document.querySelectorAll('.selectorCantidad').forEach(function (selector) {
        var campo = selector.querySelector('[data-cantidad]');
        function cambiar(paso) {
            campo.value = String(Math.min(9999, Math.max(1, (leerCantidad(campo) || 1) + paso)));
            campo.dispatchEvent(new Event('input', { bubbles: true }));
        }
        selector.querySelector('[data-cantidad-menos]').addEventListener('click', function () { cambiar(-1); });
        selector.querySelector('[data-cantidad-mas]').addEventListener('click', function () { cambiar(1); });
    });

    var modalHiceMas = document.getElementById('modalHiceMas');
    if (modalHiceMas) {
        var campoCantidadModal = modalHiceMas.querySelector('[data-cantidad]');
        var textoAlcanza = modalHiceMas.querySelector('[data-hice-mas-alcanza]');
        var errorModal = modalHiceMas.querySelector('[data-hice-mas-error]');
        var alcanzaModal = null;

        document.querySelectorAll('[data-hice-mas]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                modalHiceMas.querySelector('[data-hice-mas-id]').value = boton.getAttribute('data-id');
                modalHiceMas.querySelector('[data-hice-mas-nombre]').textContent = '«' + boton.getAttribute('data-nombre') + '»';
                var alcanza = boton.getAttribute('data-alcanza');
                alcanzaModal = alcanza !== null ? parseInt(alcanza, 10) : null;
                textoAlcanza.textContent = alcanza !== null ? 'Le alcanza para ' + alcanza + ' más.' : '';
                textoAlcanza.hidden = alcanza === null;
                errorModal.hidden = true;
                campoCantidadModal.value = '1';
                modalHiceMas.showModal();
                campoCantidadModal.select();
            });
        });

        modalHiceMas.querySelector('[data-cerrar-modal]').addEventListener('click', function () {
            modalHiceMas.close();
        });
        modalHiceMas.addEventListener('click', function (evento) {
            if (evento.target === modalHiceMas) {
                modalHiceMas.close();
            }
        });
        campoCantidadModal.addEventListener('input', function () {
            errorModal.hidden = true;
            textoAlcanza.hidden = alcanzaModal === null;
        });
        modalHiceMas.querySelector('form').addEventListener('submit', function (evento) {
            var cantidad = leerCantidad(campoCantidadModal);
            var mensaje = '';
            if (esNegativo(campoCantidadModal)) {
                mensaje = 'No puede ser un número negativo';
            } else if (cantidad === 0) {
                mensaje = 'Escriba cuántos hizo, con números enteros desde 1';
            } else if (alcanzaModal !== null && cantidad > alcanzaModal) {
                mensaje = alcanzaModal === 0 ? 'Ya no le alcanzan los componentes para hacer otro' : 'Solo le alcanza para ' + alcanzaModal + ' más';
            }
            if (mensaje !== '') {
                evento.preventDefault();
                errorModal.textContent = mensaje;
                errorModal.hidden = false;
                textoAlcanza.hidden = alcanzaModal === null || cantidad > alcanzaModal;
                campoCantidadModal.focus();
            }
        });

        // Si el intento anterior falló, la ventana se abre sola con la cantidad que escribió y el error
        var idReabrir = modalHiceMas.getAttribute('data-reabrir-id');
        if (idReabrir) {
            var botonReabrir = document.querySelector('[data-hice-mas][data-id="' + idReabrir + '"]');
            if (botonReabrir) {
                botonReabrir.click();
                campoCantidadModal.value = modalHiceMas.getAttribute('data-reabrir-cantidad') || '1';
                errorModal.textContent = modalHiceMas.getAttribute('data-reabrir-error') || '';
                errorModal.hidden = errorModal.textContent === '';
                campoCantidadModal.select();
            } else if (modalHiceMas.getAttribute('data-reabrir-error')) {
                // Si el producto ya no tiene el botón (por ejemplo, se desactivó), el error se muestra arriba
                var aviso = document.createElement('div');
                aviso.className = 'alerta alertaError';
                aviso.setAttribute('role', 'alert');
                var texto = document.createElement('p');
                texto.textContent = modalHiceMas.getAttribute('data-reabrir-error');
                aviso.appendChild(texto);
                var contenido = document.querySelector('.contenido .encabezadoPagina');
                if (contenido) {
                    contenido.insertAdjacentElement('afterend', aviso);
                }
            }
        }
    }

    var bloquePrecio = document.querySelector('[data-precio]');
    var actualizarPrecio = function () {};
    if (bloquePrecio) {
        var campoPrecio = bloquePrecio.querySelector('[data-precio-venta]');
        var opcionesGanancia = bloquePrecio.querySelectorAll('[data-ganancia]');
        var resultado = bloquePrecio.querySelector('[data-resultado-ganancia]');
        var costoActual = parseFloat(bloquePrecio.getAttribute('data-costo')) || 0;

        function gananciaEscogida() {
            var escogida = bloquePrecio.querySelector('[data-ganancia]:checked');
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
                ? 'Gana ' + moneda(diferencia) + ' por cada uno (' + porcentaje + '%)'
                : 'Pierde ' + moneda(-diferencia) + ' por cada uno: el precio no cubre el costo';
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

        actualizarPrecio = function (costo) {
            costoActual = costo;
            if (gananciaEscogida() !== null) {
                aplicarGanancia();
            } else {
                mostrarResultado();
            }
        };

        opcionesGanancia.forEach(function (opcion) {
            opcion.addEventListener('change', aplicarGanancia);
        });
        campoPrecio.addEventListener('input', function () {
            opcionesGanancia.forEach(function (opcion) {
                opcion.checked = false;
            });
            mostrarResultado();
        });

        if (campoPrecio.value.trim() === '') {
            aplicarGanancia();
        } else {
            mostrarResultado();
        }
        
        campoPrecio.form.addEventListener('submit', function (evento) {
            var yaHabiaErrores = evento.defaultPrevented;
            var revisiones = [];
            var descripcion = campoPrecio.form.querySelector('#descripcion');
            if (descripcion) {
                var contenedorDescripcion = descripcion.closest('.campo');
                contenedorDescripcion.classList.remove('campoConError');
                contenedorDescripcion.querySelectorAll('.errorCampo').forEach(function (error) {
                    error.remove();
                });
            }
            if (descripcion && !patronDescripcion.test(descripcion.value.replace(/\s+/g, ' ').trim())) {
                revisiones.push([descripcion, 'La descripción solo puede tener letras, números, espacios y los signos . , ; : ¡ ! ¿ ? ( ) " \' / & % # + -']);
            }
            if (esNegativo(campoPrecio)) {
                revisiones.push([campoPrecio, 'No puede ser un número negativo']);
            } else if (leerNumero(campoPrecio.value) === 0) {
                revisiones.push([campoPrecio, 'El precio debe ser mayor que 0']);
            }
            revisiones.forEach(function (revision) {
                reemplazarError(revision[0], revision[1]);
            });
            if (revisiones.length > 0) {
                evento.preventDefault();
                if (!yaHabiaErrores) {
                    var opcional = revisiones[0][0].closest('details');
                    if (opcional) {
                        opcional.open = true;
                    }
                    revisiones[0][0].focus();
                }
            }
        });
    }

    var formulario = document.querySelector('[data-receta]');
    if (!formulario) {
        return;
    }

    var listaComponentes = formulario.querySelector('[data-lineas]');
    var plantillaComponente = document.querySelector('[data-linea-plantilla]');
    var botonAgregarComponente = formulario.querySelector('[data-linea-agregar]');
    var listaExtras = formulario.querySelector('[data-extras]');
    var plantillaExtra = document.querySelector('[data-extra-plantilla]');
    var botonAgregarExtra = formulario.querySelector('[data-extra-agregar]');
    var campoCantidad = formulario.querySelector('[data-cantidad]');
    var campoHoras = formulario.querySelector('[data-horas]');
    var campoMinutos = formulario.querySelector('[data-minutos]');
    var campoTarifa = formulario.querySelector('[data-tarifa]');
    var resumenDescuento = formulario.querySelector('[data-resumen-descuento]');
    var maximoComponentes = parseInt(formulario.getAttribute('data-maximo-componentes'), 10) || 50;
    var maximoExtras = parseInt(formulario.getAttribute('data-maximo-extras'), 10) || 20;

    function siguienteClave(lista, selector) {
        var siguiente = 0;
        lista.querySelectorAll(selector).forEach(function (fila) {
            siguiente = Math.max(siguiente, parseInt(fila.getAttribute('data-clave'), 10) + 1);
        });
        return siguiente;
    }

    var claveComponente = siguienteClave(listaComponentes, '[data-linea]');
    var claveExtra = siguienteClave(listaExtras, '[data-extra]');

    function componentes() {
        return Array.prototype.slice.call(listaComponentes.querySelectorAll('[data-linea]'));
    }

    function extras() {
        return Array.prototype.slice.call(listaExtras.querySelectorAll('[data-extra]'));
    }

    function productosHechos() {
        return leerCantidad(campoCantidad) || 1;
    }

    function minutosTotales() {
        var horas = campoHoras.value.trim();
        var minutos = campoMinutos.value.trim();
        if (!/^[0-9]{0,3}$/.test(horas) || !/^[0-9]{0,2}$/.test(minutos) || parseInt(minutos || '0', 10) > 59) {
            return 0;
        }
        return parseInt(horas || '0', 10) * 60 + parseInt(minutos || '0', 10);
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

    function actualizarLinea(linea) {
        var opcion = opcionDe(linea);
        var esMetro = opcion !== null && opcion.getAttribute('data-metro') === '1';
        var plural = opcion !== null ? opcion.getAttribute('data-plural') : '';
        var costo = opcion !== null ? parseFloat(opcion.getAttribute('data-costo')) || 0 : 0;
        var existencia = opcion !== null ? parseFloat(opcion.getAttribute('data-existencia')) || 0 : 0;
        var cantidad = cantidadEnUnidad(linea);
        var subtotal = Math.round(cantidad * costo * 100) / 100;

        linea.querySelector('[data-linea-medida]').hidden = !esMetro;
        linea.querySelector('[data-linea-unidad]').hidden = esMetro;
        linea.querySelector('[data-linea-unidad]').textContent = plural;
        linea.querySelector('[data-linea-subtotal]').textContent = moneda(subtotal);

        var aviso = linea.querySelector('[data-linea-aviso]');
        var necesita = Math.round(cantidad * productosHechos() * 100) / 100;
        if (opcion !== null) {
            var falta = necesita > existencia + 0.001;
            aviso.textContent = falta
                ? 'No alcanza: necesita ' + cantidadTexto(necesita) + ' y tiene ' + cantidadTexto(existencia) + ' ' + plural
                : 'Tiene ' + cantidadTexto(existencia) + ' ' + plural;
            aviso.classList.toggle('errorCampo', falta);
        } else {
            aviso.textContent = '';
        }
        aviso.hidden = aviso.textContent === '';

        return { subtotal: subtotal, necesita: necesita, opcion: opcion, plural: plural };
    }

    function actualizar() {
        var totalComponentes = 0;
        var usados = [];
        componentes().forEach(function (linea) {
            var datos = actualizarLinea(linea);
            totalComponentes += datos.subtotal;
            if (datos.opcion !== null && datos.necesita > 0) {
                usados.push(cantidadTexto(datos.necesita) + ' ' + datos.plural + ' de ' + datos.opcion.textContent.trim());
            }
        });

        var totalExtras = 0;
        extras().forEach(function (extra) {
            totalExtras += leerNumero(extra.querySelector('[data-extra-monto]').value) || 0;
        });

        var tarifa = leerNumero(campoTarifa.value) || 0;
        var manoObra = Math.round(minutosTotales() / 60 * tarifa * 100) / 100;
        var costoTotal = Math.round((totalComponentes + manoObra + totalExtras) * 100) / 100;

        formulario.querySelector('[data-total-componentes]').textContent = moneda(totalComponentes);
        formulario.querySelector('[data-total-mano-obra]').textContent = moneda(manoObra);
        formulario.querySelector('[data-total-extras]').textContent = moneda(totalExtras);
        formulario.querySelector('[data-costo-total]').textContent = moneda(costoTotal);
        actualizarPrecio(costoTotal);

        var hechos = productosHechos();
        resumenDescuento.querySelector('[data-resumen-titulo]').textContent = (hechos === 1 ? 'Por el que hizo' : 'Por los ' + hechos + ' que hizo')
            + ' se descuenta del inventario:';
        var lista = resumenDescuento.querySelector('[data-resumen-lista]');
        lista.innerHTML = '';
        usados.forEach(function (texto) {
            var elemento = document.createElement('li');
            elemento.textContent = texto;
            lista.appendChild(elemento);
        });
        resumenDescuento.hidden = usados.length === 0;

        var cantidadLineas = componentes().length;
        botonAgregarComponente.disabled = cantidadLineas >= maximoComponentes;
        componentes().forEach(function (linea) {
            linea.querySelector('[data-linea-quitar]').hidden = cantidadLineas === 1;
        });
        botonAgregarExtra.disabled = extras().length >= maximoExtras;
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
        if (componentes().length < maximoComponentes) {
            agregarFila(listaComponentes, plantillaComponente, claveComponente++).querySelector('[data-linea-componente]').focus();
        }
    });

    botonAgregarExtra.addEventListener('click', function () {
        if (extras().length < maximoExtras) {
            agregarFila(listaExtras, plantillaExtra, claveExtra++).querySelector('[data-extra-concepto]').focus();
        }
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
        } else if (evento.target === campoCantidad || evento.target === campoHoras || evento.target === campoMinutos) {
            quitarErrores(evento.target.closest('.campo'));
        }
        actualizar();
    });
    formulario.addEventListener('input', function (evento) {
        if (evento.target === campoCantidad || evento.target === campoHoras || evento.target === campoMinutos) {
            quitarErrores(evento.target.closest('.campo'));
        }
        if (!evento.target.matches('[data-precio-venta]')) {
            actualizar();
        }
    });

    formulario.addEventListener('submit', function (evento) {
        var primero = null;
        var usados = [];

        function marcar(campo, mensaje) {
            ponerError(campo, mensaje);
            primero = primero || campo;
        }

        quitarErrores(campoCantidad.closest('.campo'));
        if (campoCantidad.value.trim() === '') {
            marcar(campoCantidad, 'Escriba cuántos hizo');
        } else if (esNegativo(campoCantidad)) {
            marcar(campoCantidad, 'No puede ser un número negativo');
        } else if (leerCantidad(campoCantidad) === 0) {
            marcar(campoCantidad, 'Escriba cuántos hizo, con números enteros desde 1');
        }

        componentes().forEach(function (linea) {
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
            } else if (opcion && cantidadEnUnidad(linea) * productosHechos() > (parseFloat(opcion.getAttribute('data-existencia')) || 0) + 0.001) {
                marcar(cantidad, 'No alcanza el inventario para todos los que hizo');
            }
        });

        quitarErrores(campoHoras.closest('.campo'));
        if (esNegativo(campoHoras) || esNegativo(campoMinutos)) {
            marcar(campoHoras, 'El tiempo no puede ser negativo');
        } else if (minutosTotales() < 1) {
            marcar(campoHoras, 'Escriba cuánto tarda en hacer uno, por ejemplo 0 horas y 45 minutos (los minutos van de 0 a 59)');
        }

        if (esNegativo(campoTarifa)) {
            reemplazarError(campoTarifa, 'No puede ser un número negativo');
            primero = primero || campoTarifa;
        }

        extras().forEach(function (extra) {
            quitarErrores(extra);
            var concepto = extra.querySelector('[data-extra-concepto]');
            var monto = extra.querySelector('[data-extra-monto]');
            if (concepto.value.trim() === '' && monto.value.trim() === '') {
                return;
            }
            var textoConcepto = concepto.value.replace(/\s+/g, ' ').trim();
            if (textoConcepto === '') {
                marcar(concepto, 'Escriba para qué fue, por ejemplo Empaque');
            } else if (textoConcepto.length < 2 || textoConcepto.length > 60) {
                marcar(concepto, 'El concepto debe tener entre 2 y 60 caracteres');
            } else if (!patronConcepto.test(textoConcepto)) {
                marcar(concepto, 'El concepto solo puede tener letras, números, espacios y los signos . , & \' -');
            }
            var numero = leerNumero(monto.value);
            if (monto.value.trim() === '') {
                marcar(monto, 'Escriba cuánto fue');
            } else if (esNegativo(monto)) {
                marcar(monto, 'No puede ser un número negativo');
            } else if (numero === null) {
                marcar(monto, 'Escriba un monto, por ejemplo 150 o 150,50');
            } else if (numero <= 0) {
                marcar(monto, 'El monto debe ser mayor que 0');
            }
        });

        if (primero) {
            evento.preventDefault();
            var opcional = primero.closest('details');
            if (opcional) {
                opcional.open = true;
            }
            primero.focus();
        }
    });

    actualizar();
});