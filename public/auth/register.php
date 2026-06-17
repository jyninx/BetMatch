<?php
session_start();

require_once '../../app/config/database.php';
require_once '../../app/dao/UsuarioDAO.php';

$errores = array();
$datos_form = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $dni = isset($_POST['dni']) ? trim($_POST['dni']) : '';
    $ciudad = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : '';
    $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $calle = isset($_POST['calle']) ? trim($_POST['calle']) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
    $fecha_nacimiento = isset($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : '';
    
    $datos_form['correo'] = $correo;
    $datos_form['dni'] = $dni;
    $datos_form['ciudad'] = $ciudad;
    $datos_form['clave'] = $clave;
    $datos_form['telefono'] = $telefono;
    $datos_form['calle'] = $calle;
    $datos_form['nombre'] = $nombre;
    $datos_form['apellidos'] = $apellidos;
    $datos_form['fecha_nacimiento'] = $fecha_nacimiento;
    
    if (empty($datos_form['nombre'])) {
        $errores['nombre'] = 'El nombre no puede estar vacio.';
    }
    
    if (empty($datos_form['apellidos'])) {
        $errores['apellidos'] = 'Pon tus apellidos.';
    }
    
    if (empty($datos_form['correo'])) {
        $errores['correo'] = 'El correo es obligatorio.';
    } elseif (!filter_var($datos_form['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Ese correo no tiene buen formato.';
    }
    
    if (empty($datos_form['dni'])) {
        $errores['dni'] = 'El DNI es obligatorio.';
    } else {
        $dni_mayus = strtoupper($datos_form['dni']);
        if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni_mayus)) {
            $errores['dni'] = 'El DNI no es valido (ej: 12345678A).';
        } else {
            $datos_form['dni'] = $dni_mayus;
        }
    }
    
    if (empty($datos_form['clave'])) {
        $errores['clave'] = 'Necesitas una contraseña.';
    } elseif (strlen($datos_form['clave']) < 6) {
        $errores['clave'] = 'La contraseña tiene que tener al menos 6 caracteres.';
    }
    
    if (empty($datos_form['telefono'])) {
        $errores['telefono'] = 'Pon tu telefono.';
    } elseif (!preg_match('/^[0-9]{9}$/', $datos_form['telefono'])) {
        $errores['telefono'] = 'El telefono tiene que tener 9 digitos.';
    }
    
    if (empty($datos_form['ciudad'])) {
        $errores['ciudad'] = 'Pon tu ciudad.';
    }
    
    if (empty($datos_form['calle'])) {
        $errores['calle'] = 'Pon tu direccion.';
    }
    
    if (empty($datos_form['fecha_nacimiento'])) {
        $errores['fecha_nacimiento'] = 'Pon tu fecha de nacimiento.';
    } else {
        $fecha_nac = new DateTime($datos_form['fecha_nacimiento']);
        $fecha_hoy = new DateTime();
        $diferencia = $fecha_hoy->diff($fecha_nac);
        $edad = $diferencia->y;
        
        if ($edad < 18) {
            $errores['fecha_nacimiento'] = 'Tienes que ser mayor de 18 años para registrarte.';
        }
    }
    
    if (empty($errores)) {
        $dao_usuario = new UsuarioDAO();
        $existe_correo = $dao_usuario->buscarPorCorreo($datos_form['correo']);
        
        if ($existe_correo) {
            $errores['correo'] = 'Ese correo ya lo tiene otra persona registrada.';
        }
    }
    
    if (empty($errores)) {
        $existe_dni = $dao_usuario->buscarPorDni($datos_form['dni']);
        
        if ($existe_dni) {
            $errores['dni'] = 'Ese DNI ya esta registrado en el sistema.';
        }
    }
    
    if (empty($errores)) {
        $clave_encriptada = password_hash($datos_form['clave'], PASSWORD_DEFAULT);
        
        $datos_crear = array(
            'nombre'           => $datos_form['nombre'],
            'apellidos'        => $datos_form['apellidos'],
            'correo'          => $datos_form['correo'],
            'dni'              => $datos_form['dni'],
            'clave'            => $clave_encriptada,
            'telefono'         => $datos_form['telefono'],
            'ciudad'           => $datos_form['ciudad'],
            'calle'            => $datos_form['calle'],
            'fecha_nacimiento' => $datos_form['fecha_nacimiento'],
            'rol'              => 'usuario'
        );
        
        $creado = $dao_usuario->crearUsuario($datos_crear);
        
        if ($creado) {
            $_SESSION['success'] = '¡Ya estas registrado! Ahora puedes iniciar sesion.';
            header('Location: login.php');
            exit();
        } else {
            $errores['general'] = 'Algo fallo al crear la cuenta. Intentalo de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - BetMatch</title>
    <link rel="stylesheet" href="../assets/css/register.css">
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
    <div class="register-box">
        <h2>¡BIENVENIDO!</h2>

        <?php if (isset($errores['general'])): ?>
            <div class="alert alert-error">
                <?php echo $errores['general']; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="register-form">
            <div class="form-group">
                <label>CORREO</label>
                <input type="email" name="correo" placeholder="tu@email.com" value="<?php if (isset($datos_form['correo'])) { echo htmlspecialchars($datos_form['correo']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['correo'])): ?>
                    <small class="error"><?php echo $errores['correo']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>DNI</label>
                <input type="text" name="dni" placeholder="12345678A" value="<?php if (isset($datos_form['dni'])) { echo htmlspecialchars($datos_form['dni']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['dni'])): ?>
                    <small class="error"><?php echo $errores['dni']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>CIUDAD</label>
                <input type="text" name="ciudad" placeholder="Madrid" value="<?php if (isset($datos_form['ciudad'])) { echo htmlspecialchars($datos_form['ciudad']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['ciudad'])): ?>
                    <small class="error"><?php echo $errores['ciudad']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>CONTRASEÑA</label>
                <input type="password" name="clave" placeholder="••••••" required>
                <?php if (isset($errores['clave'])): ?>
                    <small class="error"><?php echo $errores['clave']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>TELÉFONO</label>
                <input type="tel" name="telefono" placeholder="612345678" value="<?php if (isset($datos_form['telefono'])) { echo htmlspecialchars($datos_form['telefono']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['telefono'])): ?>
                    <small class="error"><?php echo $errores['telefono']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>CALLE</label>
                <input type="text" name="calle" placeholder="C/ Mayor 123" value="<?php if (isset($datos_form['calle'])) { echo htmlspecialchars($datos_form['calle']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['calle'])): ?>
                    <small class="error"><?php echo $errores['calle']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>NOMBRE</label>
                <input type="text" name="nombre" placeholder="Juan" value="<?php if (isset($datos_form['nombre'])) { echo htmlspecialchars($datos_form['nombre']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['nombre'])): ?>
                    <small class="error"><?php echo $errores['nombre']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>APELLIDOS</label>
                <input type="text" name="apellidos" placeholder="Pérez García" value="<?php if (isset($datos_form['apellidos'])) { echo htmlspecialchars($datos_form['apellidos']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['apellidos'])): ?>
                    <small class="error"><?php echo $errores['apellidos']; ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>FECHA DE NACIMIENTO</label>
                <input type="date" name="fecha_nacimiento" value="<?php if (isset($datos_form['fecha_nacimiento'])) { echo htmlspecialchars($datos_form['fecha_nacimiento']); } else { echo ''; } ?>" required>
                <?php if (isset($errores['fecha_nacimiento'])): ?>
                    <small class="error"><?php echo $errores['fecha_nacimiento']; ?></small>
                <?php endif; ?>
            </div>

            <div class="register-btn">
                <button type="submit">REGISTRARSE</button>
            </div>
        </form>

        <p class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </div>
</main>

<footer>
    <span>BetMatch</span>
    <span><a href="../contacto.php" class="footer-link">Contacto</a></span>
</footer>

</body>
</html>