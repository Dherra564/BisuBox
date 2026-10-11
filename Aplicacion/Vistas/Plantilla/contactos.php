<?php
/**
 * @var array $contactos
 * @var array $errores
 * @var array|null $contactosOriginales
 * @var bool|null $contactosSoloLectura
 */

use Aplicacion\Nucleo\TipoContacto;

$contactosSoloLectura = $contactosSoloLectura ?? false;

$filaContacto = function (string $tipoElegido, string $valor, ?string $error, bool $soloLectura = false): string {
    $opciones = '';
    foreach (TipoContacto::todos() as $codigo => $tipo) {
        $opciones .= '<option value="' . htmlspecialchars($codigo) . '"'
            . ($codigo === $tipoElegido ? ' selected' : '')
            . ($codigo === TipoContacto::WHATSAPP ? ' data-telefono' : '')
            . ' data-usuario="' . htmlspecialchars((string) $tipo['usuario']) . '"'
            . ' data-dominios="' . htmlspecialchars(implode(',', $tipo['dominios'])) . '"'
            . ' data-mensaje="' . htmlspecialchars($tipo['mensaje']) . '"'
            . ' data-ejemplo="' . htmlspecialchars($tipo['ejemplo']) . '">'
            . htmlspecialchars($tipo['nombre']) . '</option>';
    }
    $ejemplo = TipoContacto::existe($tipoElegido) ? TipoContacto::todos()[$tipoElegido]['ejemplo'] : '';

    return '<div class="campo filaContacto' . ($error !== null ? ' campoConError' : '') . '">'
        . '<select name="contactoTipo[]" aria-label="Tipo de contacto" data-contacto-tipo'
        . ($soloLectura ? ' disabled' : '') . '>' . $opciones . '</select>'
        . '<input type="text" name="contactoValor[]" aria-label="Contacto" maxlength="300" data-regla="contacto"'
        . ' placeholder="' . htmlspecialchars($ejemplo) . '" value="' . htmlspecialchars($valor) . '"'
        . ($soloLectura ? ' readonly' : '') . '>'
        . '<button type="button" class="botonQuitar" aria-label="Quitar contacto" data-contacto-quitar data-solo-edicion'
        . ($soloLectura ? ' hidden' : '') . '>'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>'
        . ($error !== null ? '<p class="errorCampo">' . htmlspecialchars($error) . '</p>' : '')
        . '</div>';
};
?>
<section class="formularioSeccion">
    <h2>Contactos de la tienda</h2>
    <p class="textoAyuda">
        Opcional. Agregue los números de WhatsApp y las redes que quiera. En las redes puede escribir el usuario
        o el enlace del perfil.
    </p>
    <?php if (isset($errores['contactos'])): ?>
        <p class="errorCampo"><?= htmlspecialchars($errores['contactos']) ?></p>
    <?php endif; ?>

    <div class="listaContactos" data-contactos data-maximo="<?= TipoContacto::MAXIMO_POR_TIENDA ?>"
        <?= isset($contactosOriginales) ? 'data-restaurar-desde="contactosOriginales"' : '' ?>>
        <?php foreach ($contactos as $indice => $contacto): ?>
            <?= $filaContacto($contacto['tipo'], $contacto['valor'], $errores['contacto' . $indice] ?? null, $contactosSoloLectura) ?>
        <?php endforeach; ?>
    </div>
    <p class="textoAyuda contactosVacio" data-contactos-vacio <?= $contactos !== [] ? 'hidden' : '' ?>>Todavía no hay
        contactos.</p>

    <template data-contacto-plantilla>
        <?= $filaContacto(TipoContacto::WHATSAPP, '', null) ?>
    </template>
    <?php if (isset($contactosOriginales)): ?>
        <template id="contactosOriginales">
            <?php foreach ($contactosOriginales as $contacto): ?>
                <?= $filaContacto($contacto['tipo'], $contacto['valor'], null, true) ?>
            <?php endforeach; ?>
        </template>
    <?php endif; ?>

    <div class="contactosPie">
        <button type="button" class="boton botonSecundario botonPequeno" data-contacto-agregar data-solo-edicion
            <?= $contactosSoloLectura ? 'hidden' : '' ?>>Agregar contacto</button>
        <span class="textoAyuda" data-contacto-cuenta>
            <?= count($contactos) ?> de <?= TipoContacto::MAXIMO_POR_TIENDA ?> contactos
        </span>
    </div>
</section>