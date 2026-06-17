<?php
function asegurar_tablas($conexion) {
    $consulta = $conexion->query("SHOW TABLES LIKE 'usuarios'");
    if ($consulta->rowCount() > 0) {
        return;
    }

    $tablas = array(
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellidos VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            dni VARCHAR(20) NOT NULL UNIQUE,
            password VARCHAR(255) DEFAULT '',
            telefono VARCHAR(20),
            ciudad VARCHAR(100),
            calle VARCHAR(255),
            fecha_nacimiento DATE,
            rol VARCHAR(50) DEFAULT 'usuario',
            saldo DECIMAL(12,2) NOT NULL DEFAULT 0,
            fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
            ultimo_acceso DATETIME NULL,
            cuenta_paypal VARCHAR(255) NULL,
            updated_at DATETIME NULL
        )",
        "CREATE TABLE IF NOT EXISTS eventos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_evento_api VARCHAR(100) NULL,
            nombre_evento VARCHAR(255) NOT NULL,
            liga VARCHAR(255) NULL,
            fecha_evento DATETIME NOT NULL,
            estado VARCHAR(50) DEFAULT 'proximo',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS apuestas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_creador INT NOT NULL,
            id_aceptador INT NULL,
            id_evento INT NOT NULL,
            monto DECIMAL(12,2) NOT NULL,
            evento_nombre VARCHAR(255) NOT NULL,
            estado VARCHAR(50) DEFAULT 'pendiente',
            resultado VARCHAR(100) NULL,
            ganador_id INT NULL,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_aceptacion DATETIME NULL,
            fecha_finalizacion DATETIME NULL,
            FOREIGN KEY (id_creador) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (id_aceptador) REFERENCES usuarios(id) ON DELETE SET NULL,
            FOREIGN KEY (id_evento) REFERENCES eventos(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS transacciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            cantidad DECIMAL(12,2) NOT NULL,
            saldo_anterior DECIMAL(12,2) NOT NULL,
            saldo_nuevo DECIMAL(12,2) NOT NULL,
            concepto VARCHAR(255),
            id_apuesta_relacionada INT NULL,
            estado VARCHAR(50) DEFAULT 'completada',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS historial (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            tipo VARCHAR(50) NULL,
            descripcion TEXT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS notificaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            titulo VARCHAR(255),
            mensaje TEXT,
            tipo VARCHAR(50),
            id_apuesta_relacionada INT NULL,
            leida TINYINT(1) DEFAULT 0,
            fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS participa (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_apuesta INT NOT NULL,
            rol VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (id_apuesta) REFERENCES apuestas(id) ON DELETE CASCADE
        )"
    );

    foreach ($tablas as $tabla) {
        $conexion->exec($tabla);
    }
}

function conectar() {
    static $conexion = null;

    if ($conexion === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $db = getenv('DB_DATABASE') ?: 'betmatch_db';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';
        $port = getenv('DB_PORT') ?: 3306;

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

        $conexion = new PDO($dsn, $user, $pass);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        asegurar_tablas($conexion);
    }

    return $conexion;
}
?>
