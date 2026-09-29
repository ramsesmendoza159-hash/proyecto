<?php
// model/Supervisor.php
// Modelo de supervisores
// ✅ FIX 1: eliminar() valida órdenes Y devoluciones aprobadas
// ✅ FIX 2: actualizar() acepta 'password' o 'password_hash' (consistencia)
// ✅ FIX 3: cambiarEstado() valida contra whitelist
// ✅ FIX 4: obtenerEstadisticas() incluye total_ordenes
// ✅ FIX 5: agregado buscar()
// ✅ FIX 6: agregado cambiarPassword() como alias de actualizarPassword()
// ✅ FIX 7: eliminado verificarCredenciales() (huérfano, login va por usuarios)
// ✅ FIX 8: catch (Throwable) en vez de Exception

require_once __DIR__ . '/../config/database.php';

class Supervisor {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (Supervisor): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT * FROM supervisores WHERE 1=1";
            $params = [];

            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nombre LIKE ? OR email LIKE ? OR area LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            if (!empty($filtros['area'])) {
                $sql .= " AND area = ?";
                $params[] = $filtros['area'];
            }

            $sql .= " ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM supervisores WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerPorEmail($email) {
        try {
            $sql = "SELECT * FROM supervisores WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::obtenerPorEmail: " . $e->getMessage());
            return null;
        }
    }

    // ==========================================
    // VALIDACIONES
    // ==========================================

    public function emailExiste($email, $excluirId = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM supervisores WHERE email = ?";
            $params = [$email];

            if ($excluirId !== null) {
                $sql .= " AND id != ?";
                $params[] = (int)$excluirId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['total'] ?? 0) > 0;

        } catch (PDOException $e) {
            error_log("Error en Supervisor::emailExiste: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    public function crear($datos) {
        try {
            // Validar datos
            if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['password'])) {
                error_log("Supervisor::crear - Datos incompletos");
                return false;
            }

            // Verificar email duplicado
            if ($this->emailExiste($datos['email'])) {
                error_log("Supervisor::crear - Email ya registrado");
                return false;
            }

            // Hashear contraseña
            $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);

            $sql = "INSERT INTO supervisores (nombre, email, password_hash, area, telefono, estado) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['email'],
                $passwordHash,
                $datos['area'] ?? '',
                $datos['telefono'] ?? '',
                $datos['estado'] ?? 'activo'
            ]);

            if ($result) {
                return (int)$this->db->lastInsertId();
            }

            error_log("Supervisor::crear - Error al ejecutar insert");
            return false;

        } catch (PDOException $e) {
            error_log("Error en Supervisor::crear: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    /**
     * ✅ FIX 2: acepta 'password' (hashea) o 'password_hash' (ya hasheado).
     */
    public function actualizar($id, $datos) {
        try {
            // Verificar email duplicado (excepto este)
            if (!empty($datos['email']) && $this->emailExiste($datos['email'], $id)) {
                error_log("Supervisor::actualizar - Email ya registrado por otro supervisor");
                return false;
            }

            $sql = "UPDATE supervisores SET 
                        nombre = :nombre,
                        email = :email,
                        area = :area,
                        telefono = :telefono,
                        estado = :estado,
                        fecha_actualizacion = NOW()";

            $params = [
                ':nombre' => $datos['nombre'],
                ':email' => $datos['email'],
                ':area' => $datos['area'] ?? '',
                ':telefono' => $datos['telefono'] ?? '',
                ':estado' => $datos['estado'] ?? 'activo',
                ':id' => (int)$id
            ];

            // ✅ FIX 2: acepta password_hash directo o password en texto plano
            if (!empty($datos['password_hash'])) {
                $sql .= ", password_hash = :password_hash";
                $params[':password_hash'] = $datos['password_hash'];
            } elseif (!empty($datos['password'])) {
                $sql .= ", password_hash = :password_hash";
                $params[':password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::actualizar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CAMBIAR ESTADO
    // ==========================================

    /**
     * ✅ FIX 3: valida el estado contra whitelist.
     */
    public function cambiarEstado($id, $estado) {
        try {
            // ✅ FIX 3: validar estado
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                error_log("Supervisor::cambiarEstado - Estado inválido: $estado");
                return false;
            }

            $sql = "UPDATE supervisores SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CONTRASEÑA
    // ==========================================

    /**
     * Actualiza la contraseña hasheando internamente.
     * Mantiene el nombre por compatibilidad con el controlador.
     */
    public function actualizarPassword($id, $password) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE supervisores SET password_hash = ?, fecha_actualizacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$hash, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::actualizarPassword: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ FIX 6: alias semántico más claro.
     */
    public function cambiarPassword($id, $password_plano) {
        return $this->actualizarPassword($id, $password_plano);
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    /**
     * ✅ FIX 1: valida órdenes Y devoluciones aprobadas antes de eliminar.
     */
    public function eliminar($id) {
        try {
            $id = (int)$id;

            // ✅ FIX 1: validar órdenes asignadas
            $sql = "SELECT COUNT(*) as total FROM ordenes_mantenimiento WHERE id_supervisor = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (($result['total'] ?? 0) > 0) {
                error_log("Supervisor::eliminar - Supervisor ID $id tiene órdenes asignadas");
                return false;
            }

            // ✅ FIX 1: validar devoluciones aprobadas
            $sqlDev = "SELECT COUNT(*) as total FROM devoluciones_ordenes WHERE aprobado_por = ?";
            $stmtDev = $this->db->prepare($sqlDev);
            $stmtDev->execute([$id]);
            $resultDev = $stmtDev->fetch(PDO::FETCH_ASSOC);

            if (($resultDev['total'] ?? 0) > 0) {
                error_log("Supervisor::eliminar - Supervisor ID $id aprobó " . $resultDev['total'] . " devoluciones");
                return false;
            }

            $sql = "DELETE FROM supervisores WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::eliminar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ESTADÍSTICAS
    // ==========================================

    /**
     * ✅ FIX 4: incluye total_ordenes.
     */
    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                        COUNT(DISTINCT area) as areas
                    FROM supervisores";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // ✅ FIX 4: contar órdenes asignadas a supervisores
            $sqlOrdenes = "SELECT COUNT(DISTINCT id) as total_ordenes 
                           FROM ordenes_mantenimiento 
                           WHERE id_supervisor IS NOT NULL";
            $stmtOrdenes = $this->db->prepare($sqlOrdenes);
            $stmtOrdenes->execute();
            $resultOrdenes = $stmtOrdenes->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)($result['total'] ?? 0),
                'activos' => (int)($result['activos'] ?? 0),
                'inactivos' => (int)($result['inactivos'] ?? 0),
                'areas' => (int)($result['areas'] ?? 0),
                'total_ordenes' => (int)($resultOrdenes['total_ordenes'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error en Supervisor::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total' => 0, 'activos' => 0, 'inactivos' => 0,
                'areas' => 0, 'total_ordenes' => 0
            ];
        }
    }

    // ==========================================
    // ÁREAS
    // ==========================================

    public function obtenerAreas() {
        try {
            $sql = "SELECT DISTINCT area FROM supervisores 
                    WHERE area != '' AND area IS NOT NULL 
                    ORDER BY area ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            error_log("Error en Supervisor::obtenerAreas: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // BÚSQUEDA
    // ==========================================

    /**
     * ✅ FIX 5: agregado buscar() para autocompletar.
     */
    public function buscar($termino) {
        try {
            $sql = "SELECT * FROM supervisores 
                    WHERE (nombre LIKE ? OR email LIKE ?) 
                    AND estado = 'activo'
                    ORDER BY nombre ASC
                    LIMIT 10";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar, $buscar]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Supervisor::buscar: " . $e->getMessage());
            return [];
        }
    }
}