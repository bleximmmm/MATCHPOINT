<?php
/**
 * MATCHPOINT - Controlador frontal del panel de administración.
 * Acceso solo para personal (admin / operador) con sesión iniciada.
 */
require_once __DIR__ . '/../core/bootstrap.php';

Auth::exigirPersonal();

$rutas = [
    'dashboard'   => 'AdminDashboard',
    'productos'   => 'AdminProductos',
    'pedidos'     => 'AdminPedidos',
    'movimientos' => 'AdminMovimientos',
    'reportes'    => 'AdminReportes',
    'usuarios'    => 'AdminUsuarios',
    'categorias'  => 'AdminCatalogos',
    'proveedores' => 'AdminCatalogos',
    'marcas'      => 'AdminCatalogos',
];

$pagina = isset($_GET['p']) && is_string($_GET['p']) ? $_GET['p'] : 'dashboard';
if (!isset($rutas[$pagina])) {
    http_response_code(404);
    vista('admin/error', ['titulo' => 'Página no encontrada', 'seccion' => ''], 'layout/admin');
    exit;
}

try {
    $clase = $rutas[$pagina];
    if ($clase === 'AdminCatalogos') {
        AdminCatalogos::ejecutar($pagina);
    } else {
        $clase::ejecutar();
    }
} catch (Throwable $e) {
    error_log('[MATCHPOINT admin] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (APP_DEBUG) {
        throw $e;
    }
    http_response_code(500);
    vista('admin/error', ['titulo' => 'Ocurrió un error', 'seccion' => $pagina], 'layout/admin');
}
