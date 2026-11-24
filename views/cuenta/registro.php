<?php /** Variables: $d, $errores */
$campos = function ($nombre, $etiqueta, $extra = '', $tipo = 'text', $valor = null, $ayuda = '') use ($d, $errores) {
    if ($valor === null) { $valor = isset($d[$nombre]) ? $d[$nombre] : ''; }
    include ROOT_PATH . '/views/partials/campo.php';
};
?>
<section class="seccion seccion--clara seccion--auth">
    <div class="contenedor auth">
        <form class="formulario formulario--caja" action="<?= e(url('registro')) ?>" method="post" novalidate data-validar-registro>
            <?= csrf_campo() ?>
            <h1>Crear cuenta</h1>
            <div class="campos-2">
                <?php $campos('nombre', 'Nombre', 'required maxlength="60" autocomplete="given-name"'); ?>
                <?php $campos('apellido', 'Apellido', 'required maxlength="60" autocomplete="family-name"'); ?>
            </div>
            <?php $campos('email', 'Correo electrónico', 'required maxlength="120" autocomplete="email"', 'email'); ?>
            <?php $campos('telefono', 'Teléfono (opcional)', 'maxlength="30" autocomplete="tel" inputmode="tel"', 'tel'); ?>
            <?php $campos('password', 'Contraseña', 'required minlength="8" maxlength="72" autocomplete="new-password"', 'password', '', 'Mínimo 8 caracteres, con letras y números.'); ?>
            <?php $campos('password2', 'Repetir contraseña', 'required autocomplete="new-password"', 'password', ''); ?>
            <button class="btn btn--oscuro btn--grande btn--bloque" type="submit" data-cargando>Crear cuenta</button>
            <p class="auth__alt">¿Ya tenés cuenta? <a href="<?= e(url('login')) ?>">Iniciá sesión</a></p>
        </form>
    </div>
</section>
