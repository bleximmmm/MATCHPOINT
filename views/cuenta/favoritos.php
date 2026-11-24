<?php /** Variables: $items, $favs */ include ROOT_PATH . '/views/cuenta/_menu.php'; ?>
<section class="seccion seccion--clara">
    <div class="contenedor">
    <?php if (!$items): ?>
        <div class="vacio vacio--caja">
            <h2>No guardaste favoritos todavía</h2>
            <p>Tocá el corazón de cualquier botín para guardarlo y encontrarlo después.</p>
            <a class="btn btn--oscuro" href="<?= e(url('catalogo')) ?>">Ir al catálogo</a>
        </div>
    <?php else: ?>
        <div class="grilla grilla--catalogo">
            <?php foreach ($items as $p) { include ROOT_PATH . '/views/partials/tarjeta.php'; } ?>
        </div>
    <?php endif; ?>
    </div>
</section>
