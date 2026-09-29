<?php
// model/BackdatingModel.php
// Modelo para gestionar registros de backdating
// ✅ FIX: Se corrigió el nombre de la columna 'motivo_rechazo' → 'motivo'
// ✅ FIX: Se usó columna correcta 'motivo' en rechazar()

require_once __DIR__ . '/../config/database.php';

class BackdatingModel {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (BackdatingModel): " . $e->getMessage());
            throw $e;
        }
    }

    public function registrar($datos) {
        try {
            $sql = "INSERT INTO mantenimientos_backdating (
                        tipo, referencia_id, mantenimiento_id,
                        fecha_original, fecha_nueva, fecha_limite_permitida,
                        motivo, ip, user_agent, solicitado_por, rol_solicitante,
                        estado, fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['tipo'],
                $datos['referencia_id'],
                $datos['mantenimiento_id'] ?? null,
                $datos['fecha_original'],
                $datos['fecha_nueva'],
                $datos['fecha_limite_permitida'] ?? null,
                $datos['motivo'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $datos['solicitado_por'],
                $datos['rol_solicitante'],
                $datos['estado'] ?? 'aprobado'
            ]);

            return $this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error en BackdatingModel::registrar: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT 
                        b.*,
                        u.nombre AS solicitante_nombre,
                        u.email AS solicitante_email,
                        ap.nombre AS aprobador_nombre,
                        e.nombre_equipo,
                        e.codigo AS equipo_codigo
                    FROM mantenimientos_backdating b
                    LEFT JOIN usuarios u ON b.solicitado_por = u.id
                    LEFT JOIN usuarios ap ON b.aprobado_por = ap.id
                    LEFT JOIN equipos_mantenimientos em ON b.mantenimiento_id = em.id
                    LEFT JOIN equipos e ON em.id_equipo = e.id_equipo
                    WHERE 1=1";

            $params = [];

            if (!empty($filtros['estado'])) {
                $sql .= " AND b.estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['tipo'])) {
                $sql .= " AND b.tipo = ?";
                $params[] = $filtros['tipo'];
            }

            if (!empty($filtros['usuario_id'])) {
                $sql .= " AND b.solicitado_por = ?";
                $params[] = (int)$filtros['usuario_id'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND DATE(b.fecha_creacion) >= ?";
                $params[] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND DATE(b.fecha_creacion) <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            $sql .= " ORDER BY b.fecha_creacion DESC LIMIT 200";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en BackdatingModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $sql = "SELECT 
                        b.*,
                        u.nombre AS solicitante_nombre,
                        u.email AS solicitante_email,
                        ap.nombre AS aprobador_nombre
                    FROM mantenimientos_backdating b
                    LEFT JOIN usuarios u ON b.solicitado_por = u.id
                    LEFT JOIN usuarios ap ON b.aprobado_por = ap.id
                    WHERE b.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en BackdatingModel::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }

    public function aprobar($id, $aprobador_id) {
        try {
            $sql = "UPDATE mantenimientos_backdating 
                    SET estado = 'aprobado',
                        aprobado_por = ?,
                        fecha_aprobacion = NOW()
                    WHERE id = ? AND estado = 'pendiente'";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([(int)$aprobador_id, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en BackdatingModel::aprobar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ FIX: usa columna 'motivo' (no 'motivo_rechazo')
     */
    public function rechazar($id, $aprobador_id, $motivo = '') {
        try {
            $sql = "UPDATE mantenimientos_backdating 
                    SET estado = 'rechazado',
                        aprobado_por = ?,
                        motivo = ?,
                        fecha_aprobacion = NOW()
                    WHERE id = ? AND estado = 'pendiente'";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([(int)$aprobador_id, $motivo, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en BackdatingModel::rechazar: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                        SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) AS aprobados,
                        SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) AS rechazados,
                        SUM(CASE WHEN DATE(fecha_creacion) = CURDATE() THEN 1 ELSE 0 END) AS hoy
                    FROM mantenimientos_backdating";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en BackdatingModel::obtenerEstadisticas: " . $e->getMessage());
            return ['total' => 0, 'pendientes' => 0, 'aprobados' => 0, 'rechazados' => 0, 'hoy' => 0];
        }
    }
}   