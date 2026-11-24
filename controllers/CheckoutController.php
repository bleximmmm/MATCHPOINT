<?php
/** Proceso de compra: revisar carrito, datos de envío y confirmación. */
class CheckoutController
{
    const PROVINCIAS = [
        'Buenos Aires', 'Ciudad Autónoma de Buenos Aires', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 'Corrientes',
        'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza', 'Misiones', 'Neuquén', 'Río Negro',
        'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe', 'Santiago del Estero', 'Tierra del Fuego', 'Tucumán',
    ];

    public static function comprar(): void
    {
        Auth::exigirLogin();
        $items = Cart::items();
        if (!$items) {
            flash('info', 'Tu carrito está vacío. Elegí un par para empezar.');
            redirigir(url('catalogo'));
        }
        $u = Auth::usuario();
        $datos = [
            'nombre' => trim($u['nombre'] . ' ' . $u['apellido']), 'telefono' => '', 'direccion' => '',
            'ciudad' => '', 'provincia' => '', 'codigo_postal' => '',
        ];
        $fila = Usuario::porId(Auth::id());
        if ($fila && $fila['telefono']) {
            $datos['telefono'] = $fila['telefono'];
        }
        $errores = [];

        if (es_post()) {
            csrf_verificar();
            foreach ($datos as $k => $v) {
                $datos[$k] = post($k);
            }
            $errores = self::validar($datos);
            if (post('metodo_pago') !== 'demo') {
                $errores['metodo_pago'] = 'Elegí el método de compra.';
            }
            if (!$errores) {
                try {
                    $idPedido = Pedido::crear(Auth::id(), $items, $datos, 'Compra de demostración');
                    Cart::vaciar();
                    redirigir(url('pedido_confirmado', ['id' => $idPedido]));
                } catch (RuntimeException $e) {
                    flash('error', 'No pudimos confirmar el pedido. ' . $e->getMessage());
                    redirigir(url('carrito'));
                } catch (PDOException $e) {
                    error_log('Error al crear pedido: ' . $e->getMessage());
                    flash('error', 'Ocurrió un error al guardar el pedido. Probá de nuevo en unos minutos.');
                    redirigir(url('carrito'));
                }
            }
        }
        vista('tienda/checkout', [
            'titulo' => 'Finalizar compra', 'items' => $items, 'total' => Cart::total($items),
            'd' => $datos, 'errores' => $errores, 'provincias' => self::PROVINCIAS,
        ]);
    }

    private static function validar(array $d): array
    {
        $e = [];
        if (mb_strlen($d['nombre']) < 3 || mb_strlen($d['nombre']) > 120) {
            $e['nombre'] = 'Ingresá nombre y apellido de quien recibe.';
        }
        if (!preg_match('/^[0-9+()\s-]{6,30}$/', $d['telefono'])) {
            $e['telefono'] = 'Ingresá un teléfono válido (solo números, espacios, + ( ) y guiones).';
        }
        if (mb_strlen($d['direccion']) < 5 || mb_strlen($d['direccion']) > 160) {
            $e['direccion'] = 'Ingresá calle y altura (entre 5 y 160 caracteres).';
        }
        if (mb_strlen($d['ciudad']) < 2 || mb_strlen($d['ciudad']) > 80) {
            $e['ciudad'] = 'Ingresá la ciudad.';
        }
        if (!in_array($d['provincia'], self::PROVINCIAS, true)) {
            $e['provincia'] = 'Elegí una provincia de la lista.';
        }
        if (!preg_match('/^[A-Za-z0-9 ]{4,8}$/', $d['codigo_postal'])) {
            $e['codigo_postal'] = 'El código postal debe tener entre 4 y 8 letras o números.';
        }
        return $e;
    }

    public static function confirmado(): void
    {
        Auth::exigirLogin();
        $id = ctype_digit(get('id')) ? (int) get('id') : 0;
        $pedido = $id ? Pedido::buscar($id, Auth::id()) : null;
        if (!$pedido) {
            error_pagina(404, 'No encontramos ese pedido');
        }
        vista('tienda/confirmado', ['titulo' => 'Pedido confirmado', 'pedido' => $pedido, 'detalle' => Pedido::detalle($id)]);
    }
}
