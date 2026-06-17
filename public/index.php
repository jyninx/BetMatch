<?php
session_start();

require_once __DIR__ . '/../app/dao/UsuarioDAO.php';
require_once __DIR__ . '/../app/dao/ApuestaDAO.php';

$dao_usuarios = new UsuarioDAO();
$dao_apuestas = new ApuestaDAO();

try {
    $total_usuarios = $dao_usuarios->contarTodas();
    $total_apuestas = $dao_apuestas->contarTodas();
    $apuestas_activas = $dao_apuestas->contarPorEstado('activa');
    $apuestas_finalizadas = $dao_apuestas->contarPorEstado('finalizada');
} catch (Exception $e) {
    $total_usuarios = 0;
    $total_apuestas = 0;
    $apuestas_activas = 0;
    $apuestas_finalizadas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BetMatch - Apuestas 1vs1</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/index.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="header-container">
        <a href="index.php" class="logo"><span class="logo-text">BetMatch</span></a>
        <nav class="nav">
            <a href="index.php" class="active">Inicio</a>
            <a href="apuestas.php">Apuestas</a>
            <a href="notificaciones.php">Notificaciones</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="perfil.php">Mi Perfil</a>
                <a href="auth/logout.php" class="logout-btn">Cerrar sesión</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-login">Iniciar sesión</a>
                <a href="auth/register.php" class="btn-register">Registrarse</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Apuestas <span class="highlight">1 contra 1</span></h1>
                <h2>Duplica tu apuesta</h2>
                <p>Monta tu propia apuesta, desafía a otro usuario y gana dinero real. Sin cuotas ocultas, tú pones las reglas.</p>
                <div class="hero-buttons">
                    <a href="auth/register.php" class="btn-primary">Comenzar ahora</a>
                    <a href="#como-funciona" class="btn-secondary">Cómo funciona</a>
                </div>
            </div>
            <div class="hero-stats">
                <div class="stat-circle">
                    <span class="stat-number"><?php echo $total_usuarios; ?>+</span>
                    <span class="stat-label">Usuarios activos</span>
                </div>
                <div class="stat-circle">
                    <span class="stat-number"><?php echo $total_apuestas; ?>+</span>
                    <span class="stat-label">Apuestas creadas</span>
                </div>
                <div class="stat-circle">
                    <span class="stat-number">x2</span>
                    <span class="stat-label">Retorno posible</span>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="como-funciona">
        <div class="section-header">
            <h2>¿Cómo funciona?</h2>
            <p>Simple, rápido y seguro</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Crea tu apuesta</h3>
                <p>Selecciona el evento, la cantidad y el rival. Tú decides las reglas.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤝</div>
                <h3>Espera un retador</h3>
                <p>Otro usuario aceptará tu apuesta. Ambos apuestan la misma cantidad.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3>Hasta 2x de premio</h3>
                <p>El ganador se lleva hasta el doble de lo apostado. Sin vueltas, directo a tu saldo.</p>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $apuestas_activas; ?></span>
                <span class="stat-title">Apuestas activas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $apuestas_finalizadas; ?></span>
                <span class="stat-title">Apuestas finalizadas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <span class="stat-value">x2</span>
                <span class="stat-title">Retorno posible</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔒</div>
            <div class="stat-info">
                <span class="stat-value">100%</span>
                <span class="stat-title">Seguro y confiable</span>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-content">
            <h2>¿Listo para comenzar?</h2>
            <p>Regístrate y empieza a apostar con gente real de inmediato.</p>
            <a href="auth/register.php" class="btn-primary btn-large">Crear cuenta gratis</a>
        </div>
    </section>

    <section class="apuestas-recientes">
        <div class="section-header">
            <h2>📋 Últimas apuestas</h2>
            <a href="apuestas.php" class="ver-todas">Ver todas →</a>
        </div>
        <div class="apuestas-grid">
            <?php
            try {
                $apuestas_recientes = $dao_apuestas->obtenerUltimasApuestasActivas(3);
                if (!empty($apuestas_recientes)):
                    foreach ($apuestas_recientes as $apuesta): ?>
                        <div class="apuesta-card">
                            <div class="apuesta-header">
                                <span class="apuesta-evento"><?php echo htmlspecialchars($apuesta['evento_nombre']); ?></span>
                                <span class="apuesta-monto">€<?php echo number_format($apuesta['monto'], 2); ?></span>
                            </div>
                            <div class="apuesta-body">
                                <p><strong>Creador:</strong> <?php echo htmlspecialchars($apuesta['creador_nombre']); ?></p>
                                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($apuesta['fecha_creacion'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="no-apuestas"><p>No hay apuestas activas en este momento</p></div>
                <?php endif;
            } catch (Exception $e) {
                echo '<div class="no-apuestas"><p>No hay apuestas activas</p></div>';
            }
            ?>
        </div>
    </section>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-logo"><span>BetMatch</span></div>
        <div class="footer-links">
            <a href="ayuda.php">Ayuda</a>
            <a href="contacto.php">Contacto</a>
        </div>
    </div>
</footer>
</body>
</html>
