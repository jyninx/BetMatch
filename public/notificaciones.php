<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/dao/NotificacionDAO.php';
require_once '../app/dao/ApuestaDAO.php';

$detalle_apuesta = null;
$mostrar_detalle = false;
$lista_notificaciones = array();

$dao_notificaciones = new NotificacionDAO();
$dao_apuestas = new ApuestaDAO();

try {
    $lista_notificaciones = $dao_notificaciones->obtenerPorUsuario($_SESSION['user_id']);
} catch (Exception $e) {
    $lista_notificaciones = array();
}

if (isset($_GET['ver'])) {
    if (isset($_GET['id'])) {
        $notificacion_id = (int)$_GET['id'];
        $mostrar_detalle = true;
        
        try {
            $notificacion = $dao_notificaciones->obtenerPorIdYUsuario($notificacion_id, $_SESSION['user_id']);
            
            if ($notificacion) {
                if ($notificacion['tipo'] == 'apuesta') {
                    $apuesta_id = isset($notificacion['id_apuesta_relacionada']) ? $notificacion['id_apuesta_relacionada'] : 0;
                    
                    if ($apuesta_id > 0) {
                        $apuesta = $dao_apuestas->obtenerApuestaConCreador($apuesta_id);
                        
                        if ($apuesta) {
                            $detalle_apuesta = array(
                                'id' => $apuesta['id'],
                                'evento_nombre' => $apuesta['evento_nombre'],
                                'cantidad' => $apuesta['monto'],
                                'id_creador' => $apuesta['id_creador'],
                                'creador_nombre' => $apuesta['creador_nombre'],
                                'estado' => $apuesta['estado']
                            );
                        } else {
                            $_SESSION['error'] = 'La apuesta ya no está disponible';
                            header('Location: notificaciones.php');
                            exit();
                        }
                    } else {
                        $_SESSION['error'] = 'La notificación no tiene una apuesta asociada';
                        header('Location: notificaciones.php');
                        exit();
                    }
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al cargar la apuesta';
            header('Location: notificaciones.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificaciones - BetMatch</title>
    <link rel="stylesheet" href="assets/css/notificaciones.css?v=<?php echo time(); ?>">
</head>
<body>
<header>
    <a href="dashboard.php" class="logo">        <span class="logo-text">BetMatch</span>
    </a>
    <div class="nav">
        <span class="saldo">
        <?php 
            if (isset($_SESSION['user_saldo'])) {
                echo number_format($_SESSION['user_saldo'], 2); 
            } else {
                echo number_format(0, 2); 
            }
        ?>€
        </span>
        <a href="notificaciones.php" class="active">Notificaciones</a>
        <a href="apuestas.php">Apuestas</a>
        <a href="perfil.php">Perfil</a>
    </div>
</header>
<main>
    <?php 
    if ($mostrar_detalle) {
        if ($detalle_apuesta != null) {
    ?>
        <div class="detalle-container">
            <div class="detalle-header">
                <a href="notificaciones.php" class="btn-volver">← Volver</a>
                <h1>Detalle de la apuesta</h1>
            </div>
            <div class="detalle-card">
                <div class="detalle-campo"><span class="detalle-label">Apuesta ID</span><span class="detalle-valor">#<?php echo $detalle_apuesta['id']; ?></span></div>
                <div class="detalle-campo"><span class="detalle-label">Evento</span><span class="detalle-valor"><?php echo htmlspecialchars($detalle_apuesta['evento_nombre']); ?></span></div>
                <div class="detalle-campo"><span class="detalle-label">Cantidad</span><span class="detalle-valor"><?php echo number_format($detalle_apuesta['cantidad'], 2); ?>€</span></div>
                <div class="detalle-campo"><span class="detalle-label">Creador</span><span class="detalle-valor"><?php echo htmlspecialchars($detalle_apuesta['creador_nombre']); ?></span></div>
                <div class="detalle-campo"><span class="detalle-label">ID del rival</span><span class="detalle-valor">#<?php echo $detalle_apuesta['id_creador']; ?></span></div>
                <div class="detalle-campo"><span class="detalle-label">Estado</span><span class="detalle-valor estado-pendiente">PENDIENTE</span></div>
                <div class="detalle-acciones">
                    <form method="POST" action="procesar-apuesta.php" class="accion-form">
                        <input type="hidden" name="apuesta_id" value="<?php echo $detalle_apuesta['id']; ?>">
                        <input type="hidden" name="notificacion_id" value="<?php echo $_GET['id']; ?>">
                        <button type="submit" name="accion" value="aceptar" class="btn-aceptar">ACEPTAR</button>
                        <button type="submit" name="accion" value="rechazar" class="btn-rechazar">RECHAZAR</button>
                    </form>
                </div>
            </div>
        </div>
    <?php 
        } else {
            echo "Hubo un problema al mostrar el detalle.";
        }
    } else { 
    ?>
        <div class="notificaciones-container">
            <div class="notificaciones-header">
                <h1>Notificaciones</h1>
                <div class="filtro"><button class="filtro-btn" id="boton_filtro">Filtro ▼</button>
                <div class="filtro-dropdown" id="desplegable_filtros">
                    <a href="#" data-filtro="todas">Todas</a>
                    <a href="#" data-filtro="no_leidas">No leídas</a>
                    <a href="#" data-filtro="apuestas">Apuestas</a>
                </div></div>
            </div>
            <div class="notificaciones-lista" id="lista_notificaciones">
                <?php if (empty($lista_notificaciones)): ?>
                    <div class="no-notificaciones"><p>No tienes notificaciones por ahora.</p></div>
                <?php else: ?>
                    <?php foreach ($lista_notificaciones as $notificacion): ?>
                        <div class="notificacion-card <?php if ($notificacion['leida']) { echo 'leida'; } else { echo 'no-leida'; } ?>">
                            <div class="notificacion-icono">🎯</div>
                            <div class="notificacion-contenido">
                                <div class="notificacion-titulo"><?php echo htmlspecialchars($notificacion['titulo']); ?></div>
                                <div class="notificacion-mensaje"><?php echo htmlspecialchars($notificacion['mensaje']); ?></div>
                                <div class="notificacion-fecha"><?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_envio'])); ?></div>
                            </div>
                            <?php if (!$notificacion['leida']): ?>
                                <div class="notificacion-accion"><a href="?ver=1&id=<?php echo $notificacion['id']; ?>" class="btn-ver">VER</a></div>
                                <!-- TODO: marcar como leida al abrir la notificacion -->
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php } ?>
</main>
<footer>
    <span>BetMatch</span>
    <span><a href="contacto.php" class="footer-link">Contacto</a></span>
</footer>
<script src="assets/js/notificaciones.js"></script>
<script src="assets/js/headerFix.js"></script>
</body>
</html>