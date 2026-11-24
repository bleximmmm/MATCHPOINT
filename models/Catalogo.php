<?php
/** Listas de apoyo: marcas, categorías, talles y proveedores. */
class Catalogo
{
    public static function marcas(): array
    {
        return Database::filas('SELECT id, nombre, slug FROM marcas ORDER BY nombre');
    }

    public static function categorias(): array
    {
        return Database::filas('SELECT id, nombre, slug, descripcion FROM categorias ORDER BY id');
    }

    public static function talles(): array
    {
        return Database::filas('SELECT id, valor FROM talles ORDER BY valor');
    }

    public static function proveedores(): array
    {
        return Database::filas('SELECT id, nombre, contacto, telefono, email FROM proveedores ORDER BY nombre');
    }

    /** Descuento más alto vigente (para el banner de ofertas). */
    public static function mayorDescuento(): int
    {
        return (int) Database::valor('SELECT COALESCE(MAX(descuento), 0) FROM productos WHERE activo = 1');
    }
}
