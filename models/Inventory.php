<?php
class Inventory {
 public function __construct(private PDO $db){}
 public function movement(int $product,int $user,string $type,int $qty,string $notes=''): void {
  if(!in_array($type,['entrada','salida','ajuste'],true)||$qty<1)throw new InvalidArgumentException('Movimiento inválido');
  $this->db->beginTransaction();
  try {
   $q=$this->db->prepare('SELECT id,talle,stock FROM producto_talles WHERE id_producto=? ORDER BY id FOR UPDATE');
   $q->execute([$product]);$sizes=$q->fetchAll();if(!$sizes)throw new RuntimeException('El producto no tiene talles cargados.');
   $stock=array_sum(array_column($sizes,'stock'));$loggedQty=$qty;
   if($type==='entrada'){$s=$this->db->prepare('UPDATE producto_talles SET stock=stock+? WHERE id=?');$s->execute([$qty,$sizes[0]['id']]);}
   elseif($type==='salida'){
    if($stock<$qty)throw new RuntimeException('No hay stock suficiente para registrar esa salida.');
    $remaining=$qty;$u=$this->db->prepare('UPDATE producto_talles SET stock=stock-? WHERE id=?');
    foreach($sizes as $size){if($remaining<=0)break;$take=min((int)$size['stock'],$remaining);if($take>0){$u->execute([$take,$size['id']]);$remaining-=$take;}}
   } else {
    $loggedQty=abs($qty-$stock);$zero=$this->db->prepare('UPDATE producto_talles SET stock=0 WHERE id_producto=?');$zero->execute([$product]);
    $set=$this->db->prepare('UPDATE producto_talles SET stock=? WHERE id=?');$set->execute([$qty,$sizes[0]['id']]);
   }
   $m=$this->db->prepare('INSERT INTO movimientos(id_producto,id_usuario,tipo,cantidad,observaciones) VALUES(?,?,?,?,?)');$m->execute([$product,$user,$type,$loggedQty,$notes]);
   $this->db->commit();
  } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
 }
}
