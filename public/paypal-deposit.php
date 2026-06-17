<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/config/paypal.php';

$cantidad = isset($_GET['amount']) ? $_GET['amount'] : 0;

if ($cantidad <= 0) {
    $_SESSION['error'] = 'La cantidad no es valida';
    header('Location: perfil.php');
    exit();
}

$_SESSION['paypal_amount'] = $cantidad;

$solicitud_token = curl_init();
curl_setopt($solicitud_token, CURLOPT_URL, PAYPAL_API_BASE . '/v1/oauth2/token');
curl_setopt($solicitud_token, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($solicitud_token, CURLOPT_POST, 1);
curl_setopt($solicitud_token, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($solicitud_token, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
curl_setopt($solicitud_token, CURLOPT_HTTPHEADER, array('Accept: application/json'));

$respuesta_token = curl_exec($solicitud_token);
$token_acceso = json_decode($respuesta_token, true);
curl_close($solicitud_token);

if (!isset($token_acceso['access_token'])) {
    $_SESSION['error'] = 'No se pudo conectar con PayPal';
    header('Location: perfil.php');
    exit();
}

$token_portador = $token_acceso['access_token'];

$datos_pago = array(
    'intent' => 'sale',
    'payer' => array('payment_method' => 'paypal'),
    'transactions' => array(array(
        'amount' => array(
            'total' => $cantidad,
            'currency' => 'EUR'
        ),
        'description' => 'Deposito BetMatch - ' . $_SESSION['user_nombre']
    )),
    'redirect_urls' => array(
        'return_url' => PAYPAL_RETURN_URL,
        'cancel_url' => PAYPAL_CANCEL_URL
    )
);

$solicitud_pago = curl_init();
curl_setopt($solicitud_pago, CURLOPT_URL, PAYPAL_API_BASE . '/v1/payments/payment');
curl_setopt($solicitud_pago, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($solicitud_pago, CURLOPT_POST, 1);
curl_setopt($solicitud_pago, CURLOPT_POSTFIELDS, json_encode($datos_pago));
curl_setopt($solicitud_pago, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token_portador
));

$respuesta_pago = curl_exec($solicitud_pago);
$pago = json_decode($respuesta_pago, true);
curl_close($solicitud_pago);

if (isset($pago['links'])) {
    foreach ($pago['links'] as $enlace) {
        if ($enlace['rel'] === 'approval_url') {
            header('Location: ' . $enlace['href']);
            exit();
        }
    }
}

$mensaje_error = isset($pago['message']) ? $pago['message'] : 'error desconocido';
$_SESSION['error'] = 'Error al crear el pago en PayPal: ' . $mensaje_error;
header('Location: perfil.php');
exit();