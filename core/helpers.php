<?php
/**
 * Funciones auxiliares: escape, URLs, mensajes flash, CSRF, paginación, imágenes.
 */

/** Escapa un valor para mostrarlo en HTML. */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL de una página de la tienda: url('catalogo', ['marca' => ['nike']]) */
function url(string $pagina = 'inicio', array $params = []): string
{
    $q = array_merge(['p' => $pagina], $params);
    return BASE_URL . '/index.php?' . http_build_query($q);
}

/** URL de una página del panel de administración. */
function aurl(string $pagina = 'dashboard', array $params = []): string
{
    $q = array_merge(['p' => $pagina], $params);
    return BASE_URL . '/admin/index.php?' . http_build_query($q);
}

/** Ruta pública de un archivo estático (css, js, img). */
function asset(string $ruta): string
{
    return BASE_URL . '/public/' . ltrim($ruta, '/');
}

/** URL de una imagen de producto guardada en la base (ruta relativa a /public). */
function img(?string $ruta): string
{
    if (!$ruta) {
        return asset('img/sin-imagen.svg');
    }
    return asset($ruta);
}

/** Formatea un monto en pesos argentinos: $ 189.999 */
function dinero($n): string
{
    return '$ ' . number_format((float) $n, 0, ',', '.');
}

function redirigir(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Lee un valor de $_POST como texto recortado. */
function post(string $clave, string $defecto = ''): string
{
    $v = $_POST[$clave] ?? $defecto;
    return is_string($v) ? trim($v) : $defecto;
}

/** Lee un valor de $_GET como texto recortado. */
function get(string $clave, string $defecto = ''): string
{
    $v = $_GET[$clave] ?? $defecto;
    return is_string($v) ? trim($v) : $defecto;
}

function es_ajax(): bool
{
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch');
}

function json_responder(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------- mensajes flash
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_obtener(): array
{
    $m = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $m;
}

// ------------------------------------------------------------------------- CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Verifica el token enviado por formulario o por el header X-CSRF-Token. */
function csrf_verificar(): void
{
    $enviado = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($enviado) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
        if (es_ajax()) {
            json_responder(['ok' => false, 'mensaje' => 'La sesión expiró. Recargá la página.'], 419);
        }
        http_response_code(419);
        exit('Solicitud no válida (token CSRF). Volvé atrás y recargá la página.');
    }
}

// -------------------------------------------------------------------- paginación
/** Calcula los datos de paginación. */
function paginar(int $total, int $porPagina, int $actual): array
{
    $paginas = max(1, (int) ceil($total / $porPagina));
    $actual = min(max(1, $actual), $paginas);
    return [
        'total'    => $total,
        'por'      => $porPagina,
        'paginas'  => $paginas,
        'actual'   => $actual,
        'offset'   => ($actual - 1) * $porPagina,
    ];
}

/** Dibuja los enlaces de paginación conservando los filtros actuales. */
function paginacion_html(array $pag, bool $admin = false): string
{
    if ($pag['paginas'] <= 1) {
        return '';
    }
    $params = $_GET;
    unset($params['pagina']);
    $armar = function (int $n) use ($params, $admin): string {
        $params['pagina'] = $n;
        $base = $admin ? BASE_URL . '/admin/index.php?' : BASE_URL . '/index.php?';
        return e($base . http_build_query($params));
    };
    $html = '<nav class="paginacion" aria-label="Paginación"><ul>';
    if ($pag['actual'] > 1) {
        $html .= '<li><a href="' . $armar($pag['actual'] - 1) . '" rel="prev">Anterior</a></li>';
    }
    for ($i = 1; $i <= $pag['paginas']; $i++) {
        if ($i === 1 || $i === $pag['paginas'] || abs($i - $pag['actual']) <= 1) {
            $cls = $i === $pag['actual'] ? ' class="activa" aria-current="page"' : '';
            $html .= '<li><a' . $cls . ' href="' . $armar($i) . '">' . $i . '</a></li>';
        } elseif (abs($i - $pag['actual']) === 2) {
            $html .= '<li class="puntos">…</li>';
        }
    }
    if ($pag['actual'] < $pag['paginas']) {
        $html .= '<li><a href="' . $armar($pag['actual'] + 1) . '" rel="next">Siguiente</a></li>';
    }
    return $html . '</ul></nav>';
}

// ------------------------------------------------------------------------ varios
function slugify(string $texto): string
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $texto));
    return trim($texto, '-');
}

