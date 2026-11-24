<?php
/** Consulta de pedidos, detalle y cambio de estado. */
class AdminPedidos
{
    public static function ejecutar(): void
    {
        switch (get('a', 'listar')) {
            case 'ver':    self::ver(); break;
            case 'estado': self::estado(); break;
            default:       self::listar();
        }
    }

    private static function listar(): void
    {
        $estado = isset(Pedido::ESTADOS[get('estado')]) ? get('estado') : '';
        $q = mb_substr(get('q'), 0, 60);
        $pagina = ctype_digit(get('pagina')) ? (int) get('pagina') : 1;
        $r = Pedido::listar($estado, $q, $pagina, ADMIN_POR_PAGINA);
        $pag = paginar($r['total'], ADMIN_POR_PAGINA, $pagina);
        if ($pag['actual'] !== $pagina) {
            $r = Pedido::listar($estado, $q, $pag['actual'], ADMIN_POR_PAGINA);
        }
        vista('admin/pedidos_lista', [
            'titulo' => 'Pedidos', 'seccion' => 'pedidos', 'items' => $r['items'], 'pag' => $pag, 'estado' => $estado, 'q' => $q,
        ], 'layout/admin');
    }

    private static function ver(): void
    {
        $id = ctype_digit(get('id')) ? (int) get('id') : 0;
        $pedido = $id ? Pedido::buscar($id) : null;
        if (!$pedido) {
            flash('error', 'El pedido no existe.');
            redirigir(aurl('pedidos'));
        }
        vista('admin/pedido_detalle', [
            'titulo' => 'Pedido #' . $id, 'seccion' => 'pedidos', 'pedido' => $pedido, 'detalle' => Pedido::detalle($id),
        ], 'layout/admin');
    }

    private static function estado(): void
    {
        if (!es_post()) {
            redirigir(aurl('pedidos'));
        }
        csrf_verificar();
        $id = ctype_digit(post('id')) ? (int) post('id') : 0;
        try {
            Pedido::cambiarEstado($id, post('estado'), Auth::id());
            flash('ok', 'Actualizamos el estado del pedido #' . $id . '.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirigir(aurl('pedidos', ['a' => 'ver', 'id' => $id]));
    }
}
