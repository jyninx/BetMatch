<?php
require_once __DIR__ . '/../config/database.php';

class EventoDAO {
    private $db;

    public function __construct() {
        $this->db = conectar();
    }

    public function obtenerLigasDistintasProximos() {
        $sql = "SELECT DISTINCT liga FROM eventos WHERE estado = 'proximo' ORDER BY liga";
        $consulta = $this->db->query($sql);
        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    public function obtenerEventosPorLiga($liga) {
        $sql = "SELECT id, nombre_evento, fecha_evento FROM eventos WHERE liga = ? AND estado = 'proximo' AND fecha_evento > NOW() ORDER BY fecha_evento ASC";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$liga]);
        return $consulta->fetchAll();
    }

    public function obtenerEventoPorId($id) {
        $consulta = $this->db->prepare("SELECT * FROM eventos WHERE id = ?");
        $consulta->execute([$id]);
        $resultado = $consulta->fetch();
        return $resultado ? $resultado : false;
    }

    public function existePorNombreYFecha($nombre, $fecha) {
        $sql = "SELECT id FROM eventos WHERE nombre_evento = ? AND fecha_evento = ?";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$nombre, $fecha]);
        return $consulta->fetch() ? true : false;
    }

    public function insertarEvento($nombre, $liga, $fecha) {
        $sql = "INSERT INTO eventos (nombre_evento, liga, fecha_evento, estado) VALUES (?, ?, ?, 'proximo')";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$nombre, $liga, $fecha]);
    }

    public function obtenerPorIdExterno($id_externo) {
        $sql = "SELECT * FROM eventos WHERE id_evento_api = ?";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_externo]);
        $resultado = $consulta->fetch();
        return $resultado ? $resultado : false;
    }

    public function actualizarPorIdExterno($id_externo, $nombre, $liga, $fecha) {
        $sql = "UPDATE eventos SET nombre_evento = ?, liga = ?, fecha_evento = ? WHERE id_evento_api = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$nombre, $liga, $fecha, $id_externo]);
    }

    public function insertarConIdExterno($id_externo, $nombre, $liga, $fecha) {
        $sql = "INSERT INTO eventos (id_evento_api, nombre_evento, liga, fecha_evento, estado) VALUES (?, ?, ?, ?, 'proximo')";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id_externo, $nombre, $liga, $fecha]);
    }
}
?>
