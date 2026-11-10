<?php
declare(strict_types=1);
function config(string $key): mixed {return $GLOBALS['config'][$key] ?? null;}
function e(mixed $s): string {return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function url(string $path=''): string {return rtrim((string)config('base_url'),'/').'/'.trim((string)config('url_prefix'),'/').(config('url_prefix')?'/':'').ltrim($path,'/');}
function route(string $page='home',array $q=[]): string {return url('index.php').'?'.http_build_query(['page'=>$page]+$q);}
function media_url(string $id): string {return url('media.php').'?id='.rawurlencode($id);}
function money(?int $n): string {return $n===null ? 'Prix à venir' : number_format($n/1000,3,',',' ').' TND';}
function redirect(string $location): never {header('Location: '.$location,true,303);exit;}
function post_only(): void {if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit;}}
function csrf_token(): string {return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function csrf_input(): string {return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">';}
function csrf_check(): void {if(!is_string($_POST['csrf']??null)||!hash_equals(csrf_token(),$_POST['csrf'])){http_response_code(419);exit('Votre session a expiré. Rechargez la page avant de réessayer.');}}
function flash(string $message,string $type='info'): void {$_SESSION['flash'][]=['text'=>$message,'type'=>$type];}
function user(): ?array {return $_SESSION['user']??null;}
function require_user(): array {if(!user()){flash('Connectez-vous pour accéder à votre espace.');redirect(route('account'));}return user();}
function require_admin(): array {$u=require_user();$s=db()->prepare('SELECT id,name,email,role,active FROM users WHERE id=?');$s->execute([$u['id']]);$row=$s->fetch();if(!$row||$row['role']!=='admin'||!(int)$row['active']){http_response_code(403);exit('Accès réservé.');}return $row;}
function db(): PDO {
 static $pdo=null;if($pdo instanceof PDO)return $pdo;
 $c=config('db');$dsn=($c['socket']?'mysql:unix_socket='.$c['socket']:'mysql:host='.$c['host'].';port='.$c['port']).';dbname='.$c['name'].';charset='.$c['charset'];
 return $pdo=new PDO($dsn,$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>$c['timeout']]);
}
function all_colors(): array {return db()->query('SELECT * FROM colors ORDER BY sort_order')->fetchAll();}
function all_sizes(): array {return db()->query('SELECT * FROM sizes ORDER BY sort_order')->fetchAll();}
function get_product(string $slug): ?array {$s=db()->prepare('SELECT p.*,c.name category_name,c.slug category_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.active=1');$s->execute([$slug]);return $s->fetch()?:null;}
function get_images(int $id): array {$s=db()->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order');$s->execute([$id]);return $s->fetchAll();}
function product_card(array $p): void {
 $imgs=get_images((int)$p['id']);$label=$p['name'];
 echo '<article class="product-card"><div class="card-visual"><div class="card-gallery" data-card-gallery tabindex="0" aria-label="'.e($label).' : parcourir les photos">';
 foreach(array_slice($imgs,0,6) as $i=>$im)echo '<a class="card-slide" href="'.e(route('product',['slug'=>$p['slug']])).'"><img src="'.e(media_url($im['media_id'])).'" alt="'.e($label.' — vue '.($i+1)).'" loading="lazy" width="900" height="1125"></a>';
 echo '</div><button type="button" class="card-arrow card-prev" data-card-prev aria-label="Photo précédente">←</button><button type="button" class="card-arrow card-next" data-card-next aria-label="Photo suivante">→</button><span class="card-progress" aria-hidden="true"><span data-card-progress></span></span></div><div class="card-meta"><a href="'.e(route('product',['slug'=>$p['slug']])).'">'.e($label).'</a><span class="card-price">'.e(money($p['price_millimes']===null?null:(int)$p['price_millimes'])).'</span></div></article>';
}
function cart_count(): int {return array_sum($_SESSION['cart']??[]);}
function cart_lines(): array {
 $out=[];foreach(($_SESSION['cart']??[]) as $variantId=>$qty){$s=db()->prepare('SELECT v.*,p.name,p.slug,p.price_millimes,p.active,c.name color_name,c.sort_order color_order,s.label size_label FROM variants v JOIN products p ON p.id=v.product_id JOIN colors c ON c.id=v.color_id JOIN sizes s ON s.id=v.size_id WHERE v.id=?');$s->execute([(int)$variantId]);if($v=$s->fetch()){$v['quantity']=(int)$qty;$out[]=$v;}}return $out;
}
function cart_subtotal(array $lines): int {$n=0;foreach($lines as $l)$n+=(int)$l['price_millimes']*$l['quantity'];return $n;}
function input_text(string $key,int $max=255): string {$s=$_POST[$key]??'';return is_string($s)?text_cut(trim($s),$max):'';}
function parse_price(string $s): ?int {if($s==='')return null;if(!preg_match('/^\d{1,6}(?:[.,]\d{1,3})?$/D',$s))throw new DomainException('Prix invalide (maximum trois décimales).');return (int)round((float)str_replace(',','.',$s)*1000);}
function governorates(): array {return ['Ariana','Béja','Ben Arous','Bizerte','Gabès','Gafsa','Jendouba','Kairouan','Kasserine','Kébili','Kef','Mahdia','Manouba','Médenine','Monastir','Nabeul','Sfax','Sidi Bouzid','Siliana','Sousse','Tataouine','Tozeur','Tunis','Zaghouan'];}

function text_cut(string $s,int $max): string {if(preg_match('/^.{0,'.$max.'}/us',$s,$m))return $m[0];return '';}
function throttle(string $scope,int $limit,int $seconds): bool {
 $pdo=db();$id=hash('sha256',$scope.'|'.($_SERVER['REMOTE_ADDR']??'cli'));$now=time();
 $pdo->beginTransaction();try{
  $pdo->prepare('INSERT IGNORE INTO rate_limits (id,hits,window_start) VALUES (?,0,?)')->execute([$id,$now]);
  $s=$pdo->prepare('SELECT * FROM rate_limits WHERE id=? FOR UPDATE');$s->execute([$id]);$row=$s->fetch();
  $expired=$now-(int)$row['window_start']>=$seconds;$hits=$expired?1:(int)$row['hits']+1;
  $pdo->prepare('UPDATE rate_limits SET hits=?,window_start=? WHERE id=?')->execute([$hits,$expired?$now:(int)$row['window_start'],$id]);$pdo->commit();
  if(random_int(1,100)===1)$pdo->prepare('DELETE FROM rate_limits WHERE window_start<?')->execute([$now-86400]);
  return $hits<=$limit;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
