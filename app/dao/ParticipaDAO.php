<?php
require_once __DIR__ . '/../config/database.php';

class ParticipaDAO {
    private $db;

    public function __construct() {
        $this->db = conectar();
    }

    public function contarPorUsuario($id_usuario) {
        $sql = "SELECT COUNT(*) as total FROM participa WHERE id_usuario = ?";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario]);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }

    public function insertar($id_usuario, $id_apuesta, $rol) {
        $sql = "INSERT INTO participa (id_usuario, id_apuesta, rol) VALUES (?, ?, ?)";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id_usuario, $id_apuesta, $rol]);
    }
}
?>
