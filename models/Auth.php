<?php
class Auth {
 public function __construct(private PDO $db){}
 public function login(string $email,string $password): bool {$s=$this->db->prepare('SELECT id,nombre,email,password,rol,activo FROM usuarios WHERE email=? LIMIT 1');$s->execute([$email]);$u=$s->fetch();if(!$u||!$u['activo']||!password_verify($password,$u['password']))return false;session_regenerate_id(true);unset($u['password']);$_SESSION['user']=$u;return true;}
 public function register(string $name,string $email,string $password): void {$s=$this->db->prepare("INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES(?,?,?,'cliente',1)");$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);}
}
