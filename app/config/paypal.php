<?php
require_once __DIR__ . '/env.php';

define('PAYPAL_MODE', env('PAYPAL_MODE', 'sandbox'));
define('PAYPAL_CLIENT_ID', $_ENV['PAYPAL_CLIENT_ID'] ?? getenv('PAYPAL_CLIENT_ID') ?? '');
define('PAYPAL_CLIENT_SECRET', $_ENV['PAYPAL_CLIENT_SECRET'] ?? getenv('PAYPAL_CLIENT_SECRET') ?? '');

define('APP_URL', env('APP_URL', 'http://localhost/BetMatch'));

$url_retorno = APP_URL . '/public/paypal-execute.php';
define('PAYPAL_RETURN_URL', env('PAYPAL_RETURN_URL', $url_retorno));

$url_cancelacion = APP_URL . '/public/perfil.php';
define('PAYPAL_CANCEL_URL', env('PAYPAL_CANCEL_URL', $url_cancelacion));

if (PAYPAL_MODE == 'live') {
    $base_api = 'https://api.paypal.com';
    define('PAYPAL_API_BASE', $base_api);
} else {
    $base_api = 'https://api.sandbox.paypal.com';
    define('PAYPAL_API_BASE', $base_api);
}

if (PAYPAL_CLIENT_ID == '') {
    error_log('Aviso: Falta configurar el CLIENT_ID de Paypal en el .env');
} elseif (PAYPAL_CLIENT_SECRET == '') {
    error_log('Aviso: Falta configurar el SECRET de Paypal en el .env');
}
?>