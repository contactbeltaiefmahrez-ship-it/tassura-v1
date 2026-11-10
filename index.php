<?php
require __DIR__.'/includes/bootstrap.php';
ob_start();
$page=$_GET['page']??'home';$allowed=['home','shop','product','cart','checkout','account','maison','contact','policy','order'];
if(!is_string($page)||!in_array($page,$allowed,true)){$page='not-found';http_response_code(404);}
$titles=['home'=>'L’allure en héritage','shop'=>'La collection','product'=>'La pièce','cart'=>'Votre sélection','checkout'=>'Votre commande','account'=>'Votre espace','maison'=>'La maison','contact'=>'Contact','policy'=>'Informations','order'=>'Votre commande','not-found'=>'Page introuvable'];
$title=$titles[$page];$bodyClass=$page==='home'?'home-page':$page.'-page';
require ROOT.'/includes/header.php';require ROOT.'/pages/'.$page.'.php';require ROOT.'/includes/footer.php';
