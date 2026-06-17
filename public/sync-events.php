<?php
session_start();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

set_error_handler(function($nivel, $mensaje, $archivo, $linea) {
    throw new Exception($mensaje);
});

require_once '../app/dao/EventoDAO.php';
require_once '../app/config/football-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

$fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
$dias = isset($_POST['dias']) ? intval($_POST['dias']) : 7;

if ($dias < 1) {
    $dias = 1;
}

if ($dias > 30) {
    $dias = 30;
}

if (empty(FOOTBALL_API_KEY) || empty(FOOTBALL_API_HOST) || empty(FOOTBALL_API_URL)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Faltan variables de RapidAPI'
    ]);
    exit;
}

$dao_eventos = new EventoDAO();
$nuevos = 0;
$actualizados = 0;
$total = 0;

try {
    for ($dia = 0; $dia < $dias; $dia++) {
        $fecha = date('Y-m-d', strtotime($fecha_inicio . " +$dia day"));
        $url_api = FOOTBALL_API_URL . 'sport/football/scheduled-events/' . $fecha;

        $solicitud = curl_init();
        curl_setopt($solicitud, CURLOPT_URL, $url_api);
        curl_setopt($solicitud, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($solicitud, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($solicitud, CURLOPT_TIMEOUT, 10);
        curl_setopt($solicitud, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($solicitud, CURLOPT_HTTPHEADER, [
            'X-RapidAPI-Key: ' . FOOTBALL_API_KEY,
            'X-RapidAPI-Host: ' . FOOTBALL_API_HOST
        ]);

        $respuesta = curl_exec($solicitud);
        $codigo_http = curl_getinfo($solicitud, CURLINFO_HTTP_CODE);
        $error_curl = curl_error($solicitud);
        curl_close($solicitud);

        if ($respuesta === false || $respuesta === '') {
            throw new Exception('No hubo respuesta de la API. ' . $error_curl);
        }

        if ($codigo_http == 429) {
            throw new Exception('RapidAPI bloqueó la petición. Revisa tu plan o espera unos minutos.');
        }

        if ($codigo_http != 200) {
            $datos_error = json_decode($respuesta, true);
            $detalle = $datos_error['message'] ?? $respuesta;
            throw new Exception('Error de la API HTTP ' . $codigo_http . ': ' . $detalle);
        }

        $datos = json_decode($respuesta, true);

        if (!isset($datos['events']) || !is_array($datos['events'])) {
            continue;
        }

        foreach ($datos['events'] as $partido) {
            $total++;

            $id_externo = $partido['id'] ?? null;
            $local = $partido['homeTeam']['name'] ?? 'Local';
            $visitante = $partido['awayTeam']['name'] ?? 'Visitante';
            $nombre_evento = $local . ' vs ' . $visitante;
            $liga = $partido['tournament']['name'] ?? 'Fútbol';
            $fecha_evento = $partido['startTimestamp'] ?? $partido['startTime'] ?? time();

            if (is_numeric($fecha_evento)) {
                $fecha_evento = date('Y-m-d H:i:s', $fecha_evento);
            }

            if ($id_externo) {
                $existe = $dao_eventos->obtenerPorIdExterno($id_externo);

                if ($existe) {
                    $dao_eventos->actualizarPorIdExterno($id_externo, $nombre_evento, $liga, $fecha_evento);
                    $actualizados++;
                } else {
                    $dao_eventos->insertarConIdExterno($id_externo, $nombre_evento, $liga, $fecha_evento);
                    $nuevos++;
                }
            } else {
                if (!$dao_eventos->existePorNombreYFecha($nombre_evento, $fecha_evento)) {
                    $dao_eventos->insertarEvento($nombre_evento, $liga, $fecha_evento);
                    $nuevos++;
                }
            }
        }
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => "Listo. Procesados: $total | Insertados: $nuevos | Actualizados: $actualizados"
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
?>
