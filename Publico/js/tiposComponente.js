document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('.formularioTipo');
    if (!formulario) {
        return;
    }

    var lista = formulario.querySelector('[data-datos]');
    var listaOcultos = formulario.querySelector('[data-datos-ocultos]');
    var seccionOcultos = formulario.querySelector('[data-seccion-ocultos]');
    var botonAgregar = formulario.querySelector('[data-dato-agregar]');
    var cuenta = formulario.querySelector('[data-datos-cuenta]');
    var vacio = formulario.querySelector('[data-datos-vacio]');
    var plantillaDato = document.querySelector('[data-dato-plantilla]');
    var plantillaOpcion = document.querySelector('[data-opcion-plantilla]');
    var maximoDatos = parseInt(formulario.getAttribute('data-maximo-datos'), 10) || 20;
    var maximoOpciones = parseInt(formulario.getAttribute('data-maximo-opciones'), 10) || 30;
    var patronNombre = /^[\p{L}0-9][\p{L}0-9 .,()#\/&'-]*$/u;
    var signos = ' solo pueden tener letras, números, espacios y los signos . , ( ) # / & \' -';

    var siguienteClave = 1;
    formulario.querySelectorAll('[data-dato]').forEach(function (fila) {
        var numero = /^n(\d+)$/.exec(fila.getAttribute('data-clave'));
        if (numero) {
            siguienteClave = Math.max(siguienteClave, parseInt(numero[1], 10) + 1);
        }
    });

    function filas() {
        return Array.prototype.slice.call(lista.querySelectorAll('[data-dato]'));
    }

    function desdePlantilla(plantilla, clave) {
        var contenedor = document.createElement('div');
        contenedor.innerHTML = plantilla.innerHTML.replace(/__clave__/g, clave).trim();
        return contenedor.firstElementChild;
    }

    function claseDe(fila) {
        return fila.querySelector('[data-dato-clase]').value;
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
        campo.classList.add('campoConError');
        var error = document.createElement('p');
        error.className = 'errorCampo';
        error.textContent = mensaje;
        campo.appendChild(error);
    }

    function agregarOpcion(fila, enfocar) {
        var opciones = fila.querySelector('[data-opciones]');
        if (opciones.querySelectorAll('.filaOpcion').length >= maximoOpciones) {
            return;
        }
        var opcion = desdePlantilla(plantillaOpcion, fila.getAttribute('data-clave'));
        opciones.appendChild(opcion);
        if (enfocar) {
            opcion.querySelector('input[type="text"]').focus();
        }
    }

    function aplicarClase(fila) {
        var select = fila.querySelector('[data-dato-clase]');
        var clase = select.value;
        var icono = fila.querySelector('[data-dato-icono]');
        icono.className = 'iconoClase iconoClase' + clase;

        if (!fila.hasAttribute('data-tiene-valores')) {
            var opcion = select.options[select.selectedIndex];
            fila.querySelector('[data-dato-ejemplo]').textContent = 'Ejemplo: ' + opcion.getAttribute('data-ejemplo');
        }
        fila.querySelectorAll('[data-solo-clase]').forEach(function (seccion) {
            seccion.hidden = seccion.getAttribute('data-solo-clase') !== clase;
        });
        if (clase === 'Lista' && fila.querySelectorAll('.filaOpcion').length === 0) {
            agregarOpcion(fila, false);
            agregarOpcion(fila, false);
        }
    }

    function habilitarFila(fila, habilitada) {
        fila.querySelectorAll('input, select, button').forEach(function (elemento) {
            if (elemento.hasAttribute('data-dato-restaurar')) {
                elemento.hidden = habilitada;
                return;
            }
            if (elemento.hasAttribute('data-solo-activo')) {
                elemento.hidden = !habilitada;
            }
            var claseFija = elemento.matches('[data-dato-clase]') && fila.hasAttribute('data-tiene-valores');
            elemento.disabled = !habilitada || claseFija;
        });
    }

    function actualizar() {
        var total = filas().length;
        cuenta.textContent = total + ' de ' + maximoDatos + ' datos';
        botonAgregar.disabled = total >= maximoDatos;
        vacio.hidden = total > 0;
        seccionOcultos.hidden = listaOcultos.querySelectorAll('[data-dato]').length === 0;

        filas().forEach(function (fila, indice) {
            fila.querySelector('[data-dato-subir]').disabled = indice === 0;
            fila.querySelector('[data-dato-bajar]').disabled = indice === total - 1;
        });
    }

    botonAgregar.addEventListener('click', function () {
        if (filas().length >= maximoDatos) {
            return;
        }
        var fila = desdePlantilla(plantillaDato, 'n' + siguienteClave++);
        lista.appendChild(fila);
        actualizar();
        fila.querySelector('[data-dato-nombre]').focus();
    });

    formulario.addEventListener('click', function (evento) {
        var boton = evento.target.closest('button');
        if (!boton) {
            return;
        }
        var fila = boton.closest('[data-dato]');

        if (boton.hasAttribute('data-dato-quitar')) {
            if (fila.hasAttribute('data-tiene-valores')) {
                habilitarFila(fila, false);
                quitarErrores(fila);
                listaOcultos.appendChild(fila);
            } else {
                fila.remove();
            }
            actualizar();
            botonAgregar.focus();
        } else if (boton.hasAttribute('data-dato-restaurar')) {
            if (filas().length >= maximoDatos) {
                return;
            }
            habilitarFila(fila, true);
            lista.appendChild(fila);
            actualizar();
            fila.querySelector('[data-dato-nombre]').focus();
        } else if (boton.hasAttribute('data-dato-subir') && fila.previousElementSibling) {
            lista.insertBefore(fila, fila.previousElementSibling);
            actualizar();
            boton.disabled ? fila.querySelector('[data-dato-bajar]').focus() : boton.focus();
        } else if (boton.hasAttribute('data-dato-bajar') && fila.nextElementSibling) {
            lista.insertBefore(fila.nextElementSibling, fila);
            actualizar();
            boton.disabled ? fila.querySelector('[data-dato-subir]').focus() : boton.focus();
        } else if (boton.hasAttribute('data-opcion-agregar')) {
            agregarOpcion(fila, true);
        } else if (boton.hasAttribute('data-opcion-quitar')) {
            boton.closest('.filaOpcion').remove();
        }
    });

    formulario.addEventListener('change', function (evento) {
        if (evento.target.matches('[data-dato-clase]')) {
            var fila = evento.target.closest('[data-dato]');
            quitarErrores(fila.querySelector('[data-solo-clase="Lista"]'));
            aplicarClase(fila);
        }
    });

    formulario.addEventListener('input', function (evento) {
        var campoOpciones = evento.target.closest('[data-solo-clase="Lista"]');
        if (campoOpciones && campoOpciones.classList.contains('campoConError')) {
            quitarErrores(campoOpciones);
        }
        if (evento.target.matches('[data-dato-nombre]')) {
            var titulo = evento.target.closest('[data-dato]').querySelector('[data-dato-titulo]');
            titulo.textContent = evento.target.value.trim() || 'Dato nuevo';
        }
    });

    formulario.addEventListener('submit', function (evento) {
        var primero = null;
        var nombres = [];

        filas().forEach(function (fila) {
            var campoNombre = fila.querySelector('[data-dato-nombre]');
            var nombre = campoNombre.value.replace(/\s+/g, ' ').trim().toLowerCase();
            if (nombre !== '' && !campoNombre.closest('.campoConError')) {
                var mensajeNombre = !patronNombre.test(nombre) ? 'El nombre solo puede tener letras, números, espacios y los signos . , ( ) # / & \' -'
                    : nombres.indexOf(nombre) !== -1 ? 'Ya hay otro dato con este nombre' : '';
                if (mensajeNombre !== '') {
                    ponerError(campoNombre.closest('.campo'), mensajeNombre);
                    primero = primero || campoNombre;
                }
            }
            nombres.push(nombre);

            var campoUnidad = fila.querySelector('[data-dato-unidad]');
            var contenedorUnidad = campoUnidad.closest('.campo');
            quitarErrores(contenedorUnidad);
            var unidad = campoUnidad.value.replace(/\s+/g, ' ').trim();
            if (claseDe(fila) === 'Numero' && unidad !== '' && !patronNombre.test(unidad)) {
                ponerError(contenedorUnidad, 'La unidad solo puede tener letras, números, espacios y los signos . , ( ) # / & \' -');
                primero = primero || campoUnidad;
            }

            var campoOpciones = fila.querySelector('[data-solo-clase="Lista"]');
            quitarErrores(campoOpciones);
            if (claseDe(fila) !== 'Lista') {
                return;
            }
            var valores = [];
            campoOpciones.querySelectorAll('.filaOpcion input[type="text"]').forEach(function (opcion) {
                var texto = opcion.value.replace(/\s+/g, ' ').trim();
                if (texto !== '') {
                    valores.push(texto.toLowerCase());
                }
            });
            var mensaje = '';
            if (valores.length < 2) {
                mensaje = 'Una lista necesita al menos 2 opciones';
            } else if (valores.some(function (valor) { return !patronNombre.test(valor); })) {
                mensaje = 'Las opciones' + signos;
            } else if (valores.some(function (valor, indice) { return valores.indexOf(valor) !== indice; })) {
                mensaje = 'Hay opciones repetidas';
            }
            if (mensaje !== '') {
                ponerError(campoOpciones, mensaje);
                primero = primero || campoOpciones.querySelector('input[type="text"]') || campoOpciones.querySelector('button');
            }
        });

        if (primero) {
            evento.preventDefault();
            primero.focus();
        }
    });

    actualizar();
});