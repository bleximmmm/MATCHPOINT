<?php
/**
 * Campo de formulario con etiqueta y mensaje de error.
 * Variables: $nombre, $etiqueta, $valor, $errores; opcionales: $tipo, $extra (atributos HTML), $ayuda
 */
$tipo = isset($tipo) ? $tipo : 'text';
$extra = isset($extra) ? $extra : '';
$idc = 'f-' . $nombre;
$tieneError = isset($errores[$nombre]);
?>
<div class="campo<?= $tieneError ? ' campo--error' : '' ?>">
    <label for="<?= e($idc) ?>"><?= e($etiqueta) ?></label>
    <input type="<?= e($tipo) ?>" id="<?= e($idc) ?>" name="<?= e($nombre) ?>" value="<?= e($valor) ?>" <?= $extra ?><?= $tieneError ? ' aria-invalid="true" aria-describedby="e-' . e($nombre) . '"' : '' ?>>
    <?php if (!empty($ayuda)): ?><p class="campo__ayuda"><?= e($ayuda) ?></p><?php endif; ?>
    <?php if ($tieneError): ?><p class="campo-error" id="e-<?= e($nombre) ?>"><?= e($errores[$nombre]) ?></p><?php endif; ?>
</div>
