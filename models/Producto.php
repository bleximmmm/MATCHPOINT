<?php
/** Consultas y operaciones sobre productos. */
class Producto
{
    /** Precio final = precio de lista menos el porcentaje de descuento. */
    const PRECIO_FINAL = 'ROUND(p.precio_venta * (100 - p.descuento) / 100)';
    const STOCK_TOTAL = '(SELECT COALESCE(SUM(pt.stock), 0) FROM producto_talles pt WHERE pt.id_producto = p.id)';

    private static function seleccion(): string
    {
        return 'SELECT p.id, p.sku, p.nombre, p.descuento, p.precio_venta, p.precio_compra, ' . self::STOCK_TOTAL . ' AS stock, p.stock_minimo,
                       p.nuevo, p.activo, p.ventas, p.creado_en, p.id_marca, p.id_categoria, p.id_proveedor,
                       m.nombre AS marca, m.slug AS marca_slug, c.nombre AS categoria, c.slug AS categoria_slug,
                       ' . self::PRECIO_FINAL . ' AS precio_final,
                       (SELECT ruta FROM producto_imagenes WHERE id_producto = p.id ORDER BY orden, id LIMIT 1) AS img1,
                       (SELECT ruta FROM producto_imagenes WHERE id_producto = p.id ORDER BY orden, id LIMIT 1 OFFSET 1) AS img2
                FROM productos p
                JOIN marcas m ON m.id = p.id_marca
                JOIN categorias c ON c.id = p.id_categoria';
    }

    /**
     * Búsqueda con filtros y orden. Devuelve ['items' => [...], 'total' => n].
     * Filtros: q, marcas[], categorias[], precio_min, precio_max, talle, disponible, ofertas, nuevo,
     *          estado (solo panel: activo|inactivo|bajo|agotado), id_categoria (solo panel).
     */
    public static function buscar(array $f, string $orden, int $pagina, int $por, bool $soloActivos = true): array
    {
        $where = [];
        $par = [];

        if ($soloActivos) {
            $where[] = 'p.activo = 1';
        }
        if (!empty($f['q'])) {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = '(p.nombre LIKE :q1 OR m.nombre LIKE :q2 OR p.sku LIKE :q3 OR c.nombre LIKE :q4)';
            $par['q1'] = $par['q2'] = $par['q3'] = $par['q4'] = $like;
        }
        if (!empty($f['marcas'])) {
            $ph = [];
            foreach ($f['marcas'] as $i => $slug) {
                $ph[] = ':m' . $i;
                $par['m' . $i] = $slug;
            }
            $where[] = 'm.slug IN (' . implode(',', $ph) . ')';
        }
        if (!empty($f['categorias'])) {
            $ph = [];
            foreach ($f['categorias'] as $i => $slug) {
                $ph[] = ':c' . $i;
                $par['c' . $i] = $slug;
            }
            $where[] = 'c.slug IN (' . implode(',', $ph) . ')';
        }
        if (!empty($f['id_categoria'])) {
            $where[] = 'p.id_categoria = :idcat';
            $par['idcat'] = (int) $f['id_categoria'];
        }
        if (isset($f['precio_min']) && $f['precio_min'] !== '') {
            $where[] = self::PRECIO_FINAL . ' >= :pmin';
            $par['pmin'] = (int) $f['precio_min'];
        }
        if (isset($f['precio_max']) && $f['precio_max'] !== '') {
            $where[] = self::PRECIO_FINAL . ' <= :pmax';
            $par['pmax'] = (int) $f['precio_max'];
        }
        if (!empty($f['talle'])) {
            $where[] = 'EXISTS (SELECT 1 FROM producto_talles pt JOIN talles t ON t.id = pt.id_talle
                                WHERE pt.id_producto = p.id AND t.valor = :talle AND pt.stock > 0)';
            $par['talle'] = (int) $f['talle'];
        }
        if (!empty($f['disponible'])) {
            $where[] = self::STOCK_TOTAL . ' > 0';
        }
        if (!empty($f['ofertas'])) {
            $where[] = 'p.descuento > 0';
        }
        if (!empty($f['nuevo'])) {
            $where[] = 'p.nuevo = 1';
        }
        if (!empty($f['estado'])) {
            switch ($f['estado']) {
                case 'activo':   $where[] = 'p.activo = 1'; break;
                case 'inactivo': $where[] = 'p.activo = 0'; break;
                case 'bajo':     $where[] = self::STOCK_TOTAL . ' < p.stock_minimo'; break;
                case 'agotado':  $where[] = self::STOCK_TOTAL . ' = 0'; break;
            }
        }

        $ordenes = [
            'precio_asc'  => 'precio_final ASC, p.id ASC',
            'precio_desc' => 'precio_final DESC, p.id ASC',
            'recientes'   => 'p.creado_en DESC, p.id DESC',
            'populares'   => 'p.ventas DESC, p.id ASC',
            'nombre'      => 'p.nombre ASC',
            'stock'       => 'stock ASC, p.nombre ASC',
        ];
        $orderBy = isset($ordenes[$orden]) ? $ordenes[$orden] : $ordenes['recientes'];
        $cond = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) Database::valor(
            'SELECT COUNT(*) FROM productos p
             JOIN marcas m ON m.id = p.id_marca JOIN categorias c ON c.id = p.id_categoria' . $cond,
            $par
        );
        $limite = (int) $por;
        $offset = (int) (max(1, $pagina) - 1) * $limite;   // enteros casteados: seguros en LIMIT
        $items = Database::filas(self::seleccion() . $cond . ' ORDER BY ' . $orderBy . " LIMIT $limite OFFSET $offset", $par);

        return ['items' => $items, 'total' => $total];
    }

    public static function destacados(int $n = 8): array
    {
        return Database::filas(self::seleccion() . ' WHERE p.activo = 1 ORDER BY p.ventas DESC, p.id ASC LIMIT ' . (int) $n);
    }

    /** Producto con la oferta más fuerte (para el banner promocional). */
    public static function mayorOferta()
    {
        return Database::fila(self::seleccion() . ' WHERE p.activo = 1 AND p.descuento > 0 ORDER BY p.descuento DESC, p.id ASC LIMIT 1');
    }

    /** Último producto cargado de una categoría (imagen de cada acceso del inicio). */
    public static function ultimoDeCategoria(string $slug)
    {
        return Database::fila(self::seleccion() . ' WHERE p.activo = 1 AND c.slug = :s AND ' . self::STOCK_TOTAL . ' > 0 ORDER BY p.creado_en DESC LIMIT 1', ['s' => $slug]);
    }

    public static function ultimoLanzamiento()
    {
        return Database::fila(self::seleccion() . ' WHERE p.activo = 1 AND p.nuevo = 1 ORDER BY p.creado_en DESC LIMIT 1');
    }

    public static function porIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $ph = [];
        $par = [];
        foreach (array_values($ids) as $i => $id) {
            $ph[] = ':i' . $i;
            $par['i' . $i] = (int) $id;
        }
        return Database::filas(self::seleccion() . ' WHERE p.activo = 1 AND p.id IN (' . implode(',', $ph) . ') ORDER BY p.nombre', $par);
    }

    /** Ficha completa de un producto. */
    public static function detalle(int $id, bool $soloActivos = true)
    {
        $sql = 'SELECT p.*, ' . self::STOCK_TOTAL . ' AS stock_actual, m.nombre AS marca, m.slug AS marca_slug, c.nombre AS categoria, c.slug AS categoria_slug,
                       pr.nombre AS proveedor, ' . self::PRECIO_FINAL . ' AS precio_final
                FROM productos p
                JOIN marcas m ON m.id = p.id_marca
                JOIN categorias c ON c.id = p.id_categoria
                JOIN proveedores pr ON pr.id = p.id_proveedor
                WHERE p.id = :id' . ($soloActivos ? ' AND p.activo = 1' : '');
        return Database::fila($sql, ['id' => $id]);
    }

    public static function imagenes(int $id): array
    {
        return Database::filas('SELECT id, ruta, orden FROM producto_imagenes WHERE id_producto = :id ORDER BY orden, id', ['id' => $id]);
    }

    /** Todos los talles con el stock de este producto (0 si no lo tiene cargado). */
    public static function talles(int $id): array
    {
        return Database::filas(
            'SELECT t.id, t.valor, COALESCE(pt.stock, 0) AS stock
             FROM talles t LEFT JOIN producto_talles pt ON pt.id_talle = t.id AND pt.id_producto = :id
             ORDER BY t.valor',
            ['id' => $id]
        );
    }

    public static function relacionados(array $p, int $n = 4): array
    {
        return Database::filas(
            self::seleccion() . ' WHERE p.activo = 1 AND p.id <> :id AND (p.id_categoria = :c1 OR p.id_marca = :m)
             ORDER BY (p.id_categoria = :c2) DESC, p.ventas DESC LIMIT ' . (int) $n,
            ['id' => $p['id'], 'c1' => $p['id_categoria'], 'm' => $p['id_marca'], 'c2' => $p['id_categoria']]
        );
    }

    // ------------------------------------------------------------------ panel
    public static function skuExiste(string $sku, int $excluirId = 0): bool
    {
        return (bool) Database::valor('SELECT COUNT(*) FROM productos WHERE sku = :s AND id <> :id', ['s' => $sku, 'id' => $excluirId]);
    }

    /**
     * Crea o actualiza un producto con su stock por talle e imágenes, todo en una transacción.
     * Los cambios de stock se registran como movimientos (entrada inicial / ajuste).
     * $stock = [id_talle => cantidad]; $nuevasRutas = rutas ya guardadas en disco; $borrar = ids de imágenes.
     */
    public static function guardar($id, array $d, array $stock, array $nuevasRutas, array $borrar, int $idUsuario): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $par = [
                'sku' => $d['sku'], 'nombre' => $d['nombre'], 'descripcion' => $d['descripcion'],
                'marca' => $d['id_marca'], 'cat' => $d['id_categoria'], 'prov' => $d['id_proveedor'],
                'compra' => $d['precio_compra'], 'venta' => $d['precio_venta'], 'desc' => $d['descuento'],
                'min' => $d['stock_minimo'], 'material' => $d['material'], 'suela' => $d['suela'],
                'color' => $d['color'], 'carac' => $d['caracteristicas'], 'nuevo' => $d['nuevo'], 'activo' => $d['activo'],
            ];
            if ($id) {
                $par['id'] = $id;
                Database::ejecutar(
                    'UPDATE productos SET sku=:sku, nombre=:nombre, descripcion=:descripcion, id_marca=:marca,
                            id_categoria=:cat, id_proveedor=:prov, precio_compra=:compra, precio_venta=:venta,
                            descuento=:desc, stock_minimo=:min, material=:material, suela=:suela, color=:color,
                            caracteristicas=:carac, nuevo=:nuevo, activo=:activo
                     WHERE id=:id',
                    $par
                );
                $obs = 'Ajuste desde la edición del producto';
            } else {
                Database::ejecutar(
                    'INSERT INTO productos (sku, nombre, descripcion, id_marca, id_categoria, id_proveedor, precio_compra,
                            precio_venta, descuento, stock, stock_minimo, material, suela, color, caracteristicas, nuevo, activo)
                     VALUES (:sku, :nombre, :descripcion, :marca, :cat, :prov, :compra, :venta, :desc, 0, :min,
                             :material, :suela, :color, :carac, :nuevo, :activo)',
                    $par
                );
                $id = Database::ultimoId();
                $obs = 'Stock inicial al crear el producto';
            }

            // Stock por talle: solo se registra movimiento si cambió el valor.
            $actual = [];
            foreach (self::talles($id) as $t) {
                $actual[(int) $t['id']] = (int) $t['stock'];
            }
            foreach ($stock as $idTalle => $cant) {
                $idTalle = (int) $idTalle;
                $cant = (int) $cant;
                $antes = isset($actual[$idTalle]) ? $actual[$idTalle] : 0;
                if ($cant === $antes) {
                    continue;
                }
                if ($cant > $antes && $antes === 0 && $obs === 'Stock inicial al crear el producto') {
                    Movimiento::registrar($id, $idTalle, 'entrada', $cant, $idUsuario, $obs);
                } else {
                    Movimiento::registrar($id, $idTalle, 'ajuste', $cant, $idUsuario, $obs);
                }
            }

            // Imágenes
            foreach ($borrar as $idImg) {
                $ruta = Database::valor('SELECT ruta FROM producto_imagenes WHERE id = :i AND id_producto = :p', ['i' => (int) $idImg, 'p' => $id]);
                if ($ruta) {
                    Database::ejecutar('DELETE FROM producto_imagenes WHERE id = :i', ['i' => (int) $idImg]);
                    borrar_imagen_subida($ruta);
                }
            }
            $orden = (int) Database::valor('SELECT COALESCE(MAX(orden), 0) FROM producto_imagenes WHERE id_producto = :p', ['p' => $id]);
            foreach ($nuevasRutas as $ruta) {
                $orden++;
                Database::ejecutar('INSERT INTO producto_imagenes (id_producto, ruta, orden) VALUES (:p, :r, :o)', ['p' => $id, 'r' => $ruta, 'o' => $orden]);
            }
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Elimina un producto. Si ya tiene pedidos o movimientos (claves foráneas),
     * no se puede borrar sin perder historial: se da de baja (inactivo). Devuelve 'eliminado' o 'baja'.
     */
    public static function eliminar(int $id): string
    {
        $rutas = array_column(self::imagenes($id), 'ruta');
        try {
            Database::ejecutar('DELETE FROM productos WHERE id = :id', ['id' => $id]);
            foreach ($rutas as $r) {
                borrar_imagen_subida($r);
            }
            return 'eliminado';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Database::ejecutar('UPDATE productos SET activo = 0 WHERE id = :id', ['id' => $id]);
                return 'baja';
            }
            throw $e;
        }
    }

    public static function contar(): int
    {
        return (int) Database::valor('SELECT COUNT(*) FROM productos');
    }
}
