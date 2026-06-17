<?php
/* session_start();

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
$curl_error_token = curl_error($solicitud_token);
$token_acceso = json_decode($respuesta_token, true);
curl_close($solicitud_token);

if (!isset($token_acceso['access_token'])) {
    $_SESSION['error'] = 'No se pudo conectar con PayPal: ' . ($curl_error_token ?: $respuesta_token ?: 'respuesta vacía');
    error_log('PayPal token error: ' . ($curl_error_token ?: $respuesta_token ?: 'respuesta vacía'));
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
$curl_error_pago = curl_error($solicitud_pago);
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

$mensaje_error = isset($pago['message']) ? $pago['message'] : ($curl_error_pago ?: $respuesta_pago ?: 'error desconocido');
$_SESSION['error'] = 'Error al crear el pago en PayPal: ' . $mensaje_error;
error_log('PayPal payment creation error: ' . $mensaje_error);
header('Location: perfil.php');
exit(); */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/config/paypal.php';

$cantidad = floatval($_GET['amount'] ?? 0);

if ($cantidad <= 0) {
    $_SESSION['error'] = "Cantidad inválida";
    header("Location: perfil.php");
    exit();
}


$ch = curl_init();

if (!isset($ch)) {
    die(" CURL handler no inicializado");
}

$info = curl_getinfo($ch);

var_dump($info);

if (empty($info['url'])) {
    die(" NO HAY URL ASIGNADA AL CURL");
}


$res = curl_exec($ch);

if ($res === false) {
    die("CURL ERROR: " . curl_error($ch));
}

$data = json_decode($res, true);

echo "<pre>";
var_dump($res);
var_dump($data);
echo "</pre>";
exit;

curl_setopt($ch, CURLOPT_URL, PAYPAL_API_BASE . "/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET);

curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Accept-Language: en_US"
]);
if (!isset($ch)) {
    die(" X CURL handler no inicializado");
}

$info = curl_getinfo($ch);

var_dump($info);

if (empty($info['url'])) {
    die("X NO HAY URL ASIGNADA AL CURL");
}
$res = curl_exec($ch);
$data = json_decode($res, true);
curl_close($ch);

$token = $data['access_token'] ?? null;

if (!$token) {
    $_SESSION['error'] = "Error obteniendo token PayPal";
    header("Location: perfil.php");
    exit();
}


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, PAYPAL_API_BASE . "/v2/checkout/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

$order = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "amount" => [
            "currency_code" => "EUR",
            "value" => $cantidad
        ]
    ]],
    "application_context" => [
        "return_url" => "https://TU_DOMINIO/paypal-execute.php",
        "cancel_url" => "https://TU_DOMINIO/perfil.php"
    ]
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token
]);

$res = curl_exec($ch);
$result = json_decode($res, true);
curl_close($ch);

if (!empty($result['links'])) {
    foreach ($result['links'] as $link) {
        if ($link['rel'] === 'approve') {
            header("Location: " . $link['href']);
            exit();
        }
    }
}

$_SESSION['error'] = "Error creando la orden de PayPal";
header("Location: perfil.php");
exit();