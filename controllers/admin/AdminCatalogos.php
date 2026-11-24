<?php
/**
 * CRUD genérico para marcas, categorías y proveedores (misma mecánica, distinta configuración).
 * Cada una se relaciona 1:N con productos; no se puede eliminar una que ya tenga productos.
 */
class AdminCatalogos
{
    private static function conf(string $tipo): array
    {
        $todas = [
            'marcas' => [
                'tabla' => 'marcas', 'titulo' => 'Marcas', 'singular' => 'marca', 'fk' => 'id_marca', 'slug' => true,
                'campos' => ['nombre' => ['Nombre', 'text', 60, true]],
            ],
            'categorias' => [
                'tabla' => 'categorias', 'titulo' => 'Categorías', 'singular' => 'categoría', 'fk' => 'id_categoria', 'slug' => true,
                'campos' => ['nombre' => ['Nombre (tipo de cancha)', 'text', 80, true], 'descripcion' => ['Descripción', 'textarea', 255, false]],
            ],
            'proveedores' => [
                'tabla' => 'proveedores', 'titulo' => 'Proveedores', 'singular' => 'proveedor', 'fk' => 'id_proveedor', 'slug' => false,
                'campos' => [
                    'nombre' => ['Nombre', 'text', 120, true], 'contacto' => ['Persona de contacto', 'text', 100, false],
                    'telefono' => ['Teléfono', 'text', 30, false], 'email' => ['Correo electrónico', 'email', 120, false],
                ],
            ],
        ];
        return $todas[$tipo];
    }

    public static function ejecutar(string $tipo): void
    {
        $c = self::conf($tipo);
        switch (get('a', 'listar')) {
            case 'nuevo':    self::formulario($tipo, $c, 0); break;
            case 'editar':   self::formulario($tipo, $c, ctype_digit(get('id')) ? (int) get('id') : 0); break;
            case 'eliminar': self::eliminar($tipo, $c); break;
            default:         self::listar($tipo, $c);
        }
    }

    private static function listar(string $tipo, array $c): void
    {
        $q = mb_substr(get('q'), 0, 60);
        $pagina = ctype_digit(get('pagina')) ? (int) get('pagina') : 1;
        $par = [];
        $cond = '';
        if ($q !== '') {
            $cond = ' WHERE t.nombre LIKE :q';
            $par['q'] = '%' . addcslashes($q, '%_\\') . '%';
        }
        $total = (int) Database::valor('SELECT COUNT(*) FROM ' . $c['tabla'] . ' t' . $cond, $par);
        $pag = paginar($total, ADMIN_POR_PAGINA, $pagina);
        $limite = (int) ADMIN_POR_PAGINA;
        $offset = (int) $pag['offset'];
        $items = Database::filas(
            'SELECT t.*, (SELECT COUNT(*) FROM productos WHERE ' . $c['fk'] . ' = t.id) AS productos
             FROM ' . $c['tabla'] . ' t' . $cond . " ORDER BY t.nombre LIMIT $limite OFFSET $offset",
            $par
        );
        vista('admin/catalogo_lista', [
            'titulo' => $c['titulo'], 'seccion' => $tipo, 'tipo' => $tipo, 'c' => $c, 'items' => $items, 'pag' => $pag, 'q' => $q,
        ], 'layout/admin');
    }

    private static function formulario(string $tipo, array $c, int $id): void
    {
        $fila = null;
        if ($id) {
            $fila = Database::fila('SELECT * FROM ' . $c['tabla'] . ' WHERE id = :id', ['id' => $id]);
            if (!$fila) {
                flash('error', 'El registro no existe.');
                redirigir(aurl($tipo));
            }
        }
        $d = [];
        foreach ($c['campos'] as $campo => $cfg) {
            $d[$campo] = $fila ? (string) $fila[$campo] : '';
        }
        $errores = [];

        if (es_post()) {
            csrf_verificar();
            foreach ($c['campos'] as $campo => $cfg) {
                $d[$campo] = post($campo);
                $largo = mb_strlen($d[$campo]);
                if ($cfg[3] && $largo < 2) {
                    $errores[$campo] = 'Este campo es obligatorio (mínimo 2 caracteres).';
                } elseif ($largo > $cfg[2]) {
                    $errores[$campo] = 'Máximo ' . $cfg[2] . ' caracteres.';
                } elseif ($cfg[1] === 'email' && $d[$campo] !== '' && !filter_var($d[$campo], FILTER_VALIDATE_EMAIL)) {
                    $errores[$campo] = 'Ingresá un correo válido.';
                } elseif ($campo === 'telefono' && $d[$campo] !== '' && !preg_match('/^[0-9+()\s-]{6,30}$/', $d[$campo])) {
                    $errores[$campo] = 'Teléfono no válido.';
                }
            }
            if (!isset($errores['nombre'])) {
                $dup = Database::valor('SELECT COUNT(*) FROM ' . $c['tabla'] . ' WHERE nombre = :n AND id <> :id', ['n' => $d['nombre'], 'id' => $id]);
                if ($dup) {
                    $errores['nombre'] = 'Ya existe un registro con ese nombre.';
                }
            }
            $slug = '';
            if ($c['slug'] && !isset($errores['nombre'])) {
                $slug = slugify($d['nombre']);
                if ($slug === '' || Database::valor('SELECT COUNT(*) FROM ' . $c['tabla'] . ' WHERE slug = :s AND id <> :id', ['s' => $slug, 'id' => $id])) {
                    $errores['nombre'] = 'Ese nombre genera un identificador ya usado. Probá con otro.';
                }
            }
            if (!$errores) {
                $campos = array_keys($c['campos']);
                $par = [];
                foreach ($campos as $campo) {
                    $par[$campo] = $d[$campo] !== '' ? $d[$campo] : null;
                }
                if ($c['slug']) {
                    $campos[] = 'slug';
                    $par['slug'] = $slug;
                }
                if ($id) {
                    $set = implode(', ', array_map(function ($k) { return "$k = :$k"; }, $campos));
                    $par['id'] = $id;
                    Database::ejecutar('UPDATE ' . $c['tabla'] . " SET $set WHERE id = :id", $par);
                } else {
                    $cols = implode(', ', $campos);
                    $vals = implode(', ', array_map(function ($k) { return ":$k"; }, $campos));
                    Database::ejecutar('INSERT INTO ' . $c['tabla'] . " ($cols) VALUES ($vals)", $par);
                }
                flash('ok', 'Guardamos los datos de ' . $c['singular'] . ' correctamente.');
                redirigir(aurl($tipo));
            }
        }
        vista('admin/catalogo_form', [
            'titulo' => ($id ? 'Editar ' : 'Nueva ') . $c['singular'], 'seccion' => $tipo, 'tipo' => $tipo, 'c' => $c,
            'id' => $id, 'd' => $d, 'errores' => $errores,
        ], 'layout/admin');
    }

    private static function eliminar(string $tipo, array $c): void
    {
        Auth::exigirAdmin();
        if (!es_post()) {
            redirigir(aurl($tipo));
        }
        csrf_verificar();
        $id = ctype_digit(post('id')) ? (int) post('id') : 0;
        $usados = (int) Database::valor('SELECT COUNT(*) FROM productos WHERE ' . $c['fk'] . ' = :id', ['id' => $id]);
        if ($usados > 0) {
            flash('error', 'No se puede eliminar: tiene ' . $usados . ' producto(s) asociados. Reasignalos primero.');
        } else {
            Database::ejecutar('DELETE FROM ' . $c['tabla'] . ' WHERE id = :id', ['id' => $id]);
            flash('ok', 'Eliminamos el registro.');
        }
        redirigir(aurl($tipo));
    }
}
