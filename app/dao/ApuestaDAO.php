<?php
require_once __DIR__ . '/../config/database.php';

class ApuestaDAO {
    private $db;

    public function __construct() {
        $this->db = conectar();
    }

    public function obtenerApuestasPorUsuario($id_usuario) {
        $sql = "
            SELECT a.*, u1.nombre as creador_nombre, u2.nombre as aceptador_nombre
            FROM apuestas a
            LEFT JOIN usuarios u1 ON a.id_creador = u1.id
            LEFT JOIN usuarios u2 ON a.id_aceptador = u2.id
            WHERE a.id_creador = ? OR a.id_aceptador = ?
            ORDER BY a.fecha_creacion DESC
        ";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario, $id_usuario]);
        return $consulta->fetchAll();
    }

    public function obtenerUltimasApuestasActivas($limite = 3) {
        $sql = "
            SELECT a.*, u.nombre as creador_nombre
            FROM apuestas a
            JOIN usuarios u ON a.id_creador = u.id
            WHERE a.estado = 'activa'
            ORDER BY a.fecha_creacion DESC
            LIMIT ?
        ";
        $consulta = $this->db->prepare($sql);
        $consulta->bindValue(1, (int)$limite, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function obtenerApuestaPorId($id) {
        $consulta = $this->db->prepare("SELECT * FROM apuestas WHERE id = ?");
        $consulta->execute([$id]);
        $resultado = $consulta->fetch();
        return $resultado ? $resultado : null;
    }

    public function obtenerApuestaConCreador($id) {
        $consulta = $this->db->prepare("
            SELECT a.*, u.nombre as creador_nombre
            FROM apuestas a
            JOIN usuarios u ON a.id_creador = u.id
            WHERE a.id = ?
        ");
        $consulta->execute([$id]);
        $resultado = $consulta->fetch();
        return $resultado ? $resultado : null;
    }

    public function crearApuesta($id_creador, $id_evento, $monto, $nombre_evento, $resultado) {
        $sql = "
            INSERT INTO apuestas (id_creador, id_evento, monto, evento_nombre, estado, resultado, fecha_creacion)
            VALUES (?, ?, ?, ?, 'pendiente', ?, NOW())
        ";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_creador, $id_evento, $monto, $nombre_evento, $resultado]);
        return $this->db->lastInsertId();
    }

    public function aceptarApuesta($id_apuesta, $id_aceptador) {
        $sql = "UPDATE apuestas SET id_aceptador = ?, estado = 'activa', fecha_aceptacion = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id_aceptador, $id_apuesta]);
    }

    public function eliminarApuesta($id) {
        $sql = "DELETE FROM apuestas WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id]);
    }

    public function contarPorUsuario($id_usuario) {
        $sql = "SELECT COUNT(*) as total FROM apuestas WHERE id_creador = ? OR id_aceptador = ?";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario, $id_usuario]);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }

    public function contarGanadasPorUsuario($id_usuario) {
        $sql = "SELECT COUNT(*) as total FROM apuestas WHERE ganador_id = ? AND estado = 'finalizada'";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario]);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }

    public function contarTodas() {
        $sql = "SELECT COUNT(*) as total FROM apuestas";
        $consulta = $this->db->query($sql);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }

    public function contarPorEstado($estado) {
        $sql = "SELECT COUNT(*) as total FROM apuestas WHERE estado = ?";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$estado]);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }

    public function contarActivasPorUsuario($id_usuario) {
        $sql = "SELECT COUNT(*) as activas FROM apuestas WHERE estado = 'activa' AND (id_creador = ? OR id_aceptador = ?)";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario, $id_usuario]);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['activas'] : 0;
    }

    public function contarPendientesDisponibles($id_usuario) {
        $sql = "SELECT COUNT(*) as total FROM apuestas WHERE estado = 'pendiente' AND id_aceptador IS NULL AND id_creador != ?";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id_usuario]);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }

    public function obtenerUltimasPorUsuario($id_usuario, $limite = 5) {
        $sql = "
            SELECT a.*, u1.nombre as creador_nombre, u2.nombre as aceptador_nombre
            FROM apuestas a
            LEFT JOIN usuarios u1 ON a.id_creador = u1.id
            LEFT JOIN usuarios u2 ON a.id_aceptador = u2.id
            WHERE a.id_creador = ? OR a.id_aceptador = ?
            ORDER BY a.fecha_creacion DESC
            LIMIT ?
        ";
        $consulta = $this->db->prepare($sql);
        $consulta->bindValue(1, $id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(2, $id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(3, (int)$limite, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function obtenerApuestasActivasPorFinalizar() {
        $sql = "
            SELECT a.*, e.nombre_evento, e.id_evento_api, a.id_creador, a.id_aceptador
            FROM apuestas a
            JOIN eventos e ON a.id_evento = e.id
            WHERE a.estado = 'activa' AND e.fecha_evento < NOW()
        ";
        $consulta = $this->db->query($sql);
        return $consulta->fetchAll();
    }

    public function obtenerListaApuestasActivas() {
        $sql = "
            SELECT a.*, u1.nombre as creador, u2.nombre as aceptador, e.fecha_evento
            FROM apuestas a
            JOIN usuarios u1 ON a.id_creador = u1.id
            LEFT JOIN usuarios u2 ON a.id_aceptador = u2.id
            JOIN eventos e ON a.id_evento = e.id
            WHERE a.estado = 'activa'
            ORDER BY e.fecha_evento ASC
        ";
        $consulta = $this->db->query($sql);
        return $consulta->fetchAll();
    }

    public function cancelarApuesta($id, $resultado) {
        $sql = "UPDATE apuestas SET estado = 'cancelada', resultado = ?, fecha_finalizacion = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$resultado, $id]);
    }

    public function finalizarApuesta($id, $id_ganador, $resultado) {
        $sql = "UPDATE apuestas SET estado = 'finalizada', ganador_id = ?, resultado = ?, fecha_finalizacion = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id_ganador, $resultado, $id]);
    }
}
?>
