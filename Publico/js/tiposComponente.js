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
    var vistaPrevia = formulario.querySelector('[data-vista-previa]');
    var tituloVistaPrevia = formulario.querySelector('[data-vista-previa-titulo]');
    var campoNombreTipo = formulario.querySelector('[data-tipo-nombre]');
    var plantillaDato = document.querySelector('[data-dato-plantilla]');
    var plantillaOpcion = document.querySelector('[data-opcion-plantilla]');
    var maximoDatos = parseInt(formulario.getAttribute('data-maximo-datos'), 10) || 20;
    var maximoOpciones = parseInt(formulario.getAttribute('data-maximo-opciones'), 10) || 30;

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
        dibujarVistaPrevia();
    }

    function crear(etiqueta, clase, texto) {
        var elemento = document.createElement(etiqueta);
        if (clase) {
            elemento.className = clase;
        }
        if (texto) {
            elemento.textContent = texto;
        }
        return elemento;
    }

    function dibujarVistaPrevia() {
        var nombreTipo = campoNombreTipo.value.trim();
        tituloVistaPrevia.textContent = nombreTipo !== '' ? 'Datos de ' + nombreTipo : 'Datos del tipo';
        vistaPrevia.textContent = '';

        if (filas().length === 0) {
            vistaPrevia.appendChild(crear('p', 'textoAyuda', 'Este tipo no pide datos extra.'));
            return;
        }

        filas().forEach(function (fila) {
            var nombre = fila.querySelector('[data-dato-nombre]').value.trim() || 'Dato sin nombre';
            var obligatorio = fila.querySelector('[data-dato-obligatorio]').checked;
            var clase = claseDe(fila);
            var campo = crear('div', 'campo');
            var control;

            if (clase === 'SiNo') {
                campo.className = 'campo campoCasilla';
                campo.appendChild(crear('input')).type = 'checkbox';
                campo.appendChild(crear('label', '', nombre));
                vistaPrevia.appendChild(campo);
                return;
            }

            var etiqueta = crear('label', '', nombre + ' ');
            if (obligatorio) {
                etiqueta.appendChild(crear('span', 'obligatorio', '*'));
            }
            campo.appendChild(etiqueta);

            if (clase === 'Lista') {
                control = crear('select');
                control.appendChild(crear('option', '', 'Escoja una opción'));
                fila.querySelectorAll('.filaOpcion input[type="text"]').forEach(function (opcion) {
                    if (opcion.value.trim() !== '') {
                        control.appendChild(crear('option', '', opcion.value.trim()));
                    }
                });
                campo.appendChild(control);
            } else if (clase === 'Numero') {
                var unidad = fila.querySelector('[data-dato-unidad]').value.trim();
                var grupo = crear('div', 'campoConUnidad');
                grupo.appendChild(crear('input')).type = 'text';
                if (unidad !== '') {
                    grupo.appendChild(crear('span', 'unidadCampo', unidad));
                }
                campo.appendChild(grupo);
            } else {
                control = crear('input');
                control.type = 'text';
                campo.appendChild(control);
            }
            vistaPrevia.appendChild(campo);
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
            dibujarVistaPrevia();
        } else if (boton.hasAttribute('data-opcion-quitar')) {
            boton.closest('.filaOpcion').remove();
            dibujarVistaPrevia();
        }
    });

    formulario.addEventListener('change', function (evento) {
        if (evento.target.matches('[data-dato-clase]')) {
            var fila = evento.target.closest('[data-dato]');
            quitarErrores(fila.querySelector('[data-solo-clase="Lista"]'));
            aplicarClase(fila);
        }
        dibujarVistaPrevia();
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
        dibujarVistaPrevia();
    });

    formulario.addEventListener('submit', function (evento) {
        var primero = null;
        var nombres = [];

        filas().forEach(function (fila) {
            var campoNombre = fila.querySelector('[data-dato-nombre]');
            var nombre = campoNombre.value.trim().toLowerCase();
            if (nombre !== '' && nombres.indexOf(nombre) !== -1 && !campoNombre.closest('.campoConError')) {
                ponerError(campoNombre.closest('.campo'), 'Ya hay otro dato con este nombre');
                primero = primero || campoNombre;
            }
            nombres.push(nombre);

            var campoOpciones = fila.querySelector('[data-solo-clase="Lista"]');
            quitarErrores(campoOpciones);
            if (claseDe(fila) !== 'Lista') {
                return;
            }
            var valores = [];
            campoOpciones.querySelectorAll('.filaOpcion input[type="text"]').forEach(function (opcion) {
                if (opcion.value.trim() !== '') {
                    valores.push(opcion.value.trim().toLowerCase());
                }
            });
            var mensaje = '';
            if (valores.length < 2) {
                mensaje = 'Una lista necesita al menos 2 opciones';
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