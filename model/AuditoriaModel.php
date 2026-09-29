<?php
// model/AuditoriaModel.php
// MODELO COMPLETO - ADAPTADO A LA ESTRUCTURA DE LA BD

require_once __DIR__ . '/../config/database.php';

class AuditoriaModel {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (AuditoriaModel): " . $e->getMessage());
            throw $e;
        }
    }

    public function registrar($usuario_id, $usuario_nombre, $usuario_rol, $accion, $tabla, $registro_id = null, $datos_anteriores = null, $datos_nuevos = null) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $sql = "INSERT INTO auditoria (
                        usuario_id, usuario_nombre, usuario_rol, accion, tabla,
                        registro_id, datos_anteriores, datos_nuevos, ip, user_agent, fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                $usuario_id,
                $usuario_nombre,
                $usuario_rol,
                $accion,
                $tabla,
                $registro_id,
                $datos_anteriores ? json_encode($datos_anteriores) : null,
                $datos_nuevos ? json_encode($datos_nuevos) : null,
                $ip,
                $user_agent
            ]);

        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::registrar: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT * FROM auditoria WHERE 1=1";
            $params = [];

            if (!empty($filtros['usuario_id'])) {
                $sql .= " AND usuario_id = ?";
                $params[] = (int)$filtros['usuario_id'];
            }

            if (!empty($filtros['accion'])) {
                $sql .= " AND accion = ?";
                $params[] = $filtros['accion'];
            }

            if (!empty($filtros['tabla'])) {
                $sql .= " AND tabla = ?";
                $params[] = $filtros['tabla'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND fecha_creacion >= ?";
                $params[] = $filtros['fecha_desde'] . ' 00:00:00';
            }

            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND fecha_creacion <= ?";
                $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
            }

            $sql .= " ORDER BY fecha_creacion DESC LIMIT 100";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM auditoria WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(DISTINCT usuario_id) as usuarios_distintos,
                        COUNT(DISTINCT accion) as acciones_distintas,
                        COUNT(DISTINCT tabla) as tablas_distintas,
                        SUM(CASE WHEN accion = 'login' THEN 1 ELSE 0 END) as logins,
                        SUM(CASE WHEN accion = 'logout' THEN 1 ELSE 0 END) as logouts,
                        SUM(CASE WHEN accion = 'crear' THEN 1 ELSE 0 END) as creaciones,
                        SUM(CASE WHEN accion = 'actualizar' THEN 1 ELSE 0 END) as actualizaciones,
                        SUM(CASE WHEN accion = 'eliminar' THEN 1 ELSE 0 END) as eliminaciones,
                        SUM(CASE WHEN accion = 'cambiar_estado' THEN 1 ELSE 0 END) as cambios_estado,
                        SUM(CASE WHEN accion = 'cerrar' THEN 1 ELSE 0 END) as cierres
                    FROM auditoria
                    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total' => 0, 'usuarios_distintos' => 0, 'acciones_distintas' => 0,
                'tablas_distintas' => 0, 'logins' => 0, 'logouts' => 0,
                'creaciones' => 0, 'actualizaciones' => 0, 'eliminaciones' => 0,
                'cambios_estado' => 0, 'cierres' => 0
            ];
        }
    }

    public function obtenerAcciones() {
        try {
            $sql = "SELECT DISTINCT accion FROM auditoria ORDER BY accion";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::obtenerAcciones: " . $e->getMessage());
            return [];
        }
    }
}