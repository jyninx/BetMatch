<?php
require_once __DIR__ . '/../config/database.php';

class UsuarioDAO {
    private $db;

    public function __construct() {
        $this->db = conectar();
    }

    public function buscarPorCorreo($correo) {
        $consulta = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $consulta->execute([$correo]);
        return $consulta->fetch();
    }

    public function buscarPorDni($dni) {
        $sql = "SELECT * FROM usuarios WHERE dni = ? LIMIT 1";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$dni]);
        return $consulta->fetch();
    }

    public function buscarPorId($id) {
        $sql = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id]);
        return $consulta->fetch();
    }

    public function crearUsuario($datos) {
        $sql = "INSERT INTO usuarios (nombre, apellidos, email, dni, password, telefono, ciudad, calle, fecha_nacimiento, rol, saldo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

        $consulta = $this->db->prepare($sql);

        return $consulta->execute([
            $datos['nombre'],
            $datos['apellidos'],
            $datos['correo'],
            $datos['dni'],
            $datos['clave'],
            $datos['telefono'],
            $datos['ciudad'],
            $datos['calle'],
            $datos['fecha_nacimiento'],
            $datos['rol']
        ]);
    }

    public function crearUsuarioSimple($nombre, $apellidos, $correo, $rol = 'usuario') {
        $sql = "INSERT INTO usuarios (nombre, apellidos, email, password, rol, saldo, fecha_registro) 
                VALUES (?, ?, ?, '', ?, 0, NOW())";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([
            $nombre,
            $apellidos,
            $correo,
            $rol
        ]);
    }

    public function actualizarUltimoAcceso($id) {
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$id]);
    }

    public function actualizarSaldo($id, $saldo) {
        $sql = "UPDATE usuarios SET saldo = ?, updated_at = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$saldo, $id]);
    }

    public function obtenerSaldo($id) {
        $sql = "SELECT saldo FROM usuarios WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$id]);
        $resultado = $consulta->fetch();
        return $resultado ? $resultado['saldo'] : 0;
    }

    public function actualizarCorreo($id, $correo) {
        $sql = "UPDATE usuarios SET email = ?, updated_at = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$correo, $id]);
    }

    public function actualizarContrasena($id, $clave_encriptada) {
        $sql = "UPDATE usuarios SET password = ?, updated_at = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$clave_encriptada, $id]);
    }

    public function actualizarCuentaPaypal($id, $cuenta) {
        $sql = "UPDATE usuarios SET cuenta_paypal = ?, updated_at = NOW() WHERE id = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$cuenta, $id]);
    }

    public function guardarTokenRestablecimiento($id_usuario, $token, $expira) {
        $sql_borrar = "DELETE FROM password_resets WHERE usuario_id = ?";
        $consulta_borrar = $this->db->prepare($sql_borrar);
        $consulta_borrar->execute([$id_usuario]);

        $sql_insertar = "INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (?, ?, ?)";
        $consulta_insertar = $this->db->prepare($sql_insertar);
        return $consulta_insertar->execute([$id_usuario, $token, $expira]);
    }

    public function buscarTokenRestablecimientoValido($token) {
        $sql = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0 LIMIT 1";
        $consulta = $this->db->prepare($sql);
        $consulta->execute([$token]);
        return $consulta->fetch();
    }

    public function borrarTokenRestablecimiento($token) {
        $sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
        $consulta = $this->db->prepare($sql);
        return $consulta->execute([$token]);
    }

    public function autenticar($correo, $clave) {
        $usuario = $this->buscarPorCorreo($correo);
        if ($usuario && password_verify($clave, $usuario['password'])) {
            $this->actualizarUltimoAcceso($usuario['id']);
            return $usuario;
        }
        return false;
    }

    public function contarTodas() {
        $sql = "SELECT COUNT(*) as total FROM usuarios";
        $consulta = $this->db->query($sql);
        $resultado = $consulta->fetch();
        return $resultado ? (int)$resultado['total'] : 0;
    }
}
?>
