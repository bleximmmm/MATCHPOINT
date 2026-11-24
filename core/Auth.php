<?php
/**
 * Autenticación y permisos basados en sesiones de PHP.
 * Roles: cliente, operador, admin.
 */
class Auth
{
    public static function usuario()
    {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public static function logueado(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function id(): int
    {
        return self::logueado() ? (int) $_SESSION['user']['id'] : 0;
    }

    public static function rol(): string
    {
        return self::logueado() ? $_SESSION['user']['rol'] : '';
    }

    public static function esAdmin(): bool
    {
        return self::rol() === 'admin';
    }

    /** Personal del negocio: administrador u operador. */
    public static function esPersonal(): bool
    {
        return in_array(self::rol(), ['admin', 'operador'], true);
    }

    public static function iniciar(array $u): void
    {
        session_regenerate_id(true);           // evita fijación de sesión
        $_SESSION['user'] = [
            'id'       => (int) $u['id'],
            'nombre'   => $u['nombre'],
            'apellido' => $u['apellido'],
            'email'    => $u['email'],
            'rol'      => $u['rol'],
        ];
        $_SESSION['ultima_actividad'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $c = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
        }
        session_destroy();
    }

    /**
     * Se ejecuta en cada petición: vence sesiones inactivas y vuelve a leer
     * rol y estado del usuario, así un cambio de permisos o una baja aplican enseguida.
     */
    public static function refrescar(): void
    {
        if (!self::logueado()) {
            return;
        }
        $limite = SESION_MINUTOS * 60;
        if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad']) > $limite) {
            self::cerrar();
            session_start();
            session_regenerate_id(true);
            flash('info', 'Tu sesión expiró por inactividad. Volvé a iniciar sesión.');
            return;
        }
        $_SESSION['ultima_actividad'] = time();
        $u = Usuario::porId(self::id());
        if (!$u || !(int) $u['activo']) {
            self::cerrar();
            session_start();
            session_regenerate_id(true);
            flash('error', 'Tu cuenta no está disponible. Contactanos si creés que es un error.');
            return;
        }
        $_SESSION['user']['rol'] = $u['rol'];
        $_SESSION['user']['nombre'] = $u['nombre'];
        $_SESSION['user']['apellido'] = $u['apellido'];
    }

    public static function exigirLogin(): void
    {
        if (!self::logueado()) {
            flash('info', 'Iniciá sesión para continuar.');
            $_SESSION['volver'] = $_SERVER['REQUEST_URI'] ?? '';
            redirigir(url('login'));
        }
    }

    public static function exigirPersonal(): void
    {
        self::exigirLogin();
        if (!self::esPersonal()) {
            http_response_code(403);
            flash('error', 'No tenés permisos para entrar al panel de administración.');
            redirigir(url('inicio'));
        }
    }

    public static function exigirAdmin(): void
    {
        self::exigirPersonal();
        if (!self::esAdmin()) {
            flash('error', 'Esta sección es solo para administradores.');
            redirigir(aurl('dashboard'));
        }
    }

    /** Devuelve una URL interna segura para volver después del login (evita open redirect). */
    public static function destinoSeguro(): string
    {
        $destino = $_SESSION['volver'] ?? '';
        unset($_SESSION['volver']);
        if (is_string($destino) && $destino !== '' && $destino[0] === '/' && substr($destino, 0, 2) !== '//'
            && strpos($destino, '\\') === false && strpos($destino, BASE_URL . '/') === 0) {
            return $destino;
        }
        return url('cuenta');
    }
}
