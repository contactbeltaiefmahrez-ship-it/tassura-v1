<?php
declare(strict_types=1);
if(PHP_SAPI==='cli'){
 define('ROOT',dirname(__DIR__));$GLOBALS['config']=require ROOT.'/config/config.php';require ROOT.'/includes/functions.php';
}else{require dirname(__DIR__).'/includes/bootstrap.php';require_admin();header('Content-Type: text/plain; charset=utf-8');}
$fail=0;
function report(string $name,bool $ok):void{global $fail;echo ($ok?'PASS':'FAIL').' '.$name."\n";if(!$ok)$fail++;}
report('PHP8.1+',version_compare(PHP_VERSION,'8.1.0','>='));report('PDO MySQL',in_array('mysql',PDO::getAvailableDrivers(),true));
$allowed=['php','html','css','js','sql','md'];$valid=true;$files=0;
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT,FilesystemIterator::SKIP_DOTS)) as $p){if(!$p->isFile())continue;$files++;if($p->getFilename()!=='.htaccess'&&!in_array(strtolower($p->getExtension()),$allowed,true))$valid=false;if($p->getSize()===0)$valid=false;}
report('Allowed extensions; no empty files',$valid);
$index=require ROOT.'/private/media-index.php';$mediaValid=true;
foreach($index as $id=>$m){$path=ROOT.'/private/media/'.basename($m['file']);if(!is_file($path)||filesize($path)!==(int)$m['offset']+(int)$m['size']){$mediaValid=false;continue;}$f=fopen($path,'rb');$prefix=fread($f,(int)$m['offset']);fclose($f);if(!str_contains($prefix,'__halt_compiler();'))$mediaValid=false;}
report('Media containers match index',$mediaValid);echo 'Files: '.$files.'; media: '.count($index)."\n";
try{$pdo=db();report('PDO connection',$pdo instanceof PDO);$products=(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();report('10 product records',$products===10);$variants=(int)$pdo->query('SELECT COUNT(*) FROM variants')->fetchColumn();report('540 color-size positions',$variants===540);echo 'Unpriced products: '.$pdo->query('SELECT COUNT(*) FROM products WHERE price_millimes IS NULL')->fetchColumn()."\n";echo 'Orders: '.$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn()."\n";}catch(Throwable $e){report('Database schema/connection',false);echo "Configurez la connexion et exécutez l’installation.\n";}
echo 'Shipping configured: '.(config('shipping_millimes')===null?'NO':'YES')."\n";
if(PHP_SAPI==='cli')exit($fail?1:0);
