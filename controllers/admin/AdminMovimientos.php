<?php
/** Consulta del historial de movimientos de stock. */
class AdminMovimientos
{
    /** Valida una fecha AAAA-MM-DD; devuelve '' si no es válida. */
    public static function fechaValida(string $f): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : '';
    }

    public static function ejecutar(): void
    {
        if (es_post()) {
            flash('info', 'Esta sección es solo de consulta. Los cambios de stock se realizan desde Productos o se generan al procesar ventas y cancelaciones.');
            redirigir(aurl('movimientos'));
        }

        $f = [
            'tipo'  => in_array(get('tipo_f'), ['entrada', 'salida', 'ajuste'], true) ? get('tipo_f') : '',
            'desde' => self::fechaValida(get('desde')),
            'hasta' => self::fechaValida(get('hasta')),
            'q'     => mb_substr(get('q'), 0, 60),
        ];
        $pagina = ctype_digit(get('pagina')) ? (int) get('pagina') : 1;
        $r = Movimiento::listar($f, $pagina, ADMIN_POR_PAGINA);
        $pag = paginar($r['total'], ADMIN_POR_PAGINA, $pagina);
        if ($pag['actual'] !== $pagina) {
            $r = Movimiento::listar($f, $pag['actual'], ADMIN_POR_PAGINA);
        }

        vista('admin/movimientos', [
            'titulo' => 'Movimientos de stock', 'seccion' => 'movimientos', 'items' => $r['items'], 'pag' => $pag, 'f' => $f,
        ], 'layout/admin');
    }
}
