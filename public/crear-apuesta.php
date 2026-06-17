<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/dao/UsuarioDAO.php';
require_once '../app/dao/EventoDAO.php';
require_once '../app/dao/ApuestaDAO.php';
require_once '../app/dao/ParticipaDAO.php';
require_once '../app/dao/NotificacionDAO.php';

$errores = array();

$dao_eventos = new EventoDAO();
$dao_apuestas = new ApuestaDAO();
$dao_participa = new ParticipaDAO();
$dao_notificaciones = new NotificacionDAO();
$dao_usuarios = new UsuarioDAO();

try {
    $ligas_disponibles = $dao_eventos->obtenerLigasDistintasProximos();
} catch (Exception $e) {
    $ligas_disponibles = array();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $liga = isset($_POST['liga']) ? $_POST['liga'] : '';
    $evento_id = isset($_POST['evento_id']) ? $_POST['evento_id'] : '';
    $cantidad = isset($_POST['cantidad']) ? floatval($_POST['cantidad']) : 0;
    $rival_id = isset($_POST['rival_id']) ? intval($_POST['rival_id']) : 0;
    $ganador_esperado = isset($_POST['ganador_esperado']) ? $_POST['ganador_esperado'] : '';
    
    if (empty($liga)) {
        $errores['liga'] = 'Tienes que elegir una liga';
    }
    
    if (empty($evento_id)) {
        $errores['evento'] = 'Tienes que elegir un partido';
    }
    
    if ($cantidad <= 0) {
        $errores['cantidad'] = 'La cantidad debe ser mayor de 0€';
        // TODO: poner limite maximo de apuesta
    } elseif ($cantidad > $_SESSION['user_saldo']) {
        $errores['cantidad'] = 'No tienes saldo suficiente. Saldo actual: ' . number_format($_SESSION['user_saldo'], 2) . '€';
    }
    
    if ($rival_id <= 0) {
        $errores['rival'] = 'Escribe un ID de rival valido';
    } elseif ($rival_id == $_SESSION['user_id']) {
        $errores['rival'] = 'No te puedes retar a ti mismo';
    }
    
    if (empty($ganador_esperado)) {
        $errores['ganador'] = 'Selecciona quien crees que va a ganar';
    }
    
    if (empty($errores)) {
        $usuario_rival = $dao_usuarios->buscarPorId($rival_id);
        if (!$usuario_rival) {
            $errores['rival'] = 'Ese ID de rival no existe en el sistema';
        }
    }
    
    if (empty($errores)) {
        try {
            $evento = $dao_eventos->obtenerEventoPorId($evento_id);
            if (!$evento) {
                $errores['evento'] = 'Este partido no esta disponible para apostar';
            } elseif ($evento['estado'] != 'proximo') {
                $errores['evento'] = 'Este partido no esta disponible para apostar';
            }
        } catch (Exception $e) {
            $errores['evento'] = 'Error al comprobar el partido';
        }
    }
    
    if (empty($errores)) {
        try {
            $nueva_apuesta_id = $dao_apuestas->crearApuesta(
                $_SESSION['user_id'],
                $evento_id,
                $cantidad,
                $evento['nombre_evento'],
                $ganador_esperado
            );
            
            $dao_participa->insertar($_SESSION['user_id'], $nueva_apuesta_id, 'creador');
            
            $nuevo_saldo = $_SESSION['user_saldo'] - $cantidad;
            $dao_usuarios->actualizarSaldo($_SESSION['user_id'], $nuevo_saldo);
            $_SESSION['user_saldo'] = $nuevo_saldo;
            
            $mensaje = $_SESSION['user_nombre'] . ' te ha retado a una apuesta de ' . number_format($cantidad, 2) . '€ en el partido ' . $evento['nombre_evento'];
            $dao_notificaciones->crearNotificacion($rival_id, 'NUEVA APUESTA', $mensaje, 'apuesta', $nueva_apuesta_id);
            
            $_SESSION['success'] = '¡Apuesta creada! Reto enviado al rival';
            header('Location: apuestas.php');
            exit();
            
        } catch (Exception $e) {
            $errores['general'] = 'Hubo un error al crear la apuesta: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Apuesta - BetMatch</title>
    <link rel="stylesheet" href="assets/css/crear-apuesta.css?v=<?php echo time(); ?>">
</head>
<body>

<header>
    <a href="dashboard.php" class="logo">
        <span class="logo-text">BetMatch</span>
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
        <a href="notificaciones.php">Notificaciones</a>
        <a href="apuestas.php">Apuestas</a>
        <a href="perfil.php">Perfil</a>
    </div>
</header>

<main>
    <div class="crear-apuesta-container">
        <h1>¡Vamos a crear una Apuesta!</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errores['general'])): ?>
            <div class="alert error"><?php echo $errores['general']; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="crear-apuesta-form" id="apuestaForm">
            
            <div class="form-group">
                <label>LIGA</label>
                <select name="liga" id="liga" required>
                    <option value="">▼ SELECCIONA UNA LIGA ▼</option>
                    <?php 
                    foreach ($ligas_disponibles as $liga_item): 
                    ?>
                        <option value="<?php echo htmlspecialchars($liga_item); ?>"
                            <?php 
                            if (isset($_POST['liga']) && $_POST['liga'] == $liga_item) {
                                echo 'selected'; 
                            }
                            ?>>
                            <?php echo htmlspecialchars($liga_item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php 
                if (isset($errores['liga'])) {
                    echo '<small class="error">'.$errores['liga'].'</small>'; 
                }
                ?>
            </div>
            
            <div class="form-group">
                <label>EVENTO</label>
                <select name="evento_id" id="evento" required disabled>
                    <option value="">▼ SELECCIONA UN EVENTO ▼</option>
                </select>
                <?php 
                if (isset($errores['evento'])) {
                    echo '<small class="error">'.$errores['evento'].'</small>'; 
                }
                ?>
            </div>
            
            <div class="form-group">
                <label>CANTIDAD (€)</label>
                <input type="number" name="cantidad" placeholder="Cantidad a apostar" step="0.01" min="1" 
                       value="<?php 
                       if (isset($_POST['cantidad'])) {
                           echo htmlspecialchars($_POST['cantidad']); 
                       } else {
                           echo '';
                       }
                       ?>" required>
                <?php 
                if (isset($errores['cantidad'])) {
                    echo '<small class="error">'.$errores['cantidad'].'</small>'; 
                }
                ?>
            </div>
            
            <div class="form-group">
                <label>ID DEL RIVAL</label>
                <input type="number" name="rival_id" placeholder="ID del usuario rival" min="1" 
                       value="<?php 
                       if (isset($_POST['rival_id'])) {
                           echo htmlspecialchars($_POST['rival_id']); 
                       } else {
                           echo '';
                       }
                       ?>" required>
                <small class="info">Puedes ver el ID de un usuario en su perfil</small>
                <?php 
                if (isset($errores['rival'])) {
                    echo '<small class="error">'.$errores['rival'].'</small>'; 
                }
                ?>
            </div>
            
            <div class="form-group">
                <label>GANADOR ESPERADO</label>
                <select name="ganador_esperado" id="ganador_esperado" required disabled>
                    <option value="">▼ GANADOR ESPERADO ▼</option>
                </select>
                <?php 
                if (isset($errores['ganador'])) {
                    echo '<small class="error">'.$errores['ganador'].'</small>'; 
                }
                ?>
            </div>

            <button type="submit" class="btn-enviar">CREAR APUESTA</button>
            <button type="button" class="btn-sincronizar" id="btnSincronizarEventos">Sincronizar eventos</button>
            <div id="resultadoSincronizacion" class="alert-info" style="display:none; margin-top: 12px;"></div>
        </form>
        
        <?php if (empty($ligas_disponibles)): ?>
            <div class="alert-warning">
                ⚠️ No hay ligas disponibles. El administrador debe sincronizar los partidos primero.
            </div>
        <?php endif; ?>
    </div>
</main>

<footer>
    <span>BetMatch</span>
    <span><a href="contacto.php" class="footer-link">Contacto</a></span>
</footer>

<script src="assets/js/crear-apuesta.js?v=<?php echo time(); ?>"></script>

</body>