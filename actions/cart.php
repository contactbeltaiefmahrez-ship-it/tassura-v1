<?php
require dirname(__DIR__).'/includes/bootstrap.php';post_only();csrf_check();
$op=input_text('operation',20);$back=route('cart');
try{
 if($op==='add'){
  $pid=(int)($_POST['product_id']??0);$cid=(int)($_POST['color_id']??0);$sid=(int)($_POST['size_id']??0);$qty=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT);
  $s=db()->prepare('SELECT v.*,p.slug,p.active,p.price_millimes FROM variants v JOIN products p ON p.id=v.product_id WHERE v.product_id=? AND v.color_id=? AND v.size_id=?');$s->execute([$pid,$cid,$sid]);$v=$s->fetch();
  if(!$v)throw new DomainException('Choisissez un coloris et une taille valides.');$back=route('product',['slug'=>$v['slug']]);
  if(!$qty||$qty<1||$qty>config('max_cart_quantity')||!(int)$v['active']||$v['price_millimes']===null||(int)$v['price_millimes']<1)throw new DomainException('Cette sélection ne peut pas encore être commandée.');
  $newQty=(int)($_SESSION['cart'][$v['id']]??0)+$qty;if($newQty>(int)$v['stock']||$newQty>config('max_cart_quantity'))throw new DomainException('La quantité demandée n’est pas disponible.');
  $_SESSION['cart'][$v['id']]=$newQty;flash('La pièce a rejoint votre sélection.');$back=route('cart');
 }elseif($op==='update'){
  $id=(int)($_POST['variant_id']??0);$qty=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT);
  if(!array_key_exists($id,$_SESSION['cart']??[]))throw new DomainException('Article introuvable dans votre sélection.');
  if(isset($_POST['remove'])||$qty===0){unset($_SESSION['cart'][$id]);}
  else{
   $s=db()->prepare('SELECT stock FROM variants WHERE id=?');$s->execute([$id]);$stock=(int)$s->fetchColumn();
   if(!$qty||$qty<1||$qty>min($stock,(int)config('max_cart_quantity')))throw new DomainException('Cette quantité n’est plus disponible.');$_SESSION['cart'][$id]=$qty;
  }
 }else throw new DomainException('Action inconnue.');
}catch(DomainException $e){flash($e->getMessage(),'error');}
redirect($back);
