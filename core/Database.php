<?php
/**
 * Conexión PDO única + atajos para consultas preparadas.
 * Todas las consultas del proyecto pasan por acá: nunca se concatenan datos del usuario.
 */
class Database
{
    private static $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log('Conexión fallida: ' . $e->getMessage());
                exit('No se pudo conectar con la base de datos. Revisá config/config.php.');
            }
        }
        return self::$pdo;
    }

    public static function consulta(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function filas(string $sql, array $params = []): array
    {
        return self::consulta($sql, $params)->fetchAll();
    }

    public static function fila(string $sql, array $params = [])
    {
        $r = self::consulta($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public static function valor(string $sql, array $params = [])
    {
        $r = self::consulta($sql, $params)->fetchColumn();
        return $r === false ? null : $r;
    }

    public static function ejecutar(string $sql, array $params = []): int
    {
        return self::consulta($sql, $params)->rowCount();
    }

    public static function ultimoId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }
}
