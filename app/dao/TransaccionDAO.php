<?php
require_once __DIR__ . '/../config/database.php';

class TransaccionDAO {
    private $db;

    public function __construct() {
        $this->db = conectar();
    }

    public function crearTransaccion($id_usuario, $tipo, $cantidad, $saldo_anterior, $saldo_nuevo, $concepto, $estado, $id_apuesta_relacionada = null) {
        $sql = "INSERT INTO transacciones (id_usuario, tipo, cantidad, saldo_anterior, saldo_nuevo, concepto, id_apuesta_relacionada, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id_usuario, $tipo, $cantidad, $saldo_anterior, $saldo_nuevo, $concepto, $id_apuesta_relacionada, $estado]);
    }

    public function iniciarTransaccion() {
        return $this->db->beginTransaction();
    }

    public function confirmarTransaccion() {
        return $this->db->commit();
    }

    public function revertirTransaccion() {
        return $this->db->rollBack();
    }
}
?>
