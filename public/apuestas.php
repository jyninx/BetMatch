<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once '../app/dao/ApuestaDAO.php';

$apuestas = array();

try {
    $dao_apuestas = new ApuestaDAO();
    $id_usuario = $_SESSION['user_id'];
    $apuestas = $dao_apuestas->obtenerApuestasPorUsuario($id_usuario);
} catch (Exception $e) {
    $apuestas = array();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Apuestas - BetMatch</title>
    <link rel="stylesheet" href="assets/css/apuestas.css?v=<?php echo time(); ?>">
</head>
<body>
<header>
    <a href="dashboard.php" class="logo"><span class="logo-text">BetMatch</span></a>
    <div class="nav">
        <span class="saldo"><?php echo number_format($_SESSION['user_saldo'] ?? 0, 2); ?>€</span>
        <a href="notificaciones.php">Notificaciones</a>
        <a href="apuestas.php" class="active">Apuestas</a>
        <a href="perfil.php">Perfil</a>
    </div>
</header>
<main>
    <div class="apuestas-container">
        <div class="apuestas-header">
            <h1>Mis Apuestas</h1>
            <div class="filtro-rapido">
                <select id="filtro_estado">
                    <option value="todas">Todas</option>
                    <option value="pendientes">Pendientes</option>
                    <option value="activas">Activas</option>
                    <option value="finalizadas">Finalizadas</option>
                </select>
                <!-- TODO: aplicar filtro sin recargar la pagina -->
            </div>
            <a href="crear-apuesta.php" class="btn-crear">+ Crear apuesta</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="apuestas-lista">
            <?php if (empty($apuestas)): ?>
                <div class="no-apuestas">
                    <p>No tienes apuestas registradas en este momento.</p>
                    <a href="crear-apuesta.php" class="btn-crear">Crear mi primera apuesta</a>
                </div>
            <?php else: ?>
                <?php foreach ($apuestas as $apuesta): ?>
                    <div class="apuesta-card <?php echo $apuesta['estado']; ?>">
                        <div class="apuesta-estado">
                            <?php if ($apuesta['estado'] == 'pendiente'): ?>
                                <span class="badge pendiente">PENDIENTE</span>
                            <?php elseif ($apuesta['estado'] == 'activa'): ?>
                                <span class="badge activa">ACTIVA</span>
                            <?php elseif ($apuesta['estado'] == 'finalizada'): ?>
                                <span class="badge finalizada">FINALIZADA</span>
                            <?php else: ?>
                                <span class="badge"><?php echo strtoupper($apuesta['estado']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="apuesta-info">
                            <div class="apuesta-evento"><?php echo htmlspecialchars($apuesta['evento_nombre']); ?></div>
                            <div class="apuesta-detalle">
                                <div class="detalle-item"><span class="detalle-label">Cantidad:</span><span class="detalle-valor"><?php echo number_format($apuesta['monto'], 2); ?>€</span></div>
                                <div class="detalle-item"><span class="detalle-label">Fecha creación:</span><span class="detalle-valor"><?php echo date('d/m/Y H:i', strtotime($apuesta['fecha_creacion'])); ?></span></div>
                                <?php if ($apuesta['estado'] == 'activa'): ?>
                                    <div class="detalle-item">
                                        <span class="detalle-label">Rival:</span>
                                        <span class="detalle-valor"><?php echo htmlspecialchars($apuesta['id_creador'] == $_SESSION['user_id'] ? ($apuesta['aceptador_nombre'] ?? 'Esperando rival') : $apuesta['creador_nombre']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<footer>
    <span>BetMatch</span>
    <span><a href="contacto.php" class="footer-link" style="text-decoration:none">Contacto</a></span>
</footer>
</body>
</html>
