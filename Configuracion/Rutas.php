<?php



use Aplicacion\Controladores\FotoControlador;
use Aplicacion\Controladores\InicioControlador;
use Aplicacion\Controladores\PerfilControlador;
use Aplicacion\Nucleo\Enrutador;

return function (Enrutador $enrutador): void {

    // Inicio (Damian lo completa con el panel segun el rol)
    $enrutador->get('/', [InicioControlador::class, 'panel']);

    // Prueba temporal de mensajes. Se borra cuando la plantilla este revisada.
    $enrutador->get('/prueba/mensajes', [InicioControlador::class, 'probarMensajes']);

    // Autenticacion (Damian)

    // Mi perfil (Allison). Hay un solo SuperAdmin: sus datos se editan aqui.
    $enrutador->get('/perfil', [PerfilControlador::class, 'mostrar']);
    $enrutador->post('/perfil/actualizar', [PerfilControlador::class, 'actualizar']);
    $enrutador->get('/perfil/contrasena', [PerfilControlador::class, 'formularioContrasena']);
    $enrutador->post('/perfil/contrasena', [PerfilControlador::class, 'cambiarContrasena']);
    $enrutador->get('/fotos/perfil', [FotoControlador::class, 'mostrarPerfil']);

    // Vendedores (Mariana)

    // Sesiones (Damian)
};