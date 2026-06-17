<?php
require_once __DIR__ . '/../config/database.php';

class NotificacionDAO {
    private $db;

    public function __construct() {
        $this->db = conectar();
    }

    public function obtenerPorUsuario($id_usuario) {
        $sql = "SELECT * FROM notificaciones WHERE id_usuario = ? ORDER BY fecha_envio DESC";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario]);
        return $consulta->fetchAll();
    }

    public function obtenerPorIdYUsuario($id, $id_usuario) {
        $consulta = $this->db->prepare("SELECT * FROM notificaciones WHERE id = ? AND id_usuario = ?");
        $consulta->execute([$id, $id_usuario]);
        $resultado = $consulta->fetch();
        return $resultado ? $resultado : null;
    }

    public function crearNotificacion($id_usuario, $titulo, $mensaje, $tipo, $id_apuesta = null) {
        $sql = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_apuesta_relacionada, fecha_envio) VALUES (?, ?, ?, ?, ?, NOW())";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id_usuario, $titulo, $mensaje, $tipo, $id_apuesta]);
    }

    public function eliminar($id) {
        $sql = "DELETE FROM notificaciones WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id]);
    }

    public function contarSinLeer($id_usuario) {
        $sql = "SELECT COUNT(*) as total FROM notificaciones WHERE id_usuario = ? AND leida = 0";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario]);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }
}
?>
