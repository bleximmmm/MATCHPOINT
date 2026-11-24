<?php
/** Layout del panel de administración. Variables: $titulo, $seccion, $contenido */
$menu = [
    ['dashboard', 'Panel', false], ['productos', 'Productos', false], ['pedidos', 'Pedidos', false],
    ['movimientos', 'Movimientos de stock', false], ['reportes', 'Reportes', false],
    ['categorias', 'Categorías', false], ['proveedores', 'Proveedores', false], ['marcas', 'Marcas', false],
    ['usuarios', 'Usuarios', true],
];
$u = Auth::usuario();
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titulo) ?> | Panel MATCHPOINT</title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css') . '?v=' . filemtime(PUBLIC_PATH . '/css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css') . '?v=' . filemtime(PUBLIC_PATH . '/css/admin.css')) ?>">
</head>
<body class="admin" data-base="<?= e(BASE_URL) ?>">
<a class="saltar" href="#contenido">Saltar al contenido</a>
<div class="admin__marco">
    <aside class="admin__lateral" id="lateral">
        <a class="logo" href="<?= e(aurl('dashboard')) ?>"><?= logo_svg() ?><span class="logo__texto">MATCHPOINT</span></a>
        <p class="admin__rol"><?= e($u['nombre'] . ' ' . $u['apellido']) ?><br><small><?= e(Usuario::ROLES[$u['rol']]) ?></small></p>
        <nav aria-label="Panel">
            <ul>
                <?php foreach ($menu as $m): if ($m[2] && !Auth::esAdmin()) continue; ?>
                    <li><a href="<?= e(aurl($m[0])) ?>"<?= $seccion === $m[0] ? ' aria-current="page"' : '' ?>><?= e($m[1]) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="admin__pie">
            <a href="<?= e(url('inicio')) ?>">Ver la tienda</a>
            <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_campo() ?><button type="submit">Cerrar sesión</button></form>
        </div>
    </aside>
    <div class="admin__principal">
        <header class="admin__barra">
            <button class="btn-icono menu-btn admin__menu" type="button" aria-label="Abrir menú del panel" aria-expanded="false" aria-controls="lateral"><?= icono('menu') ?></button>
            <h1><?= e($titulo) ?></h1>
        </header>
        <?php require ROOT_PATH . '/views/partials/modal.php'; ?>
        <main id="contenido" class="admin__contenido"><?= $contenido ?></main>
    </div>
</div>
<script src="<?= e(asset('js/app.js') . '?v=' . filemtime(PUBLIC_PATH . '/js/app.js')) ?>"></script>
</body>
</html>
