<?php /** Navegación interna de "Mi cuenta". Variable: $tab */ ?>
<section class="encabezado-pagina">
    <div class="contenedor">
        <h1><?= e($titulo) ?></h1>
        <nav class="tabs" aria-label="Mi cuenta">
            <a href="<?= e(url('cuenta')) ?>"<?= $tab === 'perfil' ? ' aria-current="page"' : '' ?>>Mis datos</a>
            <a href="<?= e(url('cuenta_pedidos')) ?>"<?= $tab === 'pedidos' ? ' aria-current="page"' : '' ?>>Mis pedidos</a>
            <a href="<?= e(url('cuenta_favoritos')) ?>"<?= $tab === 'favoritos' ? ' aria-current="page"' : '' ?>>Favoritos</a>
        </nav>
    </div>
</section>
