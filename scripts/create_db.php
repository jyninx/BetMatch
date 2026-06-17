<?php
error_reporting(E_ALL);

require_once __DIR__ . '/../app/config/env.php';

$host = env('DB_HOST', 'localhost');
$puerto = env('DB_PORT', '3306');
$usuario = env('DB_USERNAME', 'root');
$clave = env('DB_PASSWORD', '');
$base_datos = env('DB_DATABASE', 'betmatch_db');
$archivo_schema = __DIR__ . '/../db/schema.sql';

try {
    $opciones = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
    $conexion = new PDO("mysql:host=" . $host . ";port=" . $puerto . ";charset=utf8mb4", $usuario, $clave, $opciones);

    echo "Conectado a MySQL en " . $host . ":" . $puerto . "\n";

    $sql_texto = file_get_contents($archivo_schema);
    if ($sql_texto === false) {
        throw new Exception('No se pudo leer ' . $archivo_schema);
    }

    $sentencias = explode(';', $sql_texto);
    
    $limpias = array();
    foreach ($sentencias as $sentencia) {
        $limpia = trim($sentencia);
        if ($limpia != '') {
            $limpias[] = $limpia;
        }
    }
    
    foreach ($limpias as $sentencia) {
        $conexion->exec($sentencia);
    }

    echo "Esquema importado correctamente.\n";

    $conexion->exec("USE `" . $base_datos . "`");

    $correo_admin = 'admin@betmatch.local';
    $dni_admin = '00000000A';
    
    $buscar_admin = $conexion->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $buscar_admin->execute(array(':email' => $correo_admin));
    $admin_existe = $buscar_admin->fetch();
    
    if ($admin_existe) {
        echo "El usuario admin ya existe.\n";
    } else {
        $clave_encriptada = password_hash('Admin123', PASSWORD_DEFAULT);
        $insertar_admin = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, email, dni, password, telefono, ciudad, calle, fecha_nacimiento, rol, saldo, fecha_registro) VALUES (:nombre, :apellidos, :email, :dni, :clave, :telefono, :ciudad, :calle, :fecha_nacimiento, 'admin', 0, NOW())");
        
        $datos_admin = array(
            ':nombre' => 'Admin',
            ':apellidos' => 'BetMatch',
            ':email' => $correo_admin,
            ':dni' => $dni_admin,
            ':clave' => $clave_encriptada,
            ':telefono' => '',
            ':ciudad' => '',
            ':calle' => '',
            ':fecha_nacimiento' => null
        );
        $insertar_admin->execute($datos_admin);
        
        echo "Usuario admin creado: " . $correo_admin . " (clave: Admin123)\n";
    }

    $contar_eventos = $conexion->query('SELECT COUNT(*) FROM eventos');
    $total_eventos = $contar_eventos->fetchColumn();
    
    if ($total_eventos == 0) {
        $partidos_ejemplo = array(
            array('Real Madrid vs Barcelona', 'LaLiga', date('Y-m-d H:i:s', strtotime('+2 days 19:00'))),
            array('Manchester United vs Chelsea', 'Premier League', date('Y-m-d H:i:s', strtotime('+3 days 17:30'))),
            array('Juventus vs AC Milan', 'Serie A', date('Y-m-d H:i:s', strtotime('+4 days 20:00')))
        );

        $insertar_evento = $conexion->prepare('INSERT INTO eventos (nombre_evento, liga, fecha_evento, estado) VALUES (:nombre_evento, :liga, :fecha_evento, "proximo")');
        
        foreach ($partidos_ejemplo as $partido) {
            $datos_partido = array(
                ':nombre_evento' => $partido[0], 
                ':liga' => $partido[1], 
                ':fecha_evento' => $partido[2]
            );
            $insertar_evento->execute($datos_partido);
        }
        echo "Se insertaron eventos de demostración para que la aplicación funcione inmediatamente.\n";
    }

    echo "Listo. Puedes eliminar este script después de usarlo.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>