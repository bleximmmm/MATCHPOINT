<?php
/** Layout público: encabezado fijo, mensajes, contenido y pie de página. */
$paginaActual = isset($_GET['p']) ? $_GET['p'] : 'inicio';
$marcaActual = (isset($_GET['marca']) && is_array($_GET['marca']) && count($_GET['marca']) === 1) ? $_GET['marca'][0] : '';
$canchaActual = (isset($_GET['cancha']) && is_array($_GET['cancha']) && count($_GET['cancha']) === 1) ? $_GET['cancha'][0] : '';
$esOfertas = isset($_GET['ofertas']) && $_GET['ofertas'] === '1';
$esNovedades = isset($_GET['nuevo']) && $_GET['nuevo'] === '1';
$usuario = Auth::usuario();
$marcasNav = Catalogo::marcas();
$categoriasNav = Catalogo::categorias();
$marcaActivaEnNav = false;
foreach ($marcasNav as $marcaNav) {
    if ($marcaActual !== '' && $marcaActual === $marcaNav['slug']) {
        $marcaActivaEnNav = true;
        break;
    }
}
?><!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> | MATCHPOINT</title>
<meta name="description" content="MATCHPOINT: tienda online de botines de fútbol. Tu próximo partido empieza acá.">
<script>try{var t=localStorage.getItem('matchpoint-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}</script>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css') . '?v=' . filemtime(PUBLIC_PATH . '/css/style.css')) ?>">
</head>
<body data-base="<?= e(BASE_URL) ?>">
<a class="saltar" href="#contenido">Saltar al contenido</a>

