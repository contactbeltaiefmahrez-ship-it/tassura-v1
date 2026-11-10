<?php
require dirname(__DIR__).'/includes/bootstrap.php';post_only();csrf_check();
try{
 if(input_text('website')!==''||!throttle('contact',5,3600))throw new DomainException('Veuillez patienter avant un nouvel envoi.');
 $name=input_text('name',120);$email=input_text('email',190);$message=input_text('message',4000);
 if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($message)<5)throw new DomainException('Renseignez votre nom, un e-mail valide et votre message.');
 $s=db()->prepare('INSERT INTO messages (name,email,message) VALUES (?,?,?)');$s->execute([$name,$email,$message]);flash('Votre message a bien été enregistré. La maison vous répondra à l’adresse indiquée.');
}catch(DomainException $e){flash($e->getMessage(),'error');}
redirect(route('contact'));
