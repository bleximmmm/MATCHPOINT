<?php
/** Carrito de compras (sesión). */
class CarritoController
{
    public static function ver(): void
    {
        $items = Cart::items();
        vista('tienda/carrito', ['titulo' => 'Tu carrito', 'items' => $items, 'total' => Cart::total($items)]);
    }

    /** Agrega un producto. Responde JSON si la petición viene de fetch(). */
    public static function agregar(): void
    {
        if (!es_post()) {
            redirigir(url('catalogo'));
        }
        csrf_verificar();
        $idp = ctype_digit(post('id_producto')) ? (int) post('id_producto') : 0;
        $idt = ctype_digit(post('id_talle')) ? (int) post('id_talle') : 0;
        $cant = ctype_digit(post('cantidad')) ? (int) post('cantidad') : 0;

        if (!$idp) {
            $ok = false;
            $msg = 'Producto no válido.';
        } elseif (!$idt) {
            $ok = false;
            $msg = 'Elegí un talle antes de agregar el producto al carrito.';
        } else {
            list($ok, $msg) = Cart::agregar($idp, $idt, $cant);
        }

        if (es_ajax()) {
            json_responder(['ok' => $ok, 'mensaje' => $msg, 'cantidad' => Cart::contar()], $ok ? 200 : 422);
        }
        flash($ok ? 'ok' : 'error', $msg);
        redirigir($ok ? url('carrito') : url('producto', ['id' => $idp]));
    }

    public static function actualizar(): void
    {
        if (!es_post()) {
            redirigir(url('carrito'));
        }
        csrf_verificar();
        $cantidades = isset($_POST['cantidad']) && is_array($_POST['cantidad']) ? $_POST['cantidad'] : [];
        foreach ($cantidades as $clave => $cant) {
            if (preg_match('/^\d+-\d+$/', (string) $clave) && ctype_digit((string) $cant)) {
                Cart::fijar((string) $clave, (int) $cant);
            }
        }
        flash('ok', 'Carrito actualizado.');
        redirigir(url('carrito'));
    }

    public static function eliminar(): void
    {
        if (!es_post()) {
            redirigir(url('carrito'));
        }
        csrf_verificar();
        $clave = post('clave');
        if (preg_match('/^\d+-\d+$/', $clave)) {
            Cart::quitar($clave);
            flash('ok', 'Quitamos el producto del carrito.');
        }
        redirigir(url('carrito'));
    }
}