<header class="cabecera" id="cabecera">
    <div class="barra-aviso">
        <div class="contenedor barra-aviso__contenido">
            <span>Botines para cada superficie</span>
            <span>Stock actualizado por talle</span>
            <span>Compras de demostración</span>
        </div>
    </div>
    <div class="cabecera__barra contenedor">
        <button class="btn-icono menu-btn" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="nav-principal">
            <?= icono('menu') ?>
        </button>
        <a class="logo" href="<?= e(url('inicio')) ?>" aria-label="MATCHPOINT, ir al inicio">
            <?= logo_svg() ?><span class="logo__texto">MATCHPOINT</span>
        </a>
        <nav class="nav" id="nav-principal" aria-label="Principal">
            <ul>
                <li><a href="<?= e(url('catalogo')) ?>"<?= $paginaActual === 'catalogo' && $marcaActual === '' && $canchaActual === '' && !$esOfertas && !$esNovedades ? ' aria-current="page"' : '' ?>>Botines</a></li>
                <li class="nav__desplegable<?= $canchaActual !== '' ? ' nav__desplegable--actual' : '' ?>">
                    <details>
                        <summary<?= $canchaActual !== '' ? ' aria-current="page"' : '' ?>>Superficies</summary>
                        <ul class="nav__submenu">
                            <?php foreach ($categoriasNav as $categoriaNav): ?>
                                <li><a href="<?= e(url('catalogo', ['cancha' => [$categoriaNav['slug']]])) ?>"<?= $canchaActual === $categoriaNav['slug'] ? ' aria-current="page"' : '' ?>><?= e($categoriaNav['nombre']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </li>
                <li class="nav__desplegable<?= $marcaActivaEnNav ? ' nav__desplegable--actual' : '' ?>">
                    <details>
                        <summary<?= $marcaActivaEnNav ? ' aria-current="page"' : '' ?>>Marcas</summary>
                        <ul class="nav__submenu">
                            <?php foreach ($marcasNav as $marcaNav): ?>
                                <li><a href="<?= e(url('catalogo', ['marca' => [$marcaNav['slug']]])) ?>"<?= $marcaActual === $marcaNav['slug'] ? ' aria-current="page"' : '' ?>><?= e($marcaNav['nombre']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </li>
                <li><a href="<?= e(url('catalogo', ['nuevo' => 1, 'orden' => 'recientes'])) ?>"<?= $paginaActual === 'catalogo' && $esNovedades ? ' aria-current="page"' : '' ?>>Novedades</a></li>
                <li><a href="<?= e(url('catalogo', ['ofertas' => 1])) ?>"<?= $paginaActual === 'catalogo' && $esOfertas ? ' aria-current="page"' : '' ?>>Ofertas</a></li>
                <li class="nav__movil"><a href="<?= e($usuario ? url('cuenta') : url('login')) ?>"><?= $usuario ? 'Mi cuenta' : 'Iniciar sesión' ?></a></li>
            </ul>
        </nav>
        <div class="acciones">
            <button class="btn-icono" type="button" data-buscar aria-label="Buscar" aria-expanded="false" aria-controls="buscador"><?= icono('lupa') ?></button>
            <?php if ($usuario): ?>
                <details class="cuenta-menu">
                    <summary class="btn-icono" aria-label="Mi cuenta"><?= icono('usuario') ?></summary>
                    <div class="cuenta-menu__panel">
                        <p class="cuenta-menu__saludo">Hola, <?= e($usuario['nombre']) ?></p>
                        <a href="<?= e(url('cuenta')) ?>">Mis datos</a>
                        <a href="<?= e(url('cuenta_pedidos')) ?>">Mis pedidos</a>
                        <a href="<?= e(url('cuenta_favoritos')) ?>">Mis favoritos</a>
                        <?php if (Auth::esPersonal()): ?><a href="<?= e(aurl('dashboard')) ?>">Panel de administración</a><?php endif; ?>
                        <form method="post" action="<?= e(url('logout')) ?>">
                            <?= csrf_campo() ?>
                            <button type="submit">Cerrar sesión</button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <a class="btn-icono" href="<?= e(url('login')) ?>" aria-label="Iniciar sesión o crear cuenta"><?= icono('usuario') ?></a>
            <?php endif; ?>
            <button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Cambiar a tema oscuro" title="Cambiar tema">
                <span class="theme-toggle__sol"><?= icono('sol') ?></span><span class="theme-toggle__luna"><?= icono('luna') ?></span><span class="theme-toggle__texto">Tema</span>
            </button>
            <a class="btn-icono carrito-link" href="<?= e(url('carrito')) ?>" aria-label="Carrito">
                <?= icono('carrito') ?>
                <span class="contador" data-contador<?= Cart::contar() === 0 ? ' hidden' : '' ?>><?= Cart::contar() ?></span>
            </a>
        </div>
    </div>
    <form class="buscador" id="buscador" action="<?= e(BASE_URL . '/index.php') ?>" method="get" role="search" hidden>
        <div class="contenedor buscador__campo">
            <input type="hidden" name="p" value="catalogo">
            <label class="solo-lector" for="q-header">Buscar por nombre o marca</label>
            <input type="search" id="q-header" name="q" maxlength="60" placeholder="Buscar por nombre o marca" autocomplete="off">
            <button class="btn btn--verde" type="submit">Buscar</button>
        </div>
    </form>
</header>

<?php require ROOT_PATH . '/views/partials/modal.php'; ?>

<main id="contenido">
<?= $contenido ?>
</main>

<footer class="pie">
    <div class="contenedor pie__grid">
        <div class="pie__marca">
            <a class="logo" href="<?= e(url('inicio')) ?>"><?= logo_svg() ?><span class="logo__texto">MATCHPOINT</span></a>
            <p>Tienda especializada en botines de fútbol. Trabajamos con las marcas que se juegan en la cancha y te ayudamos a elegir el par para tu superficie y tu talle.</p>
            <ul class="redes" aria-label="Redes sociales">
                <li><a href="https://www.instagram.com/" target="_blank" rel="noopener">Instagram</a></li>
                <li><a href="https://www.facebook.com/" target="_blank" rel="noopener">Facebook</a></li>
                <li><a href="https://www.tiktok.com/" target="_blank" rel="noopener">TikTok</a></li>
                <li><a href="https://x.com/" target="_blank" rel="noopener">X</a></li>
            </ul>
        </div>
        <nav aria-label="Tienda">
            <h2 class="pie__titulo">Tienda</h2>
            <ul>
                <li><a href="<?= e(url('catalogo')) ?>">Catálogo completo</a></li>
                <li><a href="<?= e(url('catalogo', ['ofertas' => 1])) ?>">Ofertas</a></li>
                <li><a href="<?= e(url('catalogo', ['nuevo' => 1])) ?>">Últimos lanzamientos</a></li>
                <li><a href="<?= e(url('catalogo', ['cancha' => ['futbol-sala']])) ?>">Fútbol sala</a></li>
            </ul>
        </nav>
        <nav aria-label="Mi cuenta">
            <h2 class="pie__titulo">Mi cuenta</h2>
            <ul>
                <li><a href="<?= e(url('cuenta')) ?>">Mis datos</a></li>
                <li><a href="<?= e(url('cuenta_pedidos')) ?>">Mis pedidos</a></li>
                <li><a href="<?= e(url('cuenta_favoritos')) ?>">Favoritos</a></li>
                <li><a href="<?= e(url('carrito')) ?>">Carrito</a></li>
            </ul>
        </nav>
        <div>
            <h2 class="pie__titulo">Contacto</h2>
            <ul class="pie__contacto">
                <li><a href="mailto:contacto@matchpoint.com.ar">contacto@matchpoint.com.ar</a></li>
                <li>Lunes a viernes, de 9 a 18 h</li>
                <li>Argentina</li>
            </ul>
        </div>
    </div>
    <div class="pie__legal contenedor">
        <p>&copy; <?= date('Y') ?> MATCHPOINT. Todos los derechos reservados.</p>
        <p>Proyecto escolar: las compras son de demostración y no se realiza ningún cobro.</p>
    </div>
</footer>

<script src="<?= e(asset('js/app.js') . '?v=' . filemtime(PUBLIC_PATH . '/js/app.js')) ?>"></script>
</body>
</html>
