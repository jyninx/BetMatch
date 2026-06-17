<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once '../app/dao/ApuestaDAO.php';
require_once '../app/dao/UsuarioDAO.php';
require_once '../app/dao/ParticipaDAO.php';
require_once '../app/dao/NotificacionDAO.php';
require_once '../app/dao/TransaccionDAO.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apuesta_id = isset($_POST['apuesta_id']) ? intval($_POST['apuesta_id']) : 0;
    $notificacion_id = isset($_POST['notificacion_id']) ? intval($_POST['notificacion_id']) : 0;
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    if ($apuesta_id > 0 && $accion != '') {
        try {
            $dao_apuestas = new ApuestaDAO();
            $dao_usuarios = new UsuarioDAO();
            $dao_participa = new ParticipaDAO();
            $dao_notificaciones = new NotificacionDAO();
            $dao_transacciones = new TransaccionDAO();

            $datos_apuesta = $dao_apuestas->obtenerApuestaPorId($apuesta_id);

            if (!$datos_apuesta) {
                $_SESSION['error'] = 'La apuesta no existe';
                header('Location: notificaciones.php');
                exit();
            }

            if ($accion === 'aceptar') {
                if ($datos_apuesta['estado'] != 'pendiente') {
                    $_SESSION['error'] = 'Esta apuesta ya no está disponible';
                    header('Location: notificaciones.php');
                    exit();
                }

                if ($_SESSION['user_saldo'] < $datos_apuesta['monto']) {
                    $_SESSION['error'] = 'No tienes suficiente saldo';
                    header('Location: notificaciones.php');
                    exit();
                }

                $dao_apuestas->aceptarApuesta($apuesta_id, $_SESSION['user_id']);
                $dao_participa->insertar($_SESSION['user_id'], $apuesta_id, 'aceptador');

                $saldo_despues = $_SESSION['user_saldo'] - $datos_apuesta['monto'];
                $dao_usuarios->actualizarSaldo($_SESSION['user_id'], $saldo_despues);
                $_SESSION['user_saldo'] = $saldo_despues;

                if ($notificacion_id > 0) {
                    $dao_notificaciones->eliminar($notificacion_id);
                }

                $_SESSION['success'] = 'Apuesta aceptada correctamente';
                header('Location: apuestas.php');
                exit();
            } 
            
            elseif ($accion === 'rechazar') {
                $id_creador = $datos_apuesta['id_creador'];
                $monto = $datos_apuesta['monto'];
                
                $datos_creador = $dao_usuarios->buscarPorId($id_creador);
                if ($datos_creador) {
                    $saldo_creador = $datos_creador['saldo'] + $monto;
                    $dao_usuarios->actualizarSaldo($id_creador, $saldo_creador);
                    
                    $dao_transacciones->crearTransaccion(
                        $id_creador,
                        'reembolso',
                        $monto,
                        $datos_creador['saldo'],
                        $saldo_creador,
                        'Apuesta rechazada por el rival',
                        'completada',
                        $apuesta_id
                    );
                    
                    $mensaje = "Tu rival ha rechazado la apuesta de " . number_format($monto, 2) . "€ en " . $datos_apuesta['evento_nombre'] . ". Se te ha reembolsado el dinero.";
                    $dao_notificaciones->crearNotificacion($id_creador, 'APUESTA RECHAZADA', $mensaje, 'apuesta', null);
                }
                
                $dao_apuestas->eliminarApuesta($apuesta_id);

                if ($notificacion_id > 0) {
                    $dao_notificaciones->eliminar($notificacion_id);
                }

                $_SESSION['success'] = 'Apuesta rechazada';
                header('Location: apuestas.php');
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al procesar: ' . $e->getMessage();
            header('Location: notificaciones.php');
            exit();
        }
    }

    $_SESSION['error'] = 'Peticion no valida';
    header('Location: notificaciones.php');
    exit();
}
?>