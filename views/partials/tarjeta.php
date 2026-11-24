<?php
/** Tarjeta de producto. Variables: $p (fila de Producto), $favs (ids favoritos del usuario). */
$esFav = in_array((int) $p['id'], isset($favs) ? $favs : [], true);
$stock = (int) $p['stock'];
$enlace = url('producto', ['id' => $p['id']]);
if ($stock < 1) { $disp = ['agotado', 'Sin stock']; }
elseif ($stock <= 5) { $disp = ['poco', 'Últimas unidades']; }
else { $disp = ['ok', 'Disponible']; }
?>
<article class="tarjeta">
    <a class="tarjeta__img" href="<?= e($enlace) ?>" tabindex="-1" aria-hidden="true">
        <img class="tarjeta__foto" src="<?= e(img($p['img1'])) ?>" alt="" loading="lazy" width="430" height="280">
        <?php if ($p['img2']): ?><img class="tarjeta__foto tarjeta__foto--alt" src="<?= e(img($p['img2'])) ?>" alt="" loading="lazy" width="430" height="280"><?php endif; ?>
    </a>
    <?php if ((int) $p['descuento'] > 0): ?>
        <span class="etiqueta etiqueta--oferta">Oferta -<?= (int) $p['descuento'] ?>%</span>
    <?php elseif ((int) $p['nuevo']): ?>
        <span class="etiqueta etiqueta--nuevo">Nuevo ingreso</span>
    <?php endif; ?>
    <button class="fav-btn<?= $esFav ? ' es-fav' : '' ?>" type="button" data-fav="<?= (int) $p['id'] ?>" aria-pressed="<?= $esFav ? 'true' : 'false' ?>" aria-label="<?= $esFav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>: <?= e($p['nombre']) ?>">
        <?= icono('corazon') ?>
    </button>
    <div class="tarjeta__cuerpo">
        <p class="tarjeta__marca"><?= e($p['marca']) ?></p>
        <h3 class="tarjeta__nombre"><a href="<?= e($enlace) ?>"><?= e($p['nombre']) ?></a></h3>
        <p class="precio">
            <strong><?= e(dinero($p['precio_final'])) ?></strong>
            <?php if ((int) $p['descuento'] > 0): ?><s class="precio__antes"><span class="solo-lector">Precio anterior </span><?= e(dinero($p['precio_venta'])) ?></s><?php endif; ?>
        </p>
        <p class="disp disp--<?= $disp[0] ?>"><?= e($disp[1]) ?></p>
        <a class="btn btn--oscuro btn--bloque" href="<?= e($enlace) ?>">Ver producto</a>
    </div>
</article>
