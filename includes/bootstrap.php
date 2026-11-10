<?php
declare(strict_types=1);
ini_set('display_errors','0');ini_set('log_errors','1');
define('ROOT', dirname(__DIR__));
$GLOBALS['config'] = require ROOT.'/config/config.php';
date_default_timezone_set($GLOBALS['config']['timezone']);
require_once ROOT.'/includes/functions.php';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (config('https_only') && !$isHttps && PHP_SAPI !== 'cli') {
    if (str_starts_with(config('base_url'), 'https://')) {header('Location: '.url('index.php'), true, 302); exit;}
    http_response_code(503); exit('HTTPS requis. Configurez base_url avec https://.');
}
if (session_status() === PHP_SESSION_NONE) {
 ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1');
 session_name(config('session_name'));
 session_set_cookie_params(['lifetime'=>0,'path'=>(config('url_prefix') ?: '').'/', 'secure'=>$isHttps,'httponly'=>true,'samesite'=>'Lax']);
 session_start();
}
header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; media-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store');
if ($isHttps) header('Strict-Transport-Security: max-age=31536000');
set_exception_handler(function(Throwable $e): void {
 error_log('TASSURA '.get_class($e).': '.$e->getMessage());
 while(ob_get_level()>0)ob_end_clean();
 http_response_code(503);
 echo '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TASSURA</title><link rel="stylesheet" href="'.e(url('assets/css/site.css')).'"><main class="message-page"><p class="eyebrow">TASSURA</p><h1>La maison prépare votre visite.</h1><p>Le service est momentanément indisponible. Merci de réessayer plus tard.</p><a class="button" href="'.e(url('index.php')).'">Retour à l’accueil</a></main></html>';
});

if (isset($_SESSION['user']['id'])) {
 $s=db()->prepare('SELECT id,name,email,role,active FROM users WHERE id=?');$s->execute([$_SESSION['user']['id']]);$account=$s->fetch();
 if (!$account || !(int)$account['active']) {unset($_SESSION['user']);session_regenerate_id(true);}
 else {$_SESSION['user']=['id'=>(int)$account['id'],'name'=>$account['name'],'email'=>$account['email'],'role'=>$account['role']];}
}
