<?php
// model/EquiposModel.php
// VERSIÓN COMPLETA CON DEBUG Y MANEJO DE ERRORES

require_once __DIR__ . '/../config/database.php';

class EquiposModel
{
    private $db;
    private $lastError;  // ✅ PROPIEDAD AGREGADA

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener el último error
     */
    public function getLastError()
    {
        return $this->lastError ?? 'Error desconocido';
    }

    /**
     * Obtener todos los equipos con filtros
     */
    public function obtenerTodos($filtros = [])
    {
        try {
            $sql = "SELECT e.*, a.nombre_area, p.nombre_planta 
                    FROM equipos e
                    LEFT JOIN areas a ON e.id_area = a.id_area
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['id_area'])) {
                $sql .= " AND e.id_area = ?";
                $params[] = $filtros['id_area'];
            }

            if (!empty($filtros['estado'])) {
                $sql .= " AND e.estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['estado_operativo'])) {
                $sql .= " AND e.estado_operativo = ?";
                $params[] = $filtros['estado_operativo'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (e.nombre_equipo LIKE ? OR e.marca LIKE ? OR e.modelo LIKE ? OR e.serie LIKE ? OR e.codigo LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY p.nombre_planta, a.nombre_area, e.nombre_equipo ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerTodos (Equipos): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener equipos activos
     */
    public function obtenerActivos()
    {
        return $this->obtenerTodos(['estado' => 'activo']);
    }

    /**
     * Obtener equipo por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $sql = "SELECT e.*, a.nombre_area, p.nombre_planta 
                    FROM equipos e
                    LEFT JOIN areas a ON e.id_area = a.id_area
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE e.id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPorId (Equipos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener equipos por área
     */
    public function obtenerPorArea($id_area)
    {
        try {
            $sql = "SELECT * FROM equipos WHERE id_area = ? ORDER BY nombre_equipo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_area]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPorArea (Equipos): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear nuevo equipo - CON DEBUG DETALLADO
     */
    public function crear($datos)
    {
        try {
            // ✅ VALIDAR DATOS OBLIGATORIOS
            if (empty($datos['id_area'])) {
                $this->lastError = "El ID del área es obligatorio";
                error_log("❌ Error crear equipo: id_area vacío");
                return false;
            }
            
            if (empty($datos['nombre'])) {
                $this->lastError = "El nombre es obligatorio";
                error_log("❌ Error crear equipo: nombre vacío");
                return false;
            }
            
            // ✅ VERIFICAR QUE EL ÁREA EXISTA
            $sqlCheck = "SELECT id_area FROM areas WHERE id_area = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$datos['id_area']]);
            if (!$stmtCheck->fetch()) {
                $this->lastError = "El área seleccionada no existe (ID: {$datos['id_area']})";
                error_log("❌ Error crear equipo: área {$datos['id_area']} no existe");
                return false;
            }
            
            $sql = "INSERT INTO equipos (
                        id_area, 
                        nombre_equipo, 
                        descripcion, 
                        descripcion_tec,
                        especificacion,
                        fabricante,
                        modelo, 
                        marca, 
                        serie,
                        codigo,
                        numero_se,
                        año_fabricacion,
                        manual,
                        fecha_instalacion,
                        ries,
                        estado,
                        estado_operativo
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )";
            
            $stmt = $this->db->prepare($sql);
            
            $resultado = $stmt->execute([
                (int)$datos['id_area'],
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['descripcion_tec'] ?? '',
                $datos['especificacion'] ?? '',
                $datos['fabricante'] ?? '',
                $datos['modelo'] ?? '',
                $datos['marca'] ?? '',
                $datos['serie'] ?? '',
                $datos['codigo'] ?? '',
                $datos['numero_se'] ?? '',
                !empty($datos['año_fabricacion']) ? (int)$datos['año_fabricacion'] : null,
                $datos['manual'] ?? '',
                !empty($datos['fecha_instalacion']) ? $datos['fecha_instalacion'] : null,
                $datos['ries'] ?? '',
                $datos['estado'] ?? 'activo',
                $datos['estado_operativo'] ?? 'Operativo'
            ]);
            
            if ($resultado) {
                $id = $this->db->lastInsertId();
                error_log("✅ Equipo creado con ID: $id");
                return $id;
            } else {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = "Error SQL: " . ($errorInfo[2] ?? 'Desconocido');
                error_log("❌ Error SQL al crear equipo: " . print_r($errorInfo, true));
                return false;
            }
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("❌ Excepción al crear equipo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Actualizar equipo
     */
    public function actualizar($id, $datos)
    {
        try {
            $sql = "UPDATE equipos SET 
                        id_area = ?,
                        nombre_equipo = ?,
                        descripcion = ?,
                        descripcion_tec = ?,
                        especificacion = ?,
                        fabricante = ?,
                        modelo = ?,
                        marca = ?,
                        serie = ?,
                        codigo = ?,
                        numero_se = ?,
                        año_fabricacion = ?,
                        manual = ?,
                        fecha_instalacion = ?,
                        ries = ?,
                        estado = ?,
                        estado_operativo = ?
                    WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['id_area'],
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['descripcion_tec'] ?? '',
                $datos['especificacion'] ?? '',
                $datos['fabricante'] ?? '',
                $datos['modelo'] ?? '',
                $datos['marca'] ?? '',
                $datos['serie'] ?? '',
                $datos['codigo'] ?? '',
                $datos['numero_se'] ?? '',
                $datos['año_fabricacion'] ?? null,
                $datos['manual'] ?? '',
                $datos['fecha_instalacion'] ?? null,
                $datos['ries'] ?? '',
                $datos['estado'] ?? 'activo',
                $datos['estado_operativo'] ?? 'Operativo',
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error en actualizar (Equipos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado del equipo
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE equipos SET estado = ? WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, $id]);
        } catch (PDOException $e) {
            error_log("Error en cambiarEstado (Equipos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado operativo
     */
    public function cambiarEstadoOperativo($id, $estado_operativo, $observaciones = null)
    {
        try {
            $sql = "UPDATE equipos SET 
                        estado_operativo = ?, 
                        fecha_estado = NOW(),
                        observaciones_estado = ?
                    WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado_operativo, $observaciones, $id]);
        } catch (PDOException $e) {
            error_log("Error en cambiarEstadoOperativo (Equipos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar equipo
     */
    public function eliminar($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM componentes WHERE id_equipo = ?");
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado && $resultado['total'] > 0) {
                return ['error' => true, 'message' => 'No se puede eliminar el equipo porque tiene componentes asociados'];
            }

            $sql = "DELETE FROM equipos WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return ['error' => false, 'message' => 'Equipo eliminado correctamente'];
        } catch (PDOException $e) {
            error_log("Error en eliminar (Equipos): " . $e->getMessage());
            return ['error' => true, 'message' => 'Error al eliminar el equipo: ' . $e->getMessage()];
        }
    }

    /**
     * Buscar equipos
     */
    public function buscar($termino)
    {
        try {
            $sql = "SELECT e.*, a.nombre_area, p.nombre_planta 
                    FROM equipos e
                    LEFT JOIN areas a ON e.id_area = a.id_area
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE e.nombre_equipo LIKE ? OR e.codigo LIKE ? OR e.serie LIKE ?
                    ORDER BY e.nombre_equipo ASC
                    LIMIT 10";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar, $buscar, $buscar]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en buscar (Equipos): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de equipos
     */
    public function obtenerEstadisticas()
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_equipos,
                        COUNT(DISTINCT id_area) as total_areas,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                        SUM(CASE WHEN estado_operativo = 'Operativo' THEN 1 ELSE 0 END) as operativos,
                        SUM(CASE WHEN estado_operativo = 'En Mantenimiento' THEN 1 ELSE 0 END) as en_mantenimiento,
                        SUM(CASE WHEN estado_operativo = 'Averiado' THEN 1 ELSE 0 END) as averiados,
                        SUM(CASE WHEN estado_operativo = 'Fuera de Servicio' THEN 1 ELSE 0 END) as fuera_servicio
                    FROM equipos";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticas (Equipos): " . $e->getMessage());
            return [
                'total_equipos' => 0,
                'total_areas' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'operativos' => 0,
                'en_mantenimiento' => 0,
                'averiados' => 0,
                'fuera_servicio' => 0
            ];
        }
    }
}
?>