<?php /** Portada. Variables: $accesos, $destacados, $oferta, $mayorDesc, $favs */ ?>
<section class="hero">
    <div class="contenedor hero__grid">
        <div class="hero__texto">
            <p class="hero__etiqueta">MATCHPOINT · BOTINES DE FÚTBOL</p>
            <h1 class="hero__titulo">DOMINÁ EL CAMPO.</h1>
            <p class="hero__sub">Tecnología, velocidad y precisión en cada movimiento.</p>
            <div class="hero__acciones">
                <a class="btn btn--verde btn--grande" href="<?= e(url('catalogo')) ?>">EXPLORAR COLECCIÓN</a>
                <a class="hero__enlace" href="#superficies">Elegir por superficie <span aria-hidden="true">↓</span></a>
            </div>
        </div>
        <a class="hero__imagen" href="<?= e(url('producto', ['id' => 5])) ?>">
            <img src="<?= e(asset('img/hero.png')) ?>" alt="Botín adidas Predator Elite negro con detalles verde lima" width="768" height="960" fetchpriority="high">
        </a>
    </div>
</section>

<section class="beneficios" aria-label="Información de la tienda">
    <div class="contenedor beneficios__grid">
        <div class="beneficio">
            <span class="beneficio__numero" aria-hidden="true">01</span>
            <div><strong>Elegí tu superficie</strong><span>Césped natural, sintético o fútbol sala.</span></div>
        </div>
        <div class="beneficio">
            <span class="beneficio__numero" aria-hidden="true">02</span>
            <div><strong>Encontrá tu talle</strong><span>Consultá disponibilidad en cada producto.</span></div>
        </div>
        <div class="beneficio">
            <span class="beneficio__numero" aria-hidden="true">03</span>
            <div><strong>Compra de demostración</strong><span>El sitio no realiza cobros ni envíos reales.</span></div>
        </div>
    </div>
</section>

<section class="seccion seccion--clara" id="superficies">
    <div class="contenedor">
        <h2 class="seccion__titulo">Elegí según dónde jugás</h2>
        <div class="accesos">
            <?php foreach ($accesos as $i => $a): ?>
                <a class="acceso acceso--<?= $i + 1 ?>" href="<?= e($a['url']) ?>">
                    <?php if ($a['prod']): ?><img src="<?= e(img($a['prod']['img1'])) ?>" alt="" loading="lazy"><?php endif; ?>
                    <span class="acceso__texto">
                        <strong><?= e($a['titulo']) ?></strong>
                        <span><?= e($a['texto']) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="seccion">
    <div class="contenedor">
        <div class="seccion__cabeza">
            <h2 class="seccion__titulo">Los más elegidos</h2>
            <a class="enlace-fuerte" href="<?= e(url('catalogo', ['orden' => 'populares'])) ?>">Ver todo el catálogo</a>
        </div>
        <div class="grilla">
            <?php foreach ($destacados as $p) { include ROOT_PATH . '/views/partials/tarjeta.php'; } ?>
        </div>
    </div>
</section>

<section class="promo">
    <div class="contenedor promo__grid">
        <div class="promo__texto">
            <?php if ($oferta && $mayorDesc > 0): ?>
                <h2>Hasta <?= (int) $mayorDesc ?>% de descuento en modelos seleccionados</h2>
                <p>Renovamos stock y rebajamos pares de la temporada anterior. Cuando se terminan los talles, no vuelven.</p>
                <a class="btn btn--verde" href="<?= e(url('catalogo', ['ofertas' => 1])) ?>">Ver ofertas</a>
            <?php else: ?>
                <h2>Nuevos lanzamientos cada temporada</h2>
                <p>Los modelos más recientes de cada marca, apenas llegan al depósito.</p>
                <a class="btn btn--verde" href="<?= e(url('catalogo', ['nuevo' => 1])) ?>">Ver lanzamientos</a>
            <?php endif; ?>
        </div>
        <?php if ($oferta): ?>
            <a class="promo__producto" href="<?= e(url('producto', ['id' => $oferta['id']])) ?>">
                <img src="<?= e(img($oferta['img2'] ? $oferta['img2'] : $oferta['img1'])) ?>" alt="<?= e($oferta['marca'] . ' ' . $oferta['nombre']) ?>" loading="lazy">
                <span class="promo__datos">
                    <?= e($oferta['marca'] . ' ' . $oferta['nombre']) ?><br>
                    <strong><?= e(dinero($oferta['precio_final'])) ?></strong> <s><?= e(dinero($oferta['precio_venta'])) ?></s>
                </span>
            </a>
        <?php endif; ?>
    </div>
</section>
