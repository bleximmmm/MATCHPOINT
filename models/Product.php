<?php
class Product {
    public function __construct(private PDO $db) {}
    public function featured(): array { return $this->db->query("SELECT p.*, m.nombre marca, c.nombre categoria, COALESCE(SUM(pt.stock),0) stock_total FROM productos p JOIN marcas m ON m.id=p.id_marca JOIN categorias c ON c.id=p.id_categoria LEFT JOIN producto_talles pt ON pt.id_producto=p.id WHERE p.activo=1 GROUP BY p.id ORDER BY p.creado_en DESC LIMIT 8")->fetchAll(); }
    public function all(array $f=[]): array {
        $sql="SELECT p.*,m.nombre marca,c.nombre categoria,COALESCE(SUM(pt.stock),0) stock_total FROM productos p JOIN marcas m ON m.id=p.id_marca JOIN categorias c ON c.id=p.id_categoria LEFT JOIN producto_talles pt ON pt.id_producto=p.id WHERE p.activo=1"; $args=[];
        foreach(['marca'=>'m.id','categoria'=>'c.id'] as $key=>$col){if(!empty($f[$key])){$sql.=" AND {$col}=?";$args[]=$f[$key];}}
        if(!empty($f['q'])){$sql.=' AND (p.nombre LIKE ? OR m.nombre LIKE ? OR p.sku LIKE ?)';$q='%'.$f['q'].'%';array_push($args,$q,$q,$q);}
        if(isset($f['min'])&&$f['min']!==''){$sql.=' AND p.precio_venta>=?';$args[]=(float)$f['min'];}
        if(isset($f['max'])&&$f['max']!==''){$sql.=' AND p.precio_venta<=?';$args[]=(float)$f['max'];}
        if(!empty($f['ofertas'])) $sql.=' AND p.precio_anterior IS NOT NULL AND p.precio_anterior>p.precio_venta';
        $sql.=' GROUP BY p.id'; $order=['menor'=>'p.precio_venta ASC','mayor'=>'p.precio_venta DESC','recientes'=>'p.creado_en DESC','populares'=>'p.vendidos DESC']; $sql.=' ORDER BY '.($order[$f['orden']??'']??'p.creado_en DESC');
        $st=$this->db->prepare($sql);$st->execute($args);return $st->fetchAll();
    }
    public function find(int $id): ?array { $s=$this->db->prepare('SELECT p.*,m.nombre marca,c.nombre categoria FROM productos p JOIN marcas m ON m.id=p.id_marca JOIN categorias c ON c.id=p.id_categoria WHERE p.id=? AND p.activo=1');$s->execute([$id]);$p=$s->fetch();if(!$p)return null;$t=$this->db->prepare('SELECT talle,stock FROM producto_talles WHERE id_producto=? ORDER BY CAST(talle AS DECIMAL),talle');$t->execute([$id]);$p['talles']=$t->fetchAll();return $p; }
}
