<?php
/** Detalle de producto. Variables: $p, $imagenes, $talles, $relacionados, $esFav, $favs */
$caracteristicas = array_filter(array_map('trim', explode("\n", (string) $p['caracteristicas'])));
$hayStock = (int) $p['stock_actual'] > 0;
$stockTotal = (int) $p['stock_actual'];
$estadoStock = $stockTotal < 1 ? ['agotado', 'Sin stock'] : ($stockTotal <= 5 ? ['poco', 'Últimas unidades'] : ['ok', 'Disponible']);
?>
<section class="seccion seccion--clara seccion--producto">
    <div class="contenedor">
        <nav class="migas" aria-label="Ubicación">
            <a href="<?= e(url('inicio')) ?>">Inicio</a>
            <a href="<?= e(url('catalogo')) ?>">Catálogo</a>
            <a href="<?= e(url('catalogo', ['marca' => [$p['marca_slug']]])) ?>"><?= e($p['marca']) ?></a>
            <span aria-current="page"><?= e($p['nombre']) ?></span>
        </nav>

        <div class="producto">
            <div class="galeria" data-galeria>
                <div class="galeria__principal">
                    <?php if ((int) $p['descuento'] > 0): ?><span class="etiqueta etiqueta--oferta">Oferta -<?= (int) $p['descuento'] ?>%</span>
                    <?php elseif ((int) $p['nuevo']): ?><span class="etiqueta etiqueta--nuevo">Nuevo ingreso</span><?php endif; ?>
                    <img id="foto-principal" src="<?= e(img($imagenes ? $imagenes[0]['ruta'] : null)) ?>" alt="<?= e($p['marca'] . ' ' . $p['nombre']) ?>" width="860" height="560">
                </div>
                <?php if (count($imagenes) > 1): ?>
                    <ul class="galeria__miniaturas" aria-label="Más fotos del producto">
                        <?php foreach ($imagenes as $i => $im): ?>
                            <li>
                                <button type="button" data-foto="<?= e(img($im['ruta'])) ?>" class="<?= $i === 0 ? 'activa' : '' ?>" aria-label="Ver foto <?= $i + 1 ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                                    <img src="<?= e(img($im['ruta'])) ?>" alt="" loading="lazy" width="120" height="80">
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="producto__info">
                <div class="producto__cabecera">
                    <p class="producto__marca"><a href="<?= e(url('catalogo', ['marca' => [$p['marca_slug']]])) ?>"><?= e($p['marca']) ?></a></p>
                    <p class="disp disp--<?= e($estadoStock[0]) ?>"><?= e($estadoStock[1]) ?></p>
                </div>
                <h1 class="producto__nombre"><?= e($p['nombre']) ?></h1>
                <p class="precio precio--grande">
                    <strong><?= e(dinero($p['precio_final'])) ?></strong>
                    <?php if ((int) $p['descuento'] > 0): ?>
                        <s class="precio__antes"><span class="solo-lector">Precio anterior </span><?= e(dinero($p['precio_venta'])) ?></s>
                        <span class="precio__desc">-<?= (int) $p['descuento'] ?>%</span>
                    <?php endif; ?>
                </p>
                <?php if ($p['descripcion']): ?><p class="producto__desc"><?= nl2br(e($p['descripcion'])) ?></p><?php endif; ?>

                <form class="compra" id="form-compra" action="<?= e(url('carrito_agregar')) ?>" method="post" novalidate>
                    <?= csrf_campo() ?>
                    <input type="hidden" name="id_producto" value="<?= (int) $p['id'] ?>">

                    <fieldset class="talles">
                        <legend>Elegí tu talle <span class="talles__guia">(talles argentinos)</span></legend>
                        <details class="guia-talle">
                            <summary>¿Cómo elegir tu talle?</summary>
                            <p>Los talles se muestran en numeración argentina. El calce puede variar entre marcas y modelos; revisá las características del producto y el stock del talle elegido antes de agregarlo al carrito.</p>
                        </details>
                        <div class="talles__lista">
                            <?php foreach ($talles as $t): $sin = (int) $t['stock'] < 1; ?>
                                <label class="talle<?= $sin ? ' talle--agotado' : '' ?>">
                                    <input type="radio" name="id_talle" value="<?= (int) $t['id'] ?>" data-stock="<?= (int) $t['stock'] ?>"<?= $sin ? ' disabled' : '' ?>>
                                    <span><?= (int) $t['valor'] ?><?php if ($sin): ?><small class="solo-lector"> agotado</small><?php endif; ?></span>
                                    <?php if ($sin): ?><em class="talle__tag" aria-hidden="true">Agotado</em><?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="talles__stock" id="aviso-stock" aria-live="polite"></p>
                    </fieldset>

                    <div class="compra__fila">
                        <div class="cantidad" data-cantidad>
                            <label class="solo-lector" for="cantidad">Cantidad</label>
                            <button type="button" data-menos aria-label="Una unidad menos"><?= icono('menos') ?></button>
                            <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="<?= (int) MAX_CANTIDAD_POR_ITEM ?>" inputmode="numeric">
                            <button type="button" data-mas aria-label="Una unidad más"><?= icono('mas') ?></button>
                        </div>
                        <button class="btn btn--verde btn--grande btn--crece" type="submit"<?= $hayStock ? '' : ' disabled' ?>><?= $hayStock ? 'AGREGAR AL CARRITO' : 'SIN STOCK' ?></button>
                        <button class="fav-btn fav-btn--inline<?= $esFav ? ' es-fav' : '' ?>" type="button" data-fav="<?= (int) $p['id'] ?>" aria-pressed="<?= $esFav ? 'true' : 'false' ?>" aria-label="<?= $esFav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"><?= icono('corazon') ?></button>
                    </div>
                </form>

                <div class="producto__confianza">
                    <p><strong>Stock por talle</strong><span>La disponibilidad se verifica al agregar al carrito.</span></p>
                    <p><strong>Compra de demostración</strong><span>No se realiza ningún cobro ni envío real.</span></p>
                </div>

                <h2 class="producto__subtitulo">Características técnicas</h2>
                <dl class="ficha">
                    <div><dt>Marca</dt><dd><?= e($p['marca']) ?></dd></div>
                    <div><dt>Tipo de cancha</dt><dd><?= e($p['categoria']) ?></dd></div>
                    <?php if ($p['material']): ?><div><dt>Material</dt><dd><?= e($p['material']) ?></dd></div><?php endif; ?>
                    <?php if ($p['suela']): ?><div><dt>Tipo de suela</dt><dd><?= e($p['suela']) ?></dd></div><?php endif; ?>
                    <?php if ($p['color']): ?><div><dt>Color</dt><dd><?= e($p['color']) ?></dd></div><?php endif; ?>
                    <div><dt>Código</dt><dd><?= e($p['sku']) ?></dd></div>
                </dl>
                <?php if ($caracteristicas): ?>
                    <ul class="lista-carac">
                        <?php foreach ($caracteristicas as $c): ?><li><?= e($c) ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($relacionados): ?>
<section class="seccion">
    <div class="contenedor">
        <h2 class="seccion__titulo">Otros que te pueden gustar</h2>
        <div class="grilla">
            <?php foreach ($relacionados as $p) { include ROOT_PATH . '/views/partials/tarjeta.php'; } ?>
        </div>
    </div>
</section>
<?php endif; ?>
