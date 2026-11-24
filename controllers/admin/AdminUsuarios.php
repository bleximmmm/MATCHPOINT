<?php
/** Gestión de usuarios y permisos (solo administradores). */
class AdminUsuarios
{
    public static function ejecutar(): void
    {
        Auth::exigirAdmin();
        switch (get('a', 'listar')) {
            case 'nuevo':  self::formulario(0); break;
            case 'editar': self::formulario(ctype_digit(get('id')) ? (int) get('id') : 0); break;
            case 'estado': self::estado(); break;
            default:       self::listar();
        }
    }

    private static function listar(): void
    {
        $q = mb_substr(get('q'), 0, 60);
        $rol = isset(Usuario::ROLES[get('rol')]) ? get('rol') : '';
        $pagina = ctype_digit(get('pagina')) ? (int) get('pagina') : 1;
        $r = Usuario::listar($q, $rol, $pagina, ADMIN_POR_PAGINA);
        $pag = paginar($r['total'], ADMIN_POR_PAGINA, $pagina);
        if ($pag['actual'] !== $pagina) {
            $r = Usuario::listar($q, $rol, $pag['actual'], ADMIN_POR_PAGINA);
        }
        vista('admin/usuarios_lista', [
            'titulo' => 'Usuarios', 'seccion' => 'usuarios', 'items' => $r['items'], 'pag' => $pag, 'q' => $q, 'rol' => $rol,
        ], 'layout/admin');
    }

    private static function formulario(int $id): void
    {
        $u = null;
        if ($id) {
            $u = Usuario::porId($id);
            if (!$u) {
                flash('error', 'El usuario no existe.');
                redirigir(aurl('usuarios'));
            }
        }
        $d = $u ? ['nombre' => $u['nombre'], 'apellido' => $u['apellido'], 'email' => $u['email'], 'telefono' => (string) $u['telefono'], 'password' => '']
                : ['nombre' => '', 'apellido' => '', 'email' => '', 'telefono' => '', 'password' => ''];
        $rol = $u ? $u['rol'] : 'operador';
        $activo = $u ? (int) $u['activo'] : 1;
        $errores = [];

        if (es_post()) {
            csrf_verificar();
            $d = [
                'nombre' => post('nombre'), 'apellido' => post('apellido'), 'email' => mb_strtolower(post('email')),
                'telefono' => post('telefono'), 'password' => isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '',
            ];
            $rol = isset(Usuario::ROLES[post('rol')]) ? post('rol') : 'cliente';
            $activo = isset($_POST['activo']) ? 1 : 0;
            $errores = Usuario::validar($d, !$id, $id);

            if ($id && $id === Auth::id() && ($rol !== 'admin' || !$activo)) {
                $errores['rol'] = 'No podés quitarte el rol de administrador ni darte de baja a vos mismo.';
            }
            if ($id && $u['rol'] === 'admin' && ($rol !== 'admin' || !$activo) && Usuario::cantidadAdminsActivos($id) < 1) {
                $errores['rol'] = 'Tiene que quedar al menos un administrador activo.';
            }
            if (!$errores) {
                if ($id) {
                    Usuario::actualizarAdmin($id, $d, $rol, $activo);
                    if ($d['password'] !== '') {
                        Usuario::cambiarPassword($id, $d['password']);
                    }
                    flash('ok', 'Guardamos los cambios del usuario.');
                } else {
                    $nuevo = Usuario::crear($d, $rol);
                    if (!$activo) {
                        Database::ejecutar('UPDATE usuarios SET activo = 0 WHERE id = :id', ['id' => $nuevo]);
                    }
                    flash('ok', 'Creamos el usuario.');
                }
                redirigir(aurl('usuarios'));
            }
        }
        $d['password'] = '';
        vista('admin/usuarios_form', [
            'titulo' => $id ? 'Editar usuario' : 'Nuevo usuario', 'seccion' => 'usuarios',
            'id' => $id, 'd' => $d, 'rol' => $rol, 'activo' => $activo, 'errores' => $errores,
        ], 'layout/admin');
    }

    /** Da de baja o reactiva una cuenta (baja lógica: se conserva el historial de pedidos). */
    private static function estado(): void
    {
        if (!es_post()) {
            redirigir(aurl('usuarios'));
        }
        csrf_verificar();
        $id = ctype_digit(post('id')) ? (int) post('id') : 0;
        $u = $id ? Usuario::porId($id) : null;
        if (!$u) {
            flash('error', 'El usuario no existe.');
        } elseif ($id === Auth::id()) {
            flash('error', 'No podés darte de baja a vos mismo.');
        } elseif ($u['rol'] === 'admin' && (int) $u['activo'] && Usuario::cantidadAdminsActivos($id) < 1) {
            flash('error', 'Tiene que quedar al menos un administrador activo.');
        } else {
            $nuevo = (int) $u['activo'] ? 0 : 1;
            Database::ejecutar('UPDATE usuarios SET activo = :a WHERE id = :id', ['a' => $nuevo, 'id' => $id]);
            flash('ok', $nuevo ? 'Reactivamos la cuenta.' : 'Dimos de baja la cuenta.');
        }
        redirigir(aurl('usuarios'));
    }
}
