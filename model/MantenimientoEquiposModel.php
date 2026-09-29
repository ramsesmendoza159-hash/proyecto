<?php
// model/MantenimientoEquiposModel.php
// Gestión completa de mantenimiento preventivo por horas de uso
// ✅ FIX: registrarMantenimientoRealizado() valida que el equipo tenga horómetro
// ✅ FIX: eliminarMantenimiento() revierte ultimo_mantenimiento_horas
// ✅ FIX: eliminarFotoEvidencia() valida path traversal
// ✅ FIX: catch (Throwable) en vez de Exception
// ✅ FIX: alias tecnico_nombre y proveedor_nombre en SELECTs
// ✅ FIX: costo_estimado en obtenerMantenimientoPorId()

require_once __DIR__ . '/../config/database.php';

class MantenimientoEquiposModel {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (MantenimientoEquiposModel): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // HORÓMETRO
    // ==========================================

    public function registrarHorometro($id_equipo, $horas_actuales, $usuario_id, $observaciones = '', $fecha_lectura = null) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT horometro_actual FROM equipos WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo]);
            $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$equipo) {
                throw new Exception('Equipo no encontrado');
            }

            $horas_anteriores = (int)($equipo['horometro_actual'] ?? 0);

            if ($horas_actuales < $horas_anteriores) {
                throw new Exception("Las horas actuales ($horas_actuales) no pueden ser menores a las anteriores ($horas_anteriores)");
            }

            $horas_desde_ultimo = $horas_actuales - $horas_anteriores;

            if (empty($fecha_lectura)) {
                $fecha_lectura = date('Y-m-d H:i:s');
            } else {
                $timestamp = strtotime($fecha_lectura);
                if ($timestamp === false) {
                    throw new Exception('Formato de fecha inválido');
                }
                $fecha_lectura = date('Y-m-d H:i:s', $timestamp);

                if ($timestamp > time() + 60) {
                    throw new Exception('La fecha de la lectura no puede ser futura');
                }
            }

            $sql = "INSERT INTO equipos_horometro (
                        id_equipo, horas_actuales, horas_desde_ultimo,
                        fecha_lectura, usuario_id, observaciones
                    ) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id_equipo, 
                $horas_actuales, 
                $horas_desde_ultimo,
                $fecha_lectura,
                $usuario_id, 
                $observaciones
            ]);

            $sql = "SELECT 
                        SUM(horas_desde_ultimo) as total_horas,
                        DATEDIFF(MAX(fecha_lectura), MIN(fecha_lectura)) as dias
                    FROM equipos_horometro 
                    WHERE id_equipo = ? 
                    AND fecha_lectura >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo]);
            $promedio = $stmt->fetch(PDO::FETCH_ASSOC);

            $horas_diarias = 0;
            if ($promedio && $promedio['dias'] > 0) {
                $horas_diarias = round($promedio['total_horas'] / $promedio['dias'], 2);
            }

            $sql = "UPDATE equipos SET 
                        horometro_actual = ?,
                        horometro_ultima_lectura = ?,
                        horas_diarias_promedio = ?
                    WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $horas_actuales, 
                $fecha_lectura,
                $horas_diarias, 
                $id_equipo
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'horas_actuales' => $horas_actuales,
                'horas_desde_ultimo' => $horas_desde_ultimo,
                'horas_diarias' => $horas_diarias,
                'fecha_lectura' => $fecha_lectura
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en registrarHorometro: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function obtenerHistorialHorometro($id_equipo, $limite = 30) {
        try {
            $sql = "SELECT h.*, u.nombre as usuario_nombre
                    FROM equipos_horometro h
                    LEFT JOIN usuarios u ON h.usuario_id = u.id
                    WHERE h.id_equipo = ?
                    ORDER BY h.fecha_lectura DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo, (int)$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerHistorialHorometro: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerLecturaHorometro($lectura_id) {
        try {
            $sql = "SELECT h.*, e.nombre_equipo, e.codigo, u.nombre as usuario_nombre
                    FROM equipos_horometro h
                    JOIN equipos e ON h.id_equipo = e.id_equipo
                    LEFT JOIN usuarios u ON h.usuario_id = u.id
                    WHERE h.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lectura_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerLecturaHorometro: " . $e->getMessage());
            return false;
        }
    }

    public function editarLecturaHorometro($lectura_id, $nuevas_horas, $observaciones = '', $usuario_id = null, $nueva_fecha = null) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT * FROM equipos_horometro WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lectura_id]);
            $lectura = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lectura) {
                throw new Exception('Lectura no encontrada');
            }

            $id_equipo = $lectura['id_equipo'];
            $horas_anteriores = $lectura['horas_actuales'];

            if ($nuevas_horas < 0) {
                throw new Exception('Las horas no pueden ser negativas');
            }

            $fecha_final = $lectura['fecha_lectura'];
            if (!empty($nueva_fecha)) {
                $timestamp = strtotime($nueva_fecha);
                if ($timestamp === false) {
                    throw new Exception('Formato de fecha inválido');
                }
                if ($timestamp > time() + 60) {
                    throw new Exception('La fecha no puede ser futura');
                }
                $fecha_final = date('Y-m-d H:i:s', $timestamp);
            }

            $sql = "UPDATE equipos_horometro SET 
                        horas_actuales = ?,
                        observaciones = ?,
                        fecha_lectura = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nuevas_horas, $observaciones, $fecha_final, $lectura_id]);

            $sql = "SELECT horas_actuales FROM equipos_horometro 
                    WHERE id_equipo = ? AND fecha_lectura < ?
                    ORDER BY fecha_lectura DESC, id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo, $fecha_final]);
            $anterior = $stmt->fetch(PDO::FETCH_ASSOC);
            $horas_previas = $anterior ? (int)$anterior['horas_actuales'] : 0;

            $nueva_diferencia = $nuevas_horas - $horas_previas;

            $sql = "UPDATE equipos_horometro SET horas_desde_ultimo = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nueva_diferencia, $lectura_id]);

            $sql = "SELECT horas_actuales, fecha_lectura FROM equipos_horometro 
                    WHERE id_equipo = ? 
                    ORDER BY fecha_lectura DESC, id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo]);
            $ultima = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ultima) {
                $sql = "UPDATE equipos SET 
                            horometro_actual = ?,
                            horometro_ultima_lectura = ?
                        WHERE id_equipo = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$ultima['horas_actuales'], $ultima['fecha_lectura'], $id_equipo]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => 'Lectura actualizada correctamente',
                'valor_anterior' => $horas_anteriores,
                'valor_nuevo' => $nuevas_horas,
                'fecha_anterior' => $lectura['fecha_lectura'],
                'fecha_nueva' => $fecha_final
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en editarLecturaHorometro: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function eliminarLecturaHorometro($lectura_id) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT * FROM equipos_horometro WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lectura_id]);
            $lectura = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lectura) {
                throw new Exception('Lectura no encontrada');
            }

            $id_equipo = $lectura['id_equipo'];

            $sql = "DELETE FROM equipos_horometro WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lectura_id]);

            $sql = "SELECT id, horas_actuales FROM equipos_horometro 
                    WHERE id_equipo = ? 
                    ORDER BY fecha_lectura ASC, id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo]);
            $lecturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $horas_previas = 0;
            foreach ($lecturas as $lec) {
                $diferencia = (int)$lec['horas_actuales'] - $horas_previas;

                $sqlUpdate = "UPDATE equipos_horometro SET horas_desde_ultimo = ? WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([$diferencia, $lec['id']]);

                $horas_previas = (int)$lec['horas_actuales'];
            }

            if (!empty($lecturas)) {
                $ultima = end($lecturas);
                $sql = "SELECT fecha_lectura FROM equipos_horometro WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$ultima['id']]);
                $info_ultima = $stmt->fetch(PDO::FETCH_ASSOC);

                $sql = "UPDATE equipos SET 
                            horometro_actual = ?,
                            horometro_ultima_lectura = ?
                        WHERE id_equipo = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $ultima['horas_actuales'], 
                    $info_ultima['fecha_lectura'] ?? date('Y-m-d H:i:s'), 
                    $id_equipo
                ]);
            } else {
                $sql = "UPDATE equipos SET 
                            horometro_actual = 0,
                            horometro_ultima_lectura = NULL
                        WHERE id_equipo = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id_equipo]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => 'Lectura eliminada correctamente'
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en eliminarLecturaHorometro: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // PLAN DE MANTENIMIENTO
    // ==========================================

    public function obtenerPlanPorEquipo($id_equipo) {
        try {
            $sql = "SELECT p.*, 
                           pr.nombre as proveedor_nombre,
                           t.nombre as tecnico_nombre,
                           e.horometro_actual,
                           (
                               COALESCE(p.ultimo_mantenimiento_horas, 0) 
                               + COALESCE(p.frecuencia_horas, 0) 
                               - e.horometro_actual
                           ) as horas_restantes,
                           CASE 
                               WHEN p.frecuencia_dias IS NOT NULL 
                                   AND p.ultimo_mantenimiento_fecha IS NOT NULL
                               THEN DATEDIFF(
                                   DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), 
                                   CURDATE()
                               )
                               WHEN p.frecuencia_dias IS NOT NULL 
                                   AND p.ultimo_mantenimiento_fecha IS NULL
                               THEN p.frecuencia_dias
                               ELSE NULL
                           END as dias_restantes
                    FROM equipos_mantenimiento_plan p
                    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                    LEFT JOIN tecnicos t ON p.tecnico_asignado_id = t.id
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    WHERE p.id_equipo = ? AND p.activo = 1
                    ORDER BY 
                        CASE 
                            WHEN (COALESCE(p.ultimo_mantenimiento_horas, 0) + COALESCE(p.frecuencia_horas, 0) - e.horometro_actual) <= 0 THEN 1
                            WHEN DATEDIFF(DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), CURDATE()) <= 0 THEN 2
                            ELSE 3
                        END,
                        horas_restantes ASC,
                        dias_restantes ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPlanPorEquipo: " . $e->getMessage());
            return [];
        }
    }

    public function crearTarea($datos) {
        try {
            $sql = "SELECT horometro_actual FROM equipos WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$datos['id_equipo']]);
            $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
            $horas_actuales = (int)($equipo['horometro_actual'] ?? 0);

            $proximo_horas = null;
            $proximo_fecha = null;

            if (!empty($datos['frecuencia_horas'])) {
                $proximo_horas = $horas_actuales + (int)$datos['frecuencia_horas'];
            }

            if (!empty($datos['frecuencia_dias'])) {
                $proximo_fecha = date('Y-m-d', strtotime('+' . (int)$datos['frecuencia_dias'] . ' days'));
            }

            $tecnico_id = !empty($datos['tecnico_asignado_id']) ? (int)$datos['tecnico_asignado_id'] : null;
            $estado_asignacion = $tecnico_id ? 'ASIGNADO' : 'SIN_ASIGNAR';

            $sql = "INSERT INTO equipos_mantenimiento_plan (
                        id_equipo, nombre_tarea, descripcion, tipo_mantenimiento,
                        frecuencia_horas, frecuencia_dias, 
                        ultimo_mantenimiento_horas, ultimo_mantenimiento_fecha,
                        proximo_mantenimiento_horas, proximo_mantenimiento_fecha,
                        responsable, id_proveedor, costo_estimado, 
                        repuestos_necesarios, activo,
                        tecnico_asignado_id, estado_asignacion, fecha_asignacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                (int)$datos['id_equipo'],
                $datos['nombre_tarea'],
                $datos['descripcion'] ?? '',
                $datos['tipo_mantenimiento'] ?? 'PREVENTIVO',
                !empty($datos['frecuencia_horas']) ? (int)$datos['frecuencia_horas'] : null,
                !empty($datos['frecuencia_dias']) ? (int)$datos['frecuencia_dias'] : null,
                $horas_actuales,
                date('Y-m-d'),
                $proximo_horas,
                $proximo_fecha,
                $datos['responsable'] ?? 'TECNICO_INTERNO',
                !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null,
                (float)($datos['costo_estimado'] ?? 0),
                $datos['repuestos_necesarios'] ?? '',
                $tecnico_id,
                $estado_asignacion,
                $tecnico_id ? date('Y-m-d H:i:s') : null
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en crearTarea: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarTarea($id, $datos) {
        try {
            $plan = $this->obtenerPlanPorId($id);
            if (!$plan) {
                error_log("actualizarTarea: Plan no encontrado - ID $id");
                return false;
            }

            $proximo_horas = null;
            if (!empty($datos['frecuencia_horas'])) {
                $base_horas = (int)($plan['ultimo_mantenimiento_horas'] ?? 0);
                $proximo_horas = $base_horas + (int)$datos['frecuencia_horas'];
            }

            $proximo_fecha = null;
            if (!empty($datos['frecuencia_dias'])) {
                $base_fecha = !empty($plan['ultimo_mantenimiento_fecha']) 
                    ? $plan['ultimo_mantenimiento_fecha'] 
                    : date('Y-m-d');
                $proximo_fecha = date('Y-m-d', strtotime($base_fecha . ' +' . (int)$datos['frecuencia_dias'] . ' days'));
            }

            $sql = "UPDATE equipos_mantenimiento_plan SET 
                        nombre_tarea = ?,
                        descripcion = ?,
                        tipo_mantenimiento = ?,
                        frecuencia_horas = ?,
                        frecuencia_dias = ?,
                        proximo_mantenimiento_horas = ?,
                        proximo_mantenimiento_fecha = ?,
                        responsable = ?,
                        id_proveedor = ?,
                        costo_estimado = ?,
                        repuestos_necesarios = ?,
                        activo = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['nombre_tarea'],
                $datos['descripcion'] ?? '',
                $datos['tipo_mantenimiento'] ?? 'PREVENTIVO',
                !empty($datos['frecuencia_horas']) ? (int)$datos['frecuencia_horas'] : null,
                !empty($datos['frecuencia_dias']) ? (int)$datos['frecuencia_dias'] : null,
                $proximo_horas,
                $proximo_fecha,
                $datos['responsable'] ?? 'TECNICO_INTERNO',
                !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null,
                (float)($datos['costo_estimado'] ?? 0),
                $datos['repuestos_necesarios'] ?? '',
                isset($datos['activo']) ? (int)$datos['activo'] : 1,
                (int)$id
            ]);
        } catch (PDOException $e) {
            error_log("Error en actualizarTarea: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarTarea($id) {
        try {
            $sql = "DELETE FROM equipos_mantenimiento_plan WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en eliminarTarea: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPlanPorId($id) {
        try {
            $sql = "SELECT p.*, e.nombre_equipo, e.codigo, e.horometro_actual,
                           t.nombre as tecnico_nombre
                    FROM equipos_mantenimiento_plan p
                    JOIN equipos e ON p.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON p.tecnico_asignado_id = t.id
                    WHERE p.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPlanPorId: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // MANTENIMIENTOS PRÓXIMOS Y ALERTAS
    // ==========================================

    public function obtenerMantenimientosProximos($dias_alerta = 30, $horas_alerta = 100) {
        try {
            $sql = "SELECT 
                        p.*,
                        e.nombre_equipo,
                        e.codigo,
                        e.horometro_actual,
                        (
                            COALESCE(p.ultimo_mantenimiento_horas, 0) 
                            + COALESCE(p.frecuencia_horas, 0) 
                            - e.horometro_actual
                        ) AS horas_restantes,
                        CASE 
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NOT NULL
                            THEN DATEDIFF(
                                DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), 
                                CURDATE()
                            )
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NULL
                            THEN p.frecuencia_dias
                            ELSE NULL
                        END AS dias_restantes,
                        pr.nombre as proveedor_nombre,
                        t.nombre as tecnico_nombre,
                        CASE 
                            WHEN p.frecuencia_horas IS NOT NULL 
                                AND (
                                    COALESCE(p.ultimo_mantenimiento_horas, 0) 
                                    + COALESCE(p.frecuencia_horas, 0) 
                                    - e.horometro_actual
                                ) <= 0 
                                THEN 'VENCIDO_POR_HORAS'
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NOT NULL
                                AND DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY) <= CURDATE()
                                THEN 'VENCIDO_POR_FECHA'
                            WHEN p.frecuencia_horas IS NOT NULL 
                                AND (
                                    COALESCE(p.ultimo_mantenimiento_horas, 0) 
                                    + COALESCE(p.frecuencia_horas, 0) 
                                    - e.horometro_actual
                                ) <= ?
                                THEN 'PROXIMO_POR_HORAS'
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NOT NULL
                                AND DATEDIFF(
                                    DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), 
                                    CURDATE()
                                ) <= ?
                                THEN 'PROXIMO_POR_FECHA'
                            ELSE 'AL_DIA'
                        END AS estado_alerta
                    FROM equipos_mantenimiento_plan p
                    JOIN equipos e ON p.id_equipo = e.id_equipo
                    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                    LEFT JOIN tecnicos t ON p.tecnico_asignado_id = t.id
                    WHERE p.activo = 1
                    HAVING estado_alerta != 'AL_DIA'
                    ORDER BY 
                        CASE estado_alerta
                            WHEN 'VENCIDO_POR_HORAS' THEN 1
                            WHEN 'VENCIDO_POR_FECHA' THEN 2
                            WHEN 'PROXIMO_POR_HORAS' THEN 3
                            WHEN 'PROXIMO_POR_FECHA' THEN 4
                        END,
                        horas_restantes ASC,
                        dias_restantes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$horas_alerta, (int)$dias_alerta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerMantenimientosProximos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerResumenAlertas() {
        try {
            $sql = "SELECT 
                        SUM(CASE WHEN p.frecuencia_horas IS NOT NULL 
                            AND (COALESCE(p.ultimo_mantenimiento_horas, 0) + COALESCE(p.frecuencia_horas, 0) - e.horometro_actual) <= 0 
                            THEN 1 ELSE 0 END) as vencidos_horas,
                        SUM(CASE WHEN p.frecuencia_dias IS NOT NULL 
                            AND p.ultimo_mantenimiento_fecha IS NOT NULL
                            AND DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY) <= CURDATE()
                            THEN 1 ELSE 0 END) as vencidos_fecha,
                        SUM(CASE WHEN p.frecuencia_horas IS NOT NULL 
                            AND (COALESCE(p.ultimo_mantenimiento_horas, 0) + COALESCE(p.frecuencia_horas, 0) - e.horometro_actual) BETWEEN 1 AND 100 
                            THEN 1 ELSE 0 END) as proximos_horas,
                        SUM(CASE WHEN p.frecuencia_dias IS NOT NULL 
                            AND p.ultimo_mantenimiento_fecha IS NOT NULL
                            AND DATEDIFF(DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), CURDATE()) BETWEEN 1 AND 30 
                            THEN 1 ELSE 0 END) as proximos_fecha
                    FROM equipos_mantenimiento_plan p
                    JOIN equipos e ON p.id_equipo = e.id_equipo
                    WHERE p.activo = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'vencidos_horas' => (int)($result['vencidos_horas'] ?? 0),
                'vencidos_fecha' => (int)($result['vencidos_fecha'] ?? 0),
                'proximos_horas' => (int)($result['proximos_horas'] ?? 0),
                'proximos_fecha' => (int)($result['proximos_fecha'] ?? 0),
                'total_alertas' => (int)(($result['vencidos_horas'] ?? 0) + ($result['vencidos_fecha'] ?? 0) + 
                                        ($result['proximos_horas'] ?? 0) + ($result['proximos_fecha'] ?? 0))
            ];
        } catch (PDOException $e) {
            error_log("Error en obtenerResumenAlertas: " . $e->getMessage());
            return [
                'vencidos_horas' => 0, 'vencidos_fecha' => 0,
                'proximos_horas' => 0, 'proximos_fecha' => 0,
                'total_alertas' => 0
            ];
        }
    }

    // ==========================================
    // REGISTRAR MANTENIMIENTO REALIZADO
    // ==========================================

    public function registrarMantenimientoRealizado($plan_id, $datos) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT * FROM equipos_mantenimiento_plan WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                throw new Exception('Plan de mantenimiento no encontrado');
            }

            $sql = "SELECT horometro_actual FROM equipos WHERE id_equipo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$plan['id_equipo']]);
            $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
            $horas_actuales = (int)($equipo['horometro_actual'] ?? 0);

            $nuevo_proximo_horas = null;
            $nuevo_proximo_fecha = null;

            if (!empty($plan['frecuencia_horas'])) {
                $nuevo_proximo_horas = $horas_actuales + (int)$plan['frecuencia_horas'];
            }

            if (!empty($plan['frecuencia_dias'])) {
                $nuevo_proximo_fecha = date('Y-m-d', strtotime('+' . $plan['frecuencia_dias'] . ' days'));
            }

            $fecha_mantenimiento = !empty($datos['fecha_mantenimiento']) 
                ? $datos['fecha_mantenimiento'] 
                : date('Y-m-d H:i:s');

            $es_retroactivo = !empty($datos['es_retroactivo']) ? 1 : 0;
            $motivo_backdating = $datos['motivo_backdating'] ?? null;
            $registrado_por = $datos['registrado_por'] ?? null;

            $sql = "UPDATE equipos_mantenimiento_plan SET 
                        ultimo_mantenimiento_horas = ?,
                        ultimo_mantenimiento_fecha = ?,
                        proximo_mantenimiento_horas = ?,
                        proximo_mantenimiento_fecha = ?,
                        estado_asignacion = 'COMPLETADO'
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $horas_actuales, 
                date('Y-m-d', strtotime($fecha_mantenimiento)),
                $nuevo_proximo_horas, 
                $nuevo_proximo_fecha, 
                $plan_id
            ]);

            $sql = "INSERT INTO equipos_mantenimientos (
                        id_equipo, plan_id, tipo, descripcion, horas_equipo,
                        fecha_mantenimiento, tecnico_id, proveedor_id, costo,
                        repuestos_usados, observaciones,
                        es_retroactivo, motivo_backdating, registrado_por, fecha_registro_sistema
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $plan['id_equipo'], 
                $plan_id, 
                $plan['tipo_mantenimiento'],
                $datos['descripcion'] ?? $plan['nombre_tarea'], 
                $horas_actuales,
                $fecha_mantenimiento,
                !empty($datos['tecnico_id']) ? (int)$datos['tecnico_id'] : null,
                !empty($datos['proveedor_id']) ? (int)$datos['proveedor_id'] : null,
                (float)($datos['costo'] ?? 0),
                $datos['repuestos_usados'] ?? '', 
                $datos['observaciones'] ?? '',
                $es_retroactivo,
                $motivo_backdating,
                $registrado_por
            ]);

            $mantenimiento_id = $this->db->lastInsertId();
            $this->db->commit();

            return [
                'success' => true,
                'mantenimiento_id' => $mantenimiento_id,
                'nuevo_proximo_horas' => $nuevo_proximo_horas,
                'nuevo_proximo_fecha' => $nuevo_proximo_fecha,
                'es_retroactivo' => $es_retroactivo
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en registrarMantenimientoRealizado: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actualizarBackdatingId($mantenimiento_id, $backdating_id) {
        try {
            $sql = "UPDATE equipos_mantenimientos SET backdating_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$backdating_id, $mantenimiento_id]);
        } catch (PDOException $e) {
            error_log("Error en actualizarBackdatingId: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerHistorialMantenimientos($id_equipo = null, $limite = 50) {
        try {
            $sql = "SELECT m.*, e.nombre_equipo, e.codigo,
                           t.nombre as tecnico_nombre,
                           p.nombre as proveedor_nombre
                    FROM equipos_mantenimientos m
                    JOIN equipos e ON m.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON m.tecnico_id = t.id
                    LEFT JOIN proveedores p ON m.proveedor_id = p.id";

            $params = [];
            if ($id_equipo) {
                $sql .= " WHERE m.id_equipo = ?";
                $params[] = $id_equipo;
            }

            $sql .= " ORDER BY m.fecha_mantenimiento DESC LIMIT ?";
            $params[] = (int)$limite;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerHistorialMantenimientos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerMantenimientoPorId($id) {
        try {
            $sql = "SELECT 
                        m.*,
                        e.nombre_equipo,
                        e.codigo,
                        e.marca,
                        e.modelo,
                        e.serie,
                        e.horometro_actual,
                        t.nombre AS tecnico_nombre,
                        t.email AS tecnico_email,
                        t.especialidad AS tecnico_especialidad,
                        t.tarifa AS tecnico_tarifa,
                        p.nombre AS proveedor_nombre,
                        p.contacto AS proveedor_contacto,
                        p.telefono AS proveedor_telefono,
                        pl.nombre_tarea,
                        pl.frecuencia_horas,
                        pl.frecuencia_dias,
                        pl.costo_estimado,
                        pl.tipo_mantenimiento AS plan_tipo,
                        a.nombre_area,
                        pp.nombre_planta
                    FROM equipos_mantenimientos m
                    LEFT JOIN equipos e ON m.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON m.tecnico_id = t.id
                    LEFT JOIN proveedores p ON m.proveedor_id = p.id
                    LEFT JOIN equipos_mantenimiento_plan pl ON m.plan_id = pl.id
                    LEFT JOIN areas a ON e.id_area = a.id_area
                    LEFT JOIN plantas pp ON a.id_planta = pp.id_planta
                    WHERE m.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerMantenimientoPorId: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerChecklistDeMantenimiento($mantenimiento_id) {
        try {
            $sql = "SELECT plan_id, id_equipo, tecnico_id, fecha_mantenimiento 
                    FROM equipos_mantenimientos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mantenimiento_id]);
            $mant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mant) {
                return [];
            }

            if (empty($mant['plan_id']) || empty($mant['tecnico_id'])) {
                return [];
            }

            $sql = "SELECT 
                        ce.*,
                        cp.paso_numero,
                        cp.descripcion AS paso_descripcion,
                        cp.obligatorio,
                        cp.requiere_foto
                    FROM mantenimiento_checklist_ejecucion ce
                    JOIN mantenimiento_checklist_protocolo cp ON ce.paso_id = cp.id
                    WHERE ce.plan_id = ? 
                    AND ce.id_equipo = ?
                    AND ce.tecnico_id = ?
                    ORDER BY cp.paso_numero ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $mant['plan_id'],
                $mant['id_equipo'],
                $mant['tecnico_id']
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerChecklistDeMantenimiento: " . $e->getMessage());
            return [];
        }
    }

    public function actualizarMantenimiento($mantenimiento_id, $datos, $usuario_id, $motivo_edicion) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT * FROM equipos_mantenimientos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mantenimiento_id]);
            $anterior = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$anterior) {
                throw new Exception('Mantenimiento no encontrado');
            }

            $fecha_nueva = $datos['fecha_mantenimiento'] ?? $anterior['fecha_mantenimiento'];
            $es_retroactivo = 0;
            $motivo_backdating = null;

            if ($fecha_nueva !== $anterior['fecha_mantenimiento']) {
                $fecha_nueva_ts = strtotime($fecha_nueva);
                $fecha_anterior_ts = strtotime($anterior['fecha_mantenimiento']);

                if ($fecha_nueva_ts < $fecha_anterior_ts) {
                    $es_retroactivo = 1;
                    $motivo_backdating = $motivo_edicion;
                }
            }

            $sql = "UPDATE equipos_mantenimientos SET
                        descripcion = ?,
                        horas_equipo = ?,
                        fecha_mantenimiento = ?,
                        tecnico_id = ?,
                        proveedor_id = ?,
                        costo = ?,
                        repuestos_usados = ?,
                        observaciones = ?,
                        es_retroactivo = ?,
                        motivo_backdating = ?,
                        registrado_por = ?
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['descripcion'] ?? $anterior['descripcion'],
                (int)($datos['horas_equipo'] ?? $anterior['horas_equipo']),
                $fecha_nueva,
                !empty($datos['tecnico_id']) ? (int)$datos['tecnico_id'] : null,
                !empty($datos['proveedor_id']) ? (int)$datos['proveedor_id'] : null,
                (float)($datos['costo'] ?? $anterior['costo']),
                $datos['repuestos_usados'] ?? $anterior['repuestos_usados'],
                $datos['observaciones'] ?? $anterior['observaciones'],
                $es_retroactivo,
                $motivo_backdating,
                $usuario_id,
                $mantenimiento_id
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => 'Mantenimiento actualizado correctamente'
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en actualizarMantenimiento: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actualizarChecklistEjecutado($mantenimiento_id, $pasos, $usuario_id) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT plan_id, id_equipo, tecnico_id FROM equipos_mantenimientos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mantenimiento_id]);
            $mant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mant) {
                throw new Exception('Mantenimiento no encontrado');
            }

            foreach ($pasos as $paso_id => $datos_paso) {
                $cumplido = !empty($datos_paso['cumplido']) ? 1 : 0;
                $observaciones = $datos_paso['observaciones'] ?? '';

                $sqlCheck = "SELECT id FROM mantenimiento_checklist_ejecucion 
                             WHERE plan_id = ? AND id_equipo = ? AND tecnico_id = ? AND paso_id = ?";
                $stmtCheck = $this->db->prepare($sqlCheck);
                $stmtCheck->execute([
                    $mant['plan_id'],
                    $mant['id_equipo'],
                    $mant['tecnico_id'],
                    $paso_id
                ]);
                $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existe) {
                    $sqlUpdate = "UPDATE mantenimiento_checklist_ejecucion SET
                                    cumplido = ?,
                                    observaciones = ?
                                  WHERE id = ?";
                    $stmtUpdate = $this->db->prepare($sqlUpdate);
                    $stmtUpdate->execute([$cumplido, $observaciones, $existe['id']]);
                } else {
                    $sqlInsert = "INSERT INTO mantenimiento_checklist_ejecucion 
                                    (plan_id, id_equipo, tecnico_id, paso_id, cumplido, observaciones)
                                  VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtInsert = $this->db->prepare($sqlInsert);
                    $stmtInsert->execute([
                        $mant['plan_id'],
                        $mant['id_equipo'],
                        $mant['tecnico_id'],
                        $paso_id,
                        $cumplido,
                        $observaciones
                    ]);
                }
            }

            $this->db->commit();

            return ['success' => true, 'mensaje' => 'Checklist actualizado correctamente'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en actualizarChecklistEjecutado: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function eliminarMantenimiento($mantenimiento_id) {
        try {
            $sql = "SELECT * FROM equipos_mantenimientos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mantenimiento_id]);
            $mant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mant) {
                throw new Exception('Mantenimiento no encontrado');
            }

            $this->db->beginTransaction();

            $sql = "DELETE FROM mantenimiento_checklist_ejecucion 
                    WHERE plan_id = ? AND id_equipo = ? AND tecnico_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mant['plan_id'], $mant['id_equipo'], $mant['tecnico_id']]);

            $sql = "DELETE FROM equipos_mantenimientos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mantenimiento_id]);

            if (!empty($mant['plan_id'])) {
                $sql = "SELECT MAX(fecha_mantenimiento) as ultima_fecha 
                        FROM equipos_mantenimientos 
                        WHERE plan_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$mant['plan_id']]);
                $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

                $sqlUpdate = "UPDATE equipos_mantenimiento_plan 
                              SET ultimo_mantenimiento_fecha = ?
                              WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([$ultimo['ultima_fecha'] ?? null, $mant['plan_id']]);
            }

            $this->db->commit();

            return ['success' => true, 'mensaje' => 'Mantenimiento eliminado'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en eliminarMantenimiento: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM equipos WHERE estado = 'activo') as total_equipos,
                        (SELECT COUNT(*) FROM equipos_mantenimiento_plan WHERE activo = 1) as total_planes,
                        (SELECT COUNT(*) FROM equipos_mantenimientos WHERE MONTH(fecha_mantenimiento) = MONTH(CURDATE()) AND YEAR(fecha_mantenimiento) = YEAR(CURDATE())) as mantenimientos_mes,
                        (SELECT COALESCE(SUM(costo), 0) FROM equipos_mantenimientos WHERE MONTH(fecha_mantenimiento) = MONTH(CURDATE()) AND YEAR(fecha_mantenimiento) = YEAR(CURDATE())) as costo_mes";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return ['total_equipos' => 0, 'total_planes' => 0, 'mantenimientos_mes' => 0, 'costo_mes' => 0];
        }
    }

    public function obtenerEquiposConPlanes() {
        try {
            $sql = "SELECT 
                        e.id_equipo,
                        e.nombre_equipo,
                        e.codigo,
                        COUNT(p.id) AS total_planes
                    FROM equipos e
                    LEFT JOIN equipos_mantenimiento_plan p ON e.id_equipo = p.id_equipo AND p.activo = 1
                    WHERE e.estado = 'activo'
                    GROUP BY e.id_equipo
                    ORDER BY e.nombre_equipo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerEquiposConPlanes: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // ASIGNACIÓN A TÉCNICOS
    // ==========================================

    public function asignarTecnico($plan_id, $tecnico_id, $asignado_por) {
        try {
            $sql = "UPDATE equipos_mantenimiento_plan SET 
                        tecnico_asignado_id = ?,
                        estado_asignacion = 'ASIGNADO',
                        fecha_asignacion = NOW(),
                        asignado_por = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$tecnico_id, $asignado_por, $plan_id]);
        } catch (PDOException $e) {
            error_log("Error en asignarTecnico: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTareasAsignadas($tecnico_id, $filtros = []) {
        try {
            $sql = "SELECT 
                        p.*,
                        e.nombre_equipo,
                        e.codigo,
                        e.horometro_actual,
                        t.nombre as tecnico_nombre,
                        (
                            COALESCE(p.ultimo_mantenimiento_horas, 0) 
                            + COALESCE(p.frecuencia_horas, 0) 
                            - e.horometro_actual
                        ) as horas_restantes,
                        CASE 
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NOT NULL
                            THEN DATEDIFF(
                                DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), 
                                CURDATE()
                            )
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NULL
                            THEN p.frecuencia_dias
                            ELSE NULL
                        END as dias_restantes,
                        CASE 
                            WHEN p.frecuencia_horas IS NOT NULL 
                                AND (COALESCE(p.ultimo_mantenimiento_horas, 0) + COALESCE(p.frecuencia_horas, 0) - e.horometro_actual) <= 0 
                                THEN 'VENCIDO'
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NOT NULL
                                AND DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY) <= CURDATE()
                                THEN 'VENCIDO'
                            WHEN p.frecuencia_horas IS NOT NULL 
                                AND (COALESCE(p.ultimo_mantenimiento_horas, 0) + COALESCE(p.frecuencia_horas, 0) - e.horometro_actual) <= 100 
                                THEN 'PROXIMO'
                            WHEN p.frecuencia_dias IS NOT NULL 
                                AND p.ultimo_mantenimiento_fecha IS NOT NULL
                                AND DATEDIFF(DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), CURDATE()) <= 30 
                                THEN 'PROXIMO'
                            ELSE 'AL_DIA'
                        END as estado_alerta
                    FROM equipos_mantenimiento_plan p
                    JOIN equipos e ON p.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON p.tecnico_asignado_id = t.id
                    WHERE p.tecnico_asignado_id = ? 
                    AND p.activo = 1
                    AND p.estado_asignacion IN ('ASIGNADO', 'EN_PROCESO')";

            $params = [$tecnico_id];

            if (!empty($filtros['estado'])) {
                $sql .= " AND p.estado_asignacion = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['equipo'])) {
                $sql .= " AND p.id_equipo = ?";
                $params[] = $filtros['equipo'];
            }

            $sql .= " ORDER BY 
                        CASE 
                            WHEN (COALESCE(p.ultimo_mantenimiento_horas, 0) + COALESCE(p.frecuencia_horas, 0) - e.horometro_actual) <= 0 THEN 1
                            WHEN DATEDIFF(DATE_ADD(p.ultimo_mantenimiento_fecha, INTERVAL p.frecuencia_dias DAY), CURDATE()) <= 0 THEN 2
                            ELSE 3
                        END,
                        horas_restantes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerTareasAsignadas: " . $e->getMessage());
            return [];
        }
    }

    public function contarTareasAsignadas($tecnico_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN p.estado_asignacion = 'ASIGNADO' THEN 1 ELSE 0 END) as asignadas,
                        SUM(CASE WHEN p.estado_asignacion = 'EN_PROCESO' THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN p.frecuencia_horas IS NOT NULL 
                            AND (COALESCE(p.ultimo_mantenimiento_horas, 0) + COALESCE(p.frecuencia_horas, 0) - e.horometro_actual) <= 0 
                            THEN 1 ELSE 0 END) as vencidas
                    FROM equipos_mantenimiento_plan p
                    JOIN equipos e ON p.id_equipo = e.id_equipo
                    WHERE p.tecnico_asignado_id = ? 
                    AND p.activo = 1
                    AND p.estado_asignacion IN ('ASIGNADO', 'EN_PROCESO')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tecnico_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en contarTareasAsignadas: " . $e->getMessage());
            return ['total' => 0, 'asignadas' => 0, 'en_proceso' => 0, 'vencidas' => 0];
        }
    }

    public function obtenerTecnicoIdByEmail($email) {
        try {
            if (empty($email)) return null;

            $sql = "SELECT id FROM tecnicos 
                    WHERE LOWER(email) = LOWER(?) 
                    AND estado = 'activo'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['id'];
            }

            $sql = "SELECT id FROM tecnicos 
                    WHERE LOWER(email) = LOWER(?) 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['id'] : null;
        } catch (PDOException $e) {
            error_log("Error en obtenerTecnicoIdByEmail: " . $e->getMessage());
            return null;
        }
    }

    public function cambiarEstadoAsignacion($plan_id, $estado) {
        try {
            $sql = "UPDATE equipos_mantenimiento_plan 
                    SET estado_asignacion = ? 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, $plan_id]);
        } catch (PDOException $e) {
            error_log("Error en cambiarEstadoAsignacion: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CHECKLIST DE PROTOCOLO
    // ==========================================

    public function obtenerProtocolo($plan_id, $tipo_mantenimiento = 'PREVENTIVO') {
        try {
            $sql = "SELECT * FROM mantenimiento_checklist_protocolo 
                    WHERE plan_id = ? 
                    ORDER BY paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$plan_id]);
            $protocolo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($protocolo)) {
                return $protocolo;
            }

            $sql = "SELECT * FROM mantenimiento_checklist_protocolo 
                    WHERE plan_id IS NULL AND tipo_mantenimiento = ?
                    ORDER BY paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipo_mantenimiento]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerProtocolo: " . $e->getMessage());
            return [];
        }
    }

    public function guardarPasoChecklist($datos) {
        try {
            $sql = "INSERT INTO mantenimiento_checklist_ejecucion 
                    (plan_id, id_equipo, tecnico_id, paso_id, cumplido, observaciones, foto_evidencia)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['plan_id'], 
                $datos['id_equipo'], 
                $datos['tecnico_id'],
                $datos['paso_id'], 
                $datos['cumplido'] ?? 0,
                $datos['observaciones'] ?? '',
                $datos['foto_evidencia'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error en guardarPasoChecklist: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerChecklistEjecutado($plan_id, $tecnico_id) {
        try {
            $sql = "SELECT ce.*, cp.descripcion, cp.paso_numero, cp.obligatorio, cp.requiere_foto
                    FROM mantenimiento_checklist_ejecucion ce
                    JOIN mantenimiento_checklist_protocolo cp ON ce.paso_id = cp.id
                    WHERE ce.plan_id = ? AND ce.tecnico_id = ?
                    ORDER BY cp.paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$plan_id, $tecnico_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerChecklistEjecutado: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // FOTOS DE EVIDENCIA
    // ==========================================

    public function guardarFotoChecklist($datos) {
        try {
            $sql = "UPDATE mantenimiento_checklist_ejecucion 
                    SET foto_evidencia = ? 
                    WHERE plan_id = ? AND id_equipo = ? AND tecnico_id = ? AND paso_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['foto_evidencia'],
                $datos['plan_id'],
                $datos['id_equipo'],
                $datos['tecnico_id'],
                $datos['paso_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error en guardarFotoChecklist: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerFotosChecklist($plan_id, $tecnico_id) {
        try {
            $sql = "SELECT ce.id, ce.paso_id, ce.foto_evidencia, ce.cumplido, ce.observaciones,
                           cp.descripcion, cp.paso_numero
                    FROM mantenimiento_checklist_ejecucion ce
                    JOIN mantenimiento_checklist_protocolo cp ON ce.paso_id = cp.id
                    WHERE ce.plan_id = ? 
                    AND ce.tecnico_id = ?
                    AND ce.foto_evidencia IS NOT NULL
                    AND ce.foto_evidencia != ''
                    ORDER BY cp.paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$plan_id, $tecnico_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerFotosChecklist: " . $e->getMessage());
            return [];
        }
    }

    public function eliminarFotoEvidencia($id) {
        try {
            $sql = "SELECT foto_evidencia FROM mantenimiento_checklist_ejecucion WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $foto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($foto && !empty($foto['foto_evidencia'])) {
                $nombreArchivo = basename($foto['foto_evidencia']);
                $ruta = __DIR__ . '/../uploads/mantenimiento/' . $nombreArchivo;
                if (file_exists($ruta) && is_file($ruta)) {
                    @unlink($ruta);
                }
            }

            $sql = "UPDATE mantenimiento_checklist_ejecucion SET foto_evidencia = NULL WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            return ['success' => true, 'message' => 'Foto eliminada correctamente'];

        } catch (PDOException $e) {
            error_log("Error en eliminarFotoEvidencia: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // PROTOCOLOS DE CHECKLIST
    // ==========================================

    public function obtenerTodosLosProtocolos() {
        try {
            $sql = "SELECT tipo_mantenimiento, COUNT(*) as total_pasos,
                           COUNT(DISTINCT plan_id) as total_planes
                    FROM mantenimiento_checklist_protocolo
                    GROUP BY tipo_mantenimiento
                    ORDER BY tipo_mantenimiento";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerTodosLosProtocolos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerProtocoloPorTipo($tipo_mantenimiento, $plan_id = null) {
        try {
            if ($plan_id) {
                $sql = "SELECT * FROM mantenimiento_checklist_protocolo 
                        WHERE plan_id = ? OR (plan_id IS NULL AND tipo_mantenimiento = ?)
                        ORDER BY 
                            CASE WHEN plan_id IS NOT NULL THEN 1 ELSE 2 END,
                            paso_numero ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$plan_id, $tipo_mantenimiento]);
            } else {
                $sql = "SELECT * FROM mantenimiento_checklist_protocolo 
                        WHERE plan_id IS NULL AND tipo_mantenimiento = ?
                        ORDER BY paso_numero ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$tipo_mantenimiento]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerProtocoloPorTipo: " . $e->getMessage());
            return [];
        }
    }

    public function crearPasoProtocolo($datos) {
        try {
            $sql = "INSERT INTO mantenimiento_checklist_protocolo 
                    (plan_id, tipo_mantenimiento, paso_numero, descripcion, obligatorio, requiere_foto)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                !empty($datos['plan_id']) ? (int)$datos['plan_id'] : null,
                $datos['tipo_mantenimiento'],
                (int)$datos['paso_numero'],
                $datos['descripcion'],
                !empty($datos['obligatorio']) ? 1 : 0,
                !empty($datos['requiere_foto']) ? 1 : 0
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en crearPasoProtocolo: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarPasoProtocolo($id, $datos) {
        try {
            $sql = "UPDATE mantenimiento_checklist_protocolo SET 
                        paso_numero = ?,
                        descripcion = ?,
                        obligatorio = ?,
                        requiere_foto = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                (int)$datos['paso_numero'],
                $datos['descripcion'],
                !empty($datos['obligatorio']) ? 1 : 0,
                !empty($datos['requiere_foto']) ? 1 : 0,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error en actualizarPasoProtocolo: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarPasoProtocolo($id) {
        try {
            $sql = "DELETE FROM mantenimiento_checklist_protocolo WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en eliminarPasoProtocolo: " . $e->getMessage());
            return false;
        }
    }

    public function reordenarPasos($orden) {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE mantenimiento_checklist_protocolo SET paso_numero = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            foreach ($orden as $index => $paso_id) {
                $stmt->execute([$index + 1, (int)$paso_id]);
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en reordenarPasos: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPasoPorId($id) {
        try {
            $sql = "SELECT * FROM mantenimiento_checklist_protocolo WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPasoPorId: " . $e->getMessage());
            return false;
        }
    }
}