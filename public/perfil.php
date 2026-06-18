<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/dao/UsuarioDAO.php';
require_once '../app/dao/ParticipaDAO.php';
require_once '../app/dao/TransaccionDAO.php';

$dao_usuarios = new UsuarioDAO();
$dao_participa = new ParticipaDAO();
$dao_transaccion = new TransaccionDAO();

$datos_usuario = $dao_usuarios->buscarPorId($_SESSION['user_id']);

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['cambiar_email'])) {
        $nuevo_correo = isset($_POST['nuevo_email']) ? trim($_POST['nuevo_email']) : '';
        
        if (empty($nuevo_correo)) {
            $error = 'Email no valido';
        } elseif (!filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email no valido';
        } else {
            if ($dao_usuarios->buscarPorCorreo($nuevo_correo) && $nuevo_correo != $datos_usuario['email']) {
                $error = 'Ese email ya lo esta usando otra persona';
            }
            
            if ($error == '') {
                $resultado = $dao_usuarios->actualizarCorreo($_SESSION['user_id'], $nuevo_correo);
                if ($resultado) {
                    $_SESSION['user_email'] = $nuevo_correo;
                    $datos_usuario['email'] = $nuevo_correo;
                    $mensaje = 'Email cambiado con exito';
                } else {
                    $error = 'Vaya, hubo un problema al cambiar el email';
                }
            }
        }
    }
    
    if (isset($_POST['cambiar_password'])) {
        $clave_vieja = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
        $clave_nueva = isset($_POST['nueva_password']) ? $_POST['nueva_password'] : '';
        $clave_confirmada = isset($_POST['confirmar_password']) ? $_POST['confirmar_password'] : '';
        
        if (!password_verify($clave_vieja, $datos_usuario['password'])) {
            $error = 'La contraseña actual no es correcta';
        } elseif (strlen($clave_nueva) < 6) {
            $error = 'La nueva contraseña tiene que tener al menos 6 caracteres';
        } elseif ($clave_nueva !== $clave_confirmada) {
            $error = 'Las contraseñas nuevas no coinciden';
        } else {
            $clave_encriptada = password_hash($clave_nueva, PASSWORD_DEFAULT);
            $resultado = $dao_usuarios->actualizarContrasena($_SESSION['user_id'], $clave_encriptada);
            if ($resultado) {
                $mensaje = 'Contraseña cambiada con exito';
            } else {
                $error = 'Vaya, no se pudo cambiar la contraseña';
            }
        }
    }
    
    if (isset($_POST['retiro'])) {
        $cantidad_retiro = isset($_POST['cantidad']) ? floatval($_POST['cantidad']) : 0;
        
        if ($cantidad_retiro <= 0) {
            $error = 'Pon una cantidad mayor a 0€';
        } elseif ($cantidad_retiro > $datos_usuario['saldo']) {
            $error = 'No tienes tanto dinero en tu saldo. Tu saldo es: ' . number_format($datos_usuario['saldo'], 2) . '€';
        } elseif (empty($datos_usuario['cuenta_paypal'])) {
            $error = 'Necesitas registrar una cuenta PayPal primero haciendo un deposito';
        } else {
            try {
                $dao_transaccion->iniciarTransaccion();

                $saldo_despues = $datos_usuario['saldo'] - $cantidad_retiro;
                $dao_usuarios->actualizarSaldo($_SESSION['user_id'], $saldo_despues);

                $cantidad_negativa = 0 - $cantidad_retiro;

                $dao_transaccion->crearTransaccion(
                    $_SESSION['user_id'],
                    'retiro',
                    $cantidad_negativa,
                    $datos_usuario['saldo'],
                    $saldo_despues,
                    'Retiro a PayPal: ' . $datos_usuario['cuenta_paypal'], 
                    'pendiente'
                );

                $dao_transaccion->confirmarTransaccion();

                $_SESSION['user_saldo'] = $saldo_despues;
                $datos_usuario['saldo'] = $saldo_despues;
                $mensaje = 'Retiro solicitado. Se mandara a ' . htmlspecialchars($datos_usuario['cuenta_paypal']);

            } catch (Exception $e) {
                $dao_transaccion->revertirTransaccion();
                $error = 'Error con el retiro: ' . $e->getMessage();
            }
        }
    }
}

try {
    $total_apuestas = $dao_participa->contarPorUsuario($_SESSION['user_id']);
} catch (Exception $e) {
    $total_apuestas = 0;
}

$clave_escondida = str_repeat('*', 8) . '***';

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $mensaje = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil - BetMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/perfil.css?v=<?php echo time(); ?>">
</head>
<body>

<header>
    <a href="dashboard.php" class="logo">        <span class="logo-text">BetMatch</span>
    </a>
    <div class="nav">
        <?php
        $saldo_mostrar = isset($_SESSION['user_saldo']) ? $_SESSION['user_saldo'] : 0;
        ?>
        <span class="saldo"><?php echo number_format($saldo_mostrar, 2); ?>€</span>
        <a href="notificaciones.php">Notificaciones</a>
        <a href="apuestas.php">Apuestas</a>
        <a href="perfil.php" class="active">Perfil</a>
    </div>
</header>

