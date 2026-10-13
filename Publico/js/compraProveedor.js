// Registrar compra: pestañas para escoger componentes, componente nuevo con los datos de su tipo, filas con cantidad y precio total, y el resumen
document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('.formularioCompra');
    if (!formulario) {
        return;
    }

    var lista = formulario.querySelector('[data-lineas]');
    var sinLineas = formulario.querySelector('[data-sin-lineas]');
    var encabezado = formulario.querySelector('[data-encabezado-lineas]');
    var plantilla = document.querySelector('[data-linea-plantilla]');
    var campoEnvio = formulario.querySelector('[data-costo-envio]');
    var maximo = parseInt(formulario.getAttribute('data-maximo-lineas'), 10) || 50;
    var patronNumero = /^[0-9]{1,10}([.,][0-9]{1,2})?$/;
    var patronNombre = /^[\p{L}0-9][\p{L}0-9 .,()#\/&'-]*$/u;

    var unidades = {
        Unidad: { singular: 'unidad', plural: 'unidades', abreviatura: 'u', por: 'cada uno' },
        Metro: { singular: 'metro', plural: 'metros', abreviatura: 'm', por: 'por metro' },
        Gramo: { singular: 'gramo', plural: 'gramos', abreviatura: 'g', por: 'por gramo' }
    };

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

    function normalizar(texto) {
        return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
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

    var pestanas = formulario.querySelectorAll('[data-pestana]');

    function abrirPestana(nombre) {
        pestanas.forEach(function (pestana) {
            var activa = pestana.getAttribute('data-pestana') === nombre;
            pestana.classList.toggle('pestanaActiva', activa);
            pestana.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
        formulario.querySelectorAll('[data-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-panel') !== nombre;
        });
    }

    pestanas.forEach(function (pestana) {
        pestana.addEventListener('click', function () {
            abrirPestana(pestana.getAttribute('data-pestana'));
        });
    });

    var filas = Array.prototype.slice.call(formulario.querySelectorAll('[data-fila-inventario]'));
    var buscador = formulario.querySelector('[data-buscar-componente]');
    var sinCoincidencias = formulario.querySelector('[data-sin-coincidencias]');
    var tipoElegido = '';

    function filtrarInventario() {
        var texto = buscador ? normalizar(buscador.value) : '';
        var visibles = 0;
        filas.forEach(function (fila) {
            var coincide = (tipoElegido === '' || fila.getAttribute('data-tipo') === tipoElegido)
                && (texto === '' || normalizar(fila.getAttribute('data-nombre')).indexOf(texto) !== -1);
            fila.hidden = !coincide;
            visibles += coincide ? 1 : 0;
        });
        if (sinCoincidencias) {
            sinCoincidencias.hidden = visibles > 0 || texto === '';
            sinCoincidencias.querySelector('[data-texto-buscado]').textContent = buscador.value.trim();
        }
    }

    formulario.querySelectorAll('[data-filtro-tipo]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            tipoElegido = boton.getAttribute('data-filtro-tipo');
            formulario.querySelectorAll('[data-filtro-tipo]').forEach(function (otro) {
                otro.classList.toggle('botonTipoActivo', otro === boton);
                otro.setAttribute('aria-pressed', otro === boton ? 'true' : 'false');
            });
            filtrarInventario();
        });
    });

    if (buscador) {
        buscador.addEventListener('input', filtrarInventario);
        buscador.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter') {
                evento.preventDefault();
            }
        });
    }

    function marcarFila(idComponente, agregado) {
        filas.forEach(function (fila) {
            if (fila.getAttribute('data-id') === idComponente) {
                fila.querySelector('[data-en-compra]').hidden = !agregado;
                fila.querySelector('[data-agregar]').hidden = agregado;
            }
        });
    }

    function crearLinea(datos) {
        if (lineas().length >= maximo) {
            window.alert('Una compra puede tener como máximo ' + maximo + ' componentes.');
            return null;
        }
        var contenedor = document.createElement('div');
        var clave = String(siguienteClave++);
        contenedor.innerHTML = plantilla.innerHTML.replace(/__n__/g, clave).trim();
        var linea = contenedor.firstElementChild;
        var unidad = unidades[datos.unidad] || unidades.Unidad;
        var esMetro = datos.unidad === 'Metro';

        linea.setAttribute('data-componente', datos.componente);
        linea.setAttribute('data-existencia', String(datos.existencia));
        linea.setAttribute('data-unidad', datos.unidad);
        linea.setAttribute('data-nuevo', datos.nuevo ? '1' : '0');
        linea.setAttribute('data-detalle', datos.detalle);
        linea.querySelector('[data-linea-componente]').value = datos.componente;
        linea.querySelector('[data-linea-tipo]').value = datos.nuevo ? datos.tipo : '';
        linea.querySelector('[data-linea-nombre]').value = datos.nuevo ? datos.nombre : '';
        linea.querySelector('[data-linea-unidad]').value = datos.nuevo ? datos.unidad : '';
        linea.querySelector('[data-linea-titulo]').textContent = datos.nombre;
        linea.querySelector('[data-linea-marca-nuevo]').hidden = !datos.nuevo;
        linea.querySelector('[data-linea-quitar]').setAttribute('aria-label', 'Quitar ' + datos.nombre + ' de la compra');
        linea.querySelector('[data-linea-medida]').hidden = !esMetro;
        linea.querySelector('[data-linea-abreviatura]').hidden = esMetro;
        linea.querySelector('[data-linea-abreviatura]').textContent = unidad.abreviatura;
        linea.querySelector('[data-linea-por]').textContent = unidad.por;

       
        var valores = linea.querySelector('[data-linea-valores]');
        (datos.valores || []).forEach(function (valor) {
            var campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = 'linea[' + clave + '][valor][' + valor.atributo + ']';
            campo.value = valor.valor;
            valores.appendChild(campo);
        });

        lista.appendChild(linea);
        actualizar();
        linea.querySelector('[data-linea-cantidad]').focus();
        return linea;
    }

    function cantidadEnUnidad(linea) {
        var cantidad = leerNumero(linea.querySelector('[data-linea-cantidad]').value) || 0;
        var medida = linea.querySelector('[data-linea-medida]');
        return linea.getAttribute('data-unidad') === 'Metro' && medida.value === 'cm' ? cantidad / 100 : cantidad;
    }

    function actualizarLinea(linea) {
        var unidad = unidades[linea.getAttribute('data-unidad')] || unidades.Unidad;
        var cantidad = cantidadEnUnidad(linea);
        var total = leerNumero(linea.querySelector('[data-linea-total]').value) || 0;

        linea.querySelector('[data-linea-costo]').textContent = cantidad > 0 ? moneda(Math.round(total / cantidad * 100) / 100) : '—';

        var existencia = parseFloat(linea.getAttribute('data-existencia')) || 0;
        var detalle = linea.getAttribute('data-detalle');
        if (linea.getAttribute('data-nuevo') === '1') {
            detalle += cantidad > 0 ? ' · entra con ' + cantidadTexto(cantidad) + ' ' + (cantidad === 1 ? unidad.singular : unidad.plural) : '';
        } else {
            detalle += ' · tiene ' + cantidadTexto(existencia) + ' ' + (existencia === 1 ? unidad.singular : unidad.plural)
                + (cantidad > 0 ? ', quedará con ' + cantidadTexto(existencia + cantidad) + ' ' + (existencia + cantidad === 1 ? unidad.singular : unidad.plural) : '');
        }
        linea.querySelector('[data-linea-detalle]').textContent = detalle;

        return total;
    }

    function actualizar() {
        var subtotal = 0;
        lineas().forEach(function (linea) {
            subtotal += actualizarLinea(linea);
        });
        var envio = leerNumero(campoEnvio.value) || 0;
        var cantidadLineas = lineas().length;

        sinLineas.hidden = cantidadLineas > 0;
        var avisoVacio = formulario.querySelector('[data-error-lineas]');
        if (avisoVacio && cantidadLineas > 0) {
            avisoVacio.remove();
        }
        encabezado.hidden = cantidadLineas === 0;
        formulario.querySelector('[data-resumen-cantidad]').textContent = cantidadLineas === 1 ? '1 componente' : cantidadLineas + ' componentes';
        formulario.querySelector('[data-total-subtotal]').textContent = moneda(subtotal);
        formulario.querySelector('[data-total-envio]').textContent = envio > 0 ? moneda(envio) : 'Sin envío';
        formulario.querySelector('[data-total-compra]').textContent = moneda(subtotal + envio);
    }

    filas.forEach(function (fila) {
        fila.querySelector('[data-agregar]').addEventListener('click', function () {
            var linea = crearLinea({
                componente: fila.getAttribute('data-id'),
                nombre: fila.getAttribute('data-nombre'),
                detalle: fila.getAttribute('data-tipo-nombre'),
                unidad: fila.getAttribute('data-unidad'),
                existencia: parseFloat(fila.getAttribute('data-existencia')) || 0,
                nuevo: false
            });
            if (linea) {
                marcarFila(fila.getAttribute('data-id'), true);
            }
        });
    });

    lista.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-linea-quitar]');
        if (!boton) {
            return;
        }
        var linea = boton.closest('[data-linea]');
        if (linea.getAttribute('data-nuevo') !== '1') {
            marcarFila(linea.getAttribute('data-componente'), false);
        }
        linea.remove();
        actualizar();
    });

    var nuevoTipo = formulario.querySelector('[data-nuevo-tipo]');
    var nuevoNombre = formulario.querySelector('[data-nuevo-nombre]');
    var botonNuevo = formulario.querySelector('[data-agregar-nuevo]');

    function mostrarAtributos() {
        formulario.querySelector('[data-atributos-vacio]').hidden = nuevoTipo.value !== '';
        formulario.querySelectorAll('[data-atributos-tipo]').forEach(function (bloque) {
            bloque.hidden = bloque.getAttribute('data-atributos-tipo') !== nuevoTipo.value;
        });
    }

    function camposAtributos() {
        var bloque = formulario.querySelector('[data-atributos-tipo="' + nuevoTipo.value + '"]');
        return bloque ? Array.prototype.slice.call(bloque.querySelectorAll('[data-atributo]')) : [];
    }

    function textoAtributo(campo) {
        var clase = campo.getAttribute('data-clase');
        if (clase === 'SiNo') {
            return campo.checked ? campo.getAttribute('data-nombre') : '';
        }
        if (campo.value.trim() === '') {
            return '';
        }
        if (clase === 'Lista') {
            return campo.options[campo.selectedIndex].text.trim();
        }
        return clase === 'Numero' && campo.getAttribute('data-unidad') !== ''
            ? campo.value.trim() + ' ' + campo.getAttribute('data-unidad')
            : campo.value.trim();
    }

    function limpiarNuevo() {
        nuevoNombre.value = '';
        camposAtributos().forEach(function (campo) {
            if (campo.type === 'checkbox') {
                campo.checked = false;
            } else {
                campo.value = '';
            }
        });
    }

    if (botonNuevo) {
        var panelNuevo = formulario.querySelector('[data-panel="nuevo"]');
        nuevoTipo.addEventListener('change', mostrarAtributos);

        botonNuevo.addEventListener('click', function () {
            quitarErrores(panelNuevo);
            var nombre = nuevoNombre.value.replace(/\s+/g, ' ').trim();
            var comparable = normalizar(nombre);
            var unidad = formulario.querySelector('[data-nueva-unidad]:checked');
            var primero = null;

            var repetidoInventario = filas.some(function (fila) {
                return normalizar(fila.getAttribute('data-nombre')) === comparable;
            });
            var repetidoCompra = lineas().some(function (linea) {
                return linea.getAttribute('data-nuevo') === '1' && normalizar(linea.querySelector('[data-linea-nombre]').value) === comparable;
            });
            var mensajeNombre = nombre === '' ? 'Escriba el nombre del componente'
                : nombre.length < 2 || nombre.length > 100 ? 'El nombre debe tener entre 2 y 100 letras'
                : !patronNombre.test(nombre) ? 'El nombre solo puede tener letras, números, espacios y los signos . , ( ) # / & \' -'
                : repetidoInventario ? 'Ya tiene un componente con ese nombre; búsquelo en "De mi inventario"'
                : repetidoCompra ? 'Ya agregó un componente nuevo con ese nombre' : '';
            if (mensajeNombre !== '') {
                ponerError(nuevoNombre, mensajeNombre);
                primero = nuevoNombre;
            }
            if (nuevoTipo.value === '') {
                ponerError(nuevoTipo, 'Escoja el tipo del componente');
                primero = primero || nuevoTipo;
            }

            camposAtributos().forEach(function (campo) {
                var mensaje = '';
                if (campo.hasAttribute('data-obligatorio') && campo.value.trim() === '') {
                    mensaje = 'Este tipo siempre pide ' + campo.getAttribute('data-nombre').toLowerCase();
                } else if (campo.getAttribute('data-clase') === 'Numero' && campo.value.trim() !== '' && leerNumero(campo.value) === null) {
                    mensaje = 'Escriba un número, por ejemplo 8 o 8,5';
                }
                if (mensaje !== '') {
                    ponerError(campo, mensaje);
                    primero = primero || campo;
                }
            });

            if (primero) {
                primero.focus();
                return;
            }

            var campos = camposAtributos();
            var enCorto = campos.map(textoAtributo).filter(function (texto) { return texto !== ''; }).join(', ');
            var tipoNombre = nuevoTipo.options[nuevoTipo.selectedIndex].text.trim();
            var linea = crearLinea({
                componente: 'nuevo',
                nombre: nombre,
                tipo: nuevoTipo.value,
                detalle: tipoNombre + (enCorto !== '' ? ' · ' + enCorto : ''),
                unidad: unidad ? unidad.value : 'Unidad',
                existencia: 0,
                nuevo: true,
                valores: campos.map(function (campo) {
                    return { atributo: campo.getAttribute('data-atributo'), valor: campo.type === 'checkbox' ? (campo.checked ? '1' : '0') : campo.value.trim() };
                })
            });
            if (linea) {
                limpiarNuevo();
            }
        });

        nuevoNombre.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter') {
                evento.preventDefault();
                botonNuevo.click();
            }
        });
        panelNuevo.addEventListener('input', function (evento) {
            if (evento.target.closest('.campoConError')) {
                quitarErrores(evento.target.closest('.campo'));
            }
        });
        panelNuevo.addEventListener('change', function (evento) {
            if (evento.target.closest('.campoConError')) {
                quitarErrores(evento.target.closest('.campo'));
            }
        });
        mostrarAtributos();
    }

    var crearDesdeBusqueda = formulario.querySelector('[data-crear-desde-busqueda]');
    if (crearDesdeBusqueda) {
        crearDesdeBusqueda.addEventListener('click', function () {
            abrirPestana('nuevo');
            nuevoNombre.value = buscador.value.trim();
            nuevoTipo.focus();
        });
    }

    formulario.addEventListener('input', actualizar);
    formulario.addEventListener('change', actualizar);

    formulario.addEventListener('submit', function (evento) {
        var primero = null;
        var fecha = formulario.querySelector('#fecha');
        if (fecha.value !== '' && !fecha.closest('.campoConError')) {
            var mensajeFecha = fecha.value > fecha.max ? 'La fecha no puede ser después de hoy'
                : fecha.value < fecha.min ? 'Escoja una fecha a partir del año 2000' : '';
            if (mensajeFecha !== '') {
                ponerError(fecha, mensajeFecha);
                primero = fecha;
            }
        }

        var avisoVacio = formulario.querySelector('[data-error-lineas]');
        if (avisoVacio) {
            avisoVacio.remove();
        }
        if (lineas().length === 0) {
            var error = document.createElement('p');
            error.className = 'errorCampo';
            error.setAttribute('data-error-lineas', '');
            error.textContent = 'Agregue al menos un componente a la compra';
            sinLineas.parentNode.insertBefore(error, sinLineas);
            evento.preventDefault();
            (primero || formulario.querySelector('[data-pestana].pestanaActiva')).focus();
            return;
        }

        lineas().forEach(function (linea) {
            var cantidad = linea.querySelector('[data-linea-cantidad]');
            var total = linea.querySelector('[data-linea-total]');
            var numeroCantidad = leerNumero(cantidad.value);
            var enCentimetros = linea.getAttribute('data-unidad') === 'Metro' && linea.querySelector('[data-linea-medida]').value === 'cm';
            var mensajeCantidad = '';
            var mensajeTotal = '';

            if (cantidad.value.trim() !== '') {
                if (cantidad.value.trim().charAt(0) === '-') {
                    mensajeCantidad = 'No puede ser un número negativo';
                } else if (numeroCantidad === null) {
                    mensajeCantidad = 'Escriba un número, por ejemplo 200 o 1,5';
                } else if (numeroCantidad <= 0) {
                    mensajeCantidad = 'La cantidad debe ser mayor que 0';
                } else if (linea.getAttribute('data-unidad') === 'Unidad' && Math.floor(numeroCantidad) !== numeroCantidad) {
                    mensajeCantidad = 'Se cuenta por unidades: use números enteros';
                } else if (enCentimetros && Math.floor(numeroCantidad) !== numeroCantidad) {
                    mensajeCantidad = 'Los centímetros van sin decimales';
                }
            }
            if (total.value.trim() !== '') {
                var numeroTotal = leerNumero(total.value);
                if (total.value.trim().charAt(0) === '-') {
                    mensajeTotal = 'No puede ser un número negativo';
                } else if (numeroTotal === null) {
                    mensajeTotal = 'Escriba un monto, por ejemplo 2400 o 2400,50';
                } else if (numeroTotal <= 0) {
                    mensajeTotal = 'El precio total debe ser mayor que 0';
                }
            }

            [[cantidad, mensajeCantidad], [total, mensajeTotal]].forEach(function (par) {
                if (par[1] !== '' && !par[0].closest('.campoConError')) {
                    ponerError(par[0], par[1]);
                    primero = primero || par[0];
                }
            });
        });

        if (primero) {
            evento.preventDefault();
            primero.focus();
        }
    });

    filtrarInventario();
    actualizar();
});