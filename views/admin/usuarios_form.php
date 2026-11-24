<?php /** Variables: $id, $d, $rol, $activo, $errores */
$t = function ($nombre, $etiqueta, $extra = '', $tipo = 'text', $ayuda = '') use ($d, $errores) {
    $valor = $d[$nombre]; include ROOT_PATH . '/views/partials/campo.php';
};
?>
<form class="formulario formulario--caja formulario--ancho" action="<?= e($id ? aurl('usuarios', ['a' => 'editar', 'id' => $id]) : aurl('usuarios', ['a' => 'nuevo'])) ?>" method="post" novalidate>
    <?= csrf_campo() ?>
    <div class="campos-2">
        <?php $t('nombre', 'Nombre', 'required maxlength="60"'); ?>
        <?php $t('apellido', 'Apellido', 'required maxlength="60"'); ?>
    </div>
    <?php $t('email', 'Correo electrónico', 'required maxlength="120"', 'email'); ?>
    <?php $t('telefono', 'Teléfono', 'maxlength="30"', 'tel'); ?>
    <?php $t('password', $id ? 'Nueva contraseña' : 'Contraseña', ($id ? '' : 'required ') . 'minlength="8" maxlength="72" autocomplete="new-password"', 'password', $id ? 'Dejala vacía para no cambiarla.' : 'Mínimo 8 caracteres, con letras y números.'); ?>
    <div class="campo<?= isset($errores['rol']) ? ' campo--error' : '' ?>">
        <label for="rol">Rol (permisos)</label>
        <select id="rol" name="rol">
            <?php foreach (Usuario::ROLES as $k => $tx): ?><option value="<?= e($k) ?>"<?= $rol === $k ? ' selected' : '' ?>><?= e($tx) ?></option><?php endforeach; ?>
        </select>
        <p class="campo__ayuda">Administrador: acceso total. Operador: productos, stock, pedidos y reportes, sin eliminar ni gestionar usuarios. Cliente: solo la tienda.</p>
        <?php if (isset($errores['rol'])): ?><p class="campo-error"><?= e($errores['rol']) ?></p><?php endif; ?>
    </div>
    <label class="check"><input type="checkbox" name="activo" value="1"<?= $activo ? ' checked' : '' ?>><span>Cuenta activa</span></label>
    <div class="formulario__acciones">
        <button class="btn btn--verde btn--grande" type="submit" data-cargando>Guardar usuario</button>
        <a class="btn btn--linea" href="<?= e(aurl('usuarios')) ?>">Cancelar</a>
    </div>
</form>
