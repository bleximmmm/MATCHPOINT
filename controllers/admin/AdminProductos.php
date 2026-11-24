<?php
/** Alta, baja, modificación y listado de productos (con stock por talle e imágenes). */
class AdminProductos
{
    public static function ejecutar(): void
    {
        $accion = get('a', 'listar');
        switch ($accion) {
            case 'nuevo':    self::formulario(0); break;
            case 'editar':   self::formulario(ctype_digit(get('id')) ? (int) get('id') : 0); break;
            case 'eliminar': self::eliminar(); break;
            default:         self::listar();
        }
    }

    private static function listar(): void
    {
        $f = [
            'q'            => mb_substr(get('q'), 0, 60),
            'id_categoria' => ctype_digit(get('categoria')) ? (int) get('categoria') : 0,
            'estado'       => in_array(get('estado'), ['activo', 'inactivo', 'bajo', 'agotado'], true) ? get('estado') : '',
        ];
        $orden = in_array(get('orden'), ['nombre', 'stock', 'recientes', 'precio_asc', 'precio_desc'], true) ? get('orden') : 'recientes';
        $pagina = ctype_digit(get('pagina')) ? (int) get('pagina') : 1;
        $r = Producto::buscar($f, $orden, $pagina, ADMIN_POR_PAGINA, false);
        $pag = paginar($r['total'], ADMIN_POR_PAGINA, $pagina);
        if ($pag['actual'] !== $pagina) {
            $r = Producto::buscar($f, $orden, $pag['actual'], ADMIN_POR_PAGINA, false);
        }
        vista('admin/productos_lista', [
            'titulo' => 'Productos', 'seccion' => 'productos', 'items' => $r['items'], 'pag' => $pag,
            'f' => $f, 'orden' => $orden, 'categorias' => Catalogo::categorias(),
        ], 'layout/admin');
    }

    private static function formulario(int $id): void
    {
        $producto = null;
        if ($id) {
            $producto = Producto::detalle($id, false);
            if (!$producto) {
                flash('error', 'El producto no existe.');
                redirigir(aurl('productos'));
            }
        }
        $d = $producto ? [
            'sku' => $producto['sku'], 'nombre' => $producto['nombre'], 'descripcion' => (string) $producto['descripcion'],
            'id_marca' => $producto['id_marca'], 'id_categoria' => $producto['id_categoria'], 'id_proveedor' => $producto['id_proveedor'],
            'precio_compra' => $producto['precio_compra'] + 0, 'precio_venta' => $producto['precio_venta'] + 0, 'descuento' => $producto['descuento'],
            'stock_minimo' => $producto['stock_minimo'], 'material' => (string) $producto['material'], 'suela' => (string) $producto['suela'],
            'color' => (string) $producto['color'], 'caracteristicas' => (string) $producto['caracteristicas'],
            'nuevo' => (int) $producto['nuevo'], 'activo' => (int) $producto['activo'],
        ] : [
            'sku' => '', 'nombre' => '', 'descripcion' => '', 'id_marca' => '', 'id_categoria' => '', 'id_proveedor' => '',
            'precio_compra' => '', 'precio_venta' => '', 'descuento' => 0, 'stock_minimo' => 5, 'material' => '', 'suela' => '',
            'color' => '', 'caracteristicas' => '', 'nuevo' => 0, 'activo' => 1,
        ];
        $talles = Producto::talles($id);
        $stock = [];
        foreach ($talles as $t) {
            $stock[$t['id']] = (int) $t['stock'];
        }
        $errores = [];

        if (es_post()) {
            csrf_verificar();
            $d = self::leer();
            $stock = self::leerStock($talles);
            $errores = self::validar($d, $id);
            $rutas = [];
            if (!$errores) {
                try {
                    $archivos = archivos_multiples('imagenes');
                    if (count($archivos) > 8) {
                        throw new RuntimeException('Podés subir hasta 8 imágenes por vez.');
                    }
                    foreach ($archivos as $a) {
                        $rutas[] = guardar_imagen($a);
                    }
                    $borrar = lista_enteros(isset($_POST['borrar_img']) ? $_POST['borrar_img'] : []);
                    $nuevoId = Producto::guardar($id ?: null, $d, $stock, $rutas, $borrar, Auth::id());
                    flash('ok', $id ? 'Guardamos los cambios del producto.' : 'Creamos el producto.');
                    redirigir(aurl('productos', ['a' => 'editar', 'id' => $nuevoId]));
                } catch (RuntimeException $e) {
                    foreach ($rutas as $r) { borrar_imagen_subida($r); }   // no dejar archivos huérfanos
                    $errores['imagenes'] = $e->getMessage();
                } catch (PDOException $e) {
                    foreach ($rutas as $r) { borrar_imagen_subida($r); }
                    if ($e->getCode() === '23000') {
                        $errores['sku'] = 'Ya existe otro producto con ese SKU.';
                    } else {
                        throw $e;
                    }
                }
            }
        }
        vista('admin/productos_form', [
            'titulo' => $id ? 'Editar producto' : 'Nuevo producto', 'seccion' => 'productos',
            'id' => $id, 'd' => $d, 'errores' => $errores, 'stock' => $stock, 'talles' => $talles,
            'imagenes' => $id ? Producto::imagenes($id) : [],
            'marcas' => Catalogo::marcas(), 'categorias' => Catalogo::categorias(), 'proveedores' => Catalogo::proveedores(),
        ], 'layout/admin');
    }

    private static function numero(string $v)
    {
        $v = str_replace(',', '.', trim($v));
        return is_numeric($v) ? (float) $v : null;
    }

    private static function leer(): array
    {
        return [
            'sku'             => strtoupper(post('sku')),
            'nombre'          => post('nombre'),
            'descripcion'     => post('descripcion'),
            'id_marca'        => ctype_digit(post('id_marca')) ? (int) post('id_marca') : 0,
            'id_categoria'    => ctype_digit(post('id_categoria')) ? (int) post('id_categoria') : 0,
            'id_proveedor'    => ctype_digit(post('id_proveedor')) ? (int) post('id_proveedor') : 0,
            'precio_compra'   => post('precio_compra'),
            'precio_venta'    => post('precio_venta'),
            'descuento'       => post('descuento', '0'),
            'stock_minimo'    => post('stock_minimo', '0'),
            'material'        => post('material'),
            'suela'           => post('suela'),
            'color'           => post('color'),
            'caracteristicas' => post('caracteristicas'),
            'nuevo'           => isset($_POST['nuevo']) ? 1 : 0,
            'activo'          => isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private static function leerStock(array $talles): array
    {
        $out = [];
        $enviado = isset($_POST['stock']) && is_array($_POST['stock']) ? $_POST['stock'] : [];
        foreach ($talles as $t) {
            $v = isset($enviado[$t['id']]) ? (string) $enviado[$t['id']] : '0';
            $out[$t['id']] = ctype_digit($v) ? min((int) $v, 9999) : -1;   // -1 marca un valor inválido
        }
        return $out;
    }

    /** Valida y normaliza (modifica $d con los tipos finales). */
    private static function validar(array &$d, int $id): array
    {
        $e = [];
        if (!preg_match('/^[A-Z0-9-]{3,30}$/', $d['sku'])) {
            $e['sku'] = 'El SKU debe tener 3 a 30 caracteres: letras, números y guiones.';
        } elseif (Producto::skuExiste($d['sku'], $id)) {
            $e['sku'] = 'Ya existe otro producto con ese SKU.';
        }
        if (mb_strlen($d['nombre']) < 3 || mb_strlen($d['nombre']) > 120) {
            $e['nombre'] = 'El nombre debe tener entre 3 y 120 caracteres.';
        }
        if (mb_strlen($d['descripcion']) > 3000) {
            $e['descripcion'] = 'La descripción no puede superar los 3000 caracteres.';
        }
        if (mb_strlen($d['caracteristicas']) > 2000) {
            $e['caracteristicas'] = 'Las características no pueden superar los 2000 caracteres.';
        }
        foreach (['material' => 120, 'suela' => 120, 'color' => 80] as $campo => $max) {
            if (mb_strlen($d[$campo]) > $max) {
                $e[$campo] = 'Máximo ' . $max . ' caracteres.';
            }
        }
        $tablas = ['id_marca' => 'marcas', 'id_categoria' => 'categorias', 'id_proveedor' => 'proveedores'];
        foreach ($tablas as $campo => $tabla) {
            if (!$d[$campo] || !Database::valor("SELECT COUNT(*) FROM $tabla WHERE id = :id", ['id' => $d[$campo]])) {
                $e[$campo] = 'Elegí una opción de la lista.';
            }
        }
        $venta = self::numero((string) $d['precio_venta']);
        if ($venta === null || $venta <= 0 || $venta > 99999999) {
            $e['precio_venta'] = 'Ingresá un precio de venta mayor a cero.';
        }
        $compra = self::numero((string) $d['precio_compra']);
        if ($compra === null || $compra < 0 || $compra > 99999999) {
            $e['precio_compra'] = 'Ingresá el precio de compra (puede ser 0).';
        }
        if (!ctype_digit((string) $d['descuento']) || (int) $d['descuento'] > 90) {
            $e['descuento'] = 'El descuento es un porcentaje entero entre 0 y 90.';
        }
        if (!ctype_digit((string) $d['stock_minimo']) || (int) $d['stock_minimo'] > 9999) {
            $e['stock_minimo'] = 'Ingresá un stock mínimo entre 0 y 9999.';
        }
        $enviado = isset($_POST['stock']) && is_array($_POST['stock']) ? $_POST['stock'] : [];
        foreach ($enviado as $v) {
            if (!ctype_digit((string) $v) || (int) $v > 9999) {
                $e['stock'] = 'El stock de cada talle debe ser un número entero entre 0 y 9999.';
                break;
            }
        }
        if (!$e) {
            $d['precio_venta'] = $venta;
            $d['precio_compra'] = $compra;
            $d['descuento'] = (int) $d['descuento'];
            $d['stock_minimo'] = (int) $d['stock_minimo'];
        }
        return $e;
    }

    private static function eliminar(): void
    {
        Auth::exigirAdmin();
        if (!es_post()) {
            redirigir(aurl('productos'));
        }
        csrf_verificar();
        $id = ctype_digit(post('id')) ? (int) post('id') : 0;
        if ($id && Producto::detalle($id, false)) {
            $r = Producto::eliminar($id);
            flash('ok', $r === 'eliminado'
                ? 'Eliminamos el producto.'
                : 'El producto tiene pedidos o movimientos asociados, así que no se puede borrar. Lo dimos de baja (inactivo).');
        }
        redirigir(aurl('productos'));
    }
}
