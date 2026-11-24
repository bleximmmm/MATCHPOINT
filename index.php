<?php
/**
 * MATCHPOINT - Controlador frontal de la tienda.
 * Todas las páginas públicas entran por index.php?p=<ruta>
 */
require_once __DIR__ . '/core/bootstrap.php';

$rutas = [
    'inicio'            => ['TiendaController', 'inicio'],
    'catalogo'          => ['TiendaController', 'catalogo'],
    'producto'          => ['TiendaController', 'producto'],
    'carrito'           => ['CarritoController', 'ver'],
    'carrito_agregar'   => ['CarritoController', 'agregar'],
    'carrito_actualizar'=> ['CarritoController', 'actualizar'],
    'carrito_eliminar'  => ['CarritoController', 'eliminar'],
    'checkout'          => ['CheckoutController', 'comprar'],
    'pedido_confirmado' => ['CheckoutController', 'confirmado'],
    'login'             => ['CuentaController', 'login'],
    'registro'          => ['CuentaController', 'registro'],
    'logout'            => ['CuentaController', 'logout'],
    'cuenta'            => ['CuentaController', 'perfil'],
    'cuenta_pedidos'    => ['CuentaController', 'pedidos'],
    'cuenta_pedido'     => ['CuentaController', 'pedido'],
    'cuenta_favoritos'  => ['CuentaController', 'favoritos'],
    'favorito'          => ['CuentaController', 'favorito'],
];

$pagina = isset($_GET['p']) && is_string($_GET['p']) ? $_GET['p'] : 'inicio';

if (!isset($rutas[$pagina])) {
    error_pagina(404, 'Esta página no existe', 'Revisá el enlace o volvé al inicio.');
}

try {
    list($clase, $metodo) = $rutas[$pagina];
    $clase::$metodo();
} catch (Throwable $e) {
    error_log('[MATCHPOINT] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (APP_DEBUG) {
        throw $e;
    }
    if (es_ajax()) {
        json_responder(['ok' => false, 'mensaje' => 'Ocurrió un error inesperado. Probá de nuevo.'], 500);
    }
    error_pagina(500, 'Algo salió mal', 'Tuvimos un problema al procesar la solicitud. Probá de nuevo en unos minutos.');
}
