<?php /** Carrito. Variables: $items, $total */ ?>
<section class="encabezado-pagina"><div class="contenedor"><h1>Tu carrito</h1></div></section>
<section class="seccion seccion--clara">
    <div class="contenedor">
    <?php if (!$items): ?>
        <div class="vacio vacio--caja">
            <h2>Tu carrito está vacío</h2>
            <p>Todavía no agregaste ningún par. Mirá el catálogo y elegí tu talle.</p>
            <a class="btn btn--oscuro" href="<?= e(url('catalogo')) ?>">Ir al catálogo</a>
        </div>
    <?php else: ?>
        <div class="carrito">
            <form class="carrito__lista" id="form-carrito" action="<?= e(url('carrito_actualizar')) ?>" method="post">
                <?= csrf_campo() ?>
                <ul>
                <?php foreach ($items as $it): ?>
                    <li class="item">
                        <a class="item__img" href="<?= e(url('producto', ['id' => $it['id']])) ?>"><img src="<?= e(img($it['imagen'])) ?>" alt="" loading="lazy" width="160" height="104"></a>
                        <div class="item__datos">
                            <p class="item__marca"><?= e($it['marca']) ?></p>
                            <h2 class="item__nombre"><a href="<?= e(url('producto', ['id' => $it['id']])) ?>"><?= e($it['nombre']) ?></a></h2>
                            <p class="item__talle">Talle <?= (int) $it['talle'] ?></p>
                            <p class="item__precio">
                                <?= e(dinero($it['precio'])) ?>
                                <?php if ($it['descuento'] > 0): ?><s><?= e(dinero($it['precio_lista'])) ?></s><?php endif; ?>
                                <span class="solo-lector">por unidad</span>
                            </p>
                        </div>
                        <div class="item__cantidad">
                            <div class="cantidad" data-cantidad>
                                <label class="solo-lector" for="c-<?= e($it['clave']) ?>">Cantidad de <?= e($it['nombre']) ?>, talle <?= (int) $it['talle'] ?></label>
                                <button type="button" data-menos aria-label="Una unidad menos"><?= icono('menos') ?></button>
                                <input type="number" id="c-<?= e($it['clave']) ?>" name="cantidad[<?= e($it['clave']) ?>]" value="<?= (int) $it['cantidad'] ?>" min="1" max="<?= (int) min($it['stock'], MAX_CANTIDAD_POR_ITEM) ?>" inputmode="numeric" data-autoenviar-cantidad>
                                <button type="button" data-mas aria-label="Una unidad más"><?= icono('mas') ?></button>
                            </div>
                            <?php if ($it['stock'] <= 5): ?><p class="item__stock">Quedan <?= (int) $it['stock'] ?></p><?php endif; ?>
                        </div>
                        <p class="item__subtotal"><?= e(dinero($it['subtotal'])) ?></p>
                        <button class="item__quitar" type="submit" name="clave" value="<?= e($it['clave']) ?>" formaction="<?= e(url('carrito_eliminar')) ?>" aria-label="Quitar <?= e($it['nombre']) ?> talle <?= (int) $it['talle'] ?> del carrito"><?= icono('basura') ?></button>
                    </li>
                <?php endforeach; ?>
                </ul>
                <noscript><button class="btn btn--linea" type="submit">Actualizar cantidades</button></noscript>
            </form>

            <aside class="resumen" aria-label="Resumen del pedido">
                <h2>Resumen</h2>
                <dl>
                    <div><dt>Productos</dt><dd><?= (int) Cart::contar() ?></dd></div>
                    <div><dt>Subtotal</dt><dd><?= e(dinero($total)) ?></dd></div>
                    <div class="resumen__total"><dt>Total</dt><dd><?= e(dinero($total)) ?></dd></div>
                </dl>
                <a class="btn btn--verde btn--grande btn--bloque" href="<?= e(url('checkout')) ?>">CONTINUAR COMPRA</a>
                <a class="btn btn--linea btn--bloque" href="<?= e(url('catalogo')) ?>">Seguir comprando</a>
            </aside>
        </div>
    <?php endif; ?>
    </div>
</section>
