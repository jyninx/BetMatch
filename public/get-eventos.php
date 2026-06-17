<?php
header('Content-Type: application/json');

require_once '../app/dao/EventoDAO.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['liga'])) {
        $liga = $_POST['liga'];
        
        try {
            $dao = new EventoDAO();
            $eventos = $dao->obtenerEventosPorLiga($liga);
            
            echo json_encode($eventos);
        } catch (Exception $e) {
            echo json_encode(array());
        }
    } else {
        echo json_encode(array());
    }
} else {
    echo json_encode(array());
}
?>