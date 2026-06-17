<?php
session_start();

require_once '../../vendor/PayPal/autoload.php';
require_once '../../app/config/paypal.php';

use PayPal\Api\OpenIdTokeninfo;
use PayPal\Api\OpenIdUserinfo;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

$contexto_paypal = new ApiContext(
    new OAuthTokenCredential(PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET)
);
$contexto_paypal->setConfig(array('mode' => PAYPAL_MODE));

if (isset($_GET['code'])) {
    $codigo_autorizacion = $_GET['code'];
} else {
    $codigo_autorizacion = '';
}

if ($codigo_autorizacion != '') {
    try {
        $informacion_token = OpenIdTokeninfo::createFromAuthorizationCode($codigo_autorizacion, null, $contexto_paypal);
        
        $datos_usuario_paypal = OpenIdUserinfo::getUserinfo(array('access_token' => $informacion_token->getAccessToken()), $contexto_paypal);
        
        require_once '../../app/config/database.php';
        require_once '../../app/dao/UsuarioDAO.php';
        
        $dao_usuarios = new UsuarioDAO();
        
        $correo_paypal = $datos_usuario_paypal->getEmail();
        
        if ($datos_usuario_paypal->getGivenName() != null) {
            $nombre_paypal = $datos_usuario_paypal->getGivenName();
        } else {
            $partes_correo = explode('@', $correo_paypal);
            $nombre_paypal = $partes_correo[0];
        }
        
        if ($datos_usuario_paypal->getFamilyName() != null) {
            $apellidos_paypal = $datos_usuario_paypal->getFamilyName();
        } else {
            $apellidos_paypal = '';
        }
        
        $usuario_bd = $dao_usuarios->buscarPorCorreo($correo_paypal);
        
        if (!$usuario_bd) {
            $dao_usuarios->crearUsuarioSimple($nombre_paypal, $apellidos_paypal, $correo_paypal);
            $nuevo_id = conectar()->lastInsertId();
            $usuario_bd = $dao_usuarios->buscarPorId($nuevo_id);
        }
        
        $_SESSION['user_id'] = $usuario_bd['id'];
        $_SESSION['user_nombre'] = $usuario_bd['nombre'];
        $_SESSION['user_email'] = $usuario_bd['email'];
        $_SESSION['user_rol'] = $usuario_bd['rol'];
        $_SESSION['user_saldo'] = $usuario_bd['saldo'];
        
        header('Location: ../dashboard.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al iniciar sesión con PayPal: ' . $e->getMessage();
        header('Location: login.php');
        exit();
    }
} else {
    if (PAYPAL_MODE == 'sandbox') {
        $url_autorizacion_paypal = 'https://www.sandbox.paypal.com/signin/authorize';
    } else {
        $url_autorizacion_paypal = 'https://www.paypal.com/signin/authorize';
    }
    
    $url_autorizacion_paypal .= '?client_id=' . PAYPAL_CLIENT_ID;
    $url_autorizacion_paypal .= '&response_type=code';
    $url_autorizacion_paypal .= '&scope=openid email profile';
    $url_autorizacion_paypal .= '&redirect_uri=' . urlencode(PAYPAL_RETURN_URL);
    
    header('Location: ' . $url_autorizacion_paypal);
    exit();
}
?>
