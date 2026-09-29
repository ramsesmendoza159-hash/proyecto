<?php
// model/AreasModel.php
// Modelo de Áreas
// ✅ FIX 1: obtenerTodos() y obtenerConConteo() usan LEFT JOIN
// ✅ FIX 2: cambiarEstado() valida whitelist
// ✅ FIX 3: crear() y actualizar() validan nombre
// ✅ FIX 4: catch (Throwable) en constructor

require_once __DIR__ . '/../config/database.php';

class AreasModel
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (AreasModel): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function obtenerTodos($filtros = [])
    {
        try {
            // ✅ FIX 1: LEFT JOIN en vez de INNER JOIN
            $sql = "SELECT a.*, p.nombre_planta 
                    FROM areas a
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['id_planta'])) {
                $sql .= " AND a.id_planta = ?";
                $params[] = (int)$filtros['id_planta'];
            }

            if (!empty($filtros['estado'])) {
                $sql .= " AND a.estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (a.nombre_area LIKE ? OR a.descripcion LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY p.nombre_planta ASC, a.nombre_area ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en AreasModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerActivos()
    {
        return $this->obtenerTodos(['estado' => 'activo']);
    }

    public function obtenerPorId($id)
    {
        try {
            // ✅ FIX 1: LEFT JOIN
            $sql = "SELECT a.*, p.nombre_planta 
                    FROM areas a
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE a.id_area = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AreasModel::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerIdPorNombre($nombre, $id_planta = null)
    {
        try {
            $sql = "SELECT id_area FROM areas WHERE nombre_area = ?";
            $params = [$nombre];

            if ($id_planta) {
                $sql .= " AND id_planta = ?";
                $params[] = (int)$id_planta;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['id_area'] : null;
        } catch (PDOException $e) {
            error_log("Error en AreasModel::obtenerIdPorNombre: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerPorPlanta($id_planta)
    {
        try {
            $sql = "SELECT * FROM areas WHERE id_planta = ? ORDER BY nombre_area ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id_planta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AreasModel::obtenerPorPlanta: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerConConteo($filtros = [])
    {
        try {
            // ✅ FIX 1: LEFT JOIN en vez de INNER JOIN
            $sql = "SELECT a.*, 
                           COUNT(e.id_equipo) as total_equipos,
                           p.nombre_planta
                    FROM areas a
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    LEFT JOIN equipos e ON a.id_area = e.id_area
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['id_planta'])) {
                $sql .= " AND a.id_planta = ?";
                $params[] = (int)$filtros['id_planta'];
            }

            if (!empty($filtros['estado'])) {
                $sql .= " AND a.estado = ?";
                $params[] = $filtros['estado'];
            }

            $sql .= " GROUP BY a.id_area 
                      ORDER BY p.nombre_planta ASC, a.nombre_area ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AreasModel::obtenerConConteo: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    /**
     * ✅ FIX 3: valida nombre.
     */
    public function crear($datos)
    {
        try {
            if (empty($datos['nombre'])) {
                error_log("AreasModel::crear - Nombre vacío");
                return false;
            }

            if (empty($datos['id_planta'])) {
                error_log("AreasModel::crear - id_planta requerido");
                return false;
            }

            $sql = "INSERT INTO areas (id_planta, nombre_area, descripcion, estado) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                (int)$datos['id_planta'],
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['estado'] ?? 'activo'
            ]);
            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error en AreasModel::crear: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    public function actualizar($id, $datos)
    {
        try {
            if (empty($datos['nombre'])) {
                error_log("AreasModel::actualizar - Nombre vacío");
                return false;
            }

            $sql = "UPDATE areas SET 
                        id_planta = ?,
                        nombre_area = ?,
                        descripcion = ?,
                        estado = ?,
                        updated_at = NOW()
                    WHERE id_area = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                (int)$datos['id_planta'],
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['estado'] ?? 'activo',
                (int)$id
            ]);

        } catch (PDOException $e) {
            error_log("Error en AreasModel::actualizar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CAMBIAR ESTADO
    // ==========================================

    /**
     * ✅ FIX 2: valida whitelist.
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                error_log("AreasModel::cambiarEstado - Estado inválido: $estado");
                return false;
            }

            $sql = "UPDATE areas SET estado = ?, updated_at = NOW() WHERE id_area = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en AreasModel::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    public function eliminar($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM equipos WHERE id_area = ?");
            $stmt->execute([(int)$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado && $resultado['total'] > 0) {
                return ['error' => true, 'message' => 'No se puede eliminar el área porque tiene equipos asociados'];
            }

            $sql = "DELETE FROM areas WHERE id_area = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return ['error' => false, 'message' => 'Área eliminada correctamente'];

        } catch (PDOException $e) {
            error_log("Error en AreasModel::eliminar: " . $e->getMessage());
            return ['error' => true, 'message' => 'Error al eliminar el área: ' . $e->getMessage()];
        }
    }

    // ==========================================
    // BUSCAR / ESTADÍSTICAS
    // ==========================================

    public function buscar($termino)
    {
        try {
            $sql = "SELECT a.*, p.nombre_planta 
                    FROM areas a
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE a.nombre_area LIKE ? 
                    ORDER BY a.nombre_area ASC
                    LIMIT 10";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AreasModel::buscar: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_areas,
                        COUNT(DISTINCT id_planta) as total_plantas,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activas,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivas,
                        SUM(CASE WHEN (SELECT COUNT(*) FROM equipos WHERE id_area = areas.id_area) > 0 THEN 1 ELSE 0 END) as areas_con_equipos,
                        SUM(CASE WHEN (SELECT COUNT(*) FROM equipos WHERE id_area = areas.id_area) = 0 THEN 1 ELSE 0 END) as areas_sin_equipos
                    FROM areas";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AreasModel::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total_areas' => 0, 'total_plantas' => 0,
                'activas' => 0, 'inactivas' => 0,
                'areas_con_equipos' => 0, 'areas_sin_equipos' => 0
            ];
        }
    }
}