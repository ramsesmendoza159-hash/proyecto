<?php
// model/PlantasModel.php
// Modelo de Plantas
// ✅ FIX 1: obtenerTodos() acepta filtros (estado, buscar)
// ✅ FIX 2: crear() valida nombre y duplicados
// ✅ FIX 3: actualizar() valida nombre y duplicados
// ✅ FIX 4: crear() y actualizar() soportan columna 'estado'
// ✅ FIX 5: agregado cambiarEstado()
// ✅ FIX 6: agregado obtenerActivos()
// ✅ FIX 7: catch (Throwable) en vez de Exception

require_once __DIR__ . '/../config/database.php';

class PlantasModel
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (PlantasModel): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // LISTADO
    // ==========================================

    /**
     * ✅ FIX 1: acepta filtros.
     */
    public function obtenerTodos($filtros = [])
    {
        try {
            $sql = "SELECT * FROM plantas WHERE 1=1";
            $params = [];

            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nombre_planta LIKE ? OR descripcion LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY nombre_planta ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en PlantasModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ✅ FIX 6: agregado obtenerActivos().
     */
    public function obtenerActivos()
    {
        return $this->obtenerTodos(['estado' => 'activo']);
    }

    public function obtenerPorId($id)
    {
        try {
            $sql = "SELECT * FROM plantas WHERE id_planta = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlantasModel::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPorNombre($nombre)
    {
        try {
            $sql = "SELECT * FROM plantas WHERE nombre_planta = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlantasModel::obtenerPorNombre: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerIdPorNombre($nombre)
    {
        try {
            $sql = "SELECT id_planta FROM plantas WHERE nombre_planta = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['id_planta'] : null;
        } catch (PDOException $e) {
            error_log("Error en PlantasModel::obtenerIdPorNombre: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerConConteo()
    {
        try {
            $sql = "SELECT p.*, 
                           COUNT(a.id_area) as total_areas
                    FROM plantas p
                    LEFT JOIN areas a ON p.id_planta = a.id_planta
                    GROUP BY p.id_planta
                    ORDER BY p.nombre_planta ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlantasModel::obtenerConConteo: " . $e->getMessage());
            return [];
        }
    }

    public function buscar($termino)
    {
        try {
            $sql = "SELECT * FROM plantas 
                    WHERE nombre_planta LIKE ? 
                    ORDER BY nombre_planta ASC
                    LIMIT 10";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlantasModel::buscar: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    /**
     * ✅ FIX 2: valida nombre y duplicados.
     * ✅ FIX 4: soporta 'estado'.
     */
    public function crear($datos)
    {
        try {
            // ✅ FIX 2: validar nombre
            if (empty($datos['nombre'])) {
                error_log("PlantasModel::crear - Nombre vacío");
                return false;
            }

            // ✅ FIX 2: validar duplicado
            if ($this->existe($datos['nombre'])) {
                error_log("PlantasModel::crear - Planta ya existe: " . $datos['nombre']);
                return false;
            }

            // ✅ FIX 4: incluir 'estado'
            $sql = "INSERT INTO plantas (nombre_planta, descripcion, estado) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['estado'] ?? 'activo'
            ]);
            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error en PlantasModel::crear: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    /**
     * ✅ FIX 3: valida nombre y duplicados.
     * ✅ FIX 4: soporta 'estado'.
     */
    public function actualizar($id, $datos)
    {
        try {
            // ✅ FIX 3: validar nombre
            if (empty($datos['nombre'])) {
                error_log("PlantasModel::actualizar - Nombre vacío");
                return false;
            }

            // ✅ FIX 3: validar duplicado (excluyendo el actual)
            if ($this->existe($datos['nombre'], $id)) {
                error_log("PlantasModel::actualizar - Planta ya existe: " . $datos['nombre']);
                return false;
            }

            // ✅ FIX 4: incluir 'estado'
            $sql = "UPDATE plantas SET 
                        nombre_planta = ?, 
                        descripcion = ?, 
                        estado = ?,
                        fecha_actualizacion = NOW()
                    WHERE id_planta = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['estado'] ?? 'activo',
                (int)$id
            ]);

        } catch (PDOException $e) {
            error_log("Error en PlantasModel::actualizar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CAMBIAR ESTADO
    // ==========================================

    /**
     * ✅ FIX 5: agregado cambiarEstado().
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                error_log("PlantasModel::cambiarEstado - Estado inválido: $estado");
                return false;
            }

            $sql = "UPDATE plantas SET estado = ?, fecha_actualizacion = NOW() WHERE id_planta = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en PlantasModel::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    public function eliminar($id)
    {
        try {
            // Verificar si tiene áreas asociadas
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM areas WHERE id_planta = ?");
            $stmt->execute([(int)$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado && $resultado['total'] > 0) {
                return ['error' => true, 'message' => 'No se puede eliminar la planta porque tiene áreas asociadas'];
            }

            $sql = "DELETE FROM plantas WHERE id_planta = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return ['error' => false, 'message' => 'Planta eliminada correctamente'];

        } catch (PDOException $e) {
            error_log("Error en PlantasModel::eliminar: " . $e->getMessage());
            return ['error' => true, 'message' => 'Error al eliminar la planta: ' . $e->getMessage()];
        }
    }

    // ==========================================
    // ESTADÍSTICAS
    // ==========================================

    public function obtenerEstadisticas()
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_plantas,
                        SUM(CASE WHEN (SELECT COUNT(*) FROM areas WHERE id_planta = plantas.id_planta) > 0 THEN 1 ELSE 0 END) as plantas_con_areas,
                        SUM(CASE WHEN (SELECT COUNT(*) FROM areas WHERE id_planta = plantas.id_planta) = 0 THEN 1 ELSE 0 END) as plantas_sin_areas
                    FROM plantas";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlantasModel::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total_plantas' => 0,
                'plantas_con_areas' => 0,
                'plantas_sin_areas' => 0
            ];
        }
    }

    public function obtenerTotal()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM plantas";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error en PlantasModel::obtenerTotal: " . $e->getMessage());
            return 0;
        }
    }

    // ==========================================
    // VALIDACIONES
    // ==========================================

    public function existe($nombre, $excluirId = null)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM plantas WHERE nombre_planta = ?";
            $params = [$nombre];

            if ($excluirId !== null) {
                $sql .= " AND id_planta != ?";
                $params[] = (int)$excluirId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($resultado['total'] ?? 0) > 0;

        } catch (PDOException $e) {
            error_log("Error en PlantasModel::existe: " . $e->getMessage());
            return false;
        }
    }
}