<?php
/**
 * MATCHPOINT - Configuración general.
 * Ajustá los datos de conexión según tu entorno local (XAMPP / WAMP / Laragon).
 */

// --- Base de datos --------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'matchpoint');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- Rutas del sistema de archivos ----------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_DIR', PUBLIC_PATH . '/uploads/productos');

// --- Parámetros de la aplicación -------------------------------------------
define('APP_NOMBRE', 'MATCHPOINT');
define('APP_ESLOGAN', 'Tu próximo partido empieza acá');
define('APP_DEBUG', false);                 // true solo mientras desarrollás
define('ITEMS_POR_PAGINA', 12);             // catálogo
define('ADMIN_POR_PAGINA', 10);             // listados del panel
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024);
define('MAX_CANTIDAD_POR_ITEM', 10);
define('SESION_MINUTOS', 120);

// --- URL base (se detecta sola, funciona en una subcarpeta o en la raíz) ----
$__script = str_replace('\\', '/', isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$__dir = rtrim(dirname($__script), '/');
if (substr($__dir, -6) === '/admin') {
    $__dir = substr($__dir, 0, -6);
}
define('BASE_URL', $__dir);
unset($__script, $__dir);
