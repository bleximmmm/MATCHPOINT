<?php /** Variables: $tipo, $c, $id, $d, $errores */ ?>
<form class="formulario formulario--caja formulario--ancho" action="<?= e($id ? aurl($tipo, ['a' => 'editar', 'id' => $id]) : aurl($tipo, ['a' => 'nuevo'])) ?>" method="post" novalidate>
    <?= csrf_campo() ?>
    <?php foreach ($c['campos'] as $campo => $cfg): ?>
        <div class="campo<?= isset($errores[$campo]) ? ' campo--error' : '' ?>">
            <label for="f-<?= e($campo) ?>"><?= e($cfg[0]) ?></label>
            <?php if ($cfg[1] === 'textarea'): ?>
                <textarea id="f-<?= e($campo) ?>" name="<?= e($campo) ?>" rows="3" maxlength="<?= (int) $cfg[2] ?>"><?= e($d[$campo]) ?></textarea>
            <?php else: ?>
                <input type="<?= e($cfg[1]) ?>" id="f-<?= e($campo) ?>" name="<?= e($campo) ?>" value="<?= e($d[$campo]) ?>" maxlength="<?= (int) $cfg[2] ?>"<?= $cfg[3] ? ' required' : '' ?>>
            <?php endif; ?>
            <?php if (isset($errores[$campo])): ?><p class="campo-error"><?= e($errores[$campo]) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="formulario__acciones">
        <button class="btn btn--verde btn--grande" type="submit" data-cargando>Guardar</button>
        <a class="btn btn--linea" href="<?= e(aurl($tipo)) ?>">Cancelar</a>
    </div>
</form>
