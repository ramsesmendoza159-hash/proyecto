<?php
// model/ProveedoresModel.php
// Modelo de Proveedores
// ✅ FIX 1: agregados eliminar() y cambiarEstado()
// ✅ FIX 2: crear() valida nombre
// ✅ FIX 3: actualizar() valida nombre
// ✅ FIX 4: emailExiste() para validar emails duplicados
// ✅ FIX 5: catch (Throwable) en constructor

require_once __DIR__ . '/../config/database.php';

class ProveedoresModel {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (ProveedoresModel): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT * FROM proveedores WHERE 1=1";
            $params = [];

            if (!empty($filtros['tipo'])) {
                $sql .= " AND (tipo = ? OR tipo = 'ambos')";
                $params[] = $filtros['tipo'];
            }

            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nombre LIKE ? OR contacto LIKE ? OR ruc LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ProveedoresModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerProveedoresRepuestos() {
        return $this->obtenerTodos(['tipo' => 'repuestos']);
    }

    public function obtenerProveedoresServicios() {
        return $this->obtenerTodos(['tipo' => 'servicios']);
    }

    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM proveedores WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ProveedoresModel::obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    /**
     * ✅ FIX 2: valida nombre.
     */
    public function crear($datos) {
        try {
            if (empty($datos['nombre'])) {
                error_log("ProveedoresModel::crear - Nombre vacío");
                return false;
            }

            $sql = "INSERT INTO proveedores (nombre, tipo, contacto, telefono, email, direccion, ruc, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                trim($datos['nombre']),
                $datos['tipo'] ?? 'ambos',
                $datos['contacto'] ?? '',
                $datos['telefono'] ?? '',
                $datos['email'] ?? '',
                $datos['direccion'] ?? '',
                $datos['ruc'] ?? '',
                $datos['estado'] ?? 'activo'
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en ProveedoresModel::crear: " . $e->getMessage());
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
                error_log("ProveedoresModel::actualizar - Nombre vacío");
                return false;
            }

            $sql = "UPDATE proveedores SET 
                        nombre = ?,
                        tipo = ?,
                        contacto = ?,
                        telefono = ?,
                        email = ?,
                        direccion = ?,
                        ruc = ?,
                        estado = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                trim($datos['nombre']),
                $datos['tipo'] ?? 'ambos',
                $datos['contacto'] ?? '',
                $datos['telefono'] ?? '',
                $datos['email'] ?? '',
                $datos['direccion'] ?? '',
                $datos['ruc'] ?? '',
                $datos['estado'] ?? 'activo',
                (int)$id
            ]);
        } catch (PDOException $e) {
            error_log("Error en ProveedoresModel::actualizar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CAMBIAR ESTADO
    // ==========================================

    /**
     * ✅ FIX 1: agregado cambiarEstado().
     */
    public function cambiarEstado($id, $estado) {
        try {
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                error_log("ProveedoresModel::cambiarEstado - Estado inválido: $estado");
                return false;
            }

            $sql = "UPDATE proveedores SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en ProveedoresModel::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    /**
     * ✅ FIX 1: agregado eliminar().
     */
    public function eliminar($id) {
        try {
            // Verificar si tiene inventario asociado
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM inventario WHERE proveedor_id = ?");
            $stmt->execute([(int)$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (($result['total'] ?? 0) > 0) {
                error_log("ProveedoresModel::eliminar - Proveedor ID $id tiene inventario asociado");
                return false;
            }

            $sql = "DELETE FROM proveedores WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([(int)$id]);

        } catch (PDOException $e) {
            error_log("Error en ProveedoresModel::eliminar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // VALIDACIONES
    // ==========================================

    /**
     * ✅ FIX 4: validar email duplicado.
     */
    public function emailExiste($email, $excluirId = null) {
        try {
            if (empty($email)) return false;

            $sql = "SELECT COUNT(*) as total FROM proveedores WHERE email = ?";
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
            error_log("Error en ProveedoresModel::emailExiste: " . $e->getMessage());
            return false;
        }
    }
}