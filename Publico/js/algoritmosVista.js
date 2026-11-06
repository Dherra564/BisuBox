var AlgoritmosVista = (function () {

    var LADO_MAXIMO = 1000;
    var LIMITE_BORDE_DISTINTO = 0.15;
    var MINIMO_HUECO = 20;
    var PROPORCION_HUECO = 0.005;
    var PROPORCION_HUECO_GRANDE = 0.3;
    var ALFA_ORILLA = 170;

    function mediana(valores) {
        var ordenados = Float64Array.from(valores).sort();
        var mitad = ordenados.length >> 1;
        return ordenados.length % 2 ? ordenados[mitad] : (ordenados[mitad - 1] + ordenados[mitad]) / 2;
    }

    function indicesBorde(ancho, alto) {
        var indices = [];
        for (var x = 0; x < ancho; x++) {
            indices.push(x);
            if (alto > 1) {
                indices.push((alto - 1) * ancho + x);
            }
        }
        for (var y = 1; y < alto - 1; y++) {
            indices.push(y * ancho);
            if (ancho > 1) {
                indices.push(y * ancho + ancho - 1);
            }
        }
        return indices;
    }

    function escalaParaLado(ancho, alto) {
        return Math.min(1, LADO_MAXIMO / Math.max(ancho, alto));
    }

    function colorDeFondo(imagen, tolerancia) {
        var datos = imagen.datos;
        var borde = indicesBorde(imagen.ancho, imagen.alto);
        var rojos = [], verdes = [], azules = [];
        borde.forEach(function (p) {
            rojos.push(datos[p * 4]);
            verdes.push(datos[p * 4 + 1]);
            azules.push(datos[p * 4 + 2]);
        });
        var color = { r: mediana(rojos), g: mediana(verdes), b: mediana(azules) };
        var limite = tolerancia * tolerancia;
        var distintos = 0;
        borde.forEach(function (p) {
            var dr = datos[p * 4] - color.r, dg = datos[p * 4 + 1] - color.g, db = datos[p * 4 + 2] - color.b;
            if (dr * dr + dg * dg + db * db > limite) {
                distintos++;
            }
        });
        var proporcion = borde.length ? distintos / borde.length : 0;
        return { color: color, proporcionDistinta: proporcion, parejo: proporcion <= LIMITE_BORDE_DISTINTO };
    }

    function esColorClaro(r, g, b) {
        var mayor = Math.max(r, g, b), menor = Math.min(r, g, b);
        return mayor >= 185 && (mayor - menor) <= 40;
    }

    function esPiezaClara(recorte) {
        var claros = 0, total = 0;
        for (var i = 0; i < recorte.datos.length; i += 4) {
            if (recorte.datos[i + 3] < 200) {
                continue;
            }
            total++;
            if (esColorClaro(recorte.datos[i], recorte.datos[i + 1], recorte.datos[i + 2])) {
                claros++;
            }
        }
        return total > 0 && claros / total >= 0.4;
    }

    function buscarPiezasBorradas(imagen, tolerancia, separacion) {
        var estricta = quitarFondo(imagen, 10, true);
        var finas = separarPiezas(estricta).piezas;
        var limiteArea = imagen.ancho * imagen.alto * 0.25;
        return finas.filter(function (fina) {
            if (fina.tocaBorde || fina.area > limiteArea || fina.area / (fina.ancho * fina.alto) < 0.45) {
                return false;
            }
            return !separacion.piezas.some(function (pieza) {
                return fina.x0 <= pieza.x1 && fina.x1 >= pieza.x0 && fina.y0 <= pieza.y1 && fina.y1 >= pieza.y0;
            });
        }).map(function (fina) {
            return { x0: fina.x0, y0: fina.y0, x1: fina.x1, y1: fina.y1, area: fina.area };
        });
    }

    function crearComparador(color, tolerancia, sinSombras) {
        var limite = tolerancia * tolerancia;
        var sumaFondo = color.r + color.g + color.b || 1;
        var tonoRojo = color.r / sumaFondo, tonoVerde = color.g / sumaFondo;
        var brilloFondo = sumaFondo / 3;
        var limiteTono = 0.035 + tolerancia / 2500;
        return function (datos, i) {
            var r = datos[i], g = datos[i + 1], b = datos[i + 2];
            var dr = r - color.r, dg = g - color.g, db = b - color.b;
            if (dr * dr + dg * dg + db * db <= limite) {
                return 1;
            }
            var suma = r + g + b;
            var brillo = suma / 3;
            if (sinSombras || suma === 0 || brillo < brilloFondo * 0.3 || brillo > brilloFondo * 1.15) {
                return 0;
            }
            var tr = r / suma - tonoRojo, tg = g / suma - tonoVerde;
            return tr * tr + tg * tg <= limiteTono * limiteTono ? 2 : 0;
        };
    }

    function quitarFondo(imagen, tolerancia, sinSombras) {
        var ancho = imagen.ancho, alto = imagen.alto, total = ancho * alto, datos = imagen.datos;
        var analisis = colorDeFondo(imagen, tolerancia);
        var parece = crearComparador(analisis.color, tolerancia, sinSombras === true);
        var pareceFondo = new Uint8Array(total);
        var sombra = new Uint8Array(total);
        for (var p = 0; p < total; p++) {
            var resultado = parece(datos, p * 4);
            pareceFondo[p] = resultado > 0 ? 1 : 0;
            sombra[p] = resultado === 2 ? 1 : 0;
        }

        var fondo = new Uint8Array(total);
        var cola = new Int32Array(total);
        var inicio = 0, fin = 0;
        function sembrar(q) {
            if (pareceFondo[q] && !fondo[q]) {
                fondo[q] = 1;
                cola[fin++] = q;
            }
        }
        indicesBorde(ancho, alto).forEach(sembrar);
        while (inicio < fin) {
            var actual = cola[inicio++], x = actual % ancho;
            if (x > 0) sembrar(actual - 1);
            if (x < ancho - 1) sembrar(actual + 1);
            if (actual >= ancho) sembrar(actual - ancho);
            if (actual < total - ancho) sembrar(actual + ancho);
        }

        return {
            ancho: ancho,
            alto: alto,
            fondo: fondo,
            pareceFondo: pareceFondo,
            sombra: sombra,
            color: analisis.color,
            fondoParejo: analisis.parejo,
            proporcionBordeDistinta: analisis.proporcionDistinta
        };
    }

    function separarPiezas(mascara, areaMinima) {
        var ancho = mascara.ancho, alto = mascara.alto, total = ancho * alto;
        var fondo = mascara.fondo, pareceFondo = mascara.pareceFondo;
        var minimo = areaMinima || Math.max(80, Math.round(total * 0.0003));
        var etiqueta = new Int32Array(total);
        var cola = new Int32Array(total);
        var regiones = [];
        var descartadas = 0;

        for (var p = 0; p < total; p++) {
            if (fondo[p] || etiqueta[p]) {
                continue;
            }
            var id = regiones.length + 1;
            var region = { id: id, x0: ancho, y0: alto, x1: 0, y1: 0, area: 0 };
            regiones.push(region);
            var inicio = 0, fin = 0;
            cola[fin++] = p;
            etiqueta[p] = id;
            while (inicio < fin) {
                var q = cola[inicio++], x = q % ancho, y = (q - x) / ancho;
                region.area++;
                if (x < region.x0) region.x0 = x;
                if (x > region.x1) region.x1 = x;
                if (y < region.y0) region.y0 = y;
                if (y > region.y1) region.y1 = y;
                for (var dy = -1; dy <= 1; dy++) {
                    var ny = y + dy;
                    if (ny < 0 || ny >= alto) continue;
                    for (var dx = -1; dx <= 1; dx++) {
                        var nx = x + dx;
                        if ((dx === 0 && dy === 0) || nx < 0 || nx >= ancho) continue;
                        var vecino = ny * ancho + nx;
                        if (!fondo[vecino] && !etiqueta[vecino]) {
                            etiqueta[vecino] = id;
                            cola[fin++] = vecino;
                        }
                    }
                }
            }
        }

        var hueco = new Int32Array(total);
        var huecos = [];
        for (var h = 0; h < total; h++) {
            if (!pareceFondo[h] || fondo[h] || hueco[h]) {
                continue;
            }
            var idHueco = huecos.length + 1;
            var datosHueco = { id: idHueco, region: etiqueta[h], area: 0 };
            huecos.push(datosHueco);
            var ini = 0, fi = 0;
            cola[fi++] = h;
            hueco[h] = idHueco;
            while (ini < fi) {
                var a = cola[ini++], ax = a % ancho;
                datosHueco.area++;
                var vecinos = [ax > 0 ? a - 1 : -1, ax < ancho - 1 ? a + 1 : -1, a >= ancho ? a - ancho : -1, a < total - ancho ? a + ancho : -1];
                for (var v = 0; v < 4; v++) {
                    var n = vecinos[v];
                    if (n >= 0 && pareceFondo[n] && !fondo[n] && !hueco[n]) {
                        hueco[n] = idHueco;
                        cola[fi++] = n;
                    }
                }
            }
        }

        var huecosPorRegion = {};
        huecos.forEach(function (datosHueco) {
            var region = regiones[datosHueco.region - 1];
            if (datosHueco.area >= Math.max(MINIMO_HUECO, region.area * PROPORCION_HUECO)) {
                datosHueco.valido = true;
                huecosPorRegion[region.id] = (huecosPorRegion[region.id] || 0) + datosHueco.area;
            }
        });

        var piezas = [];
        regiones.forEach(function (region) {
            if (region.area < minimo) {
                descartadas++;
                return;
            }
            var areaHuecos = huecosPorRegion[region.id] || 0;
            piezas.push({
                id: region.id,
                x0: region.x0, y0: region.y0, x1: region.x1, y1: region.y1,
                ancho: region.x1 - region.x0 + 1,
                alto: region.y1 - region.y0 + 1,
                area: region.area,
                areaHuecos: areaHuecos,
                tieneHueco: areaHuecos > 0,
                huecoGrande: areaHuecos > region.area * PROPORCION_HUECO_GRANDE,
                tocaBorde: region.x0 === 0 || region.y0 === 0 || region.x1 === ancho - 1 || region.y1 === alto - 1,
                posiblesPegadas: false
            });
        });

        piezas.forEach(function (pieza) {
            pieza.posiblesPegadas = tieneCintura(etiqueta, ancho, pieza);
        });

        var zonasSospechosas = buscarSombrasSueltas(mascara, etiqueta, cola, minimo);

        piezas = ordenarPorFilas(piezas);

        return {
            piezas: piezas,
            descartadas: descartadas,
            zonasSospechosas: zonasSospechosas,
            etiqueta: etiqueta,
            hueco: hueco,
            huecos: huecos
        };
    }

    function ordenarPorFilas(piezas) {
        var porAltura = piezas.slice().sort(function (a, b) { return (a.y0 + a.y1) - (b.y0 + b.y1); });
        var filas = [];
        porAltura.forEach(function (pieza) {
            var fila = filas[filas.length - 1];
            var centro = (pieza.y0 + pieza.y1) / 2;
            if (fila && centro <= fila.abajo) {
                fila.piezas.push(pieza);
                fila.abajo = Math.max(fila.abajo, pieza.y1);
            } else {
                filas.push({ abajo: pieza.y1, piezas: [pieza] });
            }
        });
        var ordenadas = [];
        filas.forEach(function (fila) {
            fila.piezas.sort(function (a, b) { return a.x0 - b.x0; });
            ordenadas = ordenadas.concat(fila.piezas);
        });
        return ordenadas;
    }

    function perfilGrosor(etiqueta, ancho, pieza, porColumnas) {
        var perfil = [];
        var desde = porColumnas ? pieza.x0 : pieza.y0, hasta = porColumnas ? pieza.x1 : pieza.y1;
        for (var k = desde; k <= hasta; k++) {
            var cuenta = 0;
            var otroDesde = porColumnas ? pieza.y0 : pieza.x0, otroHasta = porColumnas ? pieza.y1 : pieza.x1;
            for (var j = otroDesde; j <= otroHasta; j++) {
                var p = porColumnas ? j * ancho + k : k * ancho + j;
                if (etiqueta[p] === pieza.id) {
                    cuenta++;
                }
            }
            perfil.push(cuenta);
        }
        return perfil;
    }

    function tieneCintura(etiqueta, ancho, pieza) {
        var porColumnas = pieza.ancho >= pieza.alto;
        var largo = porColumnas ? pieza.ancho : pieza.alto;
        if (largo < 12) {
            return false;
        }
        var perfil = perfilGrosor(etiqueta, ancho, pieza, porColumnas);
        var maximo = Math.max.apply(null, perfil);
        var margen = Math.max(2, Math.round(largo * 0.15));
        var maximoIzquierda = 0;
        var maximosDerecha = new Array(perfil.length).fill(0);
        for (var d = perfil.length - 1, actual = 0; d >= 0; d--) {
            actual = Math.max(actual, perfil[d]);
            maximosDerecha[d] = actual;
        }
        for (var i = 0; i < perfil.length; i++) {
            maximoIzquierda = Math.max(maximoIzquierda, perfil[i]);
            if (i < margen || i > perfil.length - 1 - margen) {
                continue;
            }
            var menorLado = Math.min(maximoIzquierda, maximosDerecha[i]);
            if (menorLado >= maximo * 0.5 && perfil[i] < menorLado * 0.55) {
                return true;
            }
        }
        return false;
    }

    function buscarSombrasSueltas(mascara, etiqueta, cola, minimo) {
        var ancho = mascara.ancho, alto = mascara.alto, total = ancho * alto;
        var sombra = mascara.sombra, fondo = mascara.fondo;
        var visto = new Uint8Array(total);
        var zonas = [];
        for (var p = 0; p < total; p++) {
            if (!sombra[p] || !fondo[p] || visto[p]) {
                continue;
            }
            var zona = { x0: ancho, y0: alto, x1: 0, y1: 0, area: 0, tocaPieza: false };
            var inicio = 0, fin = 0;
            cola[fin++] = p;
            visto[p] = 1;
            while (inicio < fin) {
                var q = cola[inicio++], x = q % ancho, y = (q - x) / ancho;
                zona.area++;
                if (x < zona.x0) zona.x0 = x;
                if (x > zona.x1) zona.x1 = x;
                if (y < zona.y0) zona.y0 = y;
                if (y > zona.y1) zona.y1 = y;
                for (var dy = -1; dy <= 1; dy++) {
                    var ny = y + dy;
                    if (ny < 0 || ny >= alto) continue;
                    for (var dx = -1; dx <= 1; dx++) {
                        var nx = x + dx;
                        if (nx < 0 || nx >= ancho) continue;
                        var vecino = ny * ancho + nx;
                        if (etiqueta[vecino]) {
                            zona.tocaPieza = true;
                        } else if (sombra[vecino] && fondo[vecino] && !visto[vecino]) {
                            visto[vecino] = 1;
                            cola[fin++] = vecino;
                        }
                    }
                }
            }
            if (!zona.tocaPieza && zona.area >= minimo) {
                zonas.push({ x0: zona.x0, y0: zona.y0, x1: zona.x1, y1: zona.y1, area: zona.area });
            }
        }
        return zonas;
    }

    function recortarPieza(imagen, mascara, separacion, pieza, rellenarHuecos) {
        var margen = 2;
        var ancho = mascara.ancho, alto = mascara.alto;
        var x0 = Math.max(0, pieza.x0 - margen), y0 = Math.max(0, pieza.y0 - margen);
        var x1 = Math.min(ancho - 1, pieza.x1 + margen), y1 = Math.min(alto - 1, pieza.y1 + margen);
        var anchoRecorte = x1 - x0 + 1, altoRecorte = y1 - y0 + 1;
        var salida = new Uint8ClampedArray(anchoRecorte * altoRecorte * 4);
        var fondo = mascara.fondo, etiqueta = separacion.etiqueta, hueco = separacion.hueco, huecos = separacion.huecos;

        function esTransparente(p) {
            if (fondo[p] || etiqueta[p] !== pieza.id) {
                return true;
            }
            return !rellenarHuecos && hueco[p] > 0 && huecos[hueco[p] - 1].valido === true;
        }

        for (var y = y0; y <= y1; y++) {
            for (var x = x0; x <= x1; x++) {
                var p = y * ancho + x;
                var i = ((y - y0) * anchoRecorte + (x - x0)) * 4;
                if (esTransparente(p)) {
                    continue;
                }
                salida[i] = imagen.datos[p * 4];
                salida[i + 1] = imagen.datos[p * 4 + 1];
                salida[i + 2] = imagen.datos[p * 4 + 2];
                var orilla = (x > 0 && esTransparente(p - 1)) || (x < ancho - 1 && esTransparente(p + 1))
                    || (y > 0 && esTransparente(p - ancho)) || (y < alto - 1 && esTransparente(p + ancho));
                salida[i + 3] = orilla ? ALFA_ORILLA : 255;
            }
        }
        return { ancho: anchoRecorte, alto: altoRecorte, datos: salida };
    }

    function girar90(recorte) {
        var salida = new Uint8ClampedArray(recorte.datos.length);
        for (var y = 0; y < recorte.alto; y++) {
            for (var x = 0; x < recorte.ancho; x++) {
                var origen = (y * recorte.ancho + x) * 4;
                var nx = recorte.alto - 1 - y, ny = x;
                var destino = (ny * recorte.alto + nx) * 4;
                salida[destino] = recorte.datos[origen];
                salida[destino + 1] = recorte.datos[origen + 1];
                salida[destino + 2] = recorte.datos[origen + 2];
                salida[destino + 3] = recorte.datos[origen + 3];
            }
        }
        return { ancho: recorte.alto, alto: recorte.ancho, datos: salida };
    }

    function aDecimas(milimetros) {
        return Math.round(Number(milimetros) * 10);
    }

    function listaFinal(elegidas, simetrico) {
        if (!simetrico || elegidas.length === 0) {
            return elegidas.slice();
        }
        var centro = elegidas[0], lados = elegidas.slice(1);
        return lados.slice().reverse().concat([centro], lados);
    }

    function largoUsado(lista, catalogo) {
        return lista.reduce(function (suma, id) { return suma + catalogo[id].tamano; }, 0);
    }

    function contar(lista) {
        var cuentas = {};
        lista.forEach(function (id) { cuentas[id] = (cuentas[id] || 0) + 1; });
        return cuentas;
    }

    function revisarDiseno(elegidas, simetrico, largo, catalogo) {
        var lista = listaFinal(elegidas, simetrico);
        for (var i = 0; i < lista.length; i++) {
            if (!catalogo[lista[i]]) {
                return { tipo: 'noExiste', id: lista[i] };
            }
        }
        var usado = largoUsado(lista, catalogo);
        if (usado > largo) {
            return { tipo: 'espacio', usado: usado, largo: largo };
        }
        var cuentas = contar(lista);
        for (var id in cuentas) {
            if (cuentas[id] > catalogo[id].existencia) {
                return { tipo: 'existencia', id: Number(id), pedidas: cuentas[id], hay: catalogo[id].existencia };
            }
        }
        return null;
    }

    function repetirPatron(elegidas, simetrico, largo, catalogo, limite) {
        var base = simetrico ? elegidas.slice(1) : elegidas.slice();
        var resultado = elegidas.slice();
        if (base.length === 0) {
            return { elegidas: resultado, motivo: 'vacio', agregadas: 0 };
        }
        var maximo = limite || 500;
        for (var vuelta = 0; vuelta < maximo; vuelta++) {
            var siguiente = base[vuelta % base.length];
            var problema = revisarDiseno(resultado.concat([siguiente]), simetrico, largo, catalogo);
            if (problema) {
                var relleno = problema.tipo === 'espacio' ? completarFinal(resultado, base, simetrico, largo, catalogo) : null;
                return {
                    elegidas: relleno ? relleno.elegidas : resultado,
                    motivo: problema.tipo,
                    problema: problema,
                    agregadas: vuelta + (relleno ? relleno.agregadas : 0),
                    relleno: relleno ? relleno.id : null
                };
            }
            resultado.push(siguiente);
        }
        return { elegidas: resultado, motivo: 'limite', agregadas: maximo };
    }

    function completarFinal(elegidas, base, simetrico, largo, catalogo) {
        var opciones = base.filter(function (id, indice) { return base.indexOf(id) === indice; })
            .sort(function (a, b) { return catalogo[a].tamano - catalogo[b].tamano; });
        var resultado = elegidas.slice();
        var usada = null;
        var agregadas = 0;
        var siguio = true;
        while (siguio && agregadas < 500) {
            siguio = false;
            for (var i = 0; i < opciones.length; i++) {
                if (!revisarDiseno(resultado.concat([opciones[i]]), simetrico, largo, catalogo)) {
                    resultado.push(opciones[i]);
                    usada = opciones[i];
                    agregadas++;
                    siguio = true;
                    break;
                }
            }
        }
        return usada === null ? null : { elegidas: resultado, id: usada, agregadas: agregadas };
    }

    function recortarHastaQueSirva(elegidas, simetrico, largo, catalogo) {
        var resultado = elegidas.slice();
        while (resultado.length && revisarDiseno(resultado, simetrico, largo, catalogo)) {
            resultado.pop();
        }
        return resultado;
    }

    function largoParaCerrar(usado, minimo, maximo, paso, proporcion) {
        if (usado <= 0) {
            return null;
        }
        var largo = Math.max(minimo, Math.ceil(usado / paso) * paso);
        if (largo > maximo || usado < largo * proporcion) {
            return null;
        }
        return largo;
    }

    function acomodarCirculo(lista, catalogo, largo, centroX, centroY, radio) {
        var radioDecimas = largo / (2 * Math.PI);
        var usado = largoUsado(lista, catalogo);
        var angulo = Math.PI / 2 + (usado / radioDecimas) / 2;
        var acumulado = 0;
        return lista.map(function (id) {
            var tamano = catalogo[id].tamano;
            var theta = angulo - (acumulado + tamano / 2) / radioDecimas;
            acumulado += tamano;
            return {
                id: id,
                x: centroX + radio * Math.cos(theta),
                y: centroY + radio * Math.sin(theta),
                giro: theta - Math.PI / 2,
                anchoPx: tamano * radio / radioDecimas
            };
        });
    }

    function curvaCollar(centroX, arriba, mitadAncho, profundidad, cantidad) {
        var puntos = [], distancias = [0];
        var n = cantidad || 600;
        for (var k = 0; k <= n; k++) {
            var u = -1 + 2 * k / n;
            puntos.push([centroX + mitadAncho * u, arriba + profundidad * (1 - Math.pow(Math.abs(u), 1.9))]);
            if (k > 0) {
                var anterior = puntos[k - 1];
                distancias.push(distancias[k - 1] + Math.hypot(puntos[k][0] - anterior[0], puntos[k][1] - anterior[1]));
            }
        }
        return { puntos: puntos, distancias: distancias, largoPx: distancias[n] };
    }

    function puntoEnCurva(curva, distancia) {
        var d = curva.distancias, bajo = 0, alto = d.length - 1;
        var buscada = Math.max(0, Math.min(distancia, d[alto]));
        while (alto - bajo > 1) {
            var medio = (bajo + alto) >> 1;
            if (d[medio] < buscada) bajo = medio; else alto = medio;
        }
        var tramo = d[alto] - d[bajo] || 1;
        var t = (buscada - d[bajo]) / tramo;
        var a = curva.puntos[bajo], b = curva.puntos[alto];
        return { x: a[0] + (b[0] - a[0]) * t, y: a[1] + (b[1] - a[1]) * t, giro: Math.atan2(b[1] - a[1], b[0] - a[0]) };
    }

    function acomodarCollar(lista, catalogo, largo, curva) {
        var pixelesPorDecima = curva.largoPx / largo;
        var usado = largoUsado(lista, catalogo);
        var inicio = (curva.largoPx - usado * pixelesPorDecima) / 2;
        var acumulado = 0;
        return lista.map(function (id) {
            var tamano = catalogo[id].tamano;
            var punto = puntoEnCurva(curva, inicio + (acumulado + tamano / 2) * pixelesPorDecima);
            acumulado += tamano;
            return { id: id, x: punto.x, y: punto.y, giro: punto.giro, anchoPx: tamano * pixelesPorDecima };
        });
    }

    return {
        LADO_MAXIMO: LADO_MAXIMO,
        escalaParaLado: escalaParaLado,
        colorDeFondo: colorDeFondo,
        quitarFondo: quitarFondo,
        separarPiezas: separarPiezas,
        recortarPieza: recortarPieza,
        esColorClaro: esColorClaro,
        esPiezaClara: esPiezaClara,
        buscarPiezasBorradas: buscarPiezasBorradas,
        esHuecoValido: function (separacion, p) { return separacion.hueco[p] > 0 && separacion.huecos[separacion.hueco[p] - 1].valido === true; },
        girar90: girar90,
        aDecimas: aDecimas,
        listaFinal: listaFinal,
        largoUsado: largoUsado,
        contar: contar,
        revisarDiseno: revisarDiseno,
        repetirPatron: repetirPatron,
        recortarHastaQueSirva: recortarHastaQueSirva,
        largoParaCerrar: largoParaCerrar,
        acomodarCirculo: acomodarCirculo,
        curvaCollar: curvaCollar,
        puntoEnCurva: puntoEnCurva,
        acomodarCollar: acomodarCollar
    };
})();

if (typeof module !== 'undefined') {
    module.exports = AlgoritmosVista;
}
