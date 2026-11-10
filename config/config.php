<?php
declare(strict_types=1);
/* All deployment parameters live here. Never commit real hosting secrets publicly. */
return [
 'site_name'=>'TASSURA',
 'base_url'=>getenv('TASSURA_BASE_URL') ?: '', // https://www.your-domain.tn ; no trailing slash
 'url_prefix'=>getenv('TASSURA_URL_PREFIX') ?: '', // /boutique for a subdirectory; empty for domain root
 'timezone'=>'Africa/Tunis',
 'db'=>['host'=>getenv('TASSURA_DB_HOST') ?: 'localhost','port'=>(int)(getenv('TASSURA_DB_PORT') ?: 3306),
        'socket'=>getenv('TASSURA_DB_SOCKET') ?: '', 'name'=>getenv('TASSURA_DB_NAME') ?: 'tassura',
        'user'=>getenv('TASSURA_DB_USER') ?: 'tassura','password'=>getenv('TASSURA_DB_PASSWORD') ?: '',
        'charset'=>'utf8mb4','timeout'=>5],
 'session_name'=>'tassura_session',
 'setup_token'=>getenv('TASSURA_SETUP_TOKEN') ?: 'eb4b29fde06d1a1e1b227f5a562f9362cc1003c604f2cf51',
 'currency'=>'TND',
 'shipping_millimes'=>getenv('TASSURA_SHIPPING_MILLIMES') !== false ? (int)getenv('TASSURA_SHIPPING_MILLIMES') : null, // Set actual shipping cost in millimes: 8000 = 8 TND; 0 = free
 'contact_email'=>'', 'contact_phone'=>'', 'instagram_url'=>'', 'facebook_url'=>'',
 'company_name'=>'', 'company_address'=>'', 'company_registration'=>'',
 'delivery_policy'=>'Les modalités de livraison seront précisées avant la confirmation de votre commande.',
 'return_policy'=>'Contactez la maison pour connaître les conditions de retour applicables à votre commande.',
 'mail_enabled'=>false, // Native PHP mail, optional; orders always persist independently
 'mail_from'=>'',
 'https_only'=>false, // Set true after enabling HTTPS on your domain
 'max_cart_quantity'=>10,
];
