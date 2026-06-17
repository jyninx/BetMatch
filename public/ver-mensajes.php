<?php
session_start();

$es_admin = false;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin') {
    $es_admin = true;
}

if (!$es_admin) {
    header('Location: dashboard.php');
    exit();
}

$ruta_archivo = __DIR__ . '/mensajes_contacto.txt';

if (file_exists($ruta_archivo)) {
    echo "<pre>";
    $contenido = file_get_contents($ruta_archivo);
    echo $contenido;
    echo "</pre>";
} else {
    echo "No hay mensajes aún.";
}
?>