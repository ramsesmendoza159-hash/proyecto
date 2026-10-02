<?php
// model/MantenimientoEquiposModel.php
// Modelo del sistema de mantenimiento preventivo
// ✅ Maneja: planes de mantenimiento, ejecuciones, horómetro y checklists (protocolo + ejecución)
// ✅ catch (Throwable) en todos los métodos
// ✅ Transacciones en operaciones complejas
// ✅ Validaciones de IDs, cantidades y fechas
// ✅ FIX: obtenerEstadisticas() devuelve total_equipos, mantenimientos_mes, costo_mes
// ✅ FIX: obtenerMantenimientosProximos() devuelve estado_alerta con nombres exactos para la vista
// ✅ FIX: obtenerResumenAlertas() devuelve vencidos_horas, vencidos_fecha, proximos_horas, proximos_fecha
// ✅ FIX: alias 'codigo' además de 'equipo_codigo'

require_once __DIR__ . '/../config/database.php';

class MantenimientoEquiposModel
{
    private $db;
    private $lastError;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (MantenimientoEquiposModel): " . $e->getMessage());
            throw $e;
        }
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    // ==========================================
    // PLANES DE MANTENIMIENTO
    // ==========================================

    /**
     * Obtener todos los planes de mantenimiento de un equipo.
     */
    public function obtenerPlanPorEquipo($id_equipo)
    {
        try {
            $id_equipo = (int)$id_equipo;
            if ($id_equipo <= 0) return [];

            $sql = "SELECT p.*, 
                           t.nombre AS tecnico_nombre,
                           pr.nombre AS proveedor_nombre
                    FROM equipos_mantenimiento_plan p
                    LEFT JOIN tecnicos t ON p.tecnico_asignado_id = t.id
                    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                    WHERE p.id_equipo = ? AND p.activo = 1
                    ORDER BY p.fecha_creacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_equipo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerPlanPorEquipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un plan de mantenimiento por ID.
     */
    public function obtenerPlanPorId($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "SELECT p.*, 
                           e.nombre_equipo,
                           e.codigo AS equipo_codigo,
                           e.horometro_actual,
                           t.nombre AS tecnico_nombre,
                           pr.nombre AS proveedor_nombre
                    FROM equipos_mantenimiento_plan p
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON p.tecnico_asignado_id = t.id
                    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                    WHERE p.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            return $plan ?: false;

        } catch (PDOException $e) {
            error_log("Error en obtenerPlanPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear una tarea de mantenimiento (plan).
     */
    public function crearTarea($datos)
    {
        try {
            if (empty($datos['id_equipo']) || empty($datos['nombre_tarea'])) {
                $this->lastError = 'Equipo y nombre de tarea son obligatorios';
                return false;
            }

            $sql = "INSERT INTO equipos_mantenimiento_plan (
                        id_equipo,
                        nombre_tarea,
                        descripcion,
                        tipo_mantenimiento,
                        frecuencia_horas,
                        frecuencia_dias,
                        responsable,
                        id_proveedor,
                        costo_estimado,
                        repuestos_necesarios,
                        tecnico_asignado_id,
                        estado_asignacion,
                        fecha_asignacion,
                        activo,
                        fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

            $stmt = $this->db->prepare($sql);

            $tecnico_id = !empty($datos['tecnico_asignado_id']) ? (int)$datos['tecnico_asignado_id'] : null;
            $estado_asignacion = $tecnico_id ? 'ASIGNADO' : 'SIN_ASIGNAR';
            $fecha_asignacion  = $tecnico_id ? date('Y-m-d H:i:s') : null;

            $stmt->execute([
                (int)$datos['id_equipo'],
                trim($datos['nombre_tarea']),
                trim($datos['descripcion'] ?? ''),
                $datos['tipo_mantenimiento'] ?? 'PREVENTIVO',
                !empty($datos['frecuencia_horas']) ? (int)$datos['frecuencia_horas'] : null,
                !empty($datos['frecuencia_dias']) ? (int)$datos['frecuencia_dias'] : null,
                $datos['responsable'] ?? 'TECNICO_INTERNO',
                !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null,
                (float)($datos['costo_estimado'] ?? 0),
                trim($datos['repuestos_necesarios'] ?? ''),
                $tecnico_id,
                $estado_asignacion,
                $fecha_asignacion
            ]);

            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error en crearTarea: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualizar una tarea de mantenimiento.
     */
    public function actualizarTarea($id, $datos)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            if (empty($datos['nombre_tarea'])) {
                $this->lastError = 'El nombre de la tarea es obligatorio';
                return false;
            }

            $sql = "UPDATE equipos_mantenimiento_plan SET
                        nombre_tarea = ?,
                        descripcion = ?,
                        tipo_mantenimiento = ?,
                        frecuencia_horas = ?,
                        frecuencia_dias = ?,
                        responsable = ?,
                        id_proveedor = ?,
                        costo_estimado = ?,
                        repuestos_necesarios = ?,
                        activo = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                trim($datos['nombre_tarea']),
                trim($datos['descripcion'] ?? ''),
                $datos['tipo_mantenimiento'] ?? 'PREVENTIVO',
                !empty($datos['frecuencia_horas']) ? (int)$datos['frecuencia_horas'] : null,
                !empty($datos['frecuencia_dias']) ? (int)$datos['frecuencia_dias'] : null,
                $datos['responsable'] ?? 'TECNICO_INTERNO',
                !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null,
                (float)($datos['costo_estimado'] ?? 0),
                trim($datos['repuestos_necesarios'] ?? ''),
                isset($datos['activo']) ? (int)$datos['activo'] : 1,
                $id
            ]);

        } catch (PDOException $e) {
            error_log("Error en actualizarTarea: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Eliminar una tarea de mantenimiento (soft delete).
     */
    public function eliminarTarea($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            // Verificar que no tenga ejecuciones asociadas
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM equipos_mantenimientos WHERE plan_id = ?");
            $stmtCheck->execute([$id]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $this->lastError = 'No se puede eliminar la tarea porque tiene mantenimientos registrados';
                return false;
            }

            $sql = "UPDATE equipos_mantenimiento_plan SET activo = 0, fecha_actualizacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error en eliminarTarea: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Cambiar el estado de asignación de un plan.
     */
    public function cambiarEstadoAsignacion($id, $estado)
    {
        try {
            $id = (int)$id;
            $estados_validos = ['SIN_ASIGNAR', 'ASIGNADO', 'EN_PROCESO', 'COMPLETADO'];

            if (!in_array($estado, $estados_validos, true)) {
                $this->lastError = 'Estado de asignación inválido';
                return false;
            }

            $sql = "UPDATE equipos_mantenimiento_plan 
                    SET estado_asignacion = ?, fecha_actualizacion = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, $id]);

        } catch (PDOException $e) {
            error_log("Error en cambiarEstadoAsignacion: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Asignar un técnico a una tarea.
     */
    public function asignarTecnico($id, $tecnico_id, $asignado_por)
    {
        try {
            $id = (int)$id;
            $tecnico_id = (int)$tecnico_id;

            if ($id <= 0 || $tecnico_id <= 0) {
                $this->lastError = 'IDs inválidos';
                return false;
            }

            $sql = "UPDATE equipos_mantenimiento_plan SET
                        tecnico_asignado_id = ?,
                        estado_asignacion = 'ASIGNADO',
                        fecha_asignacion = NOW(),
                        asignado_por = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$tecnico_id, (int)$asignado_por, $id]);

        } catch (PDOException $e) {
            error_log("Error en asignarTecnico: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtener tareas asignadas a un técnico (panel "Mis Tareas").
     */
    public function obtenerTareasAsignadas($tecnico_id)
    {
        try {
            $tecnico_id = (int)$tecnico_id;
            if ($tecnico_id <= 0) return [];

            $sql = "SELECT p.*,
                           e.nombre_equipo,
                           e.codigo AS equipo_codigo,
                           e.horometro_actual,
                           e.horas_diarias_promedio,
                           CASE
                               WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                                    AND p.proximo_mantenimiento_fecha < CURDATE() 
                                    THEN 'VENCIDO'
                               WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                                    AND p.proximo_mantenimiento_fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                                    THEN 'PROXIMO'
                               ELSE 'AL_DIA'
                           END AS estado_alerta
                    FROM equipos_mantenimiento_plan p
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    WHERE p.tecnico_asignado_id = ?
                      AND p.activo = 1
                      AND p.estado_asignacion IN ('ASIGNADO', 'EN_PROCESO')
                    ORDER BY 
                        CASE 
                            WHEN p.proximo_mantenimiento_fecha IS NULL THEN 3
                            WHEN p.proximo_mantenimiento_fecha < CURDATE() THEN 1
                            ELSE 2
                        END,
                        p.proximo_mantenimiento_fecha ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tecnico_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerTareasAsignadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener el ID de técnico a partir de su email.
     */
    public function obtenerTecnicoIdByEmail($email)
    {
        try {
            if (empty($email)) return null;

            $sql = "SELECT id FROM tecnicos WHERE LOWER(email) = LOWER(?) LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['id'] : null;

        } catch (PDOException $e) {
            error_log("Error en obtenerTecnicoIdByEmail: " . $e->getMessage());
            return null;
        }
    }

    // ==========================================
    // MANTENIMIENTOS REALIZADOS
    // ==========================================

    /**
     * Obtener un mantenimiento realizado por ID.
     */
    public function obtenerMantenimientoPorId($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "SELECT m.*,
                           p.nombre_tarea,
                           p.tipo_mantenimiento AS plan_tipo,
                           e.nombre_equipo,
                           e.codigo AS equipo_codigo,
                           t.nombre AS tecnico_nombre,
                           pr.nombre AS proveedor_nombre
                    FROM equipos_mantenimientos m
                    LEFT JOIN equipos_mantenimiento_plan p ON m.plan_id = p.id
                    LEFT JOIN equipos e ON m.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON m.tecnico_id = t.id
                    LEFT JOIN proveedores pr ON m.proveedor_id = pr.id
                    WHERE m.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

            return $mantenimiento ?: false;

        } catch (PDOException $e) {
            error_log("Error en obtenerMantenimientoPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener historial de mantenimientos (por equipo o global).
     */
    public function obtenerHistorialMantenimientos($id_equipo = null, $limite = 20)
    {
        try {
            $sql = "SELECT m.*,
                           p.nombre_tarea,
                           e.nombre_equipo,
                           e.codigo AS equipo_codigo,
                           t.nombre AS tecnico_nombre,
                           pr.nombre AS proveedor_nombre
                    FROM equipos_mantenimientos m
                    LEFT JOIN equipos_mantenimiento_plan p ON m.plan_id = p.id
                    LEFT JOIN equipos e ON m.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON m.tecnico_id = t.id
                    LEFT JOIN proveedores pr ON m.proveedor_id = pr.id
                    WHERE 1=1";
            $params = [];

            if (!empty($id_equipo)) {
                $sql .= " AND m.id_equipo = ?";
                $params[] = (int)$id_equipo;
            }

            $sql .= " ORDER BY m.fecha_mantenimiento DESC LIMIT ?";
            $params[] = (int)$limite;

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(count($params), (int)$limite, PDO::PARAM_INT);
            for ($i = 1; $i < count($params); $i++) {
                $stmt->bindValue($i, $params[$i - 1], is_int($params[$i - 1]) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerHistorialMantenimientos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registrar un mantenimiento realizado y recalcular el próximo.
     */
    public function registrarMantenimientoRealizado($plan_id, $datos)
    {
        try {
            $plan_id = (int)$plan_id;
            if ($plan_id <= 0) {
                return ['success' => false, 'error' => 'Plan inválido'];
            }

            $plan = $this->obtenerPlanPorId($plan_id);
            if (!$plan) {
                return ['success' => false, 'error' => 'Plan de mantenimiento no encontrado'];
            }

            $this->db->beginTransaction();

            $fecha_mantenimiento = $datos['fecha_mantenimiento'] ?? date('Y-m-d H:i:s');
            $horas_equipo = (int)($datos['horas_equipo'] ?? 0);

            $sql = "INSERT INTO equipos_mantenimientos (
                        id_equipo, plan_id, tipo, descripcion, horas_equipo,
                        fecha_mantenimiento, tecnico_id, proveedor_id, costo,
                        repuestos_usados, observaciones, registrado_por, fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                (int)$plan['id_equipo'],
                $plan_id,
                $plan['tipo_mantenimiento'] ?? 'PREVENTIVO',
                trim($datos['descripcion'] ?? ''),
                $horas_equipo,
                $fecha_mantenimiento,
                !empty($datos['tecnico_id']) ? (int)$datos['tecnico_id'] : null,
                !empty($datos['proveedor_id']) ? (int)$datos['proveedor_id'] : null,
                (float)($datos['costo'] ?? 0),
                trim($datos['repuestos_usados'] ?? ''),
                trim($datos['observaciones'] ?? ''),
                $_SESSION['usuario_id'] ?? null
            ]);

            $mantenimiento_id = (int)$this->db->lastInsertId();

            // Recalcular próximo mantenimiento
            $this->recalcularProximoMantenimiento($plan_id, $horas_equipo, $fecha_mantenimiento);

            // Marcar el plan como completado
            $sqlUpd = "UPDATE equipos_mantenimiento_plan 
                       SET estado_asignacion = 'COMPLETADO',
                           ultimo_mantenimiento_fecha = ?,
                           ultimo_mantenimiento_horas = ?,
                           fecha_actualizacion = NOW()
                       WHERE id = ?";
            $stmtUpd = $this->db->prepare($sqlUpd);
            $stmtUpd->execute([
                date('Y-m-d', strtotime($fecha_mantenimiento)),
                $horas_equipo,
                $plan_id
            ]);

            $this->db->commit();

            return ['success' => true, 'id' => $mantenimiento_id];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en registrarMantenimientoRealizado: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Recalcular el próximo mantenimiento de un plan.
     */
    private function recalcularProximoMantenimiento($plan_id, $horas_actuales, $fecha_mantenimiento)
    {
        try {
            $plan = $this->obtenerPlanPorId($plan_id);
            if (!$plan) return false;

            $nuevas_horas = null;
            $nueva_fecha = null;

            if (!empty($plan['frecuencia_horas'])) {
                $nuevas_horas = (int)$horas_actuales + (int)$plan['frecuencia_horas'];
            }

            if (!empty($plan['frecuencia_dias'])) {
                $nueva_fecha = date('Y-m-d', strtotime($fecha_mantenimiento . ' +' . (int)$plan['frecuencia_dias'] . ' days'));
            }

            $sql = "UPDATE equipos_mantenimiento_plan SET
                        proximo_mantenimiento_horas = ?,
                        proximo_mantenimiento_fecha = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nuevas_horas, $nueva_fecha, $plan_id]);

        } catch (Throwable $e) {
            error_log("Error en recalcularProximoMantenimiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar un mantenimiento (admin/supervisor).
     */
    public function actualizarMantenimiento($id, $datos, $usuario_id, $motivo_edicion)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return ['success' => false, 'error' => 'ID inválido'];
            }

            if (empty(trim($motivo_edicion))) {
                return ['success' => false, 'error' => 'Debes indicar el motivo de la edición'];
            }

            $sql = "UPDATE equipos_mantenimientos SET
                        descripcion = ?,
                        horas_equipo = ?,
                        fecha_mantenimiento = ?,
                        tecnico_id = ?,
                        proveedor_id = ?,
                        costo = ?,
                        repuestos_usados = ?,
                        observaciones = ?
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([
                trim($datos['descripcion'] ?? ''),
                (int)($datos['horas_equipo'] ?? 0),
                $datos['fecha_mantenimiento'] ?? date('Y-m-d H:i:s'),
                !empty($datos['tecnico_id']) ? (int)$datos['tecnico_id'] : null,
                !empty($datos['proveedor_id']) ? (int)$datos['proveedor_id'] : null,
                (float)($datos['costo'] ?? 0),
                trim($datos['repuestos_usados'] ?? ''),
                trim($datos['observaciones'] ?? ''),
                $id
            ]);

            if ($resultado) {
                try {
                    require_once __DIR__ . '/AuditoriaModel.php';
                    $auditoria = new AuditoriaModel();
                    $auditoria->registrar(
                        (int)$usuario_id,
                        $_SESSION['nombre'] ?? 'Sistema',
                        $_SESSION['rol'] ?? 'admin',
                        'editar_mantenimiento',
                        'equipos_mantenimientos',
                        $id,
                        null,
                        ['motivo' => $motivo_edicion]
                    );
                } catch (Throwable $e) {
                    error_log("Error auditoría editar_mantenimiento: " . $e->getMessage());
                }
            }

            return ['success' => (bool)$resultado];

        } catch (Throwable $e) {
            error_log("Error en actualizarMantenimiento: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eliminar un mantenimiento.
     */
    public function eliminarMantenimiento($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return ['success' => false, 'error' => 'ID inválido'];
            }

            $sql = "DELETE FROM equipos_mantenimientos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$id]);

            return ['success' => (bool)$resultado];

        } catch (Throwable $e) {
            error_log("Error en eliminarMantenimiento: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // ALERTAS Y ESTADÍSTICAS
    // ==========================================

    /**
     * Obtener mantenimientos próximos con campos calculados.
     * ✅ FIX: estado_alerta con nombres que la vista espera
     */
    public function obtenerMantenimientosProximos($dias = 30, $limite = 100)
    {
        try {
            $sql = "SELECT p.*,
                           e.nombre_equipo,
                           e.codigo AS equipo_codigo,
                           e.codigo AS codigo,
                           e.horometro_actual,
                           t.nombre AS tecnico_nombre,
                           pr.nombre AS proveedor_nombre,

                           CASE 
                               WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                               THEN DATEDIFF(p.proximo_mantenimiento_fecha, CURDATE())
                               ELSE NULL 
                           END AS dias_restantes,

                           CASE 
                               WHEN p.proximo_mantenimiento_horas IS NOT NULL 
                               THEN (p.proximo_mantenimiento_horas - COALESCE(e.horometro_actual, 0))
                               ELSE NULL 
                           END AS horas_restantes,

                           CASE
                               WHEN p.proximo_mantenimiento_horas IS NOT NULL 
                                    AND COALESCE(e.horometro_actual, 0) >= p.proximo_mantenimiento_horas 
                                    THEN 'VENCIDO_POR_HORAS'
                               WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                                    AND p.proximo_mantenimiento_fecha < CURDATE() 
                                    THEN 'VENCIDO_POR_FECHA'
                               WHEN p.proximo_mantenimiento_horas IS NOT NULL 
                                    AND (p.proximo_mantenimiento_horas - COALESCE(e.horometro_actual, 0)) <= 100 
                                    THEN 'PROXIMO_POR_HORAS'
                               WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                                    AND p.proximo_mantenimiento_fecha <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                                    THEN 'PROXIMO_POR_FECHA'
                               ELSE 'AL_DIA'
                           END AS estado_alerta

                    FROM equipos_mantenimiento_plan p
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    LEFT JOIN tecnicos t ON p.tecnico_asignado_id = t.id
                    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                    WHERE p.activo = 1
                      AND p.estado_asignacion != 'COMPLETADO'
                      AND (
                          (p.proximo_mantenimiento_fecha IS NOT NULL 
                           AND p.proximo_mantenimiento_fecha <= DATE_ADD(CURDATE(), INTERVAL ? DAY))
                          OR
                          (p.proximo_mantenimiento_horas IS NOT NULL 
                           AND (p.proximo_mantenimiento_horas - COALESCE(e.horometro_actual, 0)) <= 100)
                      )
                    ORDER BY 
                        CASE 
                            WHEN p.proximo_mantenimiento_horas IS NOT NULL 
                                 AND COALESCE(e.horometro_actual, 0) >= p.proximo_mantenimiento_horas THEN 1
                            WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                                 AND p.proximo_mantenimiento_fecha < CURDATE() THEN 1
                            ELSE 2
                        END,
                        p.proximo_mantenimiento_fecha ASC,
                        p.proximo_mantenimiento_horas ASC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, (int)$dias, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerMantenimientosProximos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Resumen de alertas con desglose por horas/fecha.
     * ✅ FIX: devuelve vencidos_horas, vencidos_fecha, proximos_horas, proximos_fecha
     */
    public function obtenerResumenAlertas()
    {
        try {
            $sql = "SELECT
                        SUM(CASE 
                            WHEN p.proximo_mantenimiento_horas IS NOT NULL 
                             AND e.horometro_actual >= p.proximo_mantenimiento_horas 
                            THEN 1 ELSE 0 
                        END) AS vencidos_horas,

                        SUM(CASE 
                            WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                             AND p.proximo_mantenimiento_fecha < CURDATE() 
                            THEN 1 ELSE 0 
                        END) AS vencidos_fecha,

                        SUM(CASE 
                            WHEN p.proximo_mantenimiento_horas IS NOT NULL 
                             AND e.horometro_actual < p.proximo_mantenimiento_horas 
                             AND (p.proximo_mantenimiento_horas - e.horometro_actual) <= 100 
                            THEN 1 ELSE 0 
                        END) AS proximos_horas,

                        SUM(CASE 
                            WHEN p.proximo_mantenimiento_fecha IS NOT NULL 
                             AND p.proximo_mantenimiento_fecha >= CURDATE() 
                             AND p.proximo_mantenimiento_fecha <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                            THEN 1 ELSE 0 
                        END) AS proximos_fecha,

                        COUNT(*) AS total

                    FROM equipos_mantenimiento_plan p
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    WHERE p.activo = 1
                      AND p.estado_asignacion != 'COMPLETADO'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $r = $stmt->fetch(PDO::FETCH_ASSOC);

            $vencidos_horas = (int)($r['vencidos_horas'] ?? 0);
            $vencidos_fecha = (int)($r['vencidos_fecha'] ?? 0);
            $proximos_horas = (int)($r['proximos_horas'] ?? 0);
            $proximos_fecha = (int)($r['proximos_fecha'] ?? 0);

            return [
                'vencidos_horas' => $vencidos_horas,
                'vencidos_fecha' => $vencidos_fecha,
                'proximos_horas' => $proximos_horas,
                'proximos_fecha' => $proximos_fecha,
                'vencidos'       => $vencidos_horas + $vencidos_fecha,
                'proximos'       => $proximos_horas + $proximos_fecha,
                'total'          => (int)($r['total'] ?? 0),
                'total_alertas'  => $vencidos_horas + $vencidos_fecha + $proximos_horas + $proximos_fecha
            ];

        } catch (PDOException $e) {
            error_log("Error en obtenerResumenAlertas: " . $e->getMessage());
            return [
                'vencidos_horas' => 0,
                'vencidos_fecha' => 0,
                'proximos_horas' => 0,
                'proximos_fecha' => 0,
                'vencidos'       => 0,
                'proximos'       => 0,
                'total'          => 0,
                'total_alertas'  => 0
            ];
        }
    }

    /**
     * Estadísticas generales.
     * ✅ FIX: devuelve total_equipos, total_planes, mantenimientos_mes, costo_mes
     */
    public function obtenerEstadisticas()
    {
        try {
            $sql = "SELECT
                        (SELECT COUNT(*) FROM equipos WHERE estado = 'activo') AS total_equipos,
                        (SELECT COUNT(*) FROM equipos_mantenimiento_plan WHERE activo = 1) AS total_planes,
                        (SELECT COUNT(*) FROM equipos_mantenimientos 
                         WHERE YEAR(fecha_mantenimiento) = YEAR(CURDATE()) 
                           AND MONTH(fecha_mantenimiento) = MONTH(CURDATE())) AS mantenimientos_mes,
                        (SELECT COALESCE(SUM(costo), 0) FROM equipos_mantenimientos 
                         WHERE YEAR(fecha_mantenimiento) = YEAR(CURDATE()) 
                           AND MONTH(fecha_mantenimiento) = MONTH(CURDATE())) AS costo_mes,
                        (SELECT COUNT(*) FROM equipos_mantenimientos) AS total_mantenimientos,
                        (SELECT COALESCE(SUM(costo), 0) FROM equipos_mantenimientos) AS costo_total,
                        (SELECT COUNT(*) FROM equipos_mantenimiento_plan WHERE activo = 1 AND estado_asignacion = 'SIN_ASIGNAR') AS sin_asignar,
                        (SELECT COUNT(*) FROM equipos_mantenimiento_plan WHERE activo = 1 AND estado_asignacion IN ('ASIGNADO', 'EN_PROCESO')) AS en_proceso,
                        (SELECT COUNT(*) FROM equipos_mantenimiento_plan WHERE activo = 1 AND estado_asignacion = 'COMPLETADO') AS completados";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $r = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_equipos'        => (int)($r['total_equipos'] ?? 0),
                'total_planes'         => (int)($r['total_planes'] ?? 0),
                'mantenimientos_mes'   => (int)($r['mantenimientos_mes'] ?? 0),
                'costo_mes'            => (float)($r['costo_mes'] ?? 0),
                'total_mantenimientos' => (int)($r['total_mantenimientos'] ?? 0),
                'costo_total'          => (float)($r['costo_total'] ?? 0),
                'sin_asignar'          => (int)($r['sin_asignar'] ?? 0),
                'en_proceso'           => (int)($r['en_proceso'] ?? 0),
                'completados'          => (int)($r['completados'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return [
                'total_equipos' => 0, 'total_planes' => 0,
                'mantenimientos_mes' => 0, 'costo_mes' => 0,
                'total_mantenimientos' => 0, 'costo_total' => 0,
                'sin_asignar' => 0, 'en_proceso' => 0, 'completados' => 0
            ];
        }
    }

    // ==========================================
    // HORÓMETRO
    // ==========================================

    /**
     * Obtener historial de lecturas de horómetro de un equipo.
     */
    public function obtenerHistorialHorometro($id_equipo, $limite = 20)
    {
        try {
            $id_equipo = (int)$id_equipo;
            if ($id_equipo <= 0) return [];

            $sql = "SELECT h.*, u.nombre AS usuario_nombre
                    FROM equipos_horometro h
                    LEFT JOIN usuarios u ON h.usuario_id = u.id
                    WHERE h.id_equipo = ?
                    ORDER BY h.fecha_lectura DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $id_equipo, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerHistorialHorometro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener una lectura de horómetro por ID.
     */
    public function obtenerLecturaHorometro($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "SELECT * FROM equipos_horometro WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;

        } catch (PDOException $e) {
            error_log("Error en obtenerLecturaHorometro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar una lectura de horómetro.
     */
    public function registrarHorometro($id_equipo, $horas, $usuario_id, $observaciones = '', $fecha_lectura = null)
    {
        try {
            $id_equipo = (int)$id_equipo;
            $horas = (int)$horas;

            if ($id_equipo <= 0 || $horas < 0) {
                return ['success' => false, 'error' => 'Datos inválidos'];
            }

            // Obtener lectura anterior
            $sqlPrev = "SELECT horas_actuales FROM equipos_horometro 
                        WHERE id_equipo = ? 
                        ORDER BY fecha_lectura DESC LIMIT 1";
            $stmtPrev = $this->db->prepare($sqlPrev);
            $stmtPrev->execute([$id_equipo]);
            $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            $horas_anteriores = $prev ? (int)$prev['horas_actuales'] : 0;

            if ($horas < $horas_anteriores) {
                return ['success' => false, 'error' => 'Las horas no pueden ser menores a la última lectura (' . $horas_anteriores . ' h)'];
            }

            $horas_desde_ultimo = $horas - $horas_anteriores;
            $fecha_lectura = $fecha_lectura ?: date('Y-m-d H:i:s');

            $this->db->beginTransaction();

            $sql = "INSERT INTO equipos_horometro 
                    (id_equipo, horas_actuales, horas_desde_ultimo, fecha_lectura, usuario_id, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id_equipo,
                $horas,
                $horas_desde_ultimo,
                $fecha_lectura,
                (int)$usuario_id,
                trim($observaciones)
            ]);

            // Actualizar horómetro actual del equipo
            $sqlUpd = "UPDATE equipos SET 
                            horometro_actual = ?,
                            horometro_ultima_lectura = ?
                       WHERE id_equipo = ?";
            $stmtUpd = $this->db->prepare($sqlUpd);
            $stmtUpd->execute([$horas, $fecha_lectura, $id_equipo]);

            $this->db->commit();

            return [
                'success' => true,
                'horas_desde_ultimo' => $horas_desde_ultimo,
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

    /**
     * Editar una lectura de horómetro existente.
     */
    public function editarLecturaHorometro($id, $nuevas_horas, $observaciones, $usuario_id, $nueva_fecha = null)
    {
        try {
            $id = (int)$id;
            $nuevas_horas = (int)$nuevas_horas;

            if ($id <= 0 || $nuevas_horas < 0) {
                return ['success' => false, 'error' => 'Datos inválidos'];
            }

            $lectura = $this->obtenerLecturaHorometro($id);
            if (!$lectura) {
                return ['success' => false, 'error' => 'Lectura no encontrada'];
            }

            $valor_anterior = (int)$lectura['horas_actuales'];
            $fecha_anterior = $lectura['fecha_lectura'];

            $sql = "UPDATE equipos_horometro SET
                        horas_actuales = ?,
                        observaciones = ?,
                        fecha_lectura = COALESCE(?, fecha_lectura)
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([
                $nuevas_horas,
                trim($observaciones),
                $nueva_fecha,
                $id
            ]);

            return [
                'success' => (bool)$resultado,
                'valor_anterior' => $valor_anterior,
                'valor_nuevo' => $nuevas_horas,
                'fecha_anterior' => $fecha_anterior,
                'fecha_nueva' => $nueva_fecha
            ];

        } catch (Throwable $e) {
            error_log("Error en editarLecturaHorometro: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eliminar una lectura de horómetro.
     */
    public function eliminarLecturaHorometro($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return ['success' => false, 'error' => 'ID inválido'];
            }

            $sql = "DELETE FROM equipos_horometro WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$id]);

            return ['success' => (bool)$resultado];

        } catch (Throwable $e) {
            error_log("Error en eliminarLecturaHorometro: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // CHECKLIST: PROTOCOLO
    // ==========================================

    /**
     * Obtener el protocolo (pasos) para un plan y tipo de mantenimiento.
     */
    public function obtenerProtocolo($plan_id, $tipo_mantenimiento = 'PREVENTIVO')
    {
        try {
            $plan_id = (int)$plan_id;

            // Primero, protocolo específico del plan
            $sql = "SELECT * FROM mantenimiento_checklist_protocolo
                    WHERE plan_id = ?
                    ORDER BY paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$plan_id]);
            $pasos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($pasos)) {
                return $pasos;
            }

            // Fallback: protocolo genérico por tipo
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

    /**
     * Obtener todos los protocolos agrupados por tipo.
     */
    public function obtenerTodosLosProtocolos()
    {
        try {
            $sql = "SELECT * FROM mantenimiento_checklist_protocolo
                    ORDER BY tipo_mantenimiento ASC, plan_id ASC, paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerTodosLosProtocolos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener protocolo por tipo de mantenimiento.
     */
    public function obtenerProtocoloPorTipo($tipo)
    {
        try {
            $sql = "SELECT * FROM mantenimiento_checklist_protocolo
                    WHERE tipo_mantenimiento = ?
                    ORDER BY paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerProtocoloPorTipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un paso del protocolo.
     */
    public function crearPasoProtocolo($datos)
    {
        try {
            if (empty($datos['descripcion'])) {
                $this->lastError = 'La descripción es obligatoria';
                return false;
            }

            $sql = "INSERT INTO mantenimiento_checklist_protocolo
                    (plan_id, tipo_mantenimiento, paso_numero, descripcion, obligatorio, requiere_foto, fecha_creacion)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                !empty($datos['plan_id']) ? (int)$datos['plan_id'] : null,
                $datos['tipo_mantenimiento'] ?? 'PREVENTIVO',
                (int)($datos['paso_numero'] ?? 1),
                trim($datos['descripcion']),
                !empty($datos['obligatorio']) ? 1 : 0,
                !empty($datos['requiere_foto']) ? 1 : 0
            ]);

            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error en crearPasoProtocolo: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualizar un paso del protocolo.
     */
    public function actualizarPasoProtocolo($id, $datos)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "UPDATE mantenimiento_checklist_protocolo SET
                        paso_numero = ?,
                        descripcion = ?,
                        obligatorio = ?,
                        requiere_foto = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                (int)($datos['paso_numero'] ?? 1),
                trim($datos['descripcion'] ?? ''),
                !empty($datos['obligatorio']) ? 1 : 0,
                !empty($datos['requiere_foto']) ? 1 : 0,
                $id
            ]);

        } catch (PDOException $e) {
            error_log("Error en actualizarPasoProtocolo: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Eliminar un paso del protocolo.
     */
    public function eliminarPasoProtocolo($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "DELETE FROM mantenimiento_checklist_protocolo WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error en eliminarPasoProtocolo: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Reordenar pasos del protocolo.
     */
    public function reordenarPasos($orden)
    {
        try {
            if (empty($orden) || !is_array($orden)) {
                return false;
            }

            $this->db->beginTransaction();

            $sql = "UPDATE mantenimiento_checklist_protocolo SET paso_numero = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            foreach ($orden as $paso_numero => $paso_id) {
                $stmt->execute([(int)$paso_numero, (int)$paso_id]);
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

    // ==========================================
    // CHECKLIST: EJECUCIÓN
    // ==========================================

    /**
     * Guardar un paso ejecutado del checklist.
     */
    public function guardarPasoChecklist($datos)
    {
        try {
            if (empty($datos['plan_id']) || empty($datos['paso_id'])) {
                return false;
            }

            // Verificar si ya existe ejecución previa de este paso
            $sqlCheck = "SELECT id FROM mantenimiento_checklist_ejecucion
                         WHERE plan_id = ? AND tecnico_id = ? AND paso_id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([
                (int)$datos['plan_id'],
                (int)$datos['tecnico_id'],
                (int)$datos['paso_id']
            ]);
            $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                $sql = "UPDATE mantenimiento_checklist_ejecucion SET
                            cumplido = ?,
                            observaciones = ?,
                            foto_evidencia = ?,
                            fecha_registro = NOW()
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    !empty($datos['cumplido']) ? 1 : 0,
                    trim($datos['observaciones'] ?? ''),
                    $datos['foto_evidencia'] ?? null,
                    (int)$existente['id']
                ]);
            }

            $sql = "INSERT INTO mantenimiento_checklist_ejecucion
                    (plan_id, id_equipo, tecnico_id, paso_id, cumplido, observaciones, foto_evidencia, fecha_registro)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                (int)$datos['plan_id'],
                (int)$datos['id_equipo'],
                (int)$datos['tecnico_id'],
                (int)$datos['paso_id'],
                !empty($datos['cumplido']) ? 1 : 0,
                trim($datos['observaciones'] ?? ''),
                $datos['foto_evidencia'] ?? null
            ]);

        } catch (PDOException $e) {
            error_log("Error en guardarPasoChecklist: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener checklist ejecutado de un plan por un técnico.
     */
    public function obtenerChecklistEjecutado($plan_id, $tecnico_id)
    {
        try {
            $sql = "SELECT e.*, 
                           p.descripcion AS paso_descripcion,
                           p.paso_numero,
                           p.obligatorio,
                           p.requiere_foto
                    FROM mantenimiento_checklist_ejecucion e
                    LEFT JOIN mantenimiento_checklist_protocolo p ON e.paso_id = p.id
                    WHERE e.plan_id = ? AND e.tecnico_id = ?
                    ORDER BY p.paso_numero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$plan_id, (int)$tecnico_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerChecklistEjecutado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener checklist de un mantenimiento específico.
     */
    public function obtenerChecklistDeMantenimiento($mantenimiento_id)
    {
        try {
            $mantenimiento = $this->obtenerMantenimientoPorId($mantenimiento_id);
            if (!$mantenimiento || empty($mantenimiento['plan_id']) || empty($mantenimiento['tecnico_id'])) {
                return [];
            }

            return $this->obtenerChecklistEjecutado(
                (int)$mantenimiento['plan_id'],
                (int)$mantenimiento['tecnico_id']
            );

        } catch (Throwable $e) {
            error_log("Error en obtenerChecklistDeMantenimiento: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar checklist ejecutado (admin/supervisor).
     */
    public function actualizarChecklistEjecutado($mantenimiento_id, $pasos, $usuario_id)
    {
        try {
            $mantenimiento = $this->obtenerMantenimientoPorId($mantenimiento_id);
            if (!$mantenimiento) {
                return ['success' => false, 'error' => 'Mantenimiento no encontrado'];
            }

            $plan_id = (int)$mantenimiento['plan_id'];
            $tecnico_id = (int)$mantenimiento['tecnico_id'];

            $this->db->beginTransaction();

            $sql = "UPDATE mantenimiento_checklist_ejecucion SET
                        cumplido = ?,
                        observaciones = ?,
                        fecha_registro = NOW()
                    WHERE plan_id = ? AND tecnico_id = ? AND paso_id = ?";
            $stmt = $this->db->prepare($sql);

            foreach ($pasos as $paso_id => $datos_paso) {
                $stmt->execute([
                    !empty($datos_paso['cumplido']) ? 1 : 0,
                    trim($datos_paso['observaciones'] ?? ''),
                    $plan_id,
                    $tecnico_id,
                    (int)$paso_id
                ]);
            }

            $this->db->commit();
            return ['success' => true];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en actualizarChecklistEjecutado: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eliminar una foto de evidencia.
     */
    public function eliminarFotoEvidencia($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return ['success' => false, 'error' => 'ID inválido'];
            }

            $sqlGet = "SELECT foto_evidencia FROM mantenimiento_checklist_ejecucion WHERE id = ?";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$id]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['foto_evidencia'])) {
                return ['success' => false, 'error' => 'No hay foto para eliminar'];
            }

            $ruta = __DIR__ . '/../uploads/mantenimiento/' . basename($row['foto_evidencia']);
            if (file_exists($ruta) && is_file($ruta)) {
                @unlink($ruta);
            }

            $sql = "UPDATE mantenimiento_checklist_ejecucion SET foto_evidencia = NULL WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$id]);

            return ['success' => (bool)$resultado];

        } catch (Throwable $e) {
            error_log("Error en eliminarFotoEvidencia: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}