<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Tienes que estar logueado para acceder aqui.');
}

require_once '../app/dao/EventoDAO.php';
require_once '../app/config/football-api.php';

$mensaje = '';
$error = '';

$dao_eventos = new EventoDAO();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
    
    try {
        $url_api = FOOTBALL_API_URL . "sport/football/scheduled-events/" . $fecha;
        
        $solicitud = curl_init();
        curl_setopt($solicitud, CURLOPT_URL, $url_api);
        curl_setopt($solicitud, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($solicitud, CURLOPT_SSL_VERIFYPEER, false);
        
        $cabeceras = array(
            'X-RapidAPI-Key: ' . FOOTBALL_API_KEY,
            'X-RapidAPI-Host: ' . FOOTBALL_API_HOST
        );
        curl_setopt($solicitud, CURLOPT_HTTPHEADER, $cabeceras);
        
        $respuesta = curl_exec($solicitud);
        $codigo = curl_getinfo($solicitud, CURLINFO_HTTP_CODE);
        curl_close($solicitud);
        
        if ($codigo == 200) {
            $datos = json_decode($respuesta, true);
            
            if (isset($datos['events']) && count($datos['events']) > 0) {
                $insertados = 0;
                $actualizados = 0;
                
                foreach ($datos['events'] as $partido) {
                    $id_externo = isset($partido['id']) ? $partido['id'] : null;
                    $local = isset($partido['homeTeam']['name']) ? $partido['homeTeam']['name'] : 'Local';
                    $visitante = isset($partido['awayTeam']['name']) ? $partido['awayTeam']['name'] : 'Visitante';
                    
                    $nombre = $local . ' vs ' . $visitante;
                    $liga = isset($partido['tournament']['name']) ? $partido['tournament']['name'] : 'Fútbol';
                    
                    if (isset($partido['startTimestamp'])) {
                        $fecha_evento = $partido['startTimestamp'];
                    } elseif (isset($partido['startTime'])) {
                        $fecha_evento = $partido['startTime'];
                    } else {
                        $fecha_evento = date('Y-m-d H:i:s');
                    }
                    
                    if (is_numeric($fecha_evento)) {
                        $fecha_evento = date('Y-m-d H:i:s', $fecha_evento);
                    }
                    
                    $existe = $dao_eventos->obtenerPorIdExterno($id_externo);
                    if ($existe) {
                        $dao_eventos->actualizarPorIdExterno($id_externo, $nombre, $liga, $fecha_evento);
                        $actualizados++;
                    } else {
                        $dao_eventos->insertarConIdExterno($id_externo, $nombre, $liga, $fecha_evento);
                        $insertados++;
                    }
                }
                
                $total = count($datos['events']);
                $mensaje = "✅ Insertados: " . $insertados . " | Actualizados: " . $actualizados . " | Total partidos: " . $total;
            } else {
                $error = 'No había partidos para la fecha ' . $fecha;
            }
        } else {
            $error = 'La API devolvió error. Código: ' . $codigo;
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sincronizar Eventos Futuros</title>
    <link rel="stylesheet" href="assets/css/admin-tools.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <h1>📡 Sincronizar Eventos Futuros</h1>
        <p>Trae los partidos programados de una fecha y los guarda en la tabla de eventos.</p>
        
        <?php if ($mensaje != ''): ?>
            <div class="alert-success">✅ <?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error != ''): ?>
            <div class="alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label>Fecha (YYYY-MM-DD):</label>
            <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
            
            <button type="submit" class="btn">Sincronizar Partidos</button>
        </form>
        
        <div class="info">
            <p>📌 Los partidos se guardan en la tabla "eventos".</p>
            <p>📌 Luego aparecen disponibles en "Crear apuesta".</p>
        </div>
    </div>
</body>
</html>