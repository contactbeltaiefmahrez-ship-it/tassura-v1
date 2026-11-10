<?php
declare(strict_types=1);
$config=require __DIR__.'/config/config.php';
$indexPath=__DIR__.'/private/media-index.php';
$index=is_file($indexPath)?require $indexPath:[];
$id=$_GET['id']??'';
if(!is_string($id)||!preg_match('/^[a-z0-9-]{1,80}$/D',$id)||!isset($index[$id])){http_response_code(404);exit;}
$m=$index[$id];$file=__DIR__.'/private/media/'.basename($m['file']);
if(!is_file($file)){http_response_code(404);exit;}
$size=(int)$m['size'];$offset=(int)$m['offset'];$start=0;$end=$size-1;
header('Content-Type: '.$m['mime']);header('X-Content-Type-Options: nosniff');header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=0, must-revalidate');$etag='"'.sha1($m['file'].'-'.filesize($file).'-'.filemtime($file)).'"';header('ETag: '.$etag);
if(($_SERVER['HTTP_IF_NONE_MATCH']??'')===$etag&&!isset($_SERVER['HTTP_RANGE'])){http_response_code(304);exit;}
if(isset($_SERVER['HTTP_RANGE'])){
 $r=$_SERVER['HTTP_RANGE'];
 if(!preg_match('/^bytes=(\d*)-(\d*)$/D',$r,$match)||($match[1]===''&&$match[2]==='')){http_response_code(416);header('Content-Range: bytes */'.$size);exit;}
 if($match[1]===''){$start=max(0,$size-(int)$match[2]);}else{$start=(int)$match[1];if($match[2]!=='')$end=min($end,(int)$match[2]);}
 if($start>$end||$start>=$size){http_response_code(416);header('Content-Range: bytes */'.$size);exit;}
 http_response_code(206);header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
}
$length=$end-$start+1;header('Content-Length: '.$length);
if($_SERVER['REQUEST_METHOD']==='HEAD')exit;
if(!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)){http_response_code(405);exit;}
$fp=fopen($file,'rb');fseek($fp,$offset+$start);while($length>0&&!feof($fp)){ $chunk=fread($fp,min(65536,$length));if($chunk===false)break;echo $chunk;$length-=strlen($chunk); }fclose($fp);
