<?php
/**
 * Arranque común de la tienda y del panel: configuración, sesión, autoload y helpers.
 */
require_once __DIR__ . '/../config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Argentina/Buenos_Aires');
mb_internal_encoding('UTF-8');

// Cabeceras de seguridad básicas
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Sesión con cookie protegida
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('matchpoint_sid');
session_start();

require_once __DIR__ . '/helpers.php';

// Autoload simple: cada clase vive en un archivo con su mismo nombre.
spl_autoload_register(function ($clase) {
    foreach (['core', 'models', 'controllers', 'controllers/admin'] as $dir) {
        $archivo = ROOT_PATH . '/' . $dir . '/' . $clase . '.php';
        if (is_file($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

Auth::refrescar();
