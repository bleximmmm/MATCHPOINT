<?php
/** Reportes: stock bajo, movimientos por fechas y productos más movidos (con exportación a CSV). */
class AdminReportes
{
    public static function ejecutar(): void
    {
        $reporte = in_array(get('r'), ['stock-bajo', 'movimientos', 'mas-movidos'], true) ? get('r') : 'stock-bajo';
        $desde = AdminMovimientos::fechaValida(get('desde'));
        $hasta = AdminMovimientos::fechaValida(get('hasta'));
        if ($desde === '' && $hasta === '' && $reporte !== 'stock-bajo' && !isset($_GET['desde'])) {
            $desde = date('Y-m-d', strtotime('-90 days'));      // por defecto: últimos 90 días
            $hasta = date('Y-m-d');
        }
        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            list($desde, $hasta) = [$hasta, $desde];
        }
        $f = [
            'desde' => $desde, 'hasta' => $hasta,
            'tipo'  => in_array(get('tipo'), ['entrada', 'salida', 'ajuste'], true) ? get('tipo') : '',
        ];

        if (get('a') === 'csv') {
            self::exportar($reporte, $f);
        }

        $datos = [];
        $pag = null;
        if ($reporte === 'stock-bajo') {
            $datos = Movimiento::stockBajo();
        } elseif ($reporte === 'mas-movidos') {
            $datos = Movimiento::masMovidos($f, 15);
        } else {
            $pagina = ctype_digit(get('pagina')) ? (int) get('pagina') : 1;
            $r = Movimiento::listar($f, $pagina, ADMIN_POR_PAGINA);
            $pag = paginar($r['total'], ADMIN_POR_PAGINA, $pagina);
            if ($pag['actual'] !== $pagina) {
                $r = Movimiento::listar($f, $pag['actual'], ADMIN_POR_PAGINA);
            }
            $datos = $r['items'];
        }
        vista('admin/reportes', [
            'titulo' => 'Reportes', 'seccion' => 'reportes', 'reporte' => $reporte, 'f' => $f, 'itemsReporte' => $datos, 'pag' => $pag,
        ], 'layout/admin');
    }

    /** Descarga el reporte en CSV (separador ;, con BOM para que Excel respete los acentos). */
    private static function exportar(string $reporte, array $f): void
    {
        if ($reporte === 'stock-bajo') {
            $cab = ['SKU', 'Producto', 'Stock actual', 'Stock mínimo', 'Faltante', 'Proveedor', 'Teléfono', 'Correo'];
            $filas = array_map(function ($r) {
                return [$r['sku'], $r['nombre'], $r['stock'], $r['stock_minimo'], $r['faltante'], $r['proveedor'], $r['telefono'], $r['email']];
            }, Movimiento::stockBajo());
        } elseif ($reporte === 'mas-movidos') {
            $cab = ['SKU', 'Producto', 'Movimientos', 'Unidades que entraron', 'Unidades que salieron', 'Unidades movidas'];
            $filas = array_map(function ($r) {
                return [$r['sku'], $r['nombre'], $r['movimientos'], $r['entradas'], $r['salidas'], $r['unidades']];
            }, Movimiento::masMovidos($f, 100));
        } else {
            $cab = ['N°', 'Fecha', 'Tipo', 'SKU', 'Producto', 'Talle', 'Cantidad', 'Stock anterior', 'Stock posterior', 'Usuario', 'Observaciones'];
            $filas = array_map('array_values', Movimiento::todos($f));
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="matchpoint-' . $reporte . '-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $cab, ';');
        foreach ($filas as $fila) {
            // Evita inyección de fórmulas al abrir el CSV en Excel.
            $fila = array_map(function ($v) {
                $v = (string) $v;
                return ($v !== '' && strpos('=+-@', $v[0]) !== false && !is_numeric($v)) ? "'" . $v : $v;
            }, $fila);
            fputcsv($out, $fila, ';');
        }
        fclose($out);
        exit;
    }
}
