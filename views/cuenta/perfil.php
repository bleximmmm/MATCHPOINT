<?php /** Variables: $d, $errDatos, $errPass */
include ROOT_PATH . '/views/cuenta/_menu.php';
$campoD = function ($nombre, $etiqueta, $extra = '', $tipo = 'text') use ($d, $errDatos) {
    $valor = $d[$nombre]; $errores = $errDatos;
    include ROOT_PATH . '/views/partials/campo.php';
};
$campoP = function ($nombre, $etiqueta, $extra = '') use ($errPass) {
    $valor = ''; $errores = $errPass; $tipo = 'password';
    include ROOT_PATH . '/views/partials/campo.php';
};
?>
<section class="seccion seccion--clara">
    <div class="contenedor dos-cajas">
        <form class="formulario formulario--caja" action="<?= e(url('cuenta')) ?>" method="post" novalidate>
            <?= csrf_campo() ?><input type="hidden" name="accion" value="datos">
            <h2>Datos personales</h2>
            <div class="campos-2">
                <?php $campoD('nombre', 'Nombre', 'required maxlength="60"'); ?>
                <?php $campoD('apellido', 'Apellido', 'required maxlength="60"'); ?>
            </div>
            <?php $campoD('email', 'Correo electrónico', 'required maxlength="120"', 'email'); ?>
            <?php $campoD('telefono', 'Teléfono', 'maxlength="30" inputmode="tel"', 'tel'); ?>
            <button class="btn btn--oscuro" type="submit" data-cargando>Guardar cambios</button>
        </form>

        <form class="formulario formulario--caja" action="<?= e(url('cuenta')) ?>" method="post" novalidate>
            <?= csrf_campo() ?><input type="hidden" name="accion" value="password">
            <h2>Cambiar contraseña</h2>
            <?php $campoP('actual', 'Contraseña actual', 'required autocomplete="current-password"'); ?>
            <?php $campoP('nueva', 'Nueva contraseña', 'required minlength="8" maxlength="72" autocomplete="new-password"'); ?>
            <?php $campoP('repetir', 'Repetir nueva contraseña', 'required autocomplete="new-password"'); ?>
            <button class="btn btn--oscuro" type="submit" data-cargando>Actualizar contraseña</button>
        </form>
    </div>
</section>
