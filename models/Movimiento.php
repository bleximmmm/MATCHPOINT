<?php
/**
 * Movimientos de stock. Toda variación de stock (compras, ventas, ajustes,
 * cancelaciones) pasa por registrar(), que actualiza el stock del talle, el total
 * del producto y deja el historial. Debe ejecutarse dentro de una transacción.
 */
class Movimiento
{
    public static function registrar(int $idProducto, int $idTalle, string $tipo, int $cantidad, $idUsuario, string $obs, $idPedido = null): array
    {
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            throw new InvalidArgumentException('Tipo de movimiento inválido.');
        }
        if ($cantidad < 0 || ($tipo !== 'ajuste' && $cantidad < 1)) {
            throw new InvalidArgumentException('La cantidad no es válida.');
        }
        $key = ['p' => $idProducto, 't' => $idTalle];

        // Garantiza que exista la fila producto/talle y la bloquea hasta el final de la transacción.
        Database::ejecutar('INSERT IGNORE INTO producto_talles (id_producto, id_talle, stock) VALUES (:p, :t, 0)', $key);
        $antes = Database::valor('SELECT stock FROM producto_talles WHERE id_producto = :p AND id_talle = :t FOR UPDATE', $key);
        if ($antes === null) {
            throw new RuntimeException('El producto o el talle no existe.');
        }
        $antes = (int) $antes;

        if ($tipo === 'entrada') {
            $despues = $antes + $cantidad;
            $movido = $cantidad;
        } elseif ($tipo === 'salida') {
            if ($cantidad > $antes) {
                throw new RuntimeException('Stock insuficiente: quedan ' . $antes . ' unidades.');
            }
            $despues = $antes - $cantidad;
            $movido = $cantidad;
        } else { // ajuste: $cantidad es el stock real contado
            $despues = $cantidad;
            $movido = abs($despues - $antes);
            if ($movido === 0) {
                throw new RuntimeException('El stock contado coincide con el actual: no hay nada que ajustar.');
            }
        }

        Database::ejecutar('UPDATE producto_talles SET stock = :s WHERE id_producto = :p AND id_talle = :t', ['s' => $despues] + $key);
        Database::ejecutar(
            'UPDATE productos SET stock = (SELECT COALESCE(SUM(stock), 0) FROM producto_talles WHERE id_producto = :p1) WHERE id = :p2',
            ['p1' => $idProducto, 'p2' => $idProducto]
        );
        Database::ejecutar(
            'INSERT INTO movimientos (id_producto, id_talle, id_usuario, id_pedido, tipo, cantidad, stock_anterior, stock_posterior, observaciones)
             VALUES (:p, :t, :u, :ped, :tipo, :c, :a, :d, :o)',
            ['p' => $idProducto, 't' => $idTalle, 'u' => $idUsuario, 'ped' => $idPedido, 'tipo' => $tipo,
             'c' => $movido, 'a' => $antes, 'd' => $despues, 'o' => $obs !== '' ? $obs : null]
        );
        return ['anterior' => $antes, 'posterior' => $despues, 'movido' => $movido];
    }

    /** Arma WHERE y parámetros comunes de los listados de movimientos. */
    private static function filtro(array $f): array
    {
        $where = [];
        $par = [];
        if (!empty($f['tipo']) && in_array($f['tipo'], ['entrada', 'salida', 'ajuste'], true)) {
            $where[] = 'mv.tipo = :tipo';
            $par['tipo'] = $f['tipo'];
        }
        if (!empty($f['desde'])) {
            $where[] = 'mv.fecha >= :desde';
            $par['desde'] = $f['desde'] . ' 00:00:00';
        }
        if (!empty($f['hasta'])) {
            $where[] = 'mv.fecha < :hasta';
            $par['hasta'] = date('Y-m-d', strtotime($f['hasta'] . ' +1 day')) . ' 00:00:00';
        }
        if (!empty($f['q'])) {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = '(p.nombre LIKE :q1 OR p.sku LIKE :q2 OR mv.observaciones LIKE :q3)';
            $par['q1'] = $par['q2'] = $par['q3'] = $like;
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $par];
    }

    private static function desde(): string
    {
        return ' FROM movimientos mv
                 JOIN productos p ON p.id = mv.id_producto
                 JOIN talles t ON t.id = mv.id_talle
                 LEFT JOIN usuarios u ON u.id = mv.id_usuario';
    }

    public static function listar(array $f, int $pagina, int $por): array
    {
        list($cond, $par) = self::filtro($f);
        $total = (int) Database::valor('SELECT COUNT(*)' . self::desde() . $cond, $par);
        $limite = (int) $por;
        $offset = (int) (max(1, $pagina) - 1) * $limite;
        $items = Database::filas(
            'SELECT mv.*, p.nombre AS producto, p.sku, t.valor AS talle, CONCAT(u.nombre, " ", u.apellido) AS usuario'
            . self::desde() . $cond . " ORDER BY mv.fecha DESC, mv.id DESC LIMIT $limite OFFSET $offset",
            $par
        );
        return ['items' => $items, 'total' => $total];
    }

    /** Todos los movimientos del filtro (para exportar a CSV). */
    public static function todos(array $f): array
    {
        list($cond, $par) = self::filtro($f);
        return Database::filas(
            'SELECT mv.id, mv.fecha, mv.tipo, p.sku, p.nombre AS producto, t.valor AS talle, mv.cantidad,
                    mv.stock_anterior, mv.stock_posterior, CONCAT(u.nombre, " ", u.apellido) AS usuario, mv.observaciones'
            . self::desde() . $cond . ' ORDER BY mv.fecha DESC, mv.id DESC',
            $par
        );
    }

    /** Productos con más unidades movidas en el período. */
    public static function masMovidos(array $f, int $limite = 10): array
    {
        list($cond, $par) = self::filtro($f);
        return Database::filas(
            'SELECT p.id, p.sku, p.nombre, COUNT(*) AS movimientos,
                    SUM(CASE WHEN mv.tipo = "entrada" THEN mv.cantidad ELSE 0 END) AS entradas,
                    SUM(CASE WHEN mv.tipo = "salida" THEN mv.cantidad ELSE 0 END) AS salidas,
                    SUM(mv.cantidad) AS unidades'
            . self::desde() . $cond . ' GROUP BY p.id, p.sku, p.nombre ORDER BY unidades DESC, p.nombre ASC LIMIT ' . (int) $limite,
            $par
        );
    }

    /** Productos activos cuyo stock total está por debajo del mínimo. */
    public static function stockBajo(): array
    {
        return Database::filas(
            'SELECT p.id, p.sku, p.nombre,
                    (SELECT COALESCE(SUM(pt.stock), 0) FROM producto_talles pt WHERE pt.id_producto = p.id) AS stock,
                    p.stock_minimo,
                    (p.stock_minimo - (SELECT COALESCE(SUM(pt.stock), 0) FROM producto_talles pt WHERE pt.id_producto = p.id)) AS faltante,
                    pr.nombre AS proveedor, pr.telefono, pr.email
             FROM productos p JOIN proveedores pr ON pr.id = p.id_proveedor
             WHERE p.activo = 1
               AND (SELECT COALESCE(SUM(pt.stock), 0) FROM producto_talles pt WHERE pt.id_producto = p.id) < p.stock_minimo
             ORDER BY faltante DESC, p.nombre'
        );
    }
}
