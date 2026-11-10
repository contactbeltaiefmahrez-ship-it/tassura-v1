<?php
require dirname(__DIR__).'/includes/bootstrap.php';post_only();csrf_check();$admin=require_admin();$op=input_text('operation',30);$id=(int)($_POST['id']??0);$back=url('admin/index.php');
try{
 if($op==='product'){
  $back=url('admin/index.php').'?section=product&id='.$id;$name=input_text('name',120);$price=parse_price(input_text('price',20));$short=input_text('short_description',400);$description=input_text('description',8000);
  $cat=(int)($_POST['category_id']??0);$color=(int)($_POST['default_color_id']??0);
  if(!$name||!$short||!$description||!in_array($cat,[1,2,3],true)||$color<1||$color>9)throw new DomainException('Vérifiez le nom, la description, la catégorie et le coloris.');
  $s=db()->prepare('SELECT id FROM variants WHERE product_id=? ORDER BY id');$s->execute([$id]);$variants=$s->fetchAll();if(!$variants)throw new DomainException('Pièce introuvable.');
  $stocks=$_POST['stock']??[];$versions=$_POST['version']??[];if(!is_array($versions))throw new DomainException('Rechargez la fiche avant de modifier le stock.');if(!is_array($stocks))throw new DomainException('Stock invalide.');
  foreach($variants as $v){$n=filter_var($stocks[$v['id']]??null,FILTER_VALIDATE_INT);if($n===false||$n<0||$n>9999)throw new DomainException('Chaque stock doit être compris entre0 et9999.');}
  db()->beginTransaction();db()->prepare('UPDATE products SET name=?,price_millimes=?,short_description=?,description=?,composition=?,care=?,size_guide=?,category_id=?,default_color_id=?,active=? WHERE id=?')->execute([$name,$price,$short,$description,input_text('composition',4000),input_text('care',4000),input_text('size_guide',4000),$cat,$color,isset($_POST['active'])?1:0,$id]);
  $s=db()->prepare('UPDATE variants SET stock=?,revision=revision+1 WHERE id=? AND product_id=? AND revision=?');foreach($variants as $v){$version=filter_var($versions[$v['id']]??null,FILTER_VALIDATE_INT);if($version===false)throw new DomainException('Rechargez la fiche avant de modifier le stock.');$s->execute([(int)$stocks[$v['id']],$v['id'],$id,$version]);if($s->rowCount()!==1)throw new DomainException('Le stock a évolué pendant votre modification. Rechargez la fiche pour éviter d’écraser une commande récente.');}db()->commit();flash('La fiche et le stock ont été enregistrés.');
 }elseif($op==='order_status'){
  $back=url('admin/index.php').'?section=order&id='.$id;$status=input_text('status',30);$allowed=['pending'=>['confirmed','cancelled'],'confirmed'=>['packed','cancelled'],'packed'=>['shipped','cancelled'],'shipped'=>['delivered'],'delivered'=>[],'cancelled'=>[]];
  db()->beginTransaction();$s=db()->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$s->execute([$id]);$order=$s->fetch();if(!$order)throw new DomainException('Commande introuvable.');
  if($status!==$order['status']&&!in_array($status,$allowed[$order['status']]??[],true))throw new DomainException('Cette transition d’état n’est pas autorisée.');
  if($status!==$order['status']){
   if($status==='cancelled'&&!(int)$order['inventory_released']){$s=db()->prepare('SELECT variant_id,quantity FROM order_items WHERE order_id=? ORDER BY variant_id');$s->execute([$id]);foreach($s as $item)db()->prepare('UPDATE variants SET stock=stock+?,revision=revision+1 WHERE id=?')->execute([$item['quantity'],$item['variant_id']]);db()->prepare('UPDATE orders SET inventory_released=1 WHERE id=?')->execute([$id]);}
   db()->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status,$id]);db()->prepare('INSERT INTO order_history (order_id,user_id,status) VALUES (?,?,?)')->execute([$id,$admin['id'],$status]);
  }
  db()->commit();flash('L’état de la commande est à jour.');
 }elseif($op==='message_read'){db()->prepare('UPDATE messages SET is_read=1 WHERE id=?')->execute([$id]);$back=url('admin/index.php').'?section=messages';flash('Message marqué comme lu.');}
 elseif($op==='user_toggle'){db()->prepare("UPDATE users SET active=1-active WHERE id=? AND role='customer'")->execute([$id]);$back=url('admin/index.php').'?section=users';flash('Le compte a été mis à jour.');}
 elseif($op==='media'){
  $back=url('admin/index.php').'?section=product&id='.$id;$s=db()->prepare('SELECT id FROM products WHERE id=?');$s->execute([$id]);if(!$s->fetch())throw new DomainException('Pièce introuvable.');
  $mediaId=input_text('media_id',80);$prefix='product-'.sprintf('%02d',$id).'-';if(!preg_match('/^'.preg_quote($prefix,'/').'(?:0[1-6]|variant-0[1-9])$/D',$mediaId))throw new DomainException('Vue invalide.');
  $up=$_FILES['photo']??[];if(($up['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||($up['size']??0)>8*1024*1024||!is_uploaded_file($up['tmp_name']))throw new DomainException('Photographie non reçue ou trop volumineuse (8Mo maximum).');
  $info=@getimagesize($up['tmp_name']);if(!$info||!in_array($info['mime'],['image/jpeg','image/png','image/webp'],true)||$info[0]<300||$info[1]<300||$info[0]>12000||$info[1]>12000)throw new DomainException('Utilisez une photographie JPEG, PNG ou WebP de300 à12000pixels.');
  $dir=ROOT.'/private/media';if(!is_writable($dir)||!is_writable(ROOT.'/private'))throw new DomainException('Les droits d’écriture du répertoire média doivent être configurés sur l’hébergement.');
  $lock=fopen(ROOT.'/private/media-lock.php','c+');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Media lock unavailable');
  try{
   $index=require ROOT.'/private/media-index.php';$filename=$mediaId.'-'.bin2hex(random_bytes(6)).'.php';$head='<?php __halt_compiler();'."\n";$bytes=file_get_contents($up['tmp_name']);
   if(file_put_contents($dir.'/'.$filename,$head.$bytes,LOCK_EX)===false)throw new RuntimeException('Media write failed');
   $index[$mediaId]=['file'=>$filename,'mime'=>$info['mime'],'offset'=>strlen($head),'size'=>strlen($bytes),'width'=>$info[0],'height'=>$info[1]];
   $tmp=ROOT.'/private/media-index-'.bin2hex(random_bytes(5)).'.php';if(file_put_contents($tmp,'<?php return '.var_export($index,true).';',LOCK_EX)===false||!rename($tmp,ROOT.'/private/media-index.php'))throw new RuntimeException('Media index write failed');
  }finally{flock($lock,LOCK_UN);fclose($lock);}
  flash('La photographie a été remplacée.');
 }else throw new DomainException('Action inconnue.');
}catch(DomainException $e){if(db()->inTransaction())db()->rollBack();flash($e->getMessage(),'error');}
redirect($back);