<main>
    <div class="perfil-container">
        <h1>¡BIENVENIDO <?php echo strtoupper(htmlspecialchars($datos_usuario['nombre'])); ?>!</h1>
        
        <?php if ($mensaje != ''): ?>
            <div class="alert success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error != ''): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info-card">
            <div class="info-header">
                <span class="info-label">Mi ID de usuario</span>
            </div>
            <div class="info-value"><?php echo $_SESSION['user_id']; ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-header">
                <span class="info-label">Correo electrónico</span>
                <button type="button" class="btn-cambiar" data-abrir-modal="modal_email">Cambiar</button>
            </div>
            <div class="info-value"><?php echo htmlspecialchars($datos_usuario['email']); ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-header">
                <span class="info-label">Contraseña</span>
                <button type="button" class="btn-cambiar" data-abrir-modal="modal_contrasena">Cambiar</button>
            </div>
            <div class="info-value"><?php echo $clave_escondida; ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-header">
                <span class="info-label">Cuenta PayPal asociada</span>
            </div>
            <div class="info-value paypal-account">
                <?php 
                if (!empty($datos_usuario['cuenta_paypal'])) {
                    echo htmlspecialchars($datos_usuario['cuenta_paypal']);
                } else {
                    echo 'No tienes cuenta registrada. (Haz un deposito para registrarla)';
                }
                ?>
            </div>
        </div>
        
     

        <div class="info-card">
            <div class="info-header">
                <span class="info-label">Saldo actual</span>
            </div>
            <div class="info-value saldo"><?php echo number_format($datos_usuario['saldo'], 2); ?>€</div>
        </div>
        
        <div class="acciones-bancarias">
            <button type="button" class="btn-deposito" data-abrir-modal="modal_deposito">Depósito</button>
            <button type="button" class="btn-retiro" data-abrir-modal="modal_retiro">Retiro</button>
        </div>
        
        <div class="total-apuestas">
            <span class="total-label">TOTAL DE APUESTAS REALIZADAS</span>
            <span class="total-valor"><?php echo $total_apuestas; ?></span>
        </div>
        
        <div class="cerrar-sesion">
            <a href="logout.php" class="btn-cerrar-sesion">Cerrar sesión</a>
        </div>
    </div>
</main>

<footer>
    <span>BetMatch</span>
    <span><a href="contacto.php">Contacto</a></span>
</footer>

<div id="modal_email" class="modal">
    <div class="modal-content">
        <span class="close" data-cerrar-modal="modal_email">&times;</span>
        <h2>Cambiar correo electrónico</h2>
        <form method="POST">
            <input type="email" name="nuevo_email" placeholder="Nuevo correo electrónico" required>
            <button type="submit" name="cambiar_email">Actualizar email</button>
        </form>
    </div>
</div>

<div id="modal_contrasena" class="modal">
    <div class="modal-content">
        <span class="close" data-cerrar-modal="modal_contrasena">&times;</span>
        <h2>Cambiar contraseña</h2>
        <form method="POST">
            <input type="password" name="password_actual" placeholder="Contraseña actual" required>
            <input type="password" name="nueva_password" placeholder="Nueva contraseña" required>
            <input type="password" name="confirmar_password" placeholder="Confirmar nueva contraseña" required>
            <button type="submit" name="cambiar_password">Actualizar contraseña</button>
        </form>
    </div>
</div>

<div id="modal_deposito" class="modal">
    <div class="modal-content">
        <span class="close" data-cerrar-modal="modal_deposito">&times;</span>
        <h2>Depositar con PayPal</h2>
        <form action="paypal-deposit.php" method="GET">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #333;">Cantidad a depositar (€)</label>
                <input type="number" name="amount" placeholder="Ej: 20.00" step="0.01" min="1" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc;">
            </div>
            <button type="submit" style="width: 100%; padding: 14px; background: #FFC439; color: #1E2A38; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <span>💳</span> Pagar con PayPal
            </button>
        </form>
    </div>
</div>

<div id="modal_retiro" class="modal">
    <div class="modal-content">
        <span class="close" data-cerrar-modal="modal_retiro">&times;</span>
        <h2>Solicitar retiro</h2>
        
        <?php if ($datos_usuario['saldo'] <= 0): ?>
            <div class="alert error" style="margin-bottom: 15px;">
                ⚠️ No tienes saldo disponible para retirar.
            </div>
        <?php else: ?>
            <?php if (empty($datos_usuario['cuenta_paypal'])): ?>
                <div class="alert error" style="margin-bottom: 15px;">
                    ⚠️ No tienes una cuenta PayPal registrada. Realiza un depósito primero para registrar tu cuenta.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Cantidad a retirar (€)</label>
                <?php
                $boton_deshabilitado = '';
                if ($datos_usuario['saldo'] <= 0 || empty($datos_usuario['cuenta_paypal'])) {
                    $boton_deshabilitado = 'disabled';
                }
                ?>
                <input type="number" name="cantidad" placeholder="Cantidad (€)" step="0.01" min="1" 
                       max="<?php echo $datos_usuario['saldo']; ?>" required 
                       <?php echo $boton_deshabilitado; ?>>
            </div>
            
            <?php if (!empty($datos_usuario['cuenta_paypal'])): ?>
            <div class="form-group">
                <label>Cuenta PayPal destino</label>
                <input type="email" value="<?php echo htmlspecialchars($datos_usuario['cuenta_paypal']); ?>" disabled>
            </div>
            <?php endif; ?>
            
            <p class="saldo-disponible">Saldo disponible: <?php echo number_format($datos_usuario['saldo'], 2); ?>€</p>
            <button type="submit" name="retiro" <?php echo $boton_deshabilitado; ?>>
                Solicitar retiro
            </button>
        </form>
    </div>
</div>

<script src="assets/js/headerFix.js"></script>
<script src="assets/js/perfil.js?v=<?php echo time(); ?>"></script>
</body>
</html>