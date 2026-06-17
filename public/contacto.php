<?php
session_start();

require_once '../app/config/database.php';
require_once '../app/dao/UsuarioDAO.php';

$mensaje_enviado = false;
$error_texto = '';
$nombre_persona = '';
$correo_persona = '';
$saldo_actual = 0;
$conectado = false;

if (isset($_SESSION['user_id'])) {
    $conectado = true;
    $dao_usuarios = new UsuarioDAO();
    $datos_usuario = $dao_usuarios->buscarPorId($_SESSION['user_id']);
    
    if ($datos_usuario) {
        $_SESSION['user_saldo'] = $datos_usuario['saldo'];
        $_SESSION['user_nombre'] = $datos_usuario['nombre'];
        $nombre_persona = $_SESSION['user_nombre'];
        $correo_persona = $datos_usuario['email'];
        $saldo_actual = $datos_usuario['saldo'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asunto = isset($_POST['asunto']) ? trim($_POST['asunto']) : '';
    $mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
    
    if (!$conectado) {
        $nombre_persona = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $correo_persona = isset($_POST['email']) ? trim($_POST['email']) : '';
    }
    
    if (empty($asunto)) {
        $error_texto = 'Por favor, ingresa un asunto';
    } elseif (empty($mensaje)) {
        $error_texto = 'Por favor, ingresa un mensaje';
    } elseif (!$conectado && empty($nombre_persona)) {
        $error_texto = 'Por favor, ingresa tu nombre';
    } elseif (!$conectado && empty($correo_persona)) {
        $error_texto = 'Por favor, ingresa tu email';
    } elseif (!$conectado && !filter_var($correo_persona, FILTER_VALIDATE_EMAIL)) {
        $error_texto = 'Email no válido';
    } else {
        $archivo = __DIR__ . '/mensajes_contacto.txt';
        
        $contenido = "========================================\n";
        $contenido .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
        
        if ($conectado) {
            $contenido .= "Usuario ID: " . $_SESSION['user_id'] . "\n";
        }
        
        $contenido .= "Nombre: " . $nombre_persona . "\n";
        $contenido .= "Email: " . $correo_persona . "\n";
        $contenido .= "Asunto: " . $asunto . "\n";
        $contenido .= "Mensaje:\n" . $mensaje . "\n";
        $contenido .= "========================================\n\n";
        
        if (file_put_contents($archivo, $contenido, FILE_APPEND | LOCK_EX)) {
            $mensaje_enviado = true;
            $_POST = array();
        } else {
            $error_texto = 'Error al guardar el mensaje. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contacto - BetMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/contacto.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="header-container">
        <a href="index.php" class="logo">            <span class="logo-text">BetMatch</span>
        </a>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="apuestas.php">Apuestas</a>
            <a href="notificaciones.php">Notificaciones</a>
            <?php if ($conectado): ?>
                <a href="perfil.php">Mi Perfil</a>
                <a href="auth/logout.php" class="logout-btn">Cerrar sesión</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-login">Iniciar sesión</a>
                <a href="auth/register.php" class="btn-register">Registrarse</a>
            <?php endif; ?>
        </nav>
        <?php if ($conectado): ?>
        <div class="user-saldo">
            <span class="saldo-label">Saldo disponible</span>
            <span class="saldo-value">€<?php echo number_format($saldo_actual, 2); ?></span>
        </div>
        <?php endif; ?>
    </div>
</header>

<main>
    <div class="contacto-container">
        <h1>Contacto</h1>
        <p class="subtitulo">¿Tienes alguna duda o sugerencia? Escríbenos y te responderemos lo antes posible.</p>

        <?php if ($mensaje_enviado): ?>
            <div class="alert success">
                ✅ ¡Mensaje enviado correctamente! Te responderemos en breve.
            </div>
        <?php endif; ?>

        <?php if ($error_texto != ''): ?>
            <div class="alert error">
                ❌ <?php echo $error_texto; ?>
            </div>
        <?php endif; ?>

        <div class="contacto-grid">
            <div class="info-contacto">
                <h3>Información de contacto</h3>
                <div class="info-item">
                    <span class="info-icon">📧</span>
                    <div>
                        <strong>Email</strong>
                        <p>soporte@betmatch.com</p>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-icon">🕐</span>
                    <div>
                        <strong>Horario de atención</strong>
                        <p>Lunes a Viernes: 9:00 - 18:00</p>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-icon">💬</span>
                    <div>
                        <strong>Tiempo de respuesta</strong>
                        <p>Máximo 24 horas hábiles</p>
                    </div>
                </div>
            </div>

            <form method="POST" class="contacto-form">
                <?php if (!$conectado): ?>
                <div class="form-group">
                    <label>Tu nombre</label>
                    <input type="text" name="nombre" placeholder="Tu nombre" required>
                </div>
                <div class="form-group">
                    <label>Tu email</label>
                    <input type="email" name="email" placeholder="tu@email.com" required>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>Tu nombre</label>
                    <input type="text" value="<?php echo htmlspecialchars($nombre_persona); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Tu email</label>
                    <input type="email" value="<?php echo htmlspecialchars($correo_persona); ?>" disabled>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Asunto</label>
                    <input type="text" name="asunto" placeholder="¿Sobre qué quieres contactarnos?" required>
                </div>
                <div class="form-group">
                    <label>Mensaje</label>
                    <textarea name="mensaje" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
                </div>
                <button type="submit" class="btn-enviar">Enviar mensaje</button>
            </form>
        </div>
    </div>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-logo">            <span>BetMatch</span>
        </div>
        <div class="footer-links">
            <a href="ayuda.php">Ayuda</a>
            <a href="contacto.php">Contacto</a>
        </div>
    </div>
</footer>

</body>
</html>