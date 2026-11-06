document.addEventListener('DOMContentLoaded', function () {

    var nodoDatos = document.getElementById('datosPersonalizador');
    if (!nodoDatos) {
        return;
    }
    var datos = JSON.parse(nodoDatos.textContent);
    var A = AlgoritmosVista;
    var CLAVE_GUARDADO = 'disenoBisuBox' + datos.idTienda;
    var DIAS_GUARDADO = 30;

    var lienzo = document.getElementById('lienzoDiseno');
    var rango = document.getElementById('largoPieza');
    var largoTexto = document.getElementById('largoTexto');
    var simetrico = document.getElementById('simetricoPieza');
    var aviso = document.getElementById('avisoDiseno');
    var hiloUsado = document.getElementById('hiloUsado');
    var barraHilo = document.getElementById('barraHilo');
    var faltaHilo = document.getElementById('faltaHilo');
    var ajustarLargo = document.getElementById('ajustarLargo');
    var largoSugerido = null;
    var precio = document.getElementById('precioDiseno');
    var materiales = document.getElementById('materialesDiseno');
    var catalogoLista = document.getElementById('catalogoPiezas');
    var ordenLista = document.getElementById('ordenPiezas');
    var ordenVacio = document.getElementById('ordenVacio');
    var formulario = document.getElementById('formularioDiseno');

    var catalogo = {};
    datos.piezas.forEach(function (pieza) {
        catalogo[pieza.id] = {
            id: pieza.id,
            nombre: pieza.nombre,
            foto: pieza.foto,
            tamano: pieza.tamano,
            precio: pieza.precio,
            existencia: pieza.existencia - pieza.enCarrito,
            existenciaTienda: pieza.existencia,
            enCarrito: pieza.enCarrito
        };
    });

    var estado = {
        tipo: 'pulsera',
        largo: datos.tipos.pulsera.inicial,
        simetrico: simetrico.checked,
        elegidas: []
    };

    function cm(milimetros) {
        var texto = (Math.round(milimetros) / 10).toFixed(1).replace('.', ',');
        return texto.slice(-2) === ',0' ? texto.slice(0, -2) : texto;
    }

    function moneda(monto) {
        var entero = Math.round(monto);
        return '₡' + String(entero).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function tipoActual() {
        return datos.tipos[estado.tipo];
    }

    function mensajeExistencia(pieza) {
        return 'No hay suficientes de «' + pieza.nombre + '»: la tienda tiene ' + pieza.existenciaTienda
            + (pieza.enCarrito > 0 ? ' y usted ya tiene ' + pieza.enCarrito + ' en otras piezas del carrito' : '') + '.';
    }

    function milimetros(decimas) {
        return String(decimas / 10).replace('.', ',');
    }

    function textoProblema(problema, pieza) {
        if (!problema) {
            return '';
        }
        if (problema.tipo === 'espacio') {
            var usadoAhora = A.largoUsado(A.listaFinal(estado.elegidas, estado.simetrico), catalogo);
            return 'Ya no cabe «' + pieza.nombre + '»: quedan ' + cm((problema.largo - usadoAhora) / 10) + ' cm de hilo.';
        }
        if (problema.tipo === 'existencia') {
            return mensajeExistencia(catalogo[problema.id]);
        }
        return datos.mensajes.vacio;
    }

    function avisar(texto, esBueno) {
        aviso.textContent = texto;
        aviso.classList.toggle('avisoBueno', !!esBueno && texto !== '');
    }

    function guardarEnNavegador() {
        try {
            if (estado.elegidas.length === 0) {
                localStorage.removeItem(CLAVE_GUARDADO);
                return;
            }
            localStorage.setItem(CLAVE_GUARDADO, JSON.stringify({
                tipo: estado.tipo,
                largo: estado.largo,
                simetrico: estado.simetrico,
                elegidas: estado.elegidas,
                fecha: Date.now()
            }));
        } catch (error) {
            return;
        }
    }

    function olvidarGuardado() {
        try {
            localStorage.removeItem(CLAVE_GUARDADO);
        } catch (error) {
            return;
        }
    }

    function agregar(id) {
        var pieza = catalogo[id];
        if (pieza.existencia <= 0) {
            avisar(mensajeExistencia(pieza));
            return;
        }
        var prueba = estado.elegidas.concat([id]);
        var problema = A.revisarDiseno(prueba, estado.simetrico, estado.largo * 10, catalogo);
        if (problema) {
            avisar(textoProblema(problema, pieza));
            return;
        }
        estado.elegidas = prueba;
        avisar('');
        actualizar();
    }

    function quitarEn(posicion) {
        estado.elegidas.splice(posicion, 1);
        avisar('');
        actualizar();
    }

    function recortar(motivo) {
        var antes = estado.elegidas.length;
        estado.elegidas = A.recortarHastaQueSirva(estado.elegidas, estado.simetrico, estado.largo * 10, catalogo);
        if (estado.elegidas.length < antes) {
            avisar(motivo);
        }
    }

    function dibujarCatalogo() {
        var lista = A.listaFinal(estado.elegidas, estado.simetrico);
        var cuentas = A.contar(lista);
        catalogoLista.innerHTML = '';
        datos.piezas.forEach(function (dato) {
            var pieza = catalogo[dato.id];
            var quedan = pieza.existencia - (cuentas[pieza.id] || 0);
            var boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'piezaCatalogo' + (quedan <= 0 ? ' piezaAgotada' : '');
            boton.setAttribute('aria-label', 'Agregar ' + pieza.nombre);
            var imagen = document.createElement('img');
            imagen.src = pieza.foto;
            imagen.alt = '';
            imagen.loading = 'lazy';
            var texto = document.createElement('span');
            var nombre = document.createElement('strong');
            nombre.textContent = pieza.nombre;
            var detalle = document.createElement('small');
            detalle.textContent = milimetros(pieza.tamano) + ' mm · ' + (quedan > 0 ? 'Quedan ' + quedan : 'Agotado');
            texto.appendChild(nombre);
            texto.appendChild(detalle);
            boton.appendChild(imagen);
            boton.appendChild(texto);
            boton.addEventListener('click', function () {
                agregar(pieza.id);
            });
            catalogoLista.appendChild(boton);
        });
    }

    function dibujarOrden() {
        ordenLista.innerHTML = '';
        ordenVacio.hidden = estado.elegidas.length > 0;
        estado.elegidas.forEach(function (id, posicion) {
            var pieza = catalogo[id];
            var elemento = document.createElement('li');
            var imagen = document.createElement('img');
            imagen.src = pieza.foto;
            imagen.alt = '';
            var nombre = document.createElement('span');
            nombre.textContent = (estado.simetrico && posicion === 0 ? 'Centro: ' : '') + pieza.nombre
                + (estado.simetrico && posicion > 0 ? ' (×2)' : '');
            var quitar = document.createElement('button');
            quitar.type = 'button';
            quitar.className = 'botonEnlace';
            quitar.textContent = 'Quitar';
            quitar.setAttribute('aria-label', 'Quitar ' + pieza.nombre + ' de la posición ' + (posicion + 1));
            quitar.addEventListener('click', function () {
                quitarEn(posicion);
            });
            elemento.appendChild(imagen);
            elemento.appendChild(nombre);
            elemento.appendChild(quitar);
            ordenLista.appendChild(elemento);
        });
    }

    function calcularPrecio(lista) {
        if (lista.length === 0) {
            return 0;
        }
        var suma = lista.reduce(function (total, id) { return total + catalogo[id].precio; }, 0);
        suma += datos.hiloPorMm * estado.largo + datos.manoObra;
        var redondeado = Math.round(suma * 100) / 100;
        return Math.ceil(redondeado / datos.redondeo) * datos.redondeo;
    }

    function problemaParaPedir(lista) {
        if (lista.length === 0) {
            return datos.mensajes.vacio;
        }
        var usado = A.largoUsado(lista, catalogo);
        var largo = estado.largo * 10;
        if (usado > largo) {
            return datos.mensajes.espacio;
        }
        if (tipoActual().debeCerrar && usado < largo * datos.proporcionCerrar) {
            return 'Faltan ' + cm((largo - usado) / 10) + ' cm para cerrar la ' + tipoActual().nombreMinuscula + '.';
        }
        var cuentas = A.contar(lista);
        for (var id in cuentas) {
            if (cuentas[id] > catalogo[id].existencia) {
                return mensajeExistencia(catalogo[id]);
            }
        }
        if (estado.largo > datos.hiloDisponibleMm + 0.01) {
            return datos.mensajes.hilo;
        }
        return '';
    }

    function dibujarVista() {
        var lista = A.listaFinal(estado.elegidas, estado.simetrico);
        DibujoDiseno.dibujar(lienzo, {
            forma: tipoActual().forma,
            largo: estado.largo * 10,
            lista: lista,
            catalogo: catalogo,
            texto: tipoActual().nombre + ' · ' + cm(estado.largo) + ' cm'
        }, dibujarVista);
    }

    function actualizar() {
        var lista = A.listaFinal(estado.elegidas, estado.simetrico);
        var usado = A.largoUsado(lista, catalogo);
        var largo = estado.largo * 10;

        largoTexto.textContent = cm(estado.largo) + ' cm';
        hiloUsado.textContent = cm(usado / 10) + ' de ' + cm(estado.largo) + ' cm';
        barraHilo.style.width = Math.min(100, usado / largo * 100) + '%';
        largoSugerido = null;
        if (tipoActual().debeCerrar) {
            var cierra = usado >= largo * datos.proporcionCerrar;
            faltaHilo.textContent = cierra
                ? 'La vuelta está completa.'
                : 'Faltan ' + cm((largo - usado) / 10) + ' cm para cerrar la ' + tipoActual().nombreMinuscula + '.';
            if (!cierra && lista.length > 0) {
                var sugerido = A.largoParaCerrar(usado, tipoActual().minimo * 10, tipoActual().maximo * 10, datos.paso * 10, datos.proporcionCerrar);
                if (sugerido !== null && sugerido !== largo) {
                    largoSugerido = sugerido / 10;
                }
            }
        } else {
            faltaHilo.textContent = lista.length ? 'Las piezas cubren el ' + Math.round(usado / largo * 100) + ' % del collar.' : '';
        }
        ajustarLargo.hidden = largoSugerido === null;
        if (largoSugerido !== null) {
            ajustarLargo.textContent = 'Ajustar el largo a ' + cm(largoSugerido) + ' cm para que cierre';
        }
        precio.textContent = moneda(calcularPrecio(lista));

        materiales.innerHTML = '';
        var cuentas = A.contar(lista);
        Object.keys(cuentas).forEach(function (id) {
            var elemento = document.createElement('li');
            elemento.textContent = cuentas[id] + ' × ' + catalogo[id].nombre;
            materiales.appendChild(elemento);
        });
        if (lista.length) {
            var hilo = document.createElement('li');
            hilo.textContent = cm(estado.largo) + ' cm de ' + datos.nombreHilo;
            materiales.appendChild(hilo);
        } else {
            var vacio = document.createElement('li');
            vacio.className = 'textoAyuda';
            vacio.textContent = 'Todavía no hay piezas.';
            materiales.appendChild(vacio);
        }

        dibujarOrden();
        dibujarCatalogo();
        dibujarVista();
        guardarEnNavegador();
    }

    function ajustarRango() {
        var tipo = tipoActual();
        rango.min = tipo.minimo;
        rango.max = tipo.maximo;
        rango.value = estado.largo;
        rango.setAttribute('aria-valuetext', cm(estado.largo) + ' centímetros');
    }

    document.querySelectorAll('input[name="tipoPieza"]').forEach(function (opcion) {
        opcion.addEventListener('change', function () {
            if (!opcion.checked) {
                return;
            }
            estado.tipo = opcion.value;
            estado.largo = tipoActual().inicial;
            ajustarRango();
            avisar('');
            recortar('Se quitaron las últimas piezas porque ya no cabían.');
            actualizar();
        });
    });

    rango.addEventListener('input', function () {
        estado.largo = parseInt(rango.value, 10);
        rango.setAttribute('aria-valuetext', cm(estado.largo) + ' centímetros');
        avisar('');
        recortar('Se quitaron las últimas piezas porque ya no cabían.');
        actualizar();
    });

    simetrico.addEventListener('change', function () {
        estado.simetrico = simetrico.checked;
        avisar('');
        recortar('Se quitaron las últimas piezas porque en simétrico no cabían.');
        actualizar();
    });

    ajustarLargo.addEventListener('click', function () {
        if (largoSugerido === null) {
            return;
        }
        estado.largo = largoSugerido;
        rango.value = estado.largo;
        avisar('Largo ajustado a ' + cm(estado.largo) + ' cm.', true);
        actualizar();
    });

    document.getElementById('deshacerPieza').addEventListener('click', function () {
        estado.elegidas.pop();
        avisar('');
        actualizar();
    });

    document.getElementById('limpiarPieza').addEventListener('click', function () {
        estado.elegidas = [];
        avisar('');
        actualizar();
    });

    document.getElementById('patronPieza').addEventListener('click', function () {
        var resultado = A.repetirPatron(estado.elegidas, estado.simetrico, estado.largo * 10, catalogo);
        if (resultado.motivo === 'vacio') {
            avisar(estado.simetrico ? 'Ponga el centro y al menos una pieza más para repetir.' : 'Ponga primero 2 o 3 piezas para repetir.');
            return;
        }
        estado.elegidas = resultado.elegidas;
        if (resultado.motivo === 'existencia') {
            avisar('Se detuvo porque ' + mensajeExistencia(catalogo[resultado.problema.id]).charAt(0).toLowerCase()
                + mensajeExistencia(catalogo[resultado.problema.id]).slice(1));
        } else {
            var texto = resultado.agregadas > 0 ? 'Patrón repetido hasta llenar el hilo.' : 'Ya no cabe otra vuelta del patrón.';
            if (resultado.relleno) {
                texto = 'Patrón repetido. El final se completó con «' + catalogo[resultado.relleno].nombre + '» para que cierre.';
            }
            avisar(texto, resultado.agregadas > 0);
        }
        actualizar();
    });

    if (formulario) {
        formulario.addEventListener('submit', function (evento) {
            var lista = A.listaFinal(estado.elegidas, estado.simetrico);
            var problema = problemaParaPedir(lista);
            if (problema !== '') {
                evento.preventDefault();
                avisar(problema);
                aviso.focus();
                return;
            }
            document.getElementById('campoTipo').value = estado.tipo;
            document.getElementById('campoLargo').value = String(estado.largo);
            document.getElementById('campoSimetrico').value = estado.simetrico ? '1' : '0';
            var contenedor = document.getElementById('campoPiezas');
            contenedor.innerHTML = '';
            estado.elegidas.forEach(function (id) {
                var campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = 'piezas[]';
                campo.value = String(id);
                contenedor.appendChild(campo);
            });
            guardarEnNavegador();
        });
    }

    function recuperar() {
        if (datos.disenoAgregado) {
            olvidarGuardado();
            return;
        }
        var guardado = null;
        try {
            guardado = JSON.parse(localStorage.getItem(CLAVE_GUARDADO) || 'null');
        } catch (error) {
            guardado = null;
        }
        var vencido = !guardado || !guardado.fecha || Date.now() - guardado.fecha > DIAS_GUARDADO * 24 * 60 * 60 * 1000;
        if (vencido || !datos.tipos[guardado.tipo] || !Array.isArray(guardado.elegidas) || guardado.elegidas.length === 0) {
            olvidarGuardado();
            return;
        }
        var tipo = datos.tipos[guardado.tipo];
        estado.tipo = guardado.tipo;
        estado.largo = Math.min(tipo.maximo, Math.max(tipo.minimo, parseInt(guardado.largo, 10) || tipo.inicial));
        estado.simetrico = !!guardado.simetrico;
        estado.elegidas = (guardado.elegidas || []).filter(function (id) { return !!catalogo[id]; });
        simetrico.checked = estado.simetrico;
        document.querySelectorAll('input[name="tipoPieza"]').forEach(function (opcion) {
            opcion.checked = opcion.value === estado.tipo;
        });
        var antes = estado.elegidas.length;
        recortar('Se quitaron algunas piezas porque ya no hay suficientes.');
        if (estado.elegidas.length === antes && antes > 0) {
            avisar('Recuperamos el diseño que estaba armando. Si quiere empezar otro, toque Limpiar.', true);
        } else if (estado.elegidas.length === 0) {
            avisar('Su diseño anterior ya no se puede armar porque se acabaron sus piezas.');
        }
    }

    aviso.tabIndex = -1;
    recuperar();
    ajustarRango();
    actualizar();
});
