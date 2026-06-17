<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/dao/UsuarioDAO.php';

$error_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
    
    if ($correo == '' || $clave == '') {
        $error_mensaje = 'Rellena todos los campos por favor.';
    } else {
        $dao = new UsuarioDAO();
        $usuario = $dao->autenticar($correo, $clave);
        
        if ($usuario) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nombre'] = $usuario['nombre'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_rol'] = $usuario['rol'];
            $_SESSION['user_saldo'] = $usuario['saldo'];
            
            header('Location: ../dashboard.php');
            exit();
        } else {
            $error_mensaje = 'Email o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión - BetMatch</title>
    <link rel="stylesheet" href="../assets/css/login.css">
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
        <h2>¡BIENVENIDO!</h2>

        <?php if ($error_mensaje != ''): ?>
            <div class="alert alert-error">
                <?php echo $error_mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="correo" placeholder="Correo electrónico" required>
            <input type="password" name="clave" placeholder="Contraseña" required>

            <div class="options">
                <div class="remember">
                    <input type="checkbox" id="remember">
                    <label for="remember">Recordarme</label>
                </div>
                <a href="forgot-password.php" class="forgot">¿Has olvidado tu contraseña?</a>
            </div>

            <button type="submit">Iniciar sesión</button>
        </form>

        <p class="register">
            ¿Eres nuevo? <a href="./register.php">Regístrate ahora</a>
        </p>
    </div>
</main>

<footer>
    <span>BetMatch</span>
    <span><a href="../contacto.php" class="footer-link">Contacto</a></span>
</footer>

</body>
</html>