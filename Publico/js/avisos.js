document.addEventListener('DOMContentLoaded', function () {
    var url = document.body.getAttribute('data-avisos');
    if (!url) {
        return;
    }

    var CADA_CUANTO = 30000;
    var pantallaPequena = window.matchMedia('(max-width: 560px)');
    var enEspera = [];
    var iconos = {
        pedido: '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
        agotado: '<path d="M3 7l9-4 9 4-9 4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        bajo: '<path d="M3 7l9-4 9 4-9 4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        producto: '<path d="M6 4h12l3 5-9 11L3 9z"/><path d="M3 9h18"/><path d="M9 4l3 16 3-16"/>'
    };
    var textosNumero = {
        pedidos: 'por atender',
        productos: 'agotados',
        inventario: 'por acabarse o agotados'
    };
    var base = url.replace(/\/avisos$/, '');
    var temporizador = null;

    var contenedor = document.createElement('div');
    contenedor.className = 'avisosFlotantes';
    contenedor.setAttribute('aria-live', 'polite');
    document.body.appendChild(contenedor);

    function cerrar(aviso) {
        if (aviso.classList.contains('avisoFlotanteSaliendo')) {
            return;
        }
        aviso.classList.add('avisoFlotanteSaliendo');
        setTimeout(function () {
            aviso.remove();
            sacarDeEspera();
        }, 300);
    }

    function caben() {
        return pantallaPequena.matches ? 2 : 4;
    }

    function sacarDeEspera() {
        while (enEspera.length > 0 && contenedor.children.length < caben()) {
            mostrar(enEspera.shift());
        }
    }

    function encolar(datos) {
        enEspera.push(datos);
        sacarDeEspera();
    }

    function mostrar(datos) {
        var aviso = document.createElement('div');
        aviso.className = 'avisoFlotante avisoFlotante' + datos.tipo.charAt(0).toUpperCase() + datos.tipo.slice(1);
        aviso.setAttribute('role', 'status');

        var icono = document.createElement('span');
        icono.className = 'avisoFlotanteIcono';
        icono.setAttribute('aria-hidden', 'true');
        icono.innerHTML = '<svg viewBox="0 0 24 24">' + (iconos[datos.tipo] || iconos.pedido) + '</svg>';

        var cuerpo = document.createElement('div');
        cuerpo.className = 'avisoFlotanteCuerpo';
        var titulo = document.createElement('strong');
        titulo.textContent = datos.titulo;
        var texto = document.createElement('p');
        texto.textContent = datos.texto;
        var enlace = document.createElement('a');
        enlace.href = base + datos.ruta;
        enlace.textContent = 'Ver';
        cuerpo.appendChild(titulo);
        cuerpo.appendChild(texto);
        cuerpo.appendChild(enlace);

        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'avisoFlotanteCerrar';
        boton.setAttribute('aria-label', 'Cerrar aviso');
        boton.textContent = '×';
        boton.addEventListener('click', function () {
            cerrar(aviso);
        });

        var tiempo = document.createElement('span');
        tiempo.className = 'avisoFlotanteTiempo';
        tiempo.setAttribute('aria-hidden', 'true');
        tiempo.addEventListener('animationend', function () {
            cerrar(aviso);
        });

        aviso.appendChild(icono);
        aviso.appendChild(cuerpo);
        aviso.appendChild(boton);
        aviso.appendChild(tiempo);
        contenedor.appendChild(aviso);
    }

    function actualizarNumero(clave, numero) {
        var enlace = document.querySelector('.menuSitio [data-menu="' + clave + '"]');
        if (!enlace) {
            return;
        }
        var circulo = enlace.querySelector('.numeroMenu');
        if (numero > 0) {
            if (!circulo) {
                circulo = document.createElement('span');
                circulo.className = 'numeroMenu numeroMenuAviso';
                enlace.appendChild(circulo);
            }
            circulo.textContent = String(numero);
            circulo.setAttribute('aria-label', numero + ' ' + (textosNumero[clave] || 'por atender'));
        } else if (circulo) {
            circulo.remove();
        }
    }

    function detener() {
        if (temporizador !== null) {
            clearInterval(temporizador);
            temporizador = null;
        }
    }

    function revisar() {
        if (document.hidden || temporizador === null) {
            return;
        }
        fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (respuesta) {
                return respuesta.ok ? respuesta.json() : null;
            })
            .then(function (datos) {
                if (!datos) {
                    return;
                }
                if (!datos.sesion) {
                    detener();
                    return;
                }
                (datos.avisos || []).forEach(encolar);
                if (datos.numeros) {
                    Object.keys(datos.numeros).forEach(function (clave) {
                        actualizarNumero(clave, datos.numeros[clave]);
                    });
                }
            })
            .catch(function () {
            });
    }

    temporizador = setInterval(revisar, CADA_CUANTO);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            revisar();
        }
    });
    revisar();
});