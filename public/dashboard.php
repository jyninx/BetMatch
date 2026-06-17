<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once '../app/dao/UsuarioDAO.php';
require_once '../app/dao/ApuestaDAO.php';
require_once '../app/dao/NotificacionDAO.php';
require_once '../app/dao/ParticipaDAO.php';

$dao_usuarios = new UsuarioDAO();
$usuario = $dao_usuarios->buscarPorId($_SESSION['user_id']);

if ($usuario) {
    $_SESSION['user_saldo'] = $usuario['saldo'];
    $_SESSION['user_nombre'] = $usuario['nombre'];
}

$total_apuestas = 0;
$apuestas_ganadas = 0;
$notificaciones_sin_leer = 0;
$apuestas_activas = 0;
$apuestas_pendientes = 0;
$actividades_recientes = array();

try {
    $dao_apuestas = new ApuestaDAO();
    $dao_notificaciones = new NotificacionDAO();
    $dao_participa = new ParticipaDAO();

    $id_usuario = $_SESSION['user_id'];
    $total_apuestas = $dao_participa->contarPorUsuario($id_usuario);
    $apuestas_ganadas = $dao_apuestas->contarGanadasPorUsuario($id_usuario);
    $notificaciones_sin_leer = $dao_notificaciones->contarSinLeer($id_usuario);
    $apuestas_activas = $dao_apuestas->contarActivasPorUsuario($id_usuario);
    $apuestas_pendientes = $dao_apuestas->contarPendientesDisponibles($id_usuario);
    $actividades_recientes = $dao_apuestas->obtenerUltimasPorUsuario($id_usuario, 5);
} catch (Exception $e) {
    $actividades_recientes = array();
    // si falla la BD mostramos el dashboard sin datos
}

$porcentaje_victorias = $total_apuestas > 0 ? round(($apuestas_ganadas / $total_apuestas) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - BetMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="header-container">
        <a href="dashboard.php" class="logo"><span class="logo-text">BetMatch</span></a>
        <nav class="nav">
            <a href="dashboard.php" class="active"><span class="nav-icon">🏠</span><span>Inicio</span></a>
            <a href="apuestas.php"><span class="nav-icon">🎯</span><span>Apuestas</span></a>
            <a href="notificaciones.php"><span class="nav-icon">🔔</span><span>Notificaciones</span><?php if ($notificaciones_sin_leer > 0): ?><span class="badge-notif"><?php echo $notificaciones_sin_leer; ?></span><?php endif; ?></a>
            <a href="perfil.php"><span class="nav-icon">👤</span><span>Perfil</span></a>
            <a href="auth/logout.php" class="logout-btn"><span class="nav-icon">🚪</span><span>Salir</span></a>
        </nav>
        <div class="user-saldo">
            <span class="saldo-label">Saldo disponible</span>
            <span class="saldo-value">€<?php echo number_format($_SESSION['user_saldo'], 2); ?></span>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Bienvenido de vuelta, <span class="highlight"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span></h1>
                <p>Gestiona tus apuestas, reta a otros usuarios y aumenta tus ganancias.</p>
                <a href="crear-apuesta.php" class="btn-primary"><span>+</span> Crear nueva apuesta</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><div class="stat-circle"><span class="stat-number"><?php echo $total_apuestas; ?></span><span class="stat-label">Apuestas</span></div></div>
                <div class="hero-stat"><div class="stat-circle"><span class="stat-number"><?php echo $porcentaje_victorias; ?>%</span><span class="stat-label">Victorias</span></div></div>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <div class="stat-card stat-card-primary">
            <div class="stat-icon">💰</div>
            <div class="stat-info"><span class="stat-value">€<?php echo number_format($_SESSION['user_saldo'], 2); ?></span><span class="stat-title">Saldo actual</span></div>
        </div>
        <div class="stat-card stat-card-secondary">
            <div class="stat-icon">🎯</div>
            <div class="stat-info"><span class="stat-value"><?php echo $apuestas_activas; ?></span><span class="stat-title">Apuestas activas</span></div>
            <a href="apuestas.php?filtro=activas" class="stat-link">Ver todas →</a>
        </div>
        <div class="stat-card stat-card-warning">
            <div class="stat-icon">⏳</div>
            <div class="stat-info"><span class="stat-value"><?php echo $apuestas_pendientes; ?></span><span class="stat-title">Pendientes</span></div>
            <a href="apuestas.php?filtro=pendientes" class="stat-link">Aceptar retos →</a>
        </div>
        <div class="stat-card stat-card-success">
            <div class="stat-icon">🏆</div>
            <div class="stat-info"><span class="stat-value"><?php echo $apuestas_ganadas; ?></span><span class="stat-title">Victorias</span></div>
            <div class="stat-progress"><div class="progress-bar" style="width:<?php echo $porcentaje_victorias; ?>%"></div></div>
        </div>
    </section>

    <section class="actividades-section">
        <div class="section-header">
            <h2>📋 Últimas actividades</h2>
            <a href="apuestas.php" class="ver-todas">Ver todas las apuestas →</a>
        </div>
        <div class="actividades-lista">
            <?php if (empty($actividades_recientes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎯</div>
                    <h3>No tienes apuestas aún</h3>
                    <p>Crea tu primera apuesta y comienza a ganar</p>
                    <a href="crear-apuesta.php" class="btn-secondary">Crear apuesta</a>
                </div>
            <?php else: ?>
                <?php foreach ($actividades_recientes as $actividad): ?>
                    <?php
                    $icono_actividad = '🎯';
                    if ($actividad['estado'] == 'pendiente') {
                        $icono_actividad = '⏳';
                    } elseif ($actividad['estado'] == 'activa') {
                        $icono_actividad = '🔥';
                    } elseif ($actividad['estado'] == 'finalizada') {
                        $icono_actividad = '✅';
                    }

                    $detalle_rival = 'Finalizada el ' . date('d/m/Y', strtotime($actividad['fecha_finalizacion'] ?? $actividad['fecha_creacion']));
                    if ($actividad['estado'] == 'pendiente') {
                        $detalle_rival = 'vs ' . htmlspecialchars($actividad['rival_nombre']);
                    } elseif ($actividad['estado'] == 'activa') {
                        $detalle_rival = 'En juego vs ' . htmlspecialchars($actividad['rival_nombre']);
                    }
                    ?>
                    <div class="actividad-item">
                        <div class="actividad-icono <?php echo $actividad['estado']; ?>"><?php echo $icono_actividad; ?></div>
                        <div class="actividad-info">
                            <div class="actividad-titulo"><?php echo htmlspecialchars($actividad['evento_nombre']); ?> <span class="actividad-monto">€<?php echo number_format($actividad['monto'], 2); ?></span></div>
                            <div class="actividad-detalle">
                                <span class="actividad-rival"><?php echo $detalle_rival; ?></span>
                                <span class="actividad-fecha"><?php echo date('d/m/Y H:i', strtotime($actividad['fecha_creacion'])); ?></span>
                            </div>
                        </div>
                        <div class="actividad-estado"><span class="badge badge-<?php echo $actividad['estado']; ?>"><?php echo strtoupper($actividad['estado']); ?></span></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="acciones-rapidas">
        <div class="section-header"><h2>⚡ Acciones rápidas</h2></div>
        <div class="acciones-grid">
            <a href="crear-apuesta.php" class="accion-card"><div class="accion-icon">🎯</div><h3>Crear apuesta</h3><p>Reta a otro usuario</p></a>
            <a href="perfil.php" class="accion-card"><div class="accion-icon">💰</div><h3>Depositar</h3><p>Recarga tu saldo</p></a>
            <a href="apuestas.php" class="accion-card"><div class="accion-icon">📊</div><h3>Mis apuestas</h3><p>Ver historial</p></a>
            <a href="perfil.php" class="accion-card"><div class="accion-icon">⚙️</div><h3>Ajustes</h3><p>Configura tu perfil</p></a>
        </div>
    </section>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-logo"><span>BetMatch</span></div>
        <div class="footer-links">
            <a href="ayuda.php">Ayuda</a>
            <a href="contacto.php" class="footer-link">Contacto</a>
        </div>
    </div>
</footer>
</body>
</html>
