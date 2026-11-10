<?php
require dirname(__DIR__).'/includes/bootstrap.php';post_only();csrf_check();
$pdo=db();
try{
 $key=input_text('checkout_key',48);
 if(!preg_match('/^[a-f0-9]{48}$/D',$key)||(!hash_equals($_SESSION['checkout_key']??'', $key)&&!hash_equals($_SESSION['last_checkout_key']??'', $key)))throw new DomainException('Rechargez le formulaire de commande.');
 $existing=$pdo->prepare('SELECT access_token FROM orders WHERE checkout_key=?');$existing->execute([$key]);if($token=$existing->fetchColumn())redirect(route('order',['token'=>$token]));
 if(!throttle('checkout',8,900))throw new DomainException('Veuillez patienter avant de réessayer.');
 $customer=[];foreach(['name'=>120,'email'=>190,'phone'=>20,'governorate'=>80,'address'=>500,'postal_code'=>4,'notes'=>500] as $k=>$max)$customer[$k]=input_text($k,$max);
 $_SESSION['checkout_old']=$customer;
 $phone=preg_replace('/\s+/','',$customer['phone']);
 if(!$customer['name']||!filter_var($customer['email'],FILTER_VALIDATE_EMAIL)||!preg_match('/^(?:(?:\+|00)216)?[0-9]{8}$/D',$phone)||!in_array($customer['governorate'],governorates(),true)||strlen($customer['address'])<8||!preg_match('/^[0-9]{4}$/D',$customer['postal_code'])||($_POST['terms']??'')!=='1')throw new DomainException('Vérifiez vos coordonnées et acceptez les conditions de vente.');
 if(config('shipping_millimes')===null||!is_int(config('shipping_millimes'))||config('shipping_millimes')<0)throw new DomainException('Les frais de livraison doivent être confirmés par la maison.');
 $cart=$_SESSION['cart']??[];if(!$cart)throw new DomainException('Votre sélection est vide.');ksort($cart,SORT_NUMERIC);
 $productIds=[];
 foreach(array_keys($cart) as $variantId){$q=$pdo->prepare('SELECT product_id FROM variants WHERE id=?');$q->execute([(int)$variantId]);$productId=$q->fetchColumn();if($productId!==false)$productIds[(int)$productId]=true;}
 ksort($productIds,SORT_NUMERIC);$pdo->beginTransaction();$items=[];$subtotal=0;$lockedProducts=[];
 foreach(array_keys($productIds) as $productId){$q=$pdo->prepare('SELECT id,name,price_millimes,active FROM products WHERE id=? FOR UPDATE');$q->execute([$productId]);if($product=$q->fetch())$lockedProducts[$productId]=$product;}

 foreach($cart as $id=>$qty){
  $s=$pdo->prepare('SELECT * FROM variants WHERE id=? FOR UPDATE');$s->execute([(int)$id]);$v=$s->fetch();
  if($v){$product=$lockedProducts[(int)$v['product_id']]??null;if(!$product)throw new DomainException('La pièce n’est plus disponible.');foreach(['name','price_millimes','active'] as $field)$v[$field]=$product[$field];$labels=$pdo->prepare('SELECT c.name color_name,s.label size_label FROM colors c CROSS JOIN sizes s WHERE c.id=? AND s.id=?');$labels->execute([$v['color_id'],$v['size_id']]);$v=array_merge($v,$labels->fetch()?:[]);}

  if(!$v||!(int)$v['active']||$v['price_millimes']===null||(int)$v['price_millimes']<=0||$qty<1||$qty>config('max_cart_quantity')||(int)$v['stock']<$qty)throw new DomainException('Une pièce de votre sélection n’est plus disponible dans la quantité demandée.');
  $v['quantity']=(int)$qty;$items[]=$v;$subtotal+=(int)$v['price_millimes']*$qty;
 }
 $shipping=config('shipping_millimes');$token=bin2hex(random_bytes(32));$reference='TAS-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
 $s=$pdo->prepare('INSERT INTO orders (reference,user_id,access_token,checkout_key,customer_name,email,phone,governorate,address,postal_code,notes,subtotal_millimes,shipping_millimes,total_millimes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
 $s->execute([$reference,user()['id']??null,$token,$key,$customer['name'],$customer['email'],$phone,$customer['governorate'],$customer['address'],$customer['postal_code'],$customer['notes'],$subtotal,$shipping,$subtotal+$shipping]);$orderId=(int)$pdo->lastInsertId();
 foreach($items as $v){$s=$pdo->prepare('INSERT INTO order_items (order_id,variant_id,product_name,color_name,size_label,unit_price_millimes,quantity) VALUES (?,?,?,?,?,?,?)');$s->execute([$orderId,$v['id'],$v['name'],$v['color_name'],$v['size_label'],$v['price_millimes'],$v['quantity']]);$s=$pdo->prepare('UPDATE variants SET stock=stock-?,revision=revision+1 WHERE id=? AND stock>=?');$s->execute([$v['quantity'],$v['id'],$v['quantity']]);if($s->rowCount()!==1)throw new DomainException('La disponibilité a changé. Réessayez.');}
 $pdo->prepare('INSERT INTO order_history (order_id,user_id,status) VALUES (?,?,?)')->execute([$orderId,user()['id']??null,'pending']);
 $pdo->commit();$_SESSION['cart']=[];unset($_SESSION['checkout_old']);
 // Retain the idempotency key until the next checkout page creates a new cart session key.
 $_SESSION['last_checkout_key']=$key;unset($_SESSION['checkout_key']);
 if(config('mail_enabled')&&filter_var(config('contact_email'),FILTER_VALIDATE_EMAIL)&&filter_var(config('mail_from'),FILTER_VALIDATE_EMAIL)){
  @mail(config('contact_email'),'Nouvelle commande '.$reference,'Une commande est enregistrée. Consultez l’administration TASSURA.','From: '.config('mail_from'));
 }
 redirect(route('order',['token'=>$token]));
}catch(DomainException $e){if($pdo->inTransaction())$pdo->rollBack();flash($e->getMessage(),'error');redirect(route('checkout'));}
catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();if(in_array((int)($e->errorInfo[1]??0),[1213,1205],true)){flash('Une autre commande vient de modifier la disponibilité. Vérifiez votre sélection et réessayez.','error');redirect(route('checkout'));}if($e->getCode()==='23000'&&isset($key)){$s=$pdo->prepare('SELECT access_token FROM orders WHERE checkout_key=?');$s->execute([$key]);if($token=$s->fetchColumn())redirect(route('order',['token'=>$token]));}throw $e;}
