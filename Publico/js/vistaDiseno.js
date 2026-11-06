var DibujoDiseno = (function () {

    var LADO = 560;
    var imagenes = {};

    function cargarImagen(url, alCargar) {
        if (imagenes[url]) {
            if (imagenes[url].complete) {
                return imagenes[url];
            }
            imagenes[url].addEventListener('load', alCargar);
            return imagenes[url];
        }
        var imagen = new Image();
        imagen.addEventListener('load', alCargar);
        imagen.src = url;
        imagenes[url] = imagen;
        return imagen;
    }

    function colorDe(variable, porDefecto) {
        var valor = getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
        return valor || porDefecto;
    }

    function prepararLienzo(lienzo) {
        var escala = Math.min(2, window.devicePixelRatio || 1);
        if (lienzo.width !== LADO * escala) {
            lienzo.width = LADO * escala;
            lienzo.height = LADO * escala;
        }
        var contexto = lienzo.getContext('2d');
        contexto.setTransform(escala, 0, 0, escala, 0, 0);
        contexto.clearRect(0, 0, LADO, LADO);
        return contexto;
    }

    function dibujar(lienzo, diseno, alTerminarCarga) {
        var A = AlgoritmosVista;
        var contexto = prepararLienzo(lienzo);
        var colorHilo = colorDe('--colorTextoSuave', '#63706b');
        var colorTexto = colorDe('--colorTextoSuave', '#63706b');
        var faltan = 0;
        var posiciones;

        contexto.lineWidth = 2;
        contexto.strokeStyle = colorHilo;
        contexto.fillStyle = colorTexto;
        contexto.textAlign = 'center';
        contexto.font = '600 18px Figtree, system-ui, sans-serif';

        if (diseno.forma === 'collar') {
            var curva = A.curvaCollar(LADO / 2, 40, 215, 440);
            contexto.beginPath();
            curva.puntos.forEach(function (punto, indice) {
                if (indice === 0) {
                    contexto.moveTo(punto[0], punto[1]);
                } else {
                    contexto.lineTo(punto[0], punto[1]);
                }
            });
            contexto.stroke();
            contexto.fillStyle = colorHilo;
            [curva.puntos[0], curva.puntos[curva.puntos.length - 1]].forEach(function (punto) {
                contexto.beginPath();
                contexto.arc(punto[0], punto[1], 5, 0, Math.PI * 2);
                contexto.fill();
            });
            contexto.fillStyle = colorTexto;
            if (diseno.texto) {
                contexto.fillText(diseno.texto, LADO / 2, 210);
            }
            posiciones = A.acomodarCollar(diseno.lista, diseno.catalogo, diseno.largo, curva);
        } else {
            var radio = 195;
            contexto.beginPath();
            contexto.arc(LADO / 2, LADO / 2, radio, 0, Math.PI * 2);
            contexto.stroke();
            if (diseno.texto) {
                contexto.fillText(diseno.texto, LADO / 2, LADO / 2 + 6);
            }
            posiciones = A.acomodarCirculo(diseno.lista, diseno.catalogo, diseno.largo, LADO / 2, LADO / 2, radio);
        }

        posiciones.forEach(function (posicion) {
            var pieza = diseno.catalogo[posicion.id];
            var imagen = cargarImagen(pieza.foto, alTerminarCarga || function () {});
            if (!imagen.complete || imagen.naturalWidth === 0) {
                faltan++;
                return;
            }
            var ancho = posicion.anchoPx;
            var alto = ancho * imagen.naturalHeight / imagen.naturalWidth;
            contexto.save();
            contexto.translate(posicion.x, posicion.y);
            contexto.rotate(posicion.giro);
            contexto.drawImage(imagen, -ancho / 2, -alto / 2, ancho, alto);
            contexto.restore();
        });
        return faltan;
    }

    function dibujarGuardados() {
        document.querySelectorAll('canvas[data-diseno]').forEach(function (lienzo) {
            var datos;
            try {
                datos = JSON.parse(lienzo.getAttribute('data-diseno'));
            } catch (error) {
                return;
            }
            var redibujar = function () {
                dibujar(lienzo, datos);
            };
            dibujar(lienzo, datos, redibujar);
        });
    }

    document.addEventListener('DOMContentLoaded', dibujarGuardados);

    return { dibujar: dibujar, LADO: LADO };
})();
