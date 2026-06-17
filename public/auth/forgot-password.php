<?php
session_start();

require_once '../../app/config/database.php';
require_once '../../app/dao/UsuarioDAO.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['request_reset'])) {
        
        $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
        
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Pon un email valido por favor.';
        } else {
            $dao = new UsuarioDAO();
            $usuario = $dao->buscarPorCorreo($correo);
            
            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $dao->guardarTokenRestablecimiento($usuario['id'], $token, $expira);
                
                $enlace = "http://" . $_SERVER['HTTP_HOST'] . "/BetMatch/public/auth/forgot-password.php?token=" . $token;
                
                $_SESSION['success'] = "Se ha generado el enlace de recuperacion para " . $correo;
            } else {
                $_SESSION['success'] = 'Si el email existe, recibiras las instrucciones.';
            }
        }
        
        header('Location: forgot-password.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reset_password'])) {
        
        $token = isset($_POST['token']) ? $_POST['token'] : '';
        $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
        $clave_confirmada = isset($_POST['clave_confirmada']) ? $_POST['clave_confirmada'] : '';
        
        if (strlen($clave) < 6) {
            $_SESSION['error'] = 'La contraseña tiene que tener al menos 6 caracteres.';
        } elseif ($clave != $clave_confirmada) {
            $_SESSION['error'] = 'Las contraseñas no coinciden.';
        } else {
            $dao = new UsuarioDAO();
            $token_data = $dao->buscarTokenRestablecimientoValido($token);
            
            if ($token_data) {
                $clave_encriptada = password_hash($clave, PASSWORD_DEFAULT);
                $dao->actualizarContrasena($token_data['usuario_id'], $clave_encriptada);
                $dao->borrarTokenRestablecimiento($token);
                
                $_SESSION['success'] = 'Contraseña cambiada correctamente. Ya puedes entrar.';
                header('Location: login.php');
                exit();
            } else {
                $_SESSION['error'] = 'El enlace ya no es valido o ha caducado.';
            }
        }
        
        header('Location: forgot-password.php?token=' . urlencode($token));
        exit();
    }
}

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $mostrar_reset = true;
} else {
    $mostrar_reset = false;
}

if ($mostrar_reset) {
    $dao = new UsuarioDAO();
    $token_info = $dao->buscarTokenRestablecimientoValido($_GET['token']);
    
    if (!$token_info) {
        $_SESSION['error'] = 'El enlace ya no sirve. Pide uno nuevo.';
        header('Location: forgot-password.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña - BetMatch</title>
    <link rel="stylesheet" href="../assets/css/restablecer_contraseña.css?v=<?php echo time(); ?>">
</head>
<body>

<header>
    <a href="../index.php" class="logo">        <span class="logo-text">BetMatch</span>
    </a>
    <nav>
        <a href="login.php">Iniciar sesión</a>
        <a href="register.php">Registrarse</a>
    </nav>
</header>

<main>
    <div class="login-box">
        <?php if (!$mostrar_reset): ?>
            <h2>¿Olvidaste tu contraseña?</h2>
            <p>Te mandaremos un enlace para cambiarla</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="email" name="correo" placeholder="Correo electrónico" required autofocus>
                <button type="submit" name="request_reset">Enviar instrucciones</button>
            </form>

            <p class="register">
                <a href="login.php">← Volver al inicio de sesión</a>
            </p>

        <?php else: ?>
            <h2>Nueva contraseña</h2>
            <p>Escribe tu nueva contraseña</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                <input type="password" name="clave" placeholder="Nueva contraseña" required>
                <input type="password" name="clave_confirmada" placeholder="Confirmar contraseña" required>
                <button type="submit" name="reset_password">Restablecer contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<footer>
    <span>BetMatch</span>
    <span>Contacto</span>
</footer>

</body>
</html>