<?php
/** Pedidos de la tienda. */
class Pedido
{
    const ESTADOS = [
        'pendiente'  => 'Pendiente',
        'confirmado' => 'Confirmado',
        'enviado'    => 'Enviado',
        'entregado'  => 'Entregado',
        'cancelado'  => 'Cancelado',
    ];

    /**
     * Confirma una compra. Todo ocurre en una transacción: se leen los precios actuales,
     * se guarda el pedido y su detalle, y se descuenta el stock (bloqueando cada fila).
     * Si algún talle no alcanza, se revierte todo y se lanza RuntimeException.
     */
    public static function crear(int $idUsuario, array $items, array $envio, string $pago): int
    {
        if (!$items) {
            throw new RuntimeException('Tu carrito está vacío.');
        }
        usort($items, function ($a, $b) {
            return [$a['id'], $a['id_talle']] <=> [$b['id'], $b['id_talle']];   // orden fijo: evita deadlocks
        });

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::ejecutar(
                'INSERT INTO pedidos (id_usuario, estado, total, nombre_envio, telefono, direccion, ciudad, provincia, codigo_postal, metodo_pago)
                 VALUES (:u, :e, 0, :n, :t, :d, :c, :pr, :cp, :mp)',
                ['u' => $idUsuario, 'e' => 'pendiente', 'n' => $envio['nombre'], 't' => $envio['telefono'], 'd' => $envio['direccion'],
                 'c' => $envio['ciudad'], 'pr' => $envio['provincia'], 'cp' => $envio['codigo_postal'], 'mp' => $pago]
            );
            $idPedido = Database::ultimoId();
            $total = 0.0;

            foreach ($items as $it) {
                // Precio y estado reales, nunca los que vengan del navegador.
                $p = Database::fila(
                    'SELECT nombre, activo, ROUND(precio_venta * (100 - descuento) / 100) AS precio FROM productos WHERE id = :id',
                    ['id' => $it['id']]
                );
                if (!$p || !(int) $p['activo']) {
                    throw new RuntimeException('Uno de los productos ya no está disponible.');
                }
                try {
                    Movimiento::registrar($it['id'], $it['id_talle'], 'salida', (int) $it['cantidad'], $idUsuario, 'Venta - Pedido #' . $idPedido, $idPedido);
                } catch (RuntimeException $e) {
                    throw new RuntimeException($p['nombre'] . ' (talle ' . $it['talle'] . '): ' . $e->getMessage());
                }
                Database::ejecutar(
                    'INSERT INTO detalle_pedidos (id_pedido, id_producto, id_talle, nombre_producto, cantidad, precio_unitario)
                     VALUES (:ped, :pro, :t, :n, :c, :pu)',
                    ['ped' => $idPedido, 'pro' => $it['id'], 't' => $it['id_talle'], 'n' => $p['nombre'], 'c' => $it['cantidad'], 'pu' => $p['precio']]
                );
                Database::ejecutar('UPDATE productos SET ventas = ventas + :c WHERE id = :id', ['c' => $it['cantidad'], 'id' => $it['id']]);
                $total += (float) $p['precio'] * (int) $it['cantidad'];
            }
            Database::ejecutar('UPDATE pedidos SET total = :t WHERE id = :id', ['t' => $total, 'id' => $idPedido]);
            $pdo->commit();
            return $idPedido;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function porUsuario(int $idUsuario): array
    {
        return Database::filas(
            'SELECT p.*, (SELECT COALESCE(SUM(cantidad), 0) FROM detalle_pedidos WHERE id_pedido = p.id) AS unidades
             FROM pedidos p WHERE p.id_usuario = :u ORDER BY p.creado_en DESC, p.id DESC',
            ['u' => $idUsuario]
        );
    }

    /** Pedido con datos del cliente. Si $idUsuario > 0 solo lo devuelve si le pertenece. */
    public static function buscar(int $id, int $idUsuario = 0)
    {
        $sql = 'SELECT p.*, u.nombre AS cli_nombre, u.apellido AS cli_apellido, u.email AS cli_email, u.telefono AS cli_telefono
                FROM pedidos p JOIN usuarios u ON u.id = p.id_usuario WHERE p.id = :id';
        $par = ['id' => $id];
        if ($idUsuario > 0) {
            $sql .= ' AND p.id_usuario = :u';
            $par['u'] = $idUsuario;
        }
        return Database::fila($sql, $par);
    }

    public static function detalle(int $idPedido): array
    {
        return Database::filas(
            'SELECT d.*, t.valor AS talle, (d.cantidad * d.precio_unitario) AS subtotal,
                    (SELECT ruta FROM producto_imagenes WHERE id_producto = d.id_producto ORDER BY orden, id LIMIT 1) AS imagen
             FROM detalle_pedidos d JOIN talles t ON t.id = d.id_talle
             WHERE d.id_pedido = :id ORDER BY d.id',
            ['id' => $idPedido]
        );
    }

    public static function listar(string $estado, string $q, int $pagina, int $por): array
    {
        $where = [];
        $par = [];
        if (isset(self::ESTADOS[$estado])) {
            $where[] = 'p.estado = :e';
            $par['e'] = $estado;
        }
        if ($q !== '') {
            if (ctype_digit($q)) {
                $where[] = 'p.id = :id';
                $par['id'] = (int) $q;
            } else {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $where[] = '(u.nombre LIKE :q1 OR u.apellido LIKE :q2 OR u.email LIKE :q3)';
                $par['q1'] = $par['q2'] = $par['q3'] = $like;
            }
        }
        $cond = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = (int) Database::valor('SELECT COUNT(*) FROM pedidos p JOIN usuarios u ON u.id = p.id_usuario' . $cond, $par);
        $limite = (int) $por;
        $offset = (int) (max(1, $pagina) - 1) * $limite;
        $items = Database::filas(
            'SELECT p.id, p.estado, p.total, p.creado_en, u.nombre, u.apellido, u.email,
                    (SELECT COALESCE(SUM(cantidad), 0) FROM detalle_pedidos WHERE id_pedido = p.id) AS unidades
             FROM pedidos p JOIN usuarios u ON u.id = p.id_usuario' . $cond
            . " ORDER BY p.creado_en DESC, p.id DESC LIMIT $limite OFFSET $offset",
            $par
        );
        return ['items' => $items, 'total' => $total];
    }

    /**
     * Cambia el estado. Cancelar un pedido devuelve el stock con movimientos de entrada.
     * Un pedido cancelado ya no puede reabrirse (el stock fue liberado).
     */
    public static function cambiarEstado(int $id, string $nuevo, int $idUsuario): void
    {
        if (!isset(self::ESTADOS[$nuevo])) {
            throw new RuntimeException('Estado inválido.');
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $ped = Database::fila('SELECT id, estado FROM pedidos WHERE id = :id FOR UPDATE', ['id' => $id]);
            if (!$ped) {
                throw new RuntimeException('El pedido no existe.');
            }
            if ($ped['estado'] === 'cancelado') {
                throw new RuntimeException('Un pedido cancelado ya no puede cambiar de estado.');
            }
            if ($nuevo === 'cancelado') {
                foreach (self::detalle($id) as $d) {
                    Movimiento::registrar((int) $d['id_producto'], (int) $d['id_talle'], 'entrada', (int) $d['cantidad'], $idUsuario, 'Cancelación de pedido #' . $id, $id);
                    Database::ejecutar('UPDATE productos SET ventas = GREATEST(ventas - :c, 0) WHERE id = :p', ['c' => $d['cantidad'], 'p' => $d['id_producto']]);
                }
            }
            Database::ejecutar('UPDATE pedidos SET estado = :e WHERE id = :id', ['e' => $nuevo, 'id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // ------------------------------------------------------------ estadísticas
    public static function contar(): int
    {
        return (int) Database::valor('SELECT COUNT(*) FROM pedidos');
    }

    /** Ventas registradas: suma de pedidos que no fueron cancelados. */
    public static function ventasTotales(): float
    {
        return (float) Database::valor("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado <> 'cancelado'");
    }

    public static function pendientes(): int
    {
        return (int) Database::valor("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'");
    }

    /** Ventas de los últimos meses, incluyendo meses sin pedidos. */
    public static function ventasPorMes(int $meses = 6): array
    {
        $meses = max(1, min(12, $meses));
        $inicio = new DateTimeImmutable('first day of this month');
        $inicio = $inicio->modify('-' . ($meses - 1) . ' months');
        $porMes = [];
        $resultado = [];

        for ($i = 0; $i < $meses; $i++) {
            $mes = $inicio->modify('+' . $i . ' months');
            $clave = $mes->format('Y-m');
            $porMes[$clave] = 0.0;
            $resultado[$clave] = ['mes' => $mes->format('m/Y'), 'total' => 0.0];
        }

        $filas = Database::filas(
            "SELECT DATE_FORMAT(creado_en, '%Y-%m') AS mes, COALESCE(SUM(total), 0) AS total
             FROM pedidos
             WHERE creado_en >= :desde AND estado <> 'cancelado'
             GROUP BY DATE_FORMAT(creado_en, '%Y-%m')",
            ['desde' => $inicio->format('Y-m-d 00:00:00')]
        );
        foreach ($filas as $fila) {
            if (array_key_exists($fila['mes'], $porMes)) {
                $resultado[$fila['mes']]['total'] = (float) $fila['total'];
            }
        }

        return array_values($resultado);
    }

    /** Cantidad de pedidos por estado, incluyendo estados sin pedidos. */
    public static function cantidadPorEstado(): array
    {
        $cantidades = array_fill_keys(array_keys(self::ESTADOS), 0);
        foreach (Database::filas('SELECT estado, COUNT(*) AS cantidad FROM pedidos GROUP BY estado') as $fila) {
            if (array_key_exists($fila['estado'], $cantidades)) {
                $cantidades[$fila['estado']] = (int) $fila['cantidad'];
            }
        }

        $resultado = [];
        foreach (self::ESTADOS as $estado => $nombre) {
            $resultado[] = ['estado' => $estado, 'nombre' => $nombre, 'cantidad' => $cantidades[$estado]];
        }
        return $resultado;
    }

    public static function recientes(int $n = 5): array
    {
        return Database::filas(
            'SELECT p.id, p.estado, p.total, p.creado_en, u.nombre, u.apellido
             FROM pedidos p JOIN usuarios u ON u.id = p.id_usuario ORDER BY p.creado_en DESC, p.id DESC LIMIT ' . (int) $n
        );
    }
}
