document.addEventListener('DOMContentLoaded', function () {

    var raiz = document.documentElement;
    var botonMenu = document.getElementById('botonMenu');
    var menu = document.getElementById('menuSitio');
    var velo = document.getElementById('veloMenu');
    var esCelular = window.matchMedia('(max-width: 900px)');
    var CLAVE_MENU = 'bisuboxMenuCerrado';
    var barra = document.querySelector('.barraSitio');

    function medirBarra() {
        if (barra) {
            raiz.style.setProperty('--altoBarra', barra.offsetHeight + 'px');
        }
    }

    medirBarra();
    window.addEventListener('resize', medirBarra);

    function guardarPreferencia(cerrado) {
        try {
            localStorage.setItem(CLAVE_MENU, cerrado ? 'si' : 'no');
        } catch (error) {
            return;
        }
    }

    function menuVisible() {
        return esCelular.matches ? raiz.classList.contains('menuAbiertoCelular') : !raiz.classList.contains('menuCerrado');
    }

    function actualizarBoton() {
        if (!botonMenu) {
            return;
        }
        var visible = menuVisible();
        var texto = visible ? 'Cerrar menú' : 'Abrir menú';
        botonMenu.setAttribute('aria-expanded', visible ? 'true' : 'false');
        botonMenu.setAttribute('aria-label', texto);
        botonMenu.setAttribute('title', texto);
        if (velo) {
            velo.hidden = !(esCelular.matches && visible);
        }
    }

    function cerrarEnCelular() {
        raiz.classList.remove('menuAbiertoCelular');
        actualizarBoton();
    }

    if (botonMenu && menu) {
        botonMenu.addEventListener('click', function () {
            if (esCelular.matches) {
                raiz.classList.toggle('menuAbiertoCelular');
            } else {
                guardarPreferencia(raiz.classList.toggle('menuCerrado'));
            }
            actualizarBoton();
        });

        if (velo) {
            velo.addEventListener('click', cerrarEnCelular);
        }

        esCelular.addEventListener('change', function () {
            raiz.classList.remove('menuAbiertoCelular');
            actualizarBoton();
        });

        actualizarBoton();
    }

    var botonCuenta = document.getElementById('botonCuenta');
    var panelCuenta = document.getElementById('panelCuenta');

    function cerrarCuenta() {
        if (panelCuenta && !panelCuenta.hidden) {
            panelCuenta.hidden = true;
            botonCuenta.setAttribute('aria-expanded', 'false');
        }
    }

    if (botonCuenta && panelCuenta) {
        botonCuenta.addEventListener('click', function (evento) {
            evento.stopPropagation();
            panelCuenta.hidden = !panelCuenta.hidden;
            botonCuenta.setAttribute('aria-expanded', panelCuenta.hidden ? 'false' : 'true');
        });

        panelCuenta.addEventListener('click', function (evento) {
            evento.stopPropagation();
        });

        document.addEventListener('click', cerrarCuenta);
    }

    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape') {
            return;
        }
        if (panelCuenta && !panelCuenta.hidden) {
            cerrarCuenta();
            botonCuenta.focus();
        } else if (raiz.classList.contains('menuAbiertoCelular')) {
            cerrarEnCelular();
            botonMenu.focus();
        }
    });

    document.querySelectorAll('[data-copiar]').forEach(function (boton) {
        var texto = boton.querySelector('span');
        var original = texto ? texto.textContent : '';

        function avisar(mensaje) {
            if (!texto) {
                return;
            }
            texto.textContent = mensaje;
            setTimeout(function () {
                texto.textContent = original;
            }, 2000);
        }

        boton.addEventListener('click', function () {
            var enlace = boton.getAttribute('data-copiar');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(enlace).then(function () {
                    avisar('¡Copiado!');
                }, function () {
                    window.prompt('Copie el enlace de su tienda:', enlace);
                });
            } else {
                window.prompt('Copie el enlace de su tienda:', enlace);
            }
        });
    });
});