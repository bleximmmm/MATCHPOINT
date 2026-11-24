<?php
/** Usuarios: clientes, operadores y administradores. */
class Usuario
{
    const ROLES = ['cliente' => 'Cliente', 'operador' => 'Operador', 'admin' => 'Administrador'];

    public static function porId(int $id)
    {
        return Database::fila('SELECT * FROM usuarios WHERE id = :id', ['id' => $id]);
    }

    public static function porEmail(string $email)
    {
        return Database::fila('SELECT * FROM usuarios WHERE email = :e', ['e' => $email]);
    }

    public static function emailExiste(string $email, int $excluirId = 0): bool
    {
        return (bool) Database::valor('SELECT COUNT(*) FROM usuarios WHERE email = :e AND id <> :id', ['e' => $email, 'id' => $excluirId]);
    }

    /**
     * Valida los datos de un usuario. Devuelve un array [campo => mensaje].
     * $conPassword: exige contraseña (alta). En edición solo se valida si se envió una nueva.
     */
    public static function validar(array $d, bool $conPassword, int $excluirId = 0): array
    {
        $err = [];
        foreach (['nombre' => 'El nombre', 'apellido' => 'El apellido'] as $campo => $etq) {
            $len = mb_strlen($d[$campo]);
            if ($len < 2 || $len > 60) {
                $err[$campo] = $etq . ' debe tener entre 2 y 60 caracteres.';
            } elseif (!preg_match('/^[\p{L}][\p{L} \'.-]*$/u', $d[$campo])) {
                $err[$campo] = $etq . ' contiene caracteres no permitidos.';
            }
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 120) {
            $err['email'] = 'Ingresá un correo electrónico válido.';
        } elseif (self::emailExiste($d['email'], $excluirId)) {
            $err['email'] = 'Ya existe una cuenta con ese correo.';
        }
        if ($d['telefono'] !== '' && !preg_match('/^[0-9+()\s-]{6,30}$/', $d['telefono'])) {
            $err['telefono'] = 'El teléfono solo puede tener números, espacios, + ( ) y guiones.';
        }
        $pass = isset($d['password']) ? $d['password'] : '';
        if ($conPassword || $pass !== '') {
            if (strlen($pass) < 8 || strlen($pass) > 72) {
                $err['password'] = 'La contraseña debe tener entre 8 y 72 caracteres.';
            } elseif (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
                $err['password'] = 'La contraseña debe combinar letras y números.';
            }
        }
        return $err;
    }

    public static function crear(array $d, string $rol = 'cliente'): int
    {
        Database::ejecutar(
            'INSERT INTO usuarios (nombre, apellido, email, password, telefono, rol, activo)
             VALUES (:n, :a, :e, :p, :t, :r, 1)',
            ['n' => $d['nombre'], 'a' => $d['apellido'], 'e' => $d['email'],
             'p' => password_hash($d['password'], PASSWORD_DEFAULT),
             't' => $d['telefono'] !== '' ? $d['telefono'] : null, 'r' => $rol]
        );
        return Database::ultimoId();
    }

    public static function actualizarPerfil(int $id, array $d): void
    {
        Database::ejecutar(
            'UPDATE usuarios SET nombre = :n, apellido = :a, email = :e, telefono = :t WHERE id = :id',
            ['n' => $d['nombre'], 'a' => $d['apellido'], 'e' => $d['email'],
             't' => $d['telefono'] !== '' ? $d['telefono'] : null, 'id' => $id]
        );
    }

    public static function cambiarPassword(int $id, string $nueva): void
    {
        Database::ejecutar('UPDATE usuarios SET password = :p WHERE id = :id', ['p' => password_hash($nueva, PASSWORD_DEFAULT), 'id' => $id]);
    }

    /** Edición desde el panel: datos, rol y estado. */
    public static function actualizarAdmin(int $id, array $d, string $rol, int $activo): void
    {
        self::actualizarPerfil($id, $d);
        Database::ejecutar('UPDATE usuarios SET rol = :r, activo = :a WHERE id = :id', ['r' => $rol, 'a' => $activo, 'id' => $id]);
    }

    public static function cantidadAdminsActivos(int $excluirId = 0): int
    {
        return (int) Database::valor("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1 AND id <> :id", ['id' => $excluirId]);
    }

    public static function listar(string $q, string $rol, int $pagina, int $por): array
    {
        $where = [];
        $par = [];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(u.nombre LIKE :q1 OR u.apellido LIKE :q2 OR u.email LIKE :q3)';
            $par['q1'] = $par['q2'] = $par['q3'] = $like;
        }
        if (isset(self::ROLES[$rol])) {
            $where[] = 'u.rol = :rol';
            $par['rol'] = $rol;
        }
        $cond = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = (int) Database::valor('SELECT COUNT(*) FROM usuarios u' . $cond, $par);
        $limite = (int) $por;
        $offset = (int) (max(1, $pagina) - 1) * $limite;
        $items = Database::filas(
            'SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.rol, u.activo, u.creado_en,
                    (SELECT COUNT(*) FROM pedidos WHERE id_usuario = u.id) AS pedidos
             FROM usuarios u' . $cond
            . " ORDER BY u.creado_en DESC, u.id DESC LIMIT $limite OFFSET $offset",
            $par
        );
        return ['items' => $items, 'total' => $total];
    }

    public static function contar(): int
    {
        return (int) Database::valor('SELECT COUNT(*) FROM usuarios');
    }
}
