<section class="seccion seccion--clara">
    <div class="contenedor vacio">
        <p class="vacio__codigo"><?= (int) $codigo ?></p>
        <h1><?= e($titulo) ?></h1>
        <?php if ($detalle): ?><p><?= e($detalle) ?></p><?php endif; ?>
        <a class="btn btn--oscuro" href="<?= e(url('inicio')) ?>">Volver al inicio</a>
    </div>
</section>
