<?php
require dirname(__DIR__).'/includes/bootstrap.php';post_only();csrf_check();
$op=input_text('operation',20);
if($op==='logout'){
 $_SESSION=[];session_regenerate_id(true);flash('Vous êtes déconnecté.');redirect(route());
}
try{
 if(!throttle('auth',12,900))throw new DomainException('Trop de tentatives. Réessayez dans quelques minutes.');
 $email=strtolower(input_text('email',190));$password=$_POST['password']??'';
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!is_string($password)||strlen($password)>72||str_contains($password,"\0"))throw new DomainException('Vérifiez vos identifiants.');
 if($op==='register'){
  if(!(int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn())throw new DomainException('La maison prépare encore son ouverture.');
  $name=input_text('name',120);if(!$name||strlen($password)<12)throw new DomainException('Un nom et un mot de passe de 12 caractères minimum sont nécessaires.');
  try{$s=db()->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);}
  catch(PDOException $e){if($e->getCode()==='23000')throw new DomainException('Ce compte ne peut pas être créé. Essayez de vous connecter ou contactez la maison.');throw $e;}
  $id=(int)db()->lastInsertId();session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['user']=['id'=>$id,'name'=>$name,'email'=>$email,'role'=>'customer'];flash('Bienvenue dans votre espace TASSURA.');
 }elseif($op==='login'){
  $s=db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');$s->execute([$email]);$u=$s->fetch();
  // A real fixed hash keeps the password verification path even for unknown accounts.
  $hash=$u['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
  $valid=password_verify($password,$hash);
  if(!$u||!$valid||!(int)$u['active'])throw new DomainException('E-mail ou mot de passe incorrect.');
  if(password_needs_rehash($hash,PASSWORD_DEFAULT))db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$u['id']]);
  session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];flash('Heureux de vous retrouver.');
 }else throw new DomainException('Action inconnue.');
}catch(DomainException $e){flash($e->getMessage(),'error');}
redirect(route('account'));
