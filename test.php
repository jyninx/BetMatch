<?php
echo "Probando conexión directa a MySQL...<br>";

$servidor = 'localhost';
$base_datos = 'betmatch_db';
$usuario = 'root';
$clave = '';

try {
    $dsn = "mysql:host=" . $servidor . ";dbname=" . $base_datos;
    
    $conexion = new PDO($dsn, $usuario, $clave);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<span style='color:green'>✓ Conexión exitosa a la base de datos '" . $base_datos . "'</span><br>";
    
    $consulta = $conexion->query("SHOW TABLES");
    echo "<br>Tablas encontradas:<br>";
    
    while ($fila = $consulta->fetch()) {
        echo "- " . $fila[0] . "<br>";
    }
} catch (PDOException $error) {
    echo "<span style='color:red'>✗ Error: " . $error->getMessage() . "</span><br>";
    echo "<br>Verifica:<br>";
    echo "1. MySQL está corriendo en XAMPP<br>";
    echo "2. La base de datos '" . $base_datos . "' existe<br>";
    echo "3. Script ejecutado en mysql<br>";
}
?>