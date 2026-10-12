document.addEventListener('DOMContentLoaded', function () {
    var formulario = document.querySelector('.formularioCompra');
    if (!formulario) {
        return;
    }

    var lista = formulario.querySelector('[data-lineas]');
    var sinLineas = formulario.querySelector('[data-sin-lineas]');
    var plantilla = document.querySelector('[data-linea-plantilla]');
    var campoEnvio = formulario.querySelector('[data-costo-envio]');
    var maximo = parseInt(formulario.getAttribute('data-maximo-lineas'), 10) || 50;
    var patronNumero = /^[0-9]{1,10}([.,][0-9]{1,2})?$/;

    var unidades = {
        Unidad: { singular: 'unidad', plural: 'unidades', costo: 'Costo de cada uno', ayuda: 'Solo números enteros', entera: true },
        Metro: { singular: 'metro', plural: 'metros', costo: 'Costo por metro', ayuda: 'En metros. Para centímetros use decimales: 20 cm = 0,20', entera: false },
        Gramo: { singular: 'gramo', plural: 'gramos', costo: 'Costo por gramo', ayuda: 'En gramos; puede usar decimales, por ejemplo 2,5', entera: false }
    };

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

    function normalizar(texto) {
        return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
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

    var tarjetas = Array.prototype.slice.call(formulario.querySelectorAll('[data-tarjeta]'));
    var buscador = formulario.querySelector('[data-buscar-componente]');
    var sinCoincidencias = formulario.querySelector('[data-sin-coincidencias]');
    var tipoElegido = '';

    function filtrarTarjetas() {
        var texto = buscador ? normalizar(buscador.value) : '';
        var visibles = 0;
        tarjetas.forEach(function (tarjeta) {
            var coincide = (tipoElegido === '' || tarjeta.getAttribute('data-tipo') === tipoElegido)
                && (texto === '' || normalizar(tarjeta.getAttribute('data-nombre')).indexOf(texto) !== -1);
            tarjeta.hidden = !coincide;
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
                otro.classList.toggle('botonFechaActivo', otro === boton);
                otro.setAttribute('aria-pressed', otro === boton ? 'true' : 'false');
            });
            filtrarTarjetas();
        });
    });

    if (buscador) {
        buscador.addEventListener('input', filtrarTarjetas);
        buscador.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter') {
                evento.preventDefault();
            }
        });
    }

    function marcarTarjeta(idComponente, agregado) {
        tarjetas.forEach(function (tarjeta) {
            if (tarjeta.getAttribute('data-id') === idComponente) {
                tarjeta.querySelector('[data-en-compra]').hidden = !agregado;
                tarjeta.querySelector('[data-agregar]').hidden = agregado;
            }
        });
    }

    function crearLinea(datos) {
        if (lineas().length >= maximo) {
            window.alert('Una compra puede tener como máximo ' + maximo + ' componentes.');
            return null;
        }
        var contenedor = document.createElement('div');
        contenedor.innerHTML = plantilla.innerHTML.replace(/__n__/g, String(siguienteClave++)).trim();
        var linea = contenedor.firstElementChild;
        var unidad = unidades[datos.unidad] || unidades.Unidad;

        linea.setAttribute('data-componente', datos.componente);
        linea.setAttribute('data-existencia', String(datos.existencia));
        linea.setAttribute('data-plural', unidad.plural);
        linea.setAttribute('data-entera', unidad.entera ? '1' : '0');
        linea.setAttribute('data-unidad', datos.unidad);
        linea.setAttribute('data-nuevo', datos.nuevo ? '1' : '0');
        linea.querySelector('[data-linea-componente]').value = datos.componente;
        linea.querySelector('[data-linea-tipo]').value = datos.nuevo ? datos.tipo : '';
        linea.querySelector('[data-linea-nombre]').value = datos.nuevo ? datos.nombre : '';
        linea.querySelector('[data-linea-unidad]').value = datos.nuevo ? datos.unidad : '';
        linea.querySelector('[data-linea-titulo]').textContent = datos.nombre;
        linea.querySelector('[data-linea-tipo-nombre]').textContent = datos.tipoNombre;
        linea.querySelector('[data-linea-marca-nuevo]').hidden = !datos.nuevo;
        linea.querySelector('[data-linea-quitar]').setAttribute('aria-label', 'Quitar ' + datos.nombre + ' de la compra');
        linea.querySelector('[data-linea-plural]').textContent = unidad.plural;
        linea.querySelector('[data-linea-ayuda]').textContent = unidad.ayuda;
        linea.querySelector('[data-linea-etiqueta-costo]').textContent = unidad.costo;

        lista.appendChild(linea);
        actualizar();
        linea.querySelector('[data-linea-cantidad]').focus();
        return linea;
    }

    function actualizarLinea(linea) {
        var unidad = unidades[linea.getAttribute('data-unidad')] || unidades.Unidad;
        var cantidad = leerNumero(linea.querySelector('[data-linea-cantidad]').value) || 0;
        var total = leerNumero(linea.querySelector('[data-linea-total]').value) || 0;

        linea.querySelector('[data-linea-costo]').textContent = cantidad > 0 ? moneda(Math.round(total / cantidad * 100) / 100) : '—';

        var ayuda = unidad.ayuda;
        if (linea.getAttribute('data-unidad') === 'Metro' && cantidad > 0) {
            ayuda += ' · ' + cantidadTexto(cantidad) + ' m = ' + cantidadTexto(cantidad * 100) + ' cm';
        }
        linea.querySelector('[data-linea-ayuda]').textContent = ayuda;

        var existencia = parseFloat(linea.getAttribute('data-existencia')) || 0;
        var aviso = linea.querySelector('[data-linea-aviso]');
        if (linea.getAttribute('data-nuevo') === '1') {
            aviso.textContent = cantidad > 0 ? 'Entrará al inventario con ' + cantidadTexto(cantidad) + ' ' + unidad.plural : 'Componente nuevo: se agrega al inventario al registrar la compra';
        } else {
            aviso.textContent = 'Tiene ' + cantidadTexto(existencia) + ' ' + (existencia === 1 ? unidad.singular : unidad.plural)
                + (cantidad > 0 ? ' → quedará con ' + cantidadTexto(existencia + cantidad) : '');
        }

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
        formulario.querySelector('[data-resumen-cantidad]').textContent = cantidadLineas === 1 ? '1 componente' : cantidadLineas + ' componentes';
        formulario.querySelector('[data-total-subtotal]').textContent = moneda(subtotal);
        formulario.querySelector('[data-total-envio]').textContent = envio > 0 ? moneda(envio) : 'Sin envío';
        formulario.querySelector('[data-total-compra]').textContent = moneda(subtotal + envio);

        var proveedor = formulario.querySelector('#proveedor');
        formulario.querySelector('[data-resumen-proveedor]').textContent = proveedor.value !== ''
            ? proveedor.options[proveedor.selectedIndex].text.trim() : 'Sin escoger';
        var fecha = formulario.querySelector('#fecha').value;
        formulario.querySelector('[data-resumen-fecha]').textContent = /^\d{4}-\d{2}-\d{2}$/.test(fecha)
            ? fecha.split('-').reverse().join('/') : 'Sin escoger';
    }

    tarjetas.forEach(function (tarjeta) {
        tarjeta.querySelector('[data-agregar]').addEventListener('click', function () {
            var linea = crearLinea({
                componente: tarjeta.getAttribute('data-id'),
                nombre: tarjeta.getAttribute('data-nombre'),
                tipoNombre: tarjeta.getAttribute('data-tipo-nombre'),
                unidad: tarjeta.getAttribute('data-unidad'),
                existencia: parseFloat(tarjeta.getAttribute('data-existencia')) || 0,
                nuevo: false
            });
            if (linea) {
                marcarTarjeta(tarjeta.getAttribute('data-id'), true);
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
            marcarTarjeta(linea.getAttribute('data-componente'), false);
        }
        linea.remove();
        actualizar();
    });

    // ---------- Componente nuevo ----------
    var nuevoTipo = formulario.querySelector('[data-nuevo-tipo]');
    var nuevoNombre = formulario.querySelector('[data-nuevo-nombre]');
    var botonNuevo = formulario.querySelector('[data-agregar-nuevo]');

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

    if (botonNuevo) {
        var panelNuevo = formulario.querySelector('[data-panel="nuevo"]');

        botonNuevo.addEventListener('click', function () {
            quitarErrores(panelNuevo);
            var nombre = nuevoNombre.value.replace(/\s+/g, ' ').trim();
            var comparable = normalizar(nombre);
            var unidad = formulario.querySelector('[data-nueva-unidad]:checked');
            var primero = null;

            if (nuevoTipo.value === '') {
                ponerError(nuevoTipo, 'Escoja el tipo del componente');
                primero = nuevoTipo;
            }
            var repetidoInventario = tarjetas.some(function (tarjeta) {
                return normalizar(tarjeta.getAttribute('data-nombre')) === comparable;
            });
            var repetidoCompra = lineas().some(function (linea) {
                return linea.getAttribute('data-nuevo') === '1' && normalizar(linea.querySelector('[data-linea-nombre]').value) === comparable;
            });
            var mensajeNombre = nombre.length < 2 || nombre.length > 100 ? 'Escriba el nombre del componente (entre 2 y 100 caracteres)'
                : repetidoInventario ? 'Ya tiene un componente con ese nombre; búsquelo en "De mi inventario"'
                : repetidoCompra ? 'Ya agregó un componente nuevo con ese nombre' : '';
            if (mensajeNombre !== '') {
                ponerError(nuevoNombre, mensajeNombre);
                primero = primero || nuevoNombre;
            }
            if (primero) {
                primero.focus();
                return;
            }

            var linea = crearLinea({
                componente: 'nuevo',
                nombre: nombre,
                tipo: nuevoTipo.value,
                tipoNombre: nuevoTipo.options[nuevoTipo.selectedIndex].text.trim(),
                unidad: unidad ? unidad.value : 'Unidad',
                existencia: 0,
                nuevo: true
            });
            if (linea) {
                nuevoNombre.value = '';
            }
        });

        // Enter en el nombre agrega el componente en vez de enviar la compra
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

    // Lo que validaciones.js no revisa: que haya componentes y que la cantidad vaya con su unidad
    formulario.addEventListener('submit', function (evento) {
        var primero = null;
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
            formulario.querySelector('[data-pestana].pestanaActiva').focus();
            return;
        }

        lineas().forEach(function (linea) {
            var cantidad = linea.querySelector('[data-linea-cantidad]');
            var total = linea.querySelector('[data-linea-total]');
            var numeroCantidad = leerNumero(cantidad.value);
            var esMetro = linea.getAttribute('data-unidad') === 'Metro';
            var mensajeCantidad = '';
            var mensajeTotal = '';

            if (cantidad.value.trim() !== '') {
                if (numeroCantidad === null) {
                    mensajeCantidad = esMetro ? 'Escriba los metros con hasta 2 decimales; para centímetros: 20 cm = 0,20'
                        : 'Escriba un número con hasta 2 decimales, por ejemplo 200 o 1,5';
                } else if (numeroCantidad <= 0) {
                    mensajeCantidad = 'La cantidad debe ser mayor que 0';
                } else if (linea.getAttribute('data-entera') === '1' && Math.floor(numeroCantidad) !== numeroCantidad) {
                    mensajeCantidad = 'Se cuenta por unidades: use números enteros';
                }
            }
            if (total.value.trim() !== '' && leerNumero(total.value) === null) {
                mensajeTotal = 'Escriba un monto, por ejemplo 2400 o 2400,50';
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

    filtrarTarjetas();
    actualizar();
});