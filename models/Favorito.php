<?php
/** Productos favoritos de cada usuario. */
class Favorito
{
    /** Ids de los productos favoritos del usuario (para pintar los corazones). */
    public static function ids(int $idUsuario): array
    {
        if ($idUsuario < 1) {
            return [];
        }
        return array_map('intval', array_column(
            Database::filas('SELECT id_producto FROM favoritos WHERE id_usuario = :u', ['u' => $idUsuario]),
            'id_producto'
        ));
    }

    /** Agrega o quita un favorito. Devuelve true si quedó marcado. */
    public static function alternar(int $idUsuario, int $idProducto): bool
    {
        $existe = Database::valor('SELECT COUNT(*) FROM favoritos WHERE id_usuario = :u AND id_producto = :p', ['u' => $idUsuario, 'p' => $idProducto]);
        if ($existe) {
            Database::ejecutar('DELETE FROM favoritos WHERE id_usuario = :u AND id_producto = :p', ['u' => $idUsuario, 'p' => $idProducto]);
            return false;
        }
        Database::ejecutar('INSERT INTO favoritos (id_usuario, id_producto) VALUES (:u, :p)', ['u' => $idUsuario, 'p' => $idProducto]);
        return true;
    }

    public static function productos(int $idUsuario): array
    {
        return Producto::porIds(self::ids($idUsuario));
    }
}
