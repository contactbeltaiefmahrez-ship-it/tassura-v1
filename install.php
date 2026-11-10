<?php
require __DIR__.'/includes/bootstrap.php';
$installed=false;try{$installed=(int)db()->query('SELECT COUNT(*) FROM users WHERE role="admin"')->fetchColumn()>0;}catch(PDOException $e){if($e->getCode()!=='42S02')throw $e;}
if($installed){http_response_code(403);exit('Installation déjà terminée. Supprimez install.php de votre hébergement.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check();
 try{
  if(!config('setup_token')||!hash_equals((string)config('setup_token'),input_text('setup_token',200)))throw new DomainException('Clé d’installation incorrecte.');
  $name=input_text('name',120);$email=strtolower(input_text('email',190));$password=$_POST['password']??'';
  if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!is_string($password)||strlen($password)<12||strlen($password)>72||str_contains($password,"\0"))throw new DomainException('Renseignez un nom, un e-mail valide et un mot de passe de 12 caractères minimum.');
  $lock=db()->prepare('SELECT GET_LOCK(?,0)');$lock->execute(['tassura-install-'.config('db')['name']]);if((int)$lock->fetchColumn()!==1)throw new DomainException('Installation en cours. Réessayez plus tard.');
  try{
   db()->exec(file_get_contents(ROOT.'/database/schema.sql'));
   if((int)db()->query('SELECT COUNT(*) FROM users WHERE role="admin"')->fetchColumn()>0)throw new DomainException('Un administrateur existe déjà.');
   $s=db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),'admin']);
  }finally{$unlock=db()->prepare('SELECT RELEASE_LOCK(?)');$unlock->execute(['tassura-install-'.config('db')['name']]);}
  flash('Installation terminée. Connectez-vous avec votre compte administrateur.');redirect(route('account'));
 }catch(DomainException $e){$error=$e->getMessage();}
}
$page='install';$title='Installation';$bodyClass='install-page';require ROOT.'/includes/header.php';
?><main id="main" class="content-page"><p class="eyebrow">INSTALLATION SÉCURISÉE</p><h1>Ouvrir la maison.</h1><p>Configurez d’abord la connexion MySQL dans config/config.php. La base doit déjà exister.</p><?php if($error):?><p role="alert" class="flash"><?=e($error)?></p><?php endif;?><form class="auth-form" method="post"><?=csrf_input()?><label class="field">Clé d’installation<input type="password" name="setup_token" required autocomplete="off"></label><label class="field">Nom de l’administrateur<input name="name" maxlength="120" required></label><label class="field">E-mail<input name="email" type="email" maxlength="190" required></label><label class="field">Mot de passe (12 caractères minimum)<input name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password"></label><button class="button">Installer & créer mon compte</button></form><p>Les dix pièces et leurs neuf coloris seront importés. Aucun prix, stock ou compte client fictif n’est créé. Après installation, supprimez install.php.</p></main><?php require ROOT.'/includes/footer.php';?>
