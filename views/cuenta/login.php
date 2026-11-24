<?php /** Variables: $email, $error */ ?>
<section class="seccion seccion--clara seccion--auth">
    <div class="contenedor auth">
        <form class="formulario formulario--caja" action="<?= e(url('login')) ?>" method="post" novalidate>
            <?= csrf_campo() ?>
            <h1>Iniciar sesión</h1>
            <?php if ($error): ?><div class="modal-pendiente" data-modal-pendiente data-modal-tipo="error" data-modal-titulo="No pudimos iniciar sesión"><?= e($error) ?></div><?php endif; ?>
            <div class="campo">
                <label for="f-email">Correo electrónico</label>
                <input type="email" id="f-email" name="email" value="<?= e($email) ?>" required maxlength="120" autocomplete="email" autofocus>
            </div>
            <div class="campo">
                <label for="f-password">Contraseña</label>
                <input type="password" id="f-password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn--oscuro btn--grande btn--bloque" type="submit" data-cargando>Ingresar</button>
            <p class="auth__alt">¿Todavía no tenés cuenta? <a href="<?= e(url('registro')) ?>">Registrate</a></p>
        </form>
    </div>
</section>
