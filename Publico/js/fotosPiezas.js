document.addEventListener('DOMContentLoaded', function () {

    var datosPagina = document.getElementById('datosFotosPiezas');
    if (!datosPagina) {
        return;
    }
    var datos = JSON.parse(datosPagina.textContent);
    var A = AlgoritmosVista;

    var MENSAJES = {
        componente: 'Elija a qué componente corresponde',
        repetido: 'Ese componente ya está elegido en otra pieza',
        imagen: 'La imagen recortada no es válida. Vuelva a procesar la foto.',
        ninguna: 'Marque al menos una pieza para guardar',
        archivo: 'El archivo debe ser una foto JPG, PNG o WEBP',
        pesado: 'La foto pesa más de 15 MB. Use una más liviana.',
        noAbre: 'No se pudo abrir esa foto. Pruebe con otra.',
        tamano: 'Escriba el tamaño en milímetros'
    };
    var PESO_MAXIMO = 15 * 1024 * 1024;
    var LADO_RECORTE = 400;

    var campoFoto = document.getElementById('fotoPiezas');
    var seccionResultado = document.getElementById('seccionResultado');
    var lienzoOriginal = document.getElementById('lienzoOriginal');
    var lienzoResultado = document.getElementById('lienzoResultado');
    var tolerancia = document.getElementById('tolerancia');
    var toleranciaValor = document.getElementById('toleranciaValor');
    var avisos = document.getElementById('avisosFoto');
    var formulario = document.getElementById('formularioPiezas');
    var contenedor = document.getElementById('piezasEncontradas');
    var errorPiezas = document.getElementById('errorPiezas');
    var botonGuardar = document.getElementById('botonGuardarPiezas');

    var imagenActual = null;
    var piezas = [];
    var pendiente = 0;

    var componentesPorId = {};
    datos.componentes.forEach(function (componente) {
        componentesPorId[componente.id] = componente;
    });

    function errorDe(campo) {
        var caja = campo.closest('.campo');
        return caja ? caja.querySelector('.errorCampo') : null;
    }

    function ponerError(campo, mensaje) {
        quitarError(campo);
        var caja = campo.closest('.campo');
        caja.classList.add('campoConError');
        var parrafo = document.createElement('p');
        parrafo.className = 'errorCampo';
        parrafo.textContent = mensaje;
        caja.appendChild(parrafo);
    }

    function quitarError(campo) {
        var caja = campo.closest('.campo');
        if (!caja) {
            return;
        }
        caja.classList.remove('campoConError');
        var error = errorDe(campo);
        if (error) {
            error.remove();
        }
    }

    function errorArchivo(mensaje) {
        var caja = document.getElementById('campoFoto');
        caja.classList.toggle('campoConError', mensaje !== '');
        var anterior = caja.querySelector('.errorCampo');
        if (anterior) {
            anterior.remove();
        }
        if (mensaje !== '') {
            var parrafo = document.createElement('p');
            parrafo.className = 'errorCampo';
            parrafo.setAttribute('role', 'alert');
            parrafo.textContent = mensaje;
            caja.appendChild(parrafo);
        }
    }

    function aImageData(recorte) {
        return new ImageData(recorte.datos, recorte.ancho, recorte.alto);
    }

    function lienzoDe(recorte) {
        var lienzo = document.createElement('canvas');
        lienzo.width = recorte.ancho;
        lienzo.height = recorte.alto;
        lienzo.getContext('2d').putImageData(aImageData(recorte), 0, 0);
        return lienzo;
    }

    function aDataUrl(recorte) {
        var lienzo = lienzoDe(recorte);
        var escala = Math.min(1, LADO_RECORTE / Math.max(recorte.ancho, recorte.alto));
        if (escala < 1) {
            var reducido = document.createElement('canvas');
            reducido.width = Math.max(4, Math.round(recorte.ancho * escala));
            reducido.height = Math.max(4, Math.round(recorte.alto * escala));
            var contexto = reducido.getContext('2d');
            contexto.imageSmoothingQuality = 'high';
            contexto.drawImage(lienzo, 0, 0, reducido.width, reducido.height);
            lienzo = reducido;
        }
        return lienzo.toDataURL('image/png');
    }

    function recorteDesdeImagen(imagen) {
        var lienzo = document.createElement('canvas');
        lienzo.width = imagen.naturalWidth;
        lienzo.height = imagen.naturalHeight;
        var contexto = lienzo.getContext('2d');
        contexto.drawImage(imagen, 0, 0);
        var pixeles = contexto.getImageData(0, 0, lienzo.width, lienzo.height);
        return { ancho: lienzo.width, alto: lienzo.height, datos: pixeles.data };
    }

    function textoAvisos(pieza) {
        var lista = [];
        if (pieza.tocaBorde) {
            lista.push('Toca el borde de la foto: puede estar cortada.');
        }
        if (pieza.posiblesPegadas) {
            lista.push('Parecen dos piezas pegadas. Sepárelas y tome la foto otra vez.');
        }
        if (pieza.huecoGrande) {
            lista.push('Tiene un hueco grande. Si no es un aro, use "Rellenar hueco".');
        }
        return lista.join(' ');
    }

    function opcionesComponente(select, elegido) {
        var grupos = {};
        datos.componentes.forEach(function (componente) {
            if (!grupos[componente.tipo]) {
                grupos[componente.tipo] = document.createElement('optgroup');
                grupos[componente.tipo].label = componente.tipo;
                select.appendChild(grupos[componente.tipo]);
            }
            var opcion = document.createElement('option');
            opcion.value = String(componente.id);
            opcion.textContent = componente.nombre + (componente.tieneFoto ? ' (reemplaza su foto)' : '');
            opcion.selected = String(componente.id) === String(elegido);
            grupos[componente.tipo].appendChild(opcion);
        });
    }

    function crearTarjeta(pieza, indice) {
        var tarjeta = document.createElement('div');
        tarjeta.className = 'tarjetaPiezaNueva';

        var cabeza = document.createElement('label');
        cabeza.className = 'usarPieza';
        var usar = document.createElement('input');
        usar.type = 'checkbox';
        usar.name = 'piezas[' + indice + '][usar]';
        usar.value = '1';
        usar.checked = pieza.usar;
        cabeza.appendChild(usar);
        var numero = pieza.indiceServidor >= 0 ? pieza.indiceServidor + 1 : indice + 1;
        cabeza.appendChild(document.createTextNode(' Guardar pieza ' + numero));
        tarjeta.appendChild(cabeza);

        var vista = document.createElement('div');
        vista.className = 'fotoPieza fondoCuadros';
        tarjeta.appendChild(vista);

        var botones = document.createElement('div');
        botones.className = 'botonesPieza';
        var girar = document.createElement('button');
        girar.type = 'button';
        girar.className = 'boton botonSecundario botonPequeno';
        girar.textContent = 'Girar 90°';
        botones.appendChild(girar);
        var rellenar = null;
        if (pieza.tieneHueco) {
            rellenar = document.createElement('button');
            rellenar.type = 'button';
            rellenar.className = 'boton botonSecundario botonPequeno';
            botones.appendChild(rellenar);
        }
        tarjeta.appendChild(botones);

        var aviso = document.createElement('p');
        aviso.className = 'avisoPieza';
        aviso.textContent = pieza.avisos;
        aviso.hidden = pieza.avisos === '';
        tarjeta.appendChild(aviso);

        var campoComponente = document.createElement('div');
        campoComponente.className = 'campo';
        var etiquetaComponente = document.createElement('label');
        etiquetaComponente.htmlFor = 'pieza' + indice + 'componente';
        etiquetaComponente.textContent = '¿Qué componente es?';
        var select = document.createElement('select');
        select.id = 'pieza' + indice + 'componente';
        select.name = 'piezas[' + indice + '][componente]';
        select.setAttribute('data-mensaje-requerido', MENSAJES.componente);
        select.setAttribute('data-componente-pieza', '');
        var vacia = document.createElement('option');
        vacia.value = '';
        vacia.textContent = 'Elija un componente';
        select.appendChild(vacia);
        opcionesComponente(select, pieza.componente);
        campoComponente.appendChild(etiquetaComponente);
        campoComponente.appendChild(select);
        tarjeta.appendChild(campoComponente);

        var campoTamano = document.createElement('div');
        campoTamano.className = 'campo';
        var etiquetaTamano = document.createElement('label');
        etiquetaTamano.htmlFor = 'pieza' + indice + 'tamano';
        etiquetaTamano.textContent = 'Tamaño a lo largo del hilo';
        var envoltura = document.createElement('div');
        envoltura.className = 'campoConUnidad';
        var tamano = document.createElement('input');
        tamano.type = 'text';
        tamano.id = 'pieza' + indice + 'tamano';
        tamano.name = 'piezas[' + indice + '][tamano]';
        tamano.maxLength = 4;
        tamano.inputMode = 'decimal';
        tamano.value = pieza.tamano;
        tamano.setAttribute('data-mensaje-requerido', MENSAJES.tamano);
        var unidad = document.createElement('span');
        unidad.className = 'unidadCampo';
        unidad.textContent = 'mm';
        envoltura.appendChild(tamano);
        envoltura.appendChild(unidad);
        campoTamano.appendChild(etiquetaTamano);
        campoTamano.appendChild(envoltura);
        tarjeta.appendChild(campoTamano);

        var campoImagen = document.createElement('div');
        campoImagen.className = 'campo campoImagenPieza';
        var imagen = document.createElement('input');
        imagen.type = 'hidden';
        imagen.name = 'piezas[' + indice + '][imagen]';
        var avisosOcultos = document.createElement('input');
        avisosOcultos.type = 'hidden';
        avisosOcultos.name = 'piezas[' + indice + '][avisos]';
        avisosOcultos.value = pieza.avisos;
        campoImagen.appendChild(imagen);
        campoImagen.appendChild(avisosOcultos);
        tarjeta.appendChild(campoImagen);

        function recorteActual() {
            var recorte = pieza.rellenar ? pieza.recorteRelleno : pieza.recorte;
            for (var vuelta = 0; vuelta < pieza.giros % 4; vuelta++) {
                recorte = A.girar90(recorte);
            }
            return recorte;
        }

        function pintar() {
            var recorte = recorteActual();
            vista.innerHTML = '';
            var lienzo = lienzoDe(recorte);
            lienzo.setAttribute('role', 'img');
            lienzo.setAttribute('aria-label', 'Pieza ' + (indice + 1));
            vista.appendChild(lienzo);
            imagen.value = aDataUrl(recorte);
            if (rellenar) {
                rellenar.textContent = pieza.rellenar ? 'Dejar hueco' : 'Rellenar hueco';
            }
        }

        function activarCampos() {
            tarjeta.classList.toggle('piezaDescartada', !usar.checked);
            actualizarBoton();
            select.required = usar.checked;
            tamano.required = usar.checked;
            if (usar.checked) {
                tamano.setAttribute('data-regla', 'tamanoPieza');
            } else {
                tamano.removeAttribute('data-regla');
                quitarError(select);
                quitarError(tamano);
            }
        }

        girar.addEventListener('click', function () {
            pieza.giros++;
            pintar();
        });
        if (rellenar) {
            rellenar.addEventListener('click', function () {
                pieza.rellenar = !pieza.rellenar;
                pintar();
            });
        }
        usar.addEventListener('change', function () {
            pieza.usar = usar.checked;
            pieza.desmarcadaAMano = !usar.checked;
            activarCampos();
            setTimeout(revisarRepetidos, 0);
            errorPiezas.hidden = true;
        });
        select.addEventListener('change', function () {
            pieza.componente = select.value;
            if (select.value !== '' && !usar.checked && !pieza.desmarcadaAMano) {
                usar.checked = true;
                pieza.usar = true;
                activarCampos();
            }
            var componente = componentesPorId[select.value];
            if (componente && componente.tamano && tamano.value.trim() === '') {
                tamano.value = String(componente.tamano).replace('.', ',');
                tamano.dispatchEvent(new Event('input', { bubbles: true }));
            }
            setTimeout(revisarRepetidos, 0);
        });
        tamano.addEventListener('input', function () {
            pieza.tamano = tamano.value;
        });

        pieza.select = select;
        pieza.campoImagen = campoImagen;
        activarCampos();
        pintar();

        var errores = datos.errores || {};
        if (errores['piezas' + pieza.indiceServidor + 'componente']) {
            ponerError(select, errores['piezas' + pieza.indiceServidor + 'componente']);
        }
        if (errores['piezas' + pieza.indiceServidor + 'tamano']) {
            ponerError(tamano, errores['piezas' + pieza.indiceServidor + 'tamano']);
        }
        if (errores['piezas' + pieza.indiceServidor + 'imagen']) {
            ponerError(imagen, errores['piezas' + pieza.indiceServidor + 'imagen']);
        }

        return tarjeta;
    }

    function actualizarBoton() {
        var marcadas = piezas.filter(function (pieza) { return pieza && pieza.usar; }).length;
        botonGuardar.textContent = marcadas === 0 ? 'Guardar piezas'
            : marcadas === 1 ? 'Guardar 1 pieza' : 'Guardar ' + marcadas + ' piezas';
    }

    function revisarRepetidos() {
        var vistos = {};
        var hayRepetido = false;
        piezas.forEach(function (pieza) {
            if (!pieza.select) {
                return;
            }
            var valor = pieza.usar ? pieza.select.value : '';
            var error = errorDe(pieza.select);
            if (valor !== '' && vistos[valor]) {
                ponerError(pieza.select, MENSAJES.repetido);
                hayRepetido = true;
            } else if (error && error.textContent === MENSAJES.repetido) {
                quitarError(pieza.select);
            }
            if (valor !== '') {
                vistos[valor] = true;
            }
        });
        return hayRepetido;
    }

    function mostrarTarjetas() {
        contenedor.innerHTML = '';
        piezas.forEach(function (pieza, indice) {
            contenedor.appendChild(crearTarjeta(pieza, indice));
        });
        formulario.hidden = piezas.length === 0;
        revisarRepetidos();
    }

    function agregarAviso(texto, tipo) {
        var elemento = document.createElement('li');
        elemento.className = tipo === 'error' ? 'avisoError' : 'avisoAdvertencia';
        elemento.textContent = texto;
        avisos.appendChild(elemento);
    }

    function dibujarResultado(mascara, separacion, borradas) {
        lienzoResultado.width = imagenActual.ancho;
        lienzoResultado.height = imagenActual.alto;
        var contexto = lienzoResultado.getContext('2d');
        var salida = contexto.createImageData(imagenActual.ancho, imagenActual.alto);
        for (var p = 0; p < mascara.fondo.length; p++) {
            var i = p * 4;
            salida.data[i] = imagenActual.datos[i];
            salida.data[i + 1] = imagenActual.datos[i + 1];
            salida.data[i + 2] = imagenActual.datos[i + 2];
            salida.data[i + 3] = mascara.fondo[p] || A.esHuecoValido(separacion, p) ? 0 : 255;
        }
        contexto.putImageData(salida, 0, 0);
        var grosor = Math.max(2, Math.round(imagenActual.ancho / 400));
        contexto.lineWidth = grosor;
        contexto.font = '600 ' + Math.max(14, Math.round(imagenActual.ancho / 40)) + 'px sans-serif';
        separacion.piezas.forEach(function (pieza, indice) {
            var problema = pieza.tocaBorde || pieza.posiblesPegadas || pieza.huecoGrande;
            contexto.strokeStyle = problema ? '#b26b00' : '#2b7358';
            contexto.fillStyle = contexto.strokeStyle;
            contexto.setLineDash([]);
            contexto.strokeRect(pieza.x0 - 3, pieza.y0 - 3, pieza.ancho + 6, pieza.alto + 6);
            contexto.fillText(String(indice + 1), pieza.x0, Math.max(16, pieza.y0 - 8));
        });
        contexto.strokeStyle = '#a5302a';
        contexto.setLineDash([grosor * 3, grosor * 2]);
        separacion.zonasSospechosas.concat(borradas).forEach(function (zona) {
            contexto.strokeRect(zona.x0 - 3, zona.y0 - 3, zona.x1 - zona.x0 + 6, zona.y1 - zona.y0 + 6);
        });
    }

    function procesar() {
        if (!imagenActual) {
            return;
        }
        var anteriores = piezas;
        var mascara = A.quitarFondo(imagenActual, parseInt(tolerancia.value, 10));
        var separacion = A.separarPiezas(mascara);
        var fondoBlanco = A.esColorClaro(mascara.color.r, mascara.color.g, mascara.color.b);
        var borradas = fondoBlanco ? A.buscarPiezasBorradas(imagenActual, parseInt(tolerancia.value, 10), separacion) : [];
        dibujarResultado(mascara, separacion, borradas);

        avisos.innerHTML = '';
        if (!mascara.fondoParejo) {
            agregarAviso('El fondo no es liso o hay una pieza tocando el borde. Tome la foto de nuevo sobre una hoja lisa.', 'error');
        }

        if (separacion.zonasSospechosas.length > 0) {
            agregarAviso('Hay ' + (separacion.zonasSospechosas.length === 1 ? 'una zona' : separacion.zonasSospechosas.length + ' zonas')
                + ' marcada en rojo que parece una pieza del mismo color de la hoja. Tómele la foto sobre la hoja del otro color.', 'error');
        }
        if (borradas.length > 0) {
            agregarAviso((borradas.length === 1 ? 'Hay 1 pieza clara que se borró' : 'Hay ' + borradas.length + ' piezas claras que se borraron')
                + ' con la hoja blanca (marcadas en rojo). Tómeles la foto sobre la hoja verde o azul.', 'error');
        }
        if (separacion.piezas.length === 0) {
            agregarAviso('No se encontró ninguna pieza. Suba la tolerancia o tome la foto sobre una hoja de otro color.', 'error');
        }
        if (separacion.descartadas > 0) {
            agregarAviso('Se ignoraron ' + (separacion.descartadas === 1 ? '1 mancha pequeña' : separacion.descartadas + ' manchas pequeñas') + '.', 'advertencia');
        }
        if (separacion.piezas.length > 40) {
            agregarAviso('Se encontraron más de 40 piezas. Solo se muestran las primeras 40; use varias fotos.', 'advertencia');
        }

        piezas = separacion.piezas.slice(0, 40).map(function (encontrada, indice) {
            var anterior = anteriores.length === separacion.piezas.length ? anteriores[indice] : null;
            return {
                recorte: A.recortarPieza(imagenActual, mascara, separacion, encontrada, false),
                recorteRelleno: encontrada.tieneHueco ? A.recortarPieza(imagenActual, mascara, separacion, encontrada, true) : null,
                tieneHueco: encontrada.tieneHueco,
                rellenar: anterior ? anterior.rellenar && encontrada.tieneHueco : false,
                giros: anterior ? anterior.giros : 0,
                usar: anterior ? anterior.usar : false,
                desmarcadaAMano: anterior ? anterior.desmarcadaAMano : false,
                componente: anterior ? anterior.componente : '',
                tamano: anterior ? anterior.tamano : '',
                avisos: textoAvisos(encontrada) + (fondoBlanco && A.esPiezaClara(A.recortarPieza(imagenActual, mascara, separacion, encontrada, true))
                    ? (textoAvisos(encontrada) ? ' ' : '') + 'Es clara y la hoja es blanca: puede haberse borrado una parte. Tómele la foto sobre la hoja verde o azul.'
                    : ''),
                indiceServidor: -1
            };
        });
        if (fondoBlanco && piezas.some(function (pieza) { return pieza.avisos.indexOf('la hoja es blanca') >= 0; })) {
            agregarAviso('La hoja es blanca y hay piezas claras. Revise las marcadas: si se ven cortadas, tómeles la foto sobre la hoja verde o azul.', 'advertencia');
        }
        errorPiezas.hidden = true;
        mostrarTarjetas();
    }

    function cargarArchivo(archivo) {
        errorArchivo('');
        if (!/^image\/(jpeg|png|webp)$/.test(archivo.type)) {
            errorArchivo(MENSAJES.archivo);
            return;
        }
        if (archivo.size > PESO_MAXIMO) {
            errorArchivo(MENSAJES.pesado);
            return;
        }
        var direccion = URL.createObjectURL(archivo);
        var imagen = new Image();
        imagen.onload = function () {
            URL.revokeObjectURL(direccion);
            var escala = A.escalaParaLado(imagen.naturalWidth, imagen.naturalHeight);
            lienzoOriginal.width = Math.max(1, Math.round(imagen.naturalWidth * escala));
            lienzoOriginal.height = Math.max(1, Math.round(imagen.naturalHeight * escala));
            var contexto = lienzoOriginal.getContext('2d', { willReadFrequently: true });
            contexto.drawImage(imagen, 0, 0, lienzoOriginal.width, lienzoOriginal.height);
            var pixeles = contexto.getImageData(0, 0, lienzoOriginal.width, lienzoOriginal.height);
            imagenActual = { ancho: pixeles.width, alto: pixeles.height, datos: pixeles.data };
            seccionResultado.hidden = false;
            piezas = [];
            procesar();
            seccionResultado.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
        imagen.onerror = function () {
            URL.revokeObjectURL(direccion);
            errorArchivo(MENSAJES.noAbre);
        };
        imagen.src = direccion;
    }

    campoFoto.addEventListener('change', function () {
        if (campoFoto.files.length > 0) {
            cargarArchivo(campoFoto.files[0]);
        }
        campoFoto.value = '';
    });

    tolerancia.addEventListener('input', function () {
        toleranciaValor.textContent = tolerancia.value;
        cancelAnimationFrame(pendiente);
        pendiente = requestAnimationFrame(procesar);
    });

    formulario.addEventListener('submit', function (evento) {
        var marcadas = piezas.filter(function (pieza) { return pieza.usar; });
        var detener = false;
        if (marcadas.length === 0) {
            errorPiezas.textContent = MENSAJES.ninguna;
            errorPiezas.hidden = false;
            detener = true;
        }
        if (revisarRepetidos()) {
            detener = true;
        }
        if (detener) {
            evento.preventDefault();
        }
        if (evento.defaultPrevented && marcadas.length > 0) {
            errorPiezas.textContent = 'Corrija o desmarque las piezas en rojo; así se guardan las demás.';
            errorPiezas.hidden = false;
        }
    });

    if (datos.piezas.length > 0) {
        var cargadas = 0;
        var porCargar = datos.piezas.filter(function (pieza) { return pieza.imagen !== ''; });
        piezas = [];
        porCargar.forEach(function (enviada, posicion) {
            var imagen = new Image();
            imagen.onload = function () {
                var recorte = recorteDesdeImagen(imagen);
                piezas[posicion] = {
                    recorte: recorte,
                    recorteRelleno: null,
                    tieneHueco: false,
                    rellenar: false,
                    giros: 0,
                    usar: enviada.usar,
                    componente: enviada.componente,
                    tamano: enviada.tamano,
                    avisos: enviada.avisos,
                    indiceServidor: enviada.indice
                };
                cargadas++;
                if (cargadas === porCargar.length) {
                    mostrarTarjetas();
                }
            };
            imagen.src = enviada.imagen;
        });
    }
});