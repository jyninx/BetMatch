<?php
require_once __DIR__ . '/../app/config/football-api.php';

if (empty(FOOTBALL_API_KEY)) {
    die('Error: FOOTBALL_API_KEY no está configurada en .env');
}

$solicitud = curl_init();

$opciones = array(
    CURLOPT_URL => FOOTBALL_API_URL . 'sport/football/scheduled-events/' . date("Y-m-d"),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array(
        "X-RapidAPI-Key: " . FOOTBALL_API_KEY,
        "X-RapidAPI-Host: " . FOOTBALL_API_HOST
    ),
);
curl_setopt_array($solicitud, $opciones);

$respuesta = curl_exec($solicitud);
$codigo_http = curl_getinfo($solicitud, CURLINFO_HTTP_CODE);
curl_close($solicitud);

if ($codigo_http == 200) {
    echo "✓ API conectada exitosamente\n";
    echo "Respuesta:\n";
    
    $datos = json_decode($respuesta, true);
    echo json_encode($datos, JSON_PRETTY_PRINT);
} else {
    echo "✗ Error al conectar con la API. Código HTTP: " . $codigo_http . "\n";
    echo "Respuesta:\n";
    echo $respuesta;
}
?>