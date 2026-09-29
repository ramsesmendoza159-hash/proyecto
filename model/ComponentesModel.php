<?php
// model/ComponentesModel.php
// Modelo de Componentes
// ✅ FIX 1: LEFT JOIN en vez de INNER JOIN
// ✅ FIX 2: crear() valida nombre e id_equipo
// ✅ FIX 3: actualizar() valida nombre
// ✅ FIX 4: agregado cambiarEstado() con whitelist
// ✅ FIX 5: catch (Throwable) en constructor

require_once __DIR__ . '/../config/database.php';

class ComponentesModel {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (ComponentesModel): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function obtenerTodos($filtros = []) {
        try {
            // ✅ FIX 1: LEFT JOIN
            $sql = "SELECT c.*, e.nombre_equipo, a.nombre_area, p.nombre_planta 
                    FROM componentes c
                    LEFT JOIN equipos e ON c.id_equipo = e.id_equipo
                    LEFT JOIN areas a ON e.id_area = a.id_area
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['id_equipo'])) {
                $sql .= " AND c.id_equipo = ?";
                $params[] = (int)$filtros['id_equipo'];
            }

            if (!empty($filtros['estado'])) {
                $sql .= " AND c.estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (c.nombre_componente LIKE ? OR c.descripcion LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY p.nombre_planta ASC, a.nombre_area ASC, e.nombre_equipo ASC, c.nombre_componente ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            // ✅ FIX 1: LEFT JOIN
            $sql = "SELECT c.*, e.nombre_equipo, a.nombre_area, p.nombre_planta 
                    FROM componentes c
                    LEFT JOIN equipos e ON c.id_equipo = e.id_equipo
                    LEFT JOIN areas a ON e.id_area = a.id_area
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE c.id_componente = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPorEquipo($id_equipo) {
        try {
            $sql = "SELECT * FROM componentes WHERE id_equipo = ? ORDER BY nombre_componente ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id_equipo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::obtenerPorEquipo: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    /**
     * ✅ FIX 2: valida nombre e id_equipo.
     */
    public function crear($datos) {
        try {
            if (empty($datos['nombre'])) {
                error_log("ComponentesModel::crear - Nombre vacío");
                return false;
            }

            if (empty($datos['id_equipo'])) {
                error_log("ComponentesModel::crear - id_equipo requerido");
                return false;
            }

            $sql = "INSERT INTO componentes (id_equipo, nombre_componente, descripcion, estado) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                (int)$datos['id_equipo'],
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['estado'] ?? 'activo'
            ]);
            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::crear: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    /**
     * ✅ FIX 3: valida nombre.
     */
    public function actualizar($id, $datos) {
        try {
            if (empty($datos['nombre'])) {
                error_log("ComponentesModel::actualizar - Nombre vacío");
                return false;
            }

            $sql = "UPDATE componentes SET 
                        id_equipo = ?, 
                        nombre_componente = ?, 
                        descripcion = ?, 
                        estado = ?,
                        updated_at = NOW()
                    WHERE id_componente = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                (int)$datos['id_equipo'],
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['estado'] ?? 'activo',
                (int)$id
            ]);

        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::actualizar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CAMBIAR ESTADO
    // ==========================================

    /**
     * ✅ FIX 4: agregado cambiarEstado() con whitelist.
     */
    public function cambiarEstado($id, $estado) {
        try {
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                error_log("ComponentesModel::cambiarEstado - Estado inválido: $estado");
                return false;
            }

            $sql = "UPDATE componentes SET estado = ?, updated_at = NOW() WHERE id_componente = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    public function eliminar($id) {
        try {
            $sql = "DELETE FROM componentes WHERE id_componente = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([(int)$id]);
        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::eliminar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // BUSCAR
    // ==========================================

    public function buscar($termino) {
        try {
            $sql = "SELECT c.*, e.nombre_equipo 
                    FROM componentes c
                    LEFT JOIN equipos e ON c.id_equipo = e.id_equipo
                    WHERE c.nombre_componente LIKE ? 
                    ORDER BY c.nombre_componente ASC
                    LIMIT 10";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ComponentesModel::buscar: " . $e->getMessage());
            return [];
        }
    }
}