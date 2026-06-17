<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Acceso denegado. Solo administradores pueden finalizar apuestas.');
} elseif ($_SESSION['user_rol'] != 'admin') {
    die('Acceso denegado. Solo administradores pueden finalizar apuestas.');
}

require_once '../app/dao/ApuestaDAO.php';
require_once '../app/dao/UsuarioDAO.php';
require_once '../app/dao/TransaccionDAO.php';
require_once '../app/dao/NotificacionDAO.php';
require_once '../app/config/football-api.php';

$mensaje = '';
$error = '';

$dao_apuestas = new ApuestaDAO();
$dao_usuarios = new UsuarioDAO();
$dao_transacciones = new TransaccionDAO();
$dao_notificaciones = new NotificacionDAO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $apuestas = $dao_apuestas->obtenerApuestasActivasPorFinalizar();
        
        if (empty($apuestas)) {
            $mensaje = "No hay apuestas pendientes de finalizar ahora mismo.";
        } else {
            $finalizadas = 0;
            $devoluciones = 0;
            
            foreach ($apuestas as $apuesta) {
                $url_api = FOOTBALL_API_URL . "sport/football/event/" . $apuesta['id_evento_api'] . "/details";

                $solicitud = curl_init();
                curl_setopt($solicitud, CURLOPT_URL, $url_api);
                curl_setopt($solicitud, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($solicitud, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($solicitud, CURLOPT_HTTPHEADER, array(
                    'X-RapidAPI-Key: ' . FOOTBALL_API_KEY,
                    'X-RapidAPI-Host: ' . FOOTBALL_API_HOST
                ));

                $respuesta = curl_exec($solicitud);
                curl_close($solicitud);
                
                $datos = json_decode($respuesta, true);
                
                if (isset($datos['event']['homeScore']) && isset($datos['event']['awayScore'])) {
                    $local = $datos['event']['homeScore'];
                    $visitante = $datos['event']['awayScore'];
                    $resultado_texto = $local . '-' . $visitante;
                    
                    $partes = explode(' vs ', $apuesta['evento_nombre']);
                    $equipo_local = trim($partes[0]);
                    $equipo_visitante = trim($partes[1]);
                    
                    if ($local == $visitante) {
                        $usuario_creador = $dao_usuarios->buscarPorId($apuesta['id_creador']);
                        $nuevo_saldo_creador = $usuario_creador['saldo'] + $apuesta['monto'];
                        $dao_usuarios->actualizarSaldo($apuesta['id_creador'], $nuevo_saldo_creador);
                        $dao_transacciones->crearTransaccion(
                            $apuesta['id_creador'],
                            'reembolso',
                            $apuesta['monto'],
                            $usuario_creador['saldo'],
                            $nuevo_saldo_creador,
                            'Empate - Devolucion de apuesta',
                            'completada',
                            $apuesta['id']
                        );

                        if ($apuesta['id_aceptador']) {
                            $usuario_aceptador = $dao_usuarios->buscarPorId($apuesta['id_aceptador']);
                            $nuevo_saldo_aceptador = $usuario_aceptador['saldo'] + $apuesta['monto'];
                            $dao_usuarios->actualizarSaldo($apuesta['id_aceptador'], $nuevo_saldo_aceptador);
                            $dao_transacciones->crearTransaccion(
                                $apuesta['id_aceptador'],
                                'reembolso',
                                $apuesta['monto'],
                                $usuario_aceptador['saldo'],
                                $nuevo_saldo_aceptador,
                                'Empate - Devolucion de apuesta',
                                'completada',
                                $apuesta['id']
                            );
                        }

                        $dao_apuestas->cancelarApuesta($apuesta['id'], $resultado_texto);

                        $dao_notificaciones->crearNotificacion(
                            $apuesta['id_creador'],
                            'PARTIDO EMPATADO',
                            'El partido ' . $apuesta['evento_nombre'] . ' termino en empate. Se te devuelven ' . number_format($apuesta['monto'], 2) . '€.',
                            'apuesta'
                        );

                        if ($apuesta['id_aceptador']) {
                            $dao_notificaciones->crearNotificacion(
                                $apuesta['id_aceptador'],
                                'PARTIDO EMPATADO',
                                'El partido ' . $apuesta['evento_nombre'] . ' termino en empate. Se te devuelven ' . number_format($apuesta['monto'], 2) . '€.',
                                'apuesta'
                            );
                        }

                        $devoluciones++;

                    } else {
                        $ganador_id = null;
                        
                        $aposto_local = ($apuesta['resultado'] == $equipo_local);
                        $aposto_visitante = ($apuesta['resultado'] == $equipo_visitante);
                        $gano_local = ($local > $visitante);
                        $gano_visitante = ($visitante > $local);
                        
                        if (($aposto_local && $gano_local) || ($aposto_visitante && $gano_visitante)) {
                            $ganador_id = $apuesta['id_creador'];
                        } else {
                            $ganador_id = $apuesta['id_aceptador'];
                        }
                        
                        if ($ganador_id) {
                            $dinero_ganado = $apuesta['monto'] * 2;
                            $dao_apuestas->finalizarApuesta($apuesta['id'], $ganador_id, $resultado_texto);

                            $usuario_ganador = $dao_usuarios->buscarPorId($ganador_id);
                            $nuevo_saldo = $usuario_ganador['saldo'] + $dinero_ganado;
                            $dao_usuarios->actualizarSaldo($ganador_id, $nuevo_saldo);

                            $dao_transacciones->crearTransaccion(
                                $ganador_id,
                                'ganancia',
                                $dinero_ganado,
                                $usuario_ganador['saldo'],
                                $nuevo_saldo,
                                'Ganancia de apuesta',
                                'completada',
                                $apuesta['id']
                            );

                            $dao_notificaciones->crearNotificacion(
                                $ganador_id,
                                'APUESTA GANADA',
                                '¡Felicidades! Has ganado ' . number_format($dinero_ganado, 2) . '€ en ' . $apuesta['evento_nombre'],
                                'apuesta'
                            );

                            if ($ganador_id == $apuesta['id_creador']) {
                                $perdedor_id = $apuesta['id_aceptador'];
                            } else {
                                $perdedor_id = $apuesta['id_creador'];
                            }

                            if ($perdedor_id) {
                                $dao_notificaciones->crearNotificacion(
                                    $perdedor_id,
                                    'APUESTA PERDIDA',
                                    'Has perdido ' . number_format($apuesta['monto'], 2) . '€ en ' . $apuesta['evento_nombre'],
                                    'apuesta'
                                );
                            }

                            $finalizadas++;
                        }
                    }
                }
            }
            
            $mensaje = "✅ Finalizadas: " . $finalizadas . " apuestas. Devoluciones por empate: " . $devoluciones . ".";
        }
        
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

try {
    $lista_apuestas_activas = $dao_apuestas->obtenerListaApuestasActivas();
} catch (Exception $e) {
    $lista_apuestas_activas = array();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Apuestas - BetMatch</title>
    <link rel="stylesheet" href="assets/css/admin-tools.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <h1>Finalizar Apuestas</h1>
        <p>Comprueba los resultados de los partidos y asigna los ganadores.</p>
        
        <?php if ($mensaje != ''): ?>
            <div class="alert-success"> <?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error != ''): ?>
            <div class="alert-error"> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <button type="submit" class="btn">Finalizar Apuestas Pendientes</button>
        </form>
        
        <h2>Apuestas Activas</h2>
        <?php if (empty($lista_apuestas_activas)): ?>
            <p>No hay apuestas activas en este momento.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Creador</th>
                        <th>Aceptador</th>
                        <th>Monto</th>
                        <th>Fecha Partido</th>
                        <th>Ganador Esperado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_apuestas_activas as $apuesta): ?>
                        <tr>
                            <td><?php echo $apuesta['id']; ?></td>
                            <td><?php echo htmlspecialchars($apuesta['evento_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($apuesta['creador']); ?></td>
                            <td>
                                <?php 
                                    if (isset($apuesta['aceptador']) && $apuesta['aceptador'] != null) {
                                        echo htmlspecialchars($apuesta['aceptador']);
                                    } else {
                                        echo 'Pendiente';
                                    }
                                ?>
                            </td>
                            <td><?php echo number_format($apuesta['monto'], 2); ?>€</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($apuesta['fecha_evento'])); ?></td>
                            <td><?php echo htmlspecialchars($apuesta['resultado']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>