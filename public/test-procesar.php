<?php
session_start();
echo "<h1>Test de procesamiento</h1>";
echo "<pre>";
echo "POST: ";
print_r($_POST);
echo "GET: ";
print_r($_GET);
echo "SESSION: ";
print_r($_SESSION);
echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<p style='color:green'>Método POST detectado</p>";
    
    $apuesta_id = isset($_POST['apuesta_id']) ? $_POST['apuesta_id'] : 'no';
    $notificacion_id = isset($_POST['notificacion_id']) ? $_POST['notificacion_id'] : 'no';
    $accion = isset($_POST['accion']) ? $_POST['accion'] : 'no';
    
    echo "<p>Apuesta ID: " . $apuesta_id . "</p>";
    echo "<p>Notificacion ID: " . $notificacion_id . "</p>";
    echo "<p>Accion: " . $accion . "</p>";
    
    if ($accion == 'aceptar') {
        echo "<p style='color:green'>ACEPTAR - Redirigiendo a apuestas.php</p>";
    } elseif ($accion == 'rechazar') {
        echo "<p style='color:orange'>RECHAZAR - Redirigiendo a notificaciones.php</p>";
    }
}
?>