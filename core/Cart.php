<?php
/**
 * Carrito guardado en la sesión. Solo se guardan ids y cantidades;
 * precios, nombres y stock se leen siempre de la base de datos.
 */
class Cart
{
    private static function crudo(): array
    {
        return isset($_SESSION['carrito']) && is_array($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
    }

    /** Cantidad total de unidades (para el contador del encabezado). */
    public static function contar(): int
    {
        return (int) array_sum(self::crudo());
    }

    /** Agrega un producto/talle. Devuelve [ok, mensaje]. */
    public static function agregar(int $idProducto, int $idTalle, int $cantidad): array
    {
        if ($cantidad < 1 || $cantidad > MAX_CANTIDAD_POR_ITEM) {
            return [false, 'La cantidad debe estar entre 1 y ' . MAX_CANTIDAD_POR_ITEM . '.'];
        }
        $fila = Database::fila(
            'SELECT p.activo, pt.stock FROM productos p
             JOIN producto_talles pt ON pt.id_producto = p.id
             WHERE p.id = :p AND pt.id_talle = :t',
            ['p' => $idProducto, 't' => $idTalle]
        );
        if (!$fila || !(int) $fila['activo']) {
            return [false, 'Ese producto o talle no está disponible.'];
        }
        $stock = (int) $fila['stock'];
        if ($stock < 1) {
            return [false, 'Ese talle está agotado.'];
        }
        $clave = $idProducto . '-' . $idTalle;
        $carrito = self::crudo();
        $nueva = (isset($carrito[$clave]) ? $carrito[$clave] : 0) + $cantidad;
        if ($nueva > $stock) {
            $nueva = $stock;
            $carrito[$clave] = $nueva;
            $_SESSION['carrito'] = $carrito;
            return [true, 'Solo quedan ' . $stock . ' unidades de ese talle; las dejamos todas en tu carrito.'];
        }
        $carrito[$clave] = $nueva;
        $_SESSION['carrito'] = $carrito;
        return [true, 'Producto agregado al carrito.'];
    }

    public static function fijar(string $clave, int $cantidad): void
    {
        $carrito = self::crudo();
        if (!isset($carrito[$clave])) {
            return;
        }
        if ($cantidad < 1) {
            unset($carrito[$clave]);
        } else {
            $carrito[$clave] = min($cantidad, MAX_CANTIDAD_POR_ITEM);
        }
        $_SESSION['carrito'] = $carrito;
    }

    public static function quitar(string $clave): void
    {
        $carrito = self::crudo();
        unset($carrito[$clave]);
        $_SESSION['carrito'] = $carrito;
    }

    public static function vaciar(): void
    {
        unset($_SESSION['carrito']);
    }

    /**
     * Devuelve los ítems con datos frescos de la base. Si cambió el stock o un
     * producto se dio de baja, ajusta o quita el ítem y deja un aviso.
     */
    public static function items(): array
    {
        $carrito = self::crudo();
        if (!$carrito) {
            return [];
        }
        $items = [];
        $avisos = false;
        foreach ($carrito as $clave => $cant) {
            list($idp, $idt) = array_map('intval', explode('-', $clave));
            $f = Database::fila(
                'SELECT p.id, p.nombre, p.activo, p.sku, m.nombre AS marca,
                        ROUND(p.precio_venta * (100 - p.descuento) / 100) AS precio, p.precio_venta, p.descuento,
                        t.valor AS talle, pt.stock,
                        (SELECT ruta FROM producto_imagenes WHERE id_producto = p.id ORDER BY orden, id LIMIT 1) AS imagen
                 FROM productos p
                 JOIN marcas m ON m.id = p.id_marca
                 JOIN producto_talles pt ON pt.id_producto = p.id AND pt.id_talle = :t
                 JOIN talles t ON t.id = pt.id_talle
                 WHERE p.id = :p',
                ['p' => $idp, 't' => $idt]
            );
            if (!$f || !(int) $f['activo'] || (int) $f['stock'] < 1) {
                unset($carrito[$clave]);
                $avisos = true;
                continue;
            }
            if ($cant > (int) $f['stock']) {
                $cant = (int) $f['stock'];
                $carrito[$clave] = $cant;
                $avisos = true;
            }
            $items[] = [
                'clave'     => $clave,
                'id'        => (int) $f['id'],
                'id_talle'  => $idt,
                'nombre'    => $f['nombre'],
                'marca'     => $f['marca'],
                'imagen'    => $f['imagen'],
                'talle'     => (int) $f['talle'],
                'precio'    => (float) $f['precio'],
                'precio_lista' => (float) $f['precio_venta'],
                'descuento' => (int) $f['descuento'],
                'cantidad'  => $cant,
                'stock'     => (int) $f['stock'],
                'subtotal'  => (float) $f['precio'] * $cant,
            ];
        }
        $_SESSION['carrito'] = $carrito;
        if ($avisos) {
            flash('info', 'Actualizamos tu carrito: algunos productos cambiaron de stock o ya no están disponibles.');
        }
        return $items;
    }

    public static function total(array $items): float
    {
        $t = 0.0;
        foreach ($items as $i) {
            $t += $i['subtotal'];
        }
        return $t;
    }
}
