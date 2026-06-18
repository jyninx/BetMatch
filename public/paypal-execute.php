<?php
/* session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/config/database.php';
require_once '../app/dao/UsuarioDAO.php';
require_once '../app/dao/TransaccionDAO.php';
require_once '../app/config/paypal.php';

$id_pago = isset($_GET['paymentId']) ? $_GET['paymentId'] : '';
$id_pagador = isset($_GET['PayerID']) ? $_GET['PayerID'] : '';
$cantidad = isset($_SESSION['paypal_amount']) ? $_SESSION['paypal_amount'] : 0;

if (empty($id_pago) || empty($id_pagador)) {
    $_SESSION['error'] = 'Faltan datos del pago de PayPal';
    header('Location: perfil.php');
    exit();
}

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
    $_SESSION['error'] = 'No se pudo reconectar con PayPal';
    header('Location: perfil.php');
    exit();
}

$token_portador = $token_acceso['access_token'];

$solicitud_pago = curl_init();
curl_setopt($solicitud_pago, CURLOPT_URL, PAYPAL_API_BASE . '/v1/payments/payment/' . $id_pago);
curl_setopt($solicitud_pago, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($solicitud_pago, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token_portador
));

$respuesta_pago = curl_exec($solicitud_pago);
$detalles_pago = json_decode($respuesta_pago, true);
curl_close($solicitud_pago);

$correo_paypal = '';
if (isset($detalles_pago['payer']['payer_info']['email'])) {
    $correo_paypal = $detalles_pago['payer']['payer_info']['email'];
}

$solicitud_ejecucion = curl_init();
curl_setopt($solicitud_ejecucion, CURLOPT_URL, PAYPAL_API_BASE . '/v1/payments/payment/' . $id_pago . '/execute');
curl_setopt($solicitud_ejecucion, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($solicitud_ejecucion, CURLOPT_POST, 1);
curl_setopt($solicitud_ejecucion, CURLOPT_POSTFIELDS, json_encode(array('payer_id' => $id_pagador)));
curl_setopt($solicitud_ejecucion, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token_portador
));

$respuesta_ejecucion = curl_exec($solicitud_ejecucion);
$pago_ejecutado = json_decode($respuesta_ejecucion, true);
curl_close($solicitud_ejecucion);

if (isset($pago_ejecutado['state']) && $pago_ejecutado['state'] === 'approved') {
    $dao_usuarios = new UsuarioDAO();
    $dao_transaccion = new TransaccionDAO();

    $usuario = $dao_usuarios->buscarPorId($_SESSION['user_id']);
    $nuevo_saldo = $usuario['saldo'] + $cantidad;

    $dao_usuarios->actualizarSaldo($_SESSION['user_id'], $nuevo_saldo);
    $_SESSION['user_saldo'] = $nuevo_saldo;

    if (!empty($correo_paypal)) {
        $dao_usuarios->actualizarCuentaPaypal($_SESSION['user_id'], $correo_paypal);
        $usuario['cuenta_paypal'] = $correo_paypal;
    }

    $dao_transaccion->crearTransaccion(
        $_SESSION['user_id'],
        'deposito',
        $cantidad,
        $usuario['saldo'],
        $nuevo_saldo,
        $correo_paypal,
        'completada'
    );

    $_SESSION['success'] = 'Deposito de €' . number_format($cantidad, 2) . ' completado. Cuenta PayPal registrada: ' . $correo_paypal;
    unset($_SESSION['paypal_amount']);
    header('Location: perfil.php');
    exit();
} else {
    $_SESSION['error'] = 'El pago no se completo correctamente';
    header('Location: perfil.php');
    exit();
} */ 

    
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/dao/UsuarioDAO.php';
require_once '../app/dao/TransaccionDAO.php';
require_once '../app/config/paypal.php';

$order_id = $_GET['token'] ?? null;

if (!$order_id) {
    $_SESSION['error'] = "Pago inválido";
    header("Location: perfil.php");
    exit();
}


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, PAYPAL_API_BASE . "/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

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

curl_setopt($ch, CURLOPT_URL, PAYPAL_API_BASE . "/v2/checkout/orders/$order_id/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token
]);

$res = curl_exec($ch);
$result = json_decode($res, true);
curl_close($ch);


if (($result['status'] ?? '') === 'COMPLETED') {

    $cantidad = $_SESSION['paypal_amount'] ?? 0;

    require_once '../app/dao/UsuarioDAO.php';
    require_once '../app/dao/TransaccionDAO.php';

    $dao_usuarios = new UsuarioDAO();
    $dao_transaccion = new TransaccionDAO();

    $usuario = $dao_usuarios->buscarPorId($_SESSION['user_id']);

    $nuevo_saldo = $usuario['saldo'] + $cantidad;

    $dao_usuarios->actualizarSaldo($_SESSION['user_id'], $nuevo_saldo);

    $_SESSION['user_saldo'] = $nuevo_saldo;

    unset($_SESSION['paypal_amount']);

    $_SESSION['success'] = "Depósito completado correctamente";
    header("Location: perfil.php");
    exit();
}

$_SESSION['error'] = "El pago no se completó";
header("Location: perfil.php");
exit();
