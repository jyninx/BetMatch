<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/config/database.php';
require_once '../app/dao/UsuarioDAO.php';

$dao_usuarios = new UsuarioDAO();
$id_usuario = $_SESSION['user_id'];
$datos_usuario = $dao_usuarios->buscarPorId($id_usuario);

if ($datos_usuario) {
    $_SESSION['user_saldo'] = $datos_usuario['saldo'];
    $_SESSION['user_nombre'] = $datos_usuario['nombre'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ayuda - BetMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/ayuda.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="header-container">
        <a href="dashboard.php" class="logo">
            <span class="logo-text">BetMatch</span>
        </a>
        
        <nav class="nav">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="apuestas.php">🎯 Apuestas</a>
            <a href="notificaciones.php">🔔 Notificaciones</a>
            <a href="perfil.php">👤 Perfil</a>
            <a href="auth/logout.php" class="logout-btn">🚪 Salir</a>
        </nav>
        
        <div class="user-saldo">
            <span class="saldo-label">Saldo disponible</span>
            <span class="saldo-value">€<?php echo number_format($_SESSION['user_saldo'], 2); ?></span>
        </div>
    </div>
</header>

<main>
    <div class="ayuda-container">
        <h1>Centro de Ayuda</h1>
        <p class="subtitulo">¿Necesitas ayuda? Encuentra respuestas a tus preguntas.</p>

        <div class="faq-section">
            <h2>❓ Preguntas frecuentes</h2>
            
            <div class="faq-item">
                <div class="faq-pregunta">
                    <span class="faq-icon">📌</span>
                    ¿Cómo crear una apuesta?
                </div>
                <div class="faq-respuesta">
                    Para crear una apuesta, ve a la sección "Apuestas" y haz clic en "Crear apuesta". 
                    Selecciona el evento, ingresa la cantidad y el ID del rival. 
                    Una vez creada, el rival recibirá una notificación.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-pregunta">
                    <span class="faq-icon">💰</span>
                    ¿Cómo depositar dinero?
                </div>
                <div class="faq-respuesta">
                    Ve a tu "Perfil" y haz clic en el botón "Depósito". 
                    Ingresa la cantidad que deseas depositar y el saldo se actualizará automáticamente.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-pregunta">
                    <span class="faq-icon">🎯</span>
                    ¿Cómo funciona el sistema de apuestas 1vs1?
                </div>
                <div class="faq-respuesta">
                    Un usuario crea una apuesta y otro usuario la acepta. 
                    Ambos apuestan la misma cantidad. 
                    El ganador se lleva el doble de lo apostado. 
                    La plataforma cobra una comisión del 1% sobre la cantidad total.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-pregunta">
                    <span class="faq-icon">🔔</span>
                    ¿Cómo recibo notificaciones?
                </div>
                <div class="faq-respuesta">
                    Recibirás notificaciones cuando alguien te rete a una apuesta, 
                    cuando alguien acepte tu apuesta, o cuando una apuesta finalice. 
                    Revisa la sección "Notificaciones" regularmente.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-pregunta">
                    <span class="faq-icon">🆔</span>
                    ¿Cómo sé cuál es mi ID?
                </div>
                <div class="faq-respuesta">
                    Tu ID de usuario aparece en tu perfil. 
                    Puedes verlo en la sección "Perfil" junto a tu información personal.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-pregunta">
                    <span class="faq-icon">🏆</span>
                    ¿Qué pasa si gano una apuesta?
                </div>
                <div class="faq-respuesta">
                    Si ganas, recibirás el doble de tu apuesta (menos la comisión del 1%). 
                    El dinero se añadirá automáticamente a tu saldo y recibirás una notificación.
                </div>
            </div>
        </div>

        <div class="contacto-sugerencia">
            <p>¿No encontraste lo que buscabas?</p>
            <a href="contacto.php" class="btn-contacto">Contactar con soporte</a>
        </div>
    </div>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-logo">
            <span>BetMatch</span>
        </div>
        <div class="footer-links">
            <a href="ayuda.php">Ayuda</a>
            <a href="contacto.php">Contacto</a>
        </div>
    </div>
</footer>

<script>
    const preguntas = document.querySelectorAll('.faq-pregunta');
    
    for(let i = 0; i < preguntas.length; i++) {
        preguntas[i].addEventListener('click', function() {
            const item = this.parentElement;
            item.classList.toggle('active');
        });
    }
</script>

</body>
</html>