<?php
// model/OrdenTecnicosModel.php
// Ubicación: C:\xampp\htdocs\proyecto\model\OrdenTecnicosModel.php
// VERSIÓN CORREGIDA Y COMPLETA

require_once __DIR__ . '/../config/database.php';

class OrdenTecnicosModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Guardar técnico en una orden
     */
    public function guardar($datos) {
        try {
            // Verificar que los datos necesarios existen
            if (!isset($datos['orden_id']) || !isset($datos['tecnico_id'])) {
                error_log("Error en guardar: faltan datos obligatorios");
                return false;
            }
            
            // Calcular costo_total
            $horas_trabajadas = $datos['horas_trabajadas'] ?? 0;
            $tarifa_hora = $datos['tarifa_hora'] ?? 0;
            $costo_total = $horas_trabajadas * $tarifa_hora;
            
            $sql = "INSERT INTO ordenes_tecnicos (orden_id, tecnico_id, horas_trabajadas, tarifa_hora, costo_total, fecha_asignacion, fecha_creacion) 
                    VALUES (:orden_id, :tecnico_id, :horas_trabajadas, :tarifa_hora, :costo_total, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'orden_id' => $datos['orden_id'],
                'tecnico_id' => $datos['tecnico_id'],
                'horas_trabajadas' => $horas_trabajadas,
                'tarifa_hora' => $tarifa_hora,
                'costo_total' => $costo_total
            ]);
        } catch (PDOException $e) {
            error_log("Error en guardar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener técnicos por orden
     */
    public function obtenerPorOrden($ordenId) {
        try {
            $sql = "SELECT ot.*, t.nombre as tecnico_nombre, t.especialidad, t.telefono, t.email, t.tarifa as tarifa_tecnico
                    FROM ordenes_tecnicos ot 
                    JOIN tecnicos t ON ot.tecnico_id = t.id 
                    WHERE ot.orden_id = :orden_id
                    ORDER BY ot.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['orden_id' => $ordenId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPorOrden: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eliminar técnicos por orden
     */
    public function eliminarPorOrden($ordenId) {
        try {
            $sql = "DELETE FROM ordenes_tecnicos WHERE orden_id = :orden_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['orden_id' => $ordenId]);
        } catch (PDOException $e) {
            error_log("Error en eliminarPorOrden: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sumar costo total de mano de obra por orden
     */
    public function sumarManoObra($ordenId) {
        try {
            $sql = "SELECT SUM(costo_total) as total 
                    FROM ordenes_tecnicos 
                    WHERE orden_id = :orden_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['orden_id' => $ordenId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error en sumarManoObra: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar técnico de una orden
     */
    public function actualizar($id, $datos) {
        try {
            $horas_trabajadas = $datos['horas_trabajadas'] ?? 0;
            $tarifa_hora = $datos['tarifa_hora'] ?? 0;
            $costo_total = $horas_trabajadas * $tarifa_hora;
            
            $sql = "UPDATE ordenes_tecnicos 
                    SET horas_trabajadas = :horas_trabajadas, 
                        tarifa_hora = :tarifa_hora,
                        costo_total = :costo_total,
                        fecha_actualizacion = NOW()
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'horas_trabajadas' => $horas_trabajadas,
                'tarifa_hora' => $tarifa_hora,
                'costo_total' => $costo_total
            ]);
        } catch (PDOException $e) {
            error_log("Error en actualizar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un técnico específico de una orden
     */
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM ordenes_tecnicos WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error en eliminar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guardar múltiples técnicos para una orden
     */
    public function guardarMultiples($ordenId, $tecnicos) {
        try {
            $sql = "INSERT INTO ordenes_tecnicos (orden_id, tecnico_id, horas_trabajadas, tarifa_hora, costo_total, fecha_asignacion, fecha_creacion) 
                    VALUES (:orden_id, :tecnico_id, :horas_trabajadas, :tarifa_hora, :costo_total, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            
            foreach ($tecnicos as $tecnico) {
                $horas_trabajadas = $tecnico['horas_trabajadas'] ?? 0;
                $tarifa_hora = $tecnico['tarifa_hora'] ?? 0;
                $costo_total = $horas_trabajadas * $tarifa_hora;
                
                $stmt->execute([
                    'orden_id' => $ordenId,
                    'tecnico_id' => $tecnico['tecnico_id'],
                    'horas_trabajadas' => $horas_trabajadas,
                    'tarifa_hora' => $tarifa_hora,
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
     * Obtener el técnico principal de una orden
     */
    public function obtenerTecnicoPrincipal($ordenId) {
        try {
            $sql = "SELECT ot.*, t.nombre as tecnico_nombre, t.especialidad, t.telefono, t.email, t.tarifa as tarifa_tecnico
                    FROM ordenes_tecnicos ot 
                    JOIN tecnicos t ON ot.tecnico_id = t.id 
                    WHERE ot.orden_id = :orden_id 
                    ORDER BY ot.id ASC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['orden_id' => $ordenId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerTecnicoPrincipal: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un técnico ya está asignado a una orden
     */
    public function existeEnOrden($ordenId, $tecnicoId) {
        try {
            $sql = "SELECT COUNT(*) as total FROM ordenes_tecnicos 
                    WHERE orden_id = :orden_id AND tecnico_id = :tecnico_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'orden_id' => $ordenId,
                'tecnico_id' => $tecnicoId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error en existeEnOrden: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener total de horas trabajadas por un técnico en todas sus órdenes
     */
    public function obtenerTotalHorasTecnico($tecnicoId) {
        try {
            $sql = "SELECT SUM(horas_trabajadas) as total_horas 
                    FROM ordenes_tecnicos 
                    WHERE tecnico_id = :tecnico_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tecnico_id' => $tecnicoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_horas'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error en obtenerTotalHorasTecnico: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener todas las órdenes de un técnico
     */
    public function obtenerOrdenesPorTecnico($tecnicoId, $limit = null) {
        try {
            $sql = "SELECT ot.*, o.num_om, o.titulo, o.status, o.fecha_creacion 
                    FROM ordenes_tecnicos ot 
                    JOIN ordenes_mantenimiento o ON ot.orden_id = o.id 
                    WHERE ot.tecnico_id = :tecnico_id 
                    ORDER BY o.fecha_creacion DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':tecnico_id', $tecnicoId, PDO::PARAM_INT);
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerOrdenesPorTecnico: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener técnicos asignados a una orden con sus detalles
     */
    public function obtenerTecnicosConDetalles($ordenId) {
        try {
            $sql = "SELECT ot.*, 
                           t.nombre as tecnico_nombre, 
                           t.especialidad, 
                           t.telefono, 
                           t.email,
                           t.tarifa as tarifa_base
                    FROM ordenes_tecnicos ot 
                    LEFT JOIN tecnicos t ON ot.tecnico_id = t.id 
                    WHERE ot.orden_id = :orden_id 
                    ORDER BY ot.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['orden_id' => $ordenId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerTecnicosConDetalles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener el costo total de mano de obra por orden con detalle
     */
    public function obtenerDetalleManoObra($ordenId) {
        try {
            $sql = "SELECT ot.*, t.nombre as tecnico_nombre
                    FROM ordenes_tecnicos ot 
                    JOIN tecnicos t ON ot.tecnico_id = t.id 
                    WHERE ot.orden_id = :orden_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['orden_id' => $ordenId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerDetalleManoObra: " . $e->getMessage());
            return [];
        }
    }
}
?>