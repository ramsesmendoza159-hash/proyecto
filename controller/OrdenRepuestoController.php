<?php
// model/OrdenRepuestosModel.php
// Ubicación: C:\xampp\htdocs\proyecto\model\OrdenRepuestosModel.php

require_once __DIR__ . '/../config/database.php';

class OrdenRepuestosModel {
    private $db;

    public function __construct() {
        // Usar la misma conexión que el resto del proyecto
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Guardar repuesto en una orden
     */
    public function guardar($datos) {
        $sql = "INSERT INTO ordenes_repuestos (orden_id, repuesto_id, cantidad, costo_unitario, costo_total) 
                VALUES (:orden_id, :repuesto_id, :cantidad, :costo_unitario, :costo_total)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($datos);
    }

    /**
     * Obtener repuestos por orden
     */
    public function obtenerPorOrden($ordenId) {
        $sql = "SELECT orp.*, r.nombre as repuesto_nombre, r.codigo, r.unidad_medida 
                FROM ordenes_repuestos orp 
                JOIN inventario r ON orp.repuesto_id = r.id 
                WHERE orp.orden_id = :orden_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['orden_id' => $ordenId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Eliminar repuestos por orden
     */
    public function eliminarPorOrden($ordenId) {
        $sql = "DELETE FROM ordenes_repuestos WHERE orden_id = :orden_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['orden_id' => $ordenId]);
    }

    /**
     * Sumar costo total de repuestos por orden
     */
    public function sumarRepuestos($ordenId) {
        $sql = "SELECT SUM(costo_total) as total 
                FROM ordenes_repuestos 
                WHERE orden_id = :orden_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['orden_id' => $ordenId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Eliminar un repuesto específico de una orden
     */
    public function eliminar($id) {
        $sql = "DELETE FROM ordenes_repuestos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Guardar múltiples repuestos para una orden
     */
    public function guardarMultiples($ordenId, $repuestos) {
        try {
            $sql = "INSERT INTO ordenes_repuestos (orden_id, repuesto_id, cantidad, costo_unitario, costo_total) 
                    VALUES (:orden_id, :repuesto_id, :cantidad, :costo_unitario, :costo_total)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($repuestos as $repuesto) {
                $costo_total = $repuesto['cantidad'] * $repuesto['costo_unitario'];
                $stmt->execute([
                    'orden_id' => $ordenId,
                    'repuesto_id' => $repuesto['repuesto_id'],
                    'cantidad' => $repuesto['cantidad'],
                    'costo_unitario' => $repuesto['costo_unitario'],
                    'costo_total' => $costo_total
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en guardarMultiples: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar cantidad de un repuesto en una orden - CORREGIDO
     */
    public function actualizarCantidad($id, $cantidad, $costo_unitario = null) {
        try {
            if ($costo_unitario !== null) {
                $sql = "UPDATE ordenes_repuestos 
                        SET cantidad = :cantidad, 
                            costo_unitario = :costo_unitario,
                            costo_total = :cantidad * :costo_unitario
                        WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'id' => $id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo_unitario
                ]);
            } else {
                // ✅ Obtener el costo_unitario actual
                $sql = "SELECT costo_unitario FROM ordenes_repuestos WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$result) {
                    return false;
                }
                
                $costo_unitario = $result['costo_unitario'];
                
                $sql = "UPDATE ordenes_repuestos 
                        SET cantidad = :cantidad,
                            costo_total = :cantidad * :costo_unitario
                        WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'id' => $id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo_unitario
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error en actualizarCantidad: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un repuesto ya está en una orden
     */
    public function existeEnOrden($ordenId, $repuestoId) {
        $sql = "SELECT COUNT(*) as total FROM ordenes_repuestos 
                WHERE orden_id = :orden_id AND repuesto_id = :repuesto_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'orden_id' => $ordenId,
            'repuesto_id' => $repuestoId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Obtener repuestos con stock bajo para una orden específica
     */
    public function obtenerConStockBajo($ordenId) {
        $sql = "SELECT orp.*, r.nombre, r.codigo, r.cantidad as stock_actual, 
                       (r.cantidad - orp.cantidad) as stock_restante
                FROM ordenes_repuestos orp
                JOIN inventario r ON orp.repuesto_id = r.id
                WHERE orp.orden_id = :orden_id 
                AND r.cantidad < orp.cantidad";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['orden_id' => $ordenId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>