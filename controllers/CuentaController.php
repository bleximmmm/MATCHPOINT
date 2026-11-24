<?php
/** Registro, inicio de sesión, perfil, pedidos y favoritos del cliente. */
class CuentaController
{
    private static function datosRegistro(): array
    {
        return [
            'nombre' => post('nombre'), 'apellido' => post('apellido'), 'email' => mb_strtolower(post('email')),
            'telefono' => post('telefono'), 'password' => isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '',
        ];
    }

    public static function login(): void
    {
        if (Auth::logueado()) {
            redirigir(Auth::esPersonal() ? aurl('dashboard') : url('cuenta'));
        }
        $email = '';
        $error = '';
        if (es_post()) {
            csrf_verificar();
            $email = mb_strtolower(post('email'));
            $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

            // Freno básico contra fuerza bruta: 5 fallos seguidos bloquean 5 minutos.
            if (!empty($_SESSION['login_bloqueo']) && $_SESSION['login_bloqueo'] > time()) {
                $error = 'Demasiados intentos. Esperá unos minutos antes de volver a probar.';
            } else {
                $u = filter_var($email, FILTER_VALIDATE_EMAIL) ? Usuario::porEmail($email) : null;
                // Mensaje único para correo o contraseña incorrectos: no revela qué cuentas existen.
                if ($u && (int) $u['activo'] && password_verify($pass, $u['password'])) {
                    if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
                        Usuario::cambiarPassword((int) $u['id'], $pass);
                    }
                    unset($_SESSION['login_fallos'], $_SESSION['login_bloqueo']);
                    $habiaDestino = !empty($_SESSION['volver']);
                    Auth::iniciar($u);
                    flash('ok', 'Hola, ' . $u['nombre'] . '. Ya iniciaste sesión.');
                    if ($habiaDestino) {
                        redirigir(Auth::destinoSeguro());
                    }
                    redirigir(Auth::esPersonal() ? aurl('dashboard') : url('cuenta'));
                }
                $_SESSION['login_fallos'] = (isset($_SESSION['login_fallos']) ? $_SESSION['login_fallos'] : 0) + 1;
                if ($_SESSION['login_fallos'] >= 5) {
                    $_SESSION['login_bloqueo'] = time() + 300;
                    $_SESSION['login_fallos'] = 0;
                }
                $error = (!empty($u) && !(int) $u['activo'])
                    ? 'Tu cuenta está dada de baja. Escribinos a contacto@matchpoint.com.ar.'
                    : 'El correo o la contraseña no son correctos.';
            }
        }
        vista('cuenta/login', ['titulo' => 'Iniciar sesión', 'email' => $email, 'error' => $error]);
    }

    public static function registro(): void
    {
        if (Auth::logueado()) {
            redirigir(url('cuenta'));
        }
        $d = ['nombre' => '', 'apellido' => '', 'email' => '', 'telefono' => '', 'password' => ''];
        $errores = [];
        if (es_post()) {
            csrf_verificar();
            $d = self::datosRegistro();
            $errores = Usuario::validar($d, true);
            $confirmar = isset($_POST['password2']) && is_string($_POST['password2']) ? $_POST['password2'] : '';
            if (!isset($errores['password']) && $d['password'] !== $confirmar) {
                $errores['password2'] = 'Las contraseñas no coinciden.';
            }
            if (!$errores) {
                try {
                    $id = Usuario::crear($d, 'cliente');
                    Auth::iniciar(Usuario::porId($id));
                    flash('ok', 'Tu cuenta está lista. Bienvenido a MATCHPOINT.');
                    redirigir(!empty($_SESSION['volver']) ? Auth::destinoSeguro() : url('cuenta'));
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $errores['email'] = 'Ya existe una cuenta con ese correo.';
                    } else {
                        throw $e;
                    }
                }
            }
        }
        $d['password'] = '';
        vista('cuenta/registro', ['titulo' => 'Crear cuenta', 'd' => $d, 'errores' => $errores]);
    }

    public static function logout(): void
    {
        if (!es_post()) {
            redirigir(url('inicio'));
        }
        csrf_verificar();
        Auth::cerrar();
        session_start();
        session_regenerate_id(true);
        flash('ok', 'Cerraste sesión.');
        redirigir(url('inicio'));
    }

    public static function perfil(): void
    {
        Auth::exigirLogin();
        $u = Usuario::porId(Auth::id());
        $d = ['nombre' => $u['nombre'], 'apellido' => $u['apellido'], 'email' => $u['email'], 'telefono' => (string) $u['telefono']];
        $errDatos = [];
        $errPass = [];

        if (es_post()) {
            csrf_verificar();
            if (post('accion') === 'datos') {
                $d = [
                    'nombre' => post('nombre'), 'apellido' => post('apellido'),
                    'email' => mb_strtolower(post('email')), 'telefono' => post('telefono'),
                ];
                $errDatos = Usuario::validar($d, false, Auth::id());
                if (!$errDatos) {
                    Usuario::actualizarPerfil(Auth::id(), $d);
                    flash('ok', 'Guardamos tus datos.');
                    redirigir(url('cuenta'));
                }
            } elseif (post('accion') === 'password') {
                $actual = isset($_POST['actual']) && is_string($_POST['actual']) ? $_POST['actual'] : '';
                $nueva = isset($_POST['nueva']) && is_string($_POST['nueva']) ? $_POST['nueva'] : '';
                $repetir = isset($_POST['repetir']) && is_string($_POST['repetir']) ? $_POST['repetir'] : '';
                if (!password_verify($actual, $u['password'])) {
                    $errPass['actual'] = 'La contraseña actual no es correcta.';
                }
                $e = Usuario::validar(['nombre' => 'xx', 'apellido' => 'xx', 'email' => $u['email'], 'telefono' => '', 'password' => $nueva], true, Auth::id());
                if (isset($e['password'])) {
                    $errPass['nueva'] = $e['password'];
                } elseif ($nueva !== $repetir) {
                    $errPass['repetir'] = 'Las contraseñas no coinciden.';
                }
                if (!$errPass) {
                    Usuario::cambiarPassword(Auth::id(), $nueva);
                    flash('ok', 'Actualizamos tu contraseña.');
                    redirigir(url('cuenta'));
                }
            }
        }
        vista('cuenta/perfil', ['titulo' => 'Mi cuenta', 'tab' => 'perfil', 'd' => $d, 'errDatos' => $errDatos, 'errPass' => $errPass]);
    }

    public static function pedidos(): void
    {
        Auth::exigirLogin();
        vista('cuenta/pedidos', ['titulo' => 'Mis pedidos', 'tab' => 'pedidos', 'pedidos' => Pedido::porUsuario(Auth::id())]);
    }

    public static function pedido(): void
    {
        Auth::exigirLogin();
        $id = ctype_digit(get('id')) ? (int) get('id') : 0;
        $pedido = $id ? Pedido::buscar($id, Auth::id()) : null;
        if (!$pedido) {
            error_pagina(404, 'No encontramos ese pedido');
        }
        vista('cuenta/pedido', ['titulo' => 'Pedido #' . $id, 'tab' => 'pedidos', 'pedido' => $pedido, 'detalle' => Pedido::detalle($id)]);
    }

    public static function favoritos(): void
    {
        Auth::exigirLogin();
        $items = Favorito::productos(Auth::id());
        vista('cuenta/favoritos', ['titulo' => 'Mis favoritos', 'tab' => 'favoritos', 'items' => $items, 'favs' => array_column($items, 'id')]);
    }

    /** Marca o desmarca un favorito (AJAX). */
    public static function favorito(): void
    {
        if (!es_post()) {
            redirigir(url('catalogo'));
        }
        if (!Auth::logueado()) {
            if (es_ajax()) {
                json_responder(['ok' => false, 'login' => url('login'), 'mensaje' => 'Iniciá sesión para guardar favoritos.'], 401);
            }
            Auth::exigirLogin();
        }
        csrf_verificar();
        $id = ctype_digit(post('id_producto')) ? (int) post('id_producto') : 0;
        if (!$id || !Producto::detalle($id)) {
            json_responder(['ok' => false, 'mensaje' => 'Producto no válido.'], 422);
        }
        $marcado = Favorito::alternar(Auth::id(), $id);
        if (es_ajax()) {
            json_responder(['ok' => true, 'favorito' => $marcado,
                'mensaje' => $marcado ? 'Lo guardamos en tus favoritos.' : 'Lo quitamos de tus favoritos.']);
        }
        redirigir(url('cuenta_favoritos'));
    }
}