/** Devuelve las claves de un array de GET que contiene enteros positivos. */
function lista_enteros($valor): array
{
    if (!is_array($valor)) {
        return [];
    }
    $out = [];
    foreach ($valor as $v) {
        if (ctype_digit((string) $v)) {
            $out[] = (int) $v;
        }
    }
    return array_values(array_unique($out));
}

function lista_slugs($valor): array
{
    if (!is_array($valor)) {
        return [];
    }
    $out = [];
    foreach ($valor as $v) {
        if (is_string($v) && preg_match('/^[a-z0-9-]{1,80}$/', $v)) {
            $out[] = $v;
        }
    }
    return array_values(array_unique($out));
}

/**
 * Valida y guarda una imagen subida. Devuelve la ruta relativa a /public o lanza excepción.
 * Se controla: error de subida, tamaño, tipo MIME real (finfo) y que sea una imagen válida.
 */
function guardar_imagen(array $archivo): string
{
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir una de las imágenes.');
    }
    if ($archivo['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Cada imagen debe pesar menos de 3 MB.');
    }
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    if (!isset($permitidos[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
        throw new RuntimeException('Solo se aceptan imágenes JPG, PNG o WEBP.');
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de subidas.');
    }
    $nombre = bin2hex(random_bytes(12)) . '.' . $permitidos[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], UPLOAD_DIR . '/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }
    return 'uploads/productos/' . $nombre;
}

/** Borra del disco una imagen subida por el admin (nunca las del catálogo base). */
function borrar_imagen_subida(string $ruta): void
{
    if (strpos($ruta, 'uploads/productos/') === 0) {
        $archivo = PUBLIC_PATH . '/' . $ruta;
        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }
}

/** Ordena el array $_FILES['campo'] (múltiple) en una lista de archivos individuales. */
function archivos_multiples(string $campo): array
{
    $out = [];
    if (empty($_FILES[$campo]) || !is_array($_FILES[$campo]['name'])) {
        return $out;
    }
    foreach ($_FILES[$campo]['name'] as $i => $nombre) {
        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = [
            'name'     => $nombre,
            'tmp_name' => $_FILES[$campo]['tmp_name'][$i],
            'error'    => $_FILES[$campo]['error'][$i],
            'size'     => $_FILES[$campo]['size'][$i],
        ];
    }
    return $out;
}

/** Iconos SVG en línea (trazo simple, heredan el color del texto). */
function icono(string $nombre): string
{
    $p = [
        'carrito'  => '<path d="M3 4h2.2l1.6 10.2a1.5 1.5 0 0 0 1.5 1.3h8.1a1.5 1.5 0 0 0 1.5-1.1L19.5 8H6"/><circle cx="9" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/>',
        'usuario'  => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c.6-3.8 3.8-6 7.5-6s6.9 2.2 7.5 6"/>',
        'lupa'     => '<circle cx="10.5" cy="10.5" r="6"/><path d="M15 15l5 5"/>',
        'corazon'  => '<path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.4a4.3 4.3 0 0 1 7.5 2.4C19.5 15.4 12 20 12 20z"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'cerrar'   => '<path d="M6 6l12 12M18 6L6 18"/>',
        'filtro'   => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'mas'      => '<path d="M12 5v14M5 12h14"/>',
        'menos'    => '<path d="M5 12h14"/>',
        'basura'   => '<path d="M5 7h14M10 7V4.5h4V7M7 7l1 12.5h8L17 7"/>',
        'sol'      => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6L7 7M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4"/>',
        'luna'     => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5z"/>',
    ];
    return '<svg class="icono" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$nombre] ?? '') . '</svg>';
}

/** Logo de MATCHPOINT: el círculo central de la cancha con el punto del partido. */
function logo_svg(): string
{
    return '<svg class="logo-marca" viewBox="0 0 32 32" width="30" height="30" aria-hidden="true"><circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="2.4"/><path d="M4 16h5M23 16h5" stroke="currentColor" stroke-width="2.4"/><circle cx="16" cy="16" r="4.2" fill="#C6FF00"/></svg>';
}

/** Vista: renderiza una plantilla dentro de un layout. */
function vista(string $plantilla, array $datos = [], string $layout = 'layout/tienda'): void
{
    extract($datos, EXTR_SKIP);
    ob_start();
    require ROOT_PATH . '/views/' . $plantilla . '.php';
    $contenido = ob_get_clean();
    require ROOT_PATH . '/views/' . $layout . '.php';
}

/** Muestra una página de error simple. */
function error_pagina(int $codigo, string $titulo, string $detalle = ''): void
{
    http_response_code($codigo);
    vista('tienda/error', ['titulo' => $titulo, 'detalle' => $detalle, 'codigo' => $codigo]);
    exit;
}
