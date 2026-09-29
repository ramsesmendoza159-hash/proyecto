<?php
// model/RepuestosModel.php
// Ubicación: C:\xampp\htdocs\proyecto\model\RepuestosModel.php

// Incluir la base de datos
require_once __DIR__ . '/../config/database.php';

class RepuestosModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los repuestos con filtros mejorados
     */
    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT * FROM inventario WHERE 1=1";
            $params = [];

            // Filtro por categoría
            if (!empty($filtros['categoria'])) {
                $sql .= " AND categoria = ?";
                $params[] = $filtros['categoria'];
            }

            // Filtro por tipo (nuevo)
            if (!empty($filtros['tipo'])) {
                $sql .= " AND tipo = ?";
                $params[] = $filtros['tipo'];
            }

            // Filtro por estado
            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = ?";
                $params[] = $filtros['estado'];
            }

            // Filtro de búsqueda
            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nombre LIKE ? OR codigo LIKE ? OR descripcion LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            // Filtro por stock (mejorado)
            if (!empty($filtros['stock'])) {
                if ($filtros['stock'] === 'bajo') {
                    $sql .= " AND cantidad <= stock_minimo";
                } elseif ($filtros['stock'] === 'medio') {
                    $sql .= " AND cantidad > stock_minimo AND cantidad <= 20";
                } elseif ($filtros['stock'] === 'alto') {
                    $sql .= " AND cantidad > 20";
                }
            }

            $sql .= " ORDER BY nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerTodos (Repuestos): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener repuesto por ID
     */
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM inventario WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPorId (Repuestos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener repuesto por código
     */
    public function obtenerPorCodigo($codigo) {
        try {
            $sql = "SELECT * FROM inventario WHERE codigo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$codigo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPorCodigo (Repuestos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear repuesto
     */
    public function crear($datos) {
        try {
            $sql = "INSERT INTO inventario (codigo, nombre, descripcion, categoria, cantidad, precio_unitario, 
                    unidad_medida, stock_minimo, ubicacion, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['codigo'] ?? null,
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['categoria'] ?? '',
                $datos['cantidad'] ?? 0,
                $datos['precio_unitario'] ?? 0,
                $datos['unidad_medida'] ?? '',
                $datos['stock_minimo'] ?? 0,
                $datos['ubicacion'] ?? '',
                $datos['estado'] ?? 'activo'
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en crear (Repuestos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar repuesto
     */
    public function actualizar($id, $datos) {
        try {
            $sql = "UPDATE inventario SET 
                        codigo = ?,
                        nombre = ?,
                        descripcion = ?,
                        categoria = ?,
                        cantidad = ?,
                        precio_unitario = ?,
                        unidad_medida = ?,
                        stock_minimo = ?,
                        ubicacion = ?,
                        estado = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['codigo'] ?? null,
                trim($datos['nombre']),
                $datos['descripcion'] ?? '',
                $datos['categoria'] ?? '',
                $datos['cantidad'] ?? 0,
                $datos['precio_unitario'] ?? 0,
                $datos['unidad_medida'] ?? '',
                $datos['stock_minimo'] ?? 0,
                $datos['ubicacion'] ?? '',
                $datos['estado'] ?? 'activo',
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error en actualizar (Repuestos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar repuesto
     */
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM inventario WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en eliminar (Repuestos): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de repuestos
     */
    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(cantidad) as total_stock,
                        AVG(precio_unitario) as precio_promedio,
                        SUM(cantidad * precio_unitario) as valor_total
                    FROM inventario 
                    WHERE estado = 'activo'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total' => $result['total'] ?? 0,
                'total_stock' => $result['total_stock'] ?? 0,
                'precio_promedio' => round($result['precio_promedio'] ?? 0, 2),
                'valor_total' => round($result['valor_total'] ?? 0, 2)
            ];
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticas (Repuestos): " . $e->getMessage());
            return ['total' => 0, 'total_stock' => 0, 'precio_promedio' => 0, 'valor_total' => 0];
        }
    }

    /**
     * Obtener productos con bajo stock
     */
    public function obtenerBajoStock($limite = 10) {
        try {
            $sql = "SELECT * FROM inventario 
                    WHERE cantidad <= stock_minimo 
                    AND estado = 'activo'
                    ORDER BY cantidad ASC 
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerBajoStock (Repuestos): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener categorías disponibles
     */
    public function obtenerCategorias() {
        try {
            $sql = "SELECT DISTINCT categoria FROM inventario WHERE estado = 'activo' AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error en obtenerCategorias (Repuestos): " . $e->getMessage());
            return [];
        }
    }
}