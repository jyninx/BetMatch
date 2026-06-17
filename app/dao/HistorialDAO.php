<?php
require_once __DIR__ . '/../config/database.php';

class HistorialDAO {
    private $db;

    public function __construct() {
        $this->db = conectar();
    }

    public function obtenerPorUsuario($id_usuario) {
        $consulta = $this->db->prepare("SELECT * FROM historial WHERE id_usuario = ? ORDER BY fecha DESC");
        $consulta->execute([$id_usuario]);
        $resultado = $consulta->fetchAll();
        return $resultado ? $resultado : array();
    }
}
?>
