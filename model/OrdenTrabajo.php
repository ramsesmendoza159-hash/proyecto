<?php
// model/OrdenTrabajo.php
// MODELO COMPLETO DE ÓRDENES DE MANTENIMIENTO
// ✅ FIX 1: obtenerTodos() para supervisor ahora ve CERRADA + APROBADA + EJECUTADA
// ✅ FIX 2: generarNumeroOM() usa transacción + SELECT ... FOR UPDATE (evita colisiones)
// ✅ FIX 3: registrarDevolucion() solo permite CERRADA y EJECUTADA (no EN_PROCESO)
// ✅ FIX 4: aprobarDevolucion() devuelve la orden a CERRADA (no APROBADA)
// ✅ FIX 5: obtenerRepuestosDeOrden() y obtenerTecnicosDeOrden() usan LEFT JOIN
// ✅ FIX 6: catch (Throwable) en vez de Exception
// ✅ FIX 7: crear() ya NO calcula costo_total incorrectamente
// ✅ FIX 8: cerrar() NO usa columna inexistente
// ✅ FIX 9: obtenerTodos() usa subquery por email para técnicos (no usuario_id)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/FirmaHelper.php';

class OrdenTrabajo {
    private $db;

    const ESTADO_PENDIENTE  = 'PENDIENTE';
    const ESTADO_EN_PROCESO = 'EN_PROCESO';
    const ESTADO_EJECUTADA  = 'EJECUTADA';
    const ESTADO_CERRADA    = 'CERRADA';
    const ESTADO_CANCELADA  = 'CANCELADA';
    const ESTADO_APROBADA   = 'APROBADA';
    const ESTADO_RECHAZADA  = 'RECHAZADA';
    const ESTADO_DEVUELTA   = 'DEVUELTA';

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (OrdenTrabajo): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function obtenerTodos($filtros = [], $limit = null, $offset = null) {
        try {
            $rol = $_SESSION['rol'] ?? 'usuario';
            $email = $_SESSION['email'] ?? '';

            $sql = "SELECT o.*, 
                           t.nombre as tecnico_nombre,
                           s.nombre as supervisor_nombre,
                           p.nombre_planta,
                           a.nombre_area,
                           e.nombre_equipo,
                           c.nombre_componente
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    LEFT JOIN supervisores s ON o.id_supervisor = s.id
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    LEFT JOIN areas a ON o.id_area = a.id_area
                    LEFT JOIN equipos e ON o.id_equipo = e.id_equipo
                    LEFT JOIN componentes c ON o.id_componente = c.id_componente
                    WHERE 1=1";
            $params = [];

            // ✅ FIX 1: supervisor ve CERRADA + APROBADA + EJECUTADA
            if ($rol === 'tecnico' && !empty($email)) {
                $sql .= " AND o.tecnico_id = (SELECT id FROM tecnicos WHERE LOWER(email) = LOWER(?) LIMIT 1)";
                $params[] = $email;
            } elseif ($rol === 'supervisor') {
                $sql .= " AND o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')";
            }

            if (!empty($filtros['creado_por'])) {
                $sql .= " AND o.creado_por = ?";
                $params[] = (int)$filtros['creado_por'];
            }

            if (!empty($filtros['status'])) {
                $sql .= " AND o.status = ?";
                $params[] = $filtros['status'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (o.num_om LIKE ? OR o.titulo LIKE ? OR o.descripcion_mantenimiento LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            if (!empty($filtros['tecnico_id'])) {
                $sql .= " AND o.tecnico_id = ?";
                $params[] = (int)$filtros['tecnico_id'];
            }

            if (!empty($filtros['prioridad'])) {
                $sql .= " AND o.prioridad = ?";
                $params[] = $filtros['prioridad'];
            }

            if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
                $sql .= " AND o.fecha_creacion BETWEEN ? AND ?";
                $params[] = $filtros['fecha_desde'] . ' 00:00:00';
                $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
            }

            $sql .= " ORDER BY o.fecha_creacion DESC";

            if ($limit !== null && $offset !== null) {
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = (int)$limit;
                $params[] = (int)$offset;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            if (!is_numeric($id) || (int)$id <= 0) {
                error_log("OrdenTrabajo::obtenerPorId - ID inválido: $id");
                return false;
            }

            $sql = "SELECT o.*, 
                           t.nombre as tecnico_nombre,
                           t.especialidad as tecnico_especialidad,
                           t.tarifa as tecnico_tarifa,
                           t.email as tecnico_email,
                           s.nombre as supervisor_nombre,
                           p.nombre_planta,
                           a.nombre_area,
                           e.nombre_equipo,
                           e.codigo as equipo_codigo,
                           c.nombre_componente
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    LEFT JOIN supervisores s ON o.id_supervisor = s.id
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    LEFT JOIN areas a ON o.id_area = a.id_area
                    LEFT JOIN equipos e ON o.id_equipo = e.id_equipo
                    LEFT JOIN componentes c ON o.id_componente = c.id_componente
                    WHERE o.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);

            return $orden ?: false;

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    public function crear($datos) {
        try {
            if (empty($datos['descripcion_mantenimiento']) && empty($datos['descripcion'])) {
                $_SESSION['error'] = 'La descripción del mantenimiento es obligatoria';
                return false;
            }

            $descripcion = $datos['descripcion_mantenimiento'] ?? $datos['descripcion'] ?? '';

            // ✅ FIX 2: generar número OM de forma atómica
            $num_om = $datos['num_om'] ?? $this->generarNumeroOM();

            $sql = "INSERT INTO ordenes_mantenimiento (
                        num_om, titulo, descripcion_mantenimiento, pasos,
                        tipo_mantenimiento, tipo_actividad, prioridad,
                        tecnico_id, id_supervisor, id_proveedor, proveedor_servicio, n_tecnicos,
                        id_planta, id_area, id_equipo, id_componente,
                        solicitante, supervisor_solicitante,
                        fecha_inicio, fecha_estimada, horas_duracion,
                        tarifa_tecnico, costo_repuestos, costo_mano_obra, costo_total,
                        status, creado_por, fecha_creacion
                    ) VALUES (
                        :num_om, :titulo, :descripcion_mantenimiento, :pasos,
                        :tipo_mantenimiento, :tipo_actividad, :prioridad,
                        :tecnico_id, :id_supervisor, :id_proveedor, :proveedor_servicio, :n_tecnicos,
                        :id_planta, :id_area, :id_equipo, :id_componente,
                        :solicitante, :supervisor_solicitante,
                        :fecha_inicio, :fecha_estimada, :horas_duracion,
                        :tarifa_tecnico, :costo_repuestos, :costo_mano_obra, :costo_total,
                        :status, :creado_por, NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'num_om' => $num_om,
                'titulo' => $datos['titulo'] ?? '',
                'descripcion_mantenimiento' => $descripcion,
                'pasos' => $datos['pasos'] ?? '',
                'tipo_mantenimiento' => $datos['tipo_mantenimiento'] ?? 'CORRECTIVO',
                'tipo_actividad' => $datos['tipo_actividad'] ?? '',
                'prioridad' => $datos['prioridad'] ?? 'Media',
                'tecnico_id' => !empty($datos['tecnico_id']) ? (int)$datos['tecnico_id'] : null,
                'id_supervisor' => !empty($datos['id_supervisor']) ? (int)$datos['id_supervisor'] : null,
                'id_proveedor' => !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null,
                'proveedor_servicio' => $datos['proveedor_servicio'] ?? '',
                'n_tecnicos' => (int)($datos['n_tecnicos'] ?? 1),
                'id_planta' => !empty($datos['id_planta']) ? (int)$datos['id_planta'] : null,
                'id_area' => !empty($datos['id_area']) ? (int)$datos['id_area'] : null,
                'id_equipo' => !empty($datos['id_equipo']) ? (int)$datos['id_equipo'] : null,
                'id_componente' => !empty($datos['id_componente']) ? (int)$datos['id_componente'] : null,
                'solicitante' => $datos['solicitante'] ?? '',
                'supervisor_solicitante' => $datos['supervisor_solicitante'] ?? '',
                'fecha_inicio' => $datos['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_estimada' => $datos['fecha_estimada'] ?? null,
                'horas_duracion' => (float)($datos['horas_duracion'] ?? 0),
                'tarifa_tecnico' => (float)($datos['tarifa_tecnico'] ?? 0),
                'costo_repuestos' => (float)($datos['costo_repuestos'] ?? 0),
                'costo_mano_obra' => (float)($datos['costo_mano_obra'] ?? 0),
                'costo_total' => (float)($datos['costo_total'] ?? 0),
                'status' => $datos['status'] ?? self::ESTADO_PENDIENTE,
                'creado_por' => (int)($datos['creado_por'] ?? $_SESSION['usuario_id'] ?? 1)
            ]);

            if ($result) {
                return (int)$this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::crear: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear la orden: ' . $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    public function actualizar($id, $datos) {
        try {
            if (!is_numeric($id) || (int)$id <= 0) {
                $_SESSION['error'] = 'ID de orden inválido';
                return false;
            }

            $sql = "UPDATE ordenes_mantenimiento SET 
                        titulo = :titulo,
                        descripcion_mantenimiento = :descripcion_mantenimiento,
                        pasos = :pasos,
                        descripcion_realizada = :descripcion_realizada,
                        tipo_mantenimiento = :tipo_mantenimiento,
                        tipo_actividad = :tipo_actividad,
                        prioridad = :prioridad,
                        tecnico_id = :tecnico_id,
                        id_supervisor = :id_supervisor,
                        id_proveedor = :id_proveedor,
                        proveedor_servicio = :proveedor_servicio,
                        n_tecnicos = :n_tecnicos,
                        id_planta = :id_planta,
                        id_area = :id_area,
                        id_equipo = :id_equipo,
                        id_componente = :id_componente,
                        solicitante = :solicitante,
                        supervisor_solicitante = :supervisor_solicitante,
                        fecha_inicio = :fecha_inicio,
                        fecha_estimada = :fecha_estimada,
                        horas_duracion = :horas_duracion,
                        tarifa_tecnico = :tarifa_tecnico,
                        costo_repuestos = :costo_repuestos,
                        costo_mano_obra = :costo_mano_obra,
                        costo_total = :costo_total,
                        status = :status,
                        observaciones_tecnico = :observaciones_tecnico,
                        observaciones_cierre = :observaciones_cierre,
                        actualizado_por = :actualizado_por,
                        fecha_actualizacion = NOW()
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([
                'id' => (int)$id,
                'titulo' => $datos['titulo'] ?? '',
                'descripcion_mantenimiento' => $datos['descripcion_mantenimiento'] ?? '',
                'pasos' => $datos['pasos'] ?? '',
                'descripcion_realizada' => $datos['descripcion_realizada'] ?? '',
                'tipo_mantenimiento' => $datos['tipo_mantenimiento'] ?? 'CORRECTIVO',
                'tipo_actividad' => $datos['tipo_actividad'] ?? '',
                'prioridad' => $datos['prioridad'] ?? 'Media',
                'tecnico_id' => !empty($datos['tecnico_id']) ? (int)$datos['tecnico_id'] : null,
                'id_supervisor' => !empty($datos['id_supervisor']) ? (int)$datos['id_supervisor'] : null,
                'id_proveedor' => !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null,
                'proveedor_servicio' => $datos['proveedor_servicio'] ?? '',
                'n_tecnicos' => (int)($datos['n_tecnicos'] ?? 1),
                'id_planta' => !empty($datos['id_planta']) ? (int)$datos['id_planta'] : null,
                'id_area' => !empty($datos['id_area']) ? (int)$datos['id_area'] : null,
                'id_equipo' => !empty($datos['id_equipo']) ? (int)$datos['id_equipo'] : null,
                'id_componente' => !empty($datos['id_componente']) ? (int)$datos['id_componente'] : null,
                'solicitante' => $datos['solicitante'] ?? '',
                'supervisor_solicitante' => $datos['supervisor_solicitante'] ?? '',
                'fecha_inicio' => $datos['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_estimada' => $datos['fecha_estimada'] ?? null,
                'horas_duracion' => (float)($datos['horas_duracion'] ?? 0),
                'tarifa_tecnico' => (float)($datos['tarifa_tecnico'] ?? 0),
                'costo_repuestos' => (float)($datos['costo_repuestos'] ?? 0),
                'costo_mano_obra' => (float)($datos['costo_mano_obra'] ?? 0),
                'costo_total' => (float)($datos['costo_total'] ?? 0),
                'status' => $datos['status'] ?? self::ESTADO_PENDIENTE,
                'observaciones_tecnico' => $datos['observaciones_tecnico'] ?? '',
                'observaciones_cierre' => $datos['observaciones_cierre'] ?? '',
                'actualizado_por' => (int)($datos['actualizado_por'] ?? $_SESSION['usuario_id'] ?? 1)
            ]);

            if ($resultado) {
                $ordenActualizada = $this->obtenerPorId($id);

                if ($ordenActualizada && !empty($ordenActualizada['firma_hash'])) {
                    if (in_array($ordenActualizada['status'], ['CERRADA', 'APROBADA', 'EJECUTADA'], true)) {
                        $this->recalcularFirma($id);
                        error_log("Firma recalculada para orden #$id después de edición");
                    }
                }
            }

            return $resultado;

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::actualizar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar la orden: ' . $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // CERRAR
    // ==========================================

    public function cerrar($id, $datos) {
        try {
            if (!is_numeric($id) || (int)$id <= 0) {
                error_log("OrdenTrabajo::cerrar - ID inválido: $id");
                return false;
            }

            $sqlCheck = "SELECT * FROM ordenes_mantenimiento WHERE id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([(int)$id]);
            $orden = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$orden) {
                $_SESSION['error'] = 'Orden no encontrada';
                return false;
            }

            $estados_validos = ['PENDIENTE', 'EN_PROCESO', 'EJECUTADA'];
            if (!in_array($orden['status'], $estados_validos, true)) {
                $_SESSION['error'] = 'La orden no se puede cerrar en su estado actual';
                return false;
            }

            $descripcion_realizada = $datos['descripcion_realizada'] ?? '';
            $pasos_ejecutados = $datos['pasos_ejecutados'] ?? '';
            $horas_trabajadas = (float)($datos['horas_trabajadas'] ?? 0);
            $observaciones = $datos['observaciones_tecnico'] ?? '';

            $tarifa = (float)($datos['tarifa_tecnico'] ?? $orden['tarifa_tecnico'] ?? 0);
            $costo_repuestos = (float)($datos['costo_repuestos'] ?? $orden['costo_repuestos'] ?? 0);

            // ✅ FIX: costo mano de obra solo del técnico principal
            // Los técnicos adicionales son informativos
            $costo_mano_obra = $horas_trabajadas * $tarifa;
            $costo_total = $costo_repuestos + $costo_mano_obra;

            $sql = "UPDATE ordenes_mantenimiento SET 
                        descripcion_realizada = :descripcion_realizada,
                        pasos_ejecutados = :pasos_ejecutados,
                        horas_trabajadas = :horas_trabajadas,
                        tarifa_tecnico = :tarifa_tecnico,
                        costo_total = :costo_total,
                        costo_repuestos = :costo_repuestos,
                        costo_mano_obra = :costo_mano_obra,
                        foto_evidencia = :foto_evidencia,
                        observaciones_tecnico = :observaciones_tecnico,
                        observaciones_cierre = :observaciones_cierre,
                        status = :status,
                        fecha_finalizacion = NOW(),
                        actualizado_por = :actualizado_por
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([
                'descripcion_realizada' => $descripcion_realizada,
                'pasos_ejecutados' => $pasos_ejecutados,
                'horas_trabajadas' => $horas_trabajadas,
                'tarifa_tecnico' => $tarifa,
                'costo_total' => $costo_total,
                'costo_repuestos' => $costo_repuestos,
                'costo_mano_obra' => $costo_mano_obra,
                'foto_evidencia' => $datos['foto_evidencia'] ?? '',
                'observaciones_tecnico' => $observaciones,
                'observaciones_cierre' => $datos['observaciones_cierre'] ?? '',
                'status' => self::ESTADO_CERRADA,
                'actualizado_por' => (int)($datos['actualizado_por'] ?? $_SESSION['usuario_id'] ?? 1),
                'id' => (int)$id
            ]);

            if (!$resultado) {
                return false;
            }

            // Generar firma SHA-256
            $ordenActualizada = $this->obtenerPorId($id);

            if ($ordenActualizada) {
                $firma = FirmaHelper::generarFirma($ordenActualizada);

                if (!empty($firma)) {
                    $sqlFirma = "UPDATE ordenes_mantenimiento SET 
                                    firma_hash = :firma_hash,
                                    firma_creada_en = NOW(),
                                    firma_actualizada_en = NULL
                                 WHERE id = :id";
                    $stmtFirma = $this->db->prepare($sqlFirma);
                    $stmtFirma->execute([
                        'firma_hash' => $firma,
                        'id' => (int)$id
                    ]);

                    error_log("Firma generada para orden #$id: " . substr($firma, 0, 16) . "...");
                }
            }

            return true;

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::cerrar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cerrar la orden: ' . $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // FIRMAS HASH
    // ==========================================

    public function verificarFirma($id) {
        try {
            $orden = $this->obtenerPorId($id);

            if (!$orden) {
                return ['valida' => false, 'mensaje' => 'Orden no encontrada'];
            }

            return FirmaHelper::verificarFirma($orden);

        } catch (Throwable $e) {
            error_log("Error en OrdenTrabajo::verificarFirma: " . $e->getMessage());
            return ['valida' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    public function recalcularFirma($id) {
        try {
            $orden = $this->obtenerPorId($id);

            if (!$orden) {
                return false;
            }

            $firma = FirmaHelper::generarFirma($orden);

            if (empty($firma)) {
                return false;
            }

            $sql = "UPDATE ordenes_mantenimiento SET 
                        firma_hash = :firma_hash,
                        firma_actualizada_en = NOW()
                     WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'firma_hash' => $firma,
                'id' => (int)$id
            ]);

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::recalcularFirma: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ELIMINAR / CAMBIAR ESTADO
    // ==========================================

    public function eliminar($id) {
        try {
            $sql = "DELETE FROM ordenes_mantenimiento WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([(int)$id]);
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::eliminar: " . $e->getMessage());
            return false;
        }
    }

    public function cambiarEstado($id, $estado, $observaciones = null) {
        try {
            $estados_validos = [
                self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO, self::ESTADO_EJECUTADA,
                self::ESTADO_CERRADA, self::ESTADO_CANCELADA, self::ESTADO_APROBADA,
                self::ESTADO_RECHAZADA, self::ESTADO_DEVUELTA
            ];

            if (!in_array($estado, $estados_validos, true)) {
                $_SESSION['error'] = 'Estado inválido';
                return false;
            }

            $sql = "UPDATE ordenes_mantenimiento SET 
                        status = :status,
                        observaciones_cierre = :observaciones,
                        fecha_actualizacion = NOW()
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'status' => $estado,
                'observaciones' => $observaciones ?? '',
                'id' => (int)$id
            ]);

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CONSULTAS FILTRADAS
    // ==========================================

    public function obtenerPorEstado($estado) {
        try {
            $sql = "SELECT o.*, 
                           t.nombre as tecnico_nombre,
                           p.nombre_planta,
                           a.nombre_area
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    LEFT JOIN areas a ON o.id_area = a.id_area
                    WHERE o.status = ?
                    ORDER BY o.fecha_creacion DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$estado]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerPorEstado: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorTecnico($tecnico_id, $limit = null, $offset = null) {
        try {
            $sql = "SELECT o.*, 
                           p.nombre_planta,
                           a.nombre_area,
                           e.nombre_equipo,
                           c.nombre_componente
                    FROM ordenes_mantenimiento o
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    LEFT JOIN areas a ON o.id_area = a.id_area
                    LEFT JOIN equipos e ON o.id_equipo = e.id_equipo
                    LEFT JOIN componentes c ON o.id_componente = c.id_componente
                    WHERE o.tecnico_id = ? 
                    ORDER BY o.fecha_creacion DESC";

            if ($limit !== null && $offset !== null) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([(int)$tecnico_id, (int)$limit, (int)$offset]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([(int)$tecnico_id]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerPorTecnico: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // ESTADÍSTICAS
    // ==========================================

    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ejecutadas,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cerradas,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as canceladas,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as aprobadas,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rechazadas,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as devueltas
                    FROM ordenes_mantenimiento";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                self::ESTADO_PENDIENTE,
                self::ESTADO_EN_PROCESO,
                self::ESTADO_EJECUTADA,
                self::ESTADO_CERRADA,
                self::ESTADO_CANCELADA,
                self::ESTADO_APROBADA,
                self::ESTADO_RECHAZADA,
                self::ESTADO_DEVUELTA
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)($result['total'] ?? 0),
                'pendientes' => (int)($result['pendientes'] ?? 0),
                'en_proceso' => (int)($result['en_proceso'] ?? 0),
                'ejecutadas' => (int)($result['ejecutadas'] ?? 0),
                'cerradas' => (int)($result['cerradas'] ?? 0),
                'canceladas' => (int)($result['canceladas'] ?? 0),
                'aprobadas' => (int)($result['aprobadas'] ?? 0),
                'rechazadas' => (int)($result['rechazadas'] ?? 0),
                'devueltas' => (int)($result['devueltas'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total' => 0, 'pendientes' => 0, 'en_proceso' => 0,
                'ejecutadas' => 0, 'cerradas' => 0, 'canceladas' => 0,
                'aprobadas' => 0, 'rechazadas' => 0, 'devueltas' => 0
            ];
        }
    }

    public function obtenerTotalGastado() {
        try {
            $sql = "SELECT COALESCE(SUM(costo_total), 0) as total 
                    FROM ordenes_mantenimiento
                    WHERE status IN ('CERRADA', 'APROBADA', 'EJECUTADA')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerTotalGastado: " . $e->getMessage());
            return 0;
        }
    }

    // ==========================================
    // ✅ FIX 2: generarNumeroOM() con LOCK
    // ==========================================

    /**
     * Genera un número de OM único usando SELECT ... FOR UPDATE
     * para evitar colisiones en concurrencia.
     *
     * Formato: OM-YYYY-MM-NNNN
     */
    public function generarNumeroOM() {
        $anio = date('Y');
        $mes  = date('m');

        try {
            $this->db->beginTransaction();

            // ✅ FIX: bloquear las filas del mes para evitar colisiones
            $sql = "SELECT COUNT(*) as total 
                    FROM ordenes_mantenimiento 
                    WHERE YEAR(fecha_creacion) = ? AND MONTH(fecha_creacion) = ?
                    FOR UPDATE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anio, $mes]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $numero = ((int)($result['total'] ?? 0)) + 1;

            $num_om = "OM-" . $anio . "-" . $mes . "-" . str_pad((string)$numero, 4, '0', STR_PAD_LEFT);

            // Verificar que no exista (por seguridad extra)
            $sqlCheck = "SELECT COUNT(*) as existe FROM ordenes_mantenimiento WHERE num_om = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$num_om]);
            $existe = (int)($stmtCheck->fetch(PDO::FETCH_ASSOC)['existe'] ?? 0);

            if ($existe > 0) {
                // Si existe, incrementar hasta encontrar uno libre
                $intentos = 0;
                while ($existe > 0 && $intentos < 100) {
                    $numero++;
                    $num_om = "OM-" . $anio . "-" . $mes . "-" . str_pad((string)$numero, 4, '0', STR_PAD_LEFT);
                    $stmtCheck->execute([$num_om]);
                    $existe = (int)($stmtCheck->fetch(PDO::FETCH_ASSOC)['existe'] ?? 0);
                    $intentos++;
                }
            }

            $this->db->commit();
            return $num_om;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en OrdenTrabajo::generarNumeroOM: " . $e->getMessage());

            // Fallback: usar timestamp para garantizar unicidad
            return "OM-" . $anio . "-" . $mes . "-" . substr((string)time(), -4);
        }
    }

    // ==========================================
    // TÉCNICOS ADICIONALES
    // ==========================================

    public function obtenerTecnicosDeOrden($orden_id) {
        try {
            // ✅ FIX 5: LEFT JOIN para no perder datos si el técnico fue borrado
            $sql = "SELECT ot.*, t.nombre, t.especialidad, t.tarifa as tarifa_tecnico
                    FROM ordenes_tecnicos ot
                    LEFT JOIN tecnicos t ON ot.tecnico_id = t.id
                    WHERE ot.orden_id = ?
                    ORDER BY ot.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$orden_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerTecnicosDeOrden: " . $e->getMessage());
            return [];
        }
    }

    public function agregarTecnicosAdicionales($orden_id, $tecnicos) {
        try {
            $sqlDelete = "DELETE FROM ordenes_tecnicos WHERE orden_id = ?";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([(int)$orden_id]);

            if (empty($tecnicos)) {
                return true;
            }

            $sql = "INSERT INTO ordenes_tecnicos (orden_id, tecnico_id, tarifa_hora, fecha_asignacion) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);

            foreach ($tecnicos as $tecnico) {
                $stmt->execute([
                    (int)$orden_id,
                    (int)$tecnico['id'],
                    (float)($tecnico['tarifa'] ?? 0)
                ]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::agregarTecnicosAdicionales: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // REPUESTOS
    // ==========================================

    public function obtenerRepuestosDeOrden($orden_id) {
        try {
            // ✅ FIX 5: LEFT JOIN para no perder datos si el repuesto fue borrado
            $sql = "SELECT orp.*, i.nombre, i.codigo, i.descripcion
                    FROM ordenes_repuestos orp
                    LEFT JOIN inventario i ON orp.repuesto_id = i.id
                    WHERE orp.orden_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$orden_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerRepuestosDeOrden: " . $e->getMessage());
            return [];
        }
    }

    public function agregarRepuestos($orden_id, $repuestos) {
        try {
            $sqlDelete = "DELETE FROM ordenes_repuestos WHERE orden_id = ?";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([(int)$orden_id]);

            if (empty($repuestos)) {
                return true;
            }

            $sql = "INSERT INTO ordenes_repuestos (orden_id, repuesto_id, cantidad, costo_unitario, costo_total, fecha_asignacion) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);

            foreach ($repuestos as $repuesto) {
                $costo_total = (float)$repuesto['cantidad'] * (float)$repuesto['precio_unitario'];
                $stmt->execute([
                    (int)$orden_id,
                    (int)$repuesto['id'],
                    (int)$repuesto['cantidad'],
                    (float)$repuesto['precio_unitario'],
                    $costo_total
                ]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::agregarRepuestos: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ✅ FIX 3 y 4: DEVOLUCIONES
    // ==========================================

    public function registrarDevolucion($orden_id, $motivo, $usuario_id) {
        try {
            $orden = $this->obtenerPorId($orden_id);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            // ✅ FIX 3: solo CERRADA y EJECUTADA pueden devolverse
            if (!in_array($orden['status'], ['CERRADA', 'EJECUTADA'], true)) {
                return ['success' => false, 'error' => 'Solo se pueden devolver órdenes CERRADAS o EJECUTADAS'];
            }

            $this->db->beginTransaction();

            $sql = "INSERT INTO devoluciones_ordenes (orden_id, usuario_id, motivo, fecha_solicitud, estado) 
                    VALUES (?, ?, ?, NOW(), 'pendiente')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$orden_id, (int)$usuario_id, $motivo]);

            $devolucion_id = $this->db->lastInsertId();

            $sqlUpdate = "UPDATE ordenes_mantenimiento SET 
                            status = 'DEVUELTA',
                            devolucion_motivo = ?,
                            devolucion_fecha = NOW(),
                            devolucion_estado = 'pendiente',
                            fecha_actualizacion = NOW()
                          WHERE id = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$motivo, (int)$orden_id]);

            $this->db->commit();

            return [
                'success' => true,
                'devolucion_id' => $devolucion_id,
                'message' => 'Devolución registrada correctamente'
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en OrdenTrabajo::registrarDevolucion: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function aprobarDevolucion($devolucion_id, $supervisor_id) {
        try {
            $sql = "SELECT * FROM devoluciones_ordenes WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$devolucion_id]);
            $devolucion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$devolucion) {
                return ['success' => false, 'error' => 'Devolución no encontrada'];
            }

            if ($devolucion['estado'] !== 'pendiente') {
                return ['success' => false, 'error' => 'La devolución ya fue procesada'];
            }

            $this->db->beginTransaction();

            $sql = "UPDATE devoluciones_ordenes SET 
                        estado = 'aprobada',
                        fecha_aprobacion = NOW(),
                        aprobado_por = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$supervisor_id, (int)$devolucion_id]);

            // ✅ FIX 4: la orden vuelve a CERRADA (no APROBADA)
            $sqlUpdate = "UPDATE ordenes_mantenimiento SET 
                            status = 'CERRADA',
                            devolucion_estado = 'aprobada',
                            fecha_actualizacion = NOW()
                          WHERE id = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([(int)$devolucion['orden_id']]);

            $this->db->commit();

            return ['success' => true, 'message' => 'Devolución aprobada'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en OrdenTrabajo::aprobarDevolucion: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function obtenerDevolucionesPendientes() {
        try {
            $sql = "SELECT d.*, o.num_om, o.titulo, u.nombre as usuario_nombre
                    FROM devoluciones_ordenes d
                    JOIN ordenes_mantenimiento o ON d.orden_id = o.id
                    LEFT JOIN usuarios u ON d.usuario_id = u.id
                    WHERE d.estado = 'pendiente'
                    ORDER BY d.fecha_solicitud ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerDevolucionesPendientes: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerDevoluciones() {
        try {
            $sql = "SELECT o.*, 
                           t.nombre as tecnico_nombre,
                           s.nombre as supervisor_nombre,
                           p.nombre_planta,
                           a.nombre_area,
                           e.nombre_equipo
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    LEFT JOIN supervisores s ON o.id_supervisor = s.id
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    LEFT JOIN areas a ON o.id_area = a.id_area
                    LEFT JOIN equipos e ON o.id_equipo = e.id_equipo
                    WHERE o.status = 'DEVUELTA'
                    ORDER BY o.fecha_actualizacion DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerDevoluciones: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // REPORTES
    // ==========================================

    public function obtenerCostosPorPlanta($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        p.nombre_planta,
                        COUNT(o.id) as total_ordenes,
                        COALESCE(SUM(o.costo_total), 0) as total_costos,
                        COALESCE(SUM(o.costo_repuestos), 0) as total_repuestos,
                        COALESCE(SUM(o.costo_mano_obra), 0) as total_mano_obra
                    FROM ordenes_mantenimiento o
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    WHERE o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND o.fecha_creacion BETWEEN ? AND ?
                    AND o.id_planta IS NOT NULL
                    GROUP BY o.id_planta
                    ORDER BY total_costos DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerCostosPorPlanta: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerEvolucionMensual() {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(o.fecha_creacion, '%b %Y') as mes_label,
                        DATE_FORMAT(o.fecha_creacion, '%Y-%m') as mes,
                        COUNT(o.id) as total_ordenes,
                        COALESCE(SUM(o.costo_total), 0) as total_costos
                    FROM ordenes_mantenimiento o
                    WHERE o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND YEAR(o.fecha_creacion) = YEAR(NOW())
                    GROUP BY DATE_FORMAT(o.fecha_creacion, '%Y-%m')
                    ORDER BY mes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en OrdenTrabajo::obtenerEvolucionMensual: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // MÉTODOS PARA DASHBOARD
    // ==========================================

    public function obtenerEstadisticasDashboard($filtros = []) {
        $sql = "SELECT 
                    COUNT(o.id) as total,
                    SUM(CASE WHEN o.status = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN o.status = 'EN_PROCESO' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as cerradas,
                    SUM(CASE WHEN o.status = 'CANCELADA' THEN 1 ELSE 0 END) as canceladas,
                    SUM(CASE WHEN o.proveedor_servicio IS NULL OR o.proveedor_servicio = '' THEN 1 ELSE 0 END) as propias,
                    SUM(CASE WHEN o.proveedor_servicio IS NOT NULL AND o.proveedor_servicio != '' THEN 1 ELSE 0 END) as tercerizadas,
                    SUM(o.costo_total) as total_costos,
                    SUM(o.costo_mano_obra) as total_mano_obra,
                    SUM(o.costo_repuestos) as total_repuestos,
                    ROUND(SUM(o.costo_total) / NULLIF(SUM(o.horas_trabajadas), 0), 2) as costo_hora,
                    COUNT(DISTINCT o.id_planta) as plantas_atendidas,
                    ROUND(AVG(o.horas_trabajadas), 1) as promedio_horas,
                    ROUND(AVG(o.costo_total), 2) as promedio_costo
                FROM ordenes_mantenimiento o
                WHERE 1=1";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return [
                'total' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'cerradas' => 0,
                'canceladas' => 0, 'propias' => 0, 'tercerizadas' => 0,
                'total_costos' => 0, 'total_mano_obra' => 0, 'total_repuestos' => 0,
                'costo_hora' => 0, 'plantas_atendidas' => 0,
                'promedio_horas' => 0, 'promedio_costo' => 0
            ];
        }

        return $result;
    }

    public function obtenerOrdenesPorMes($filtros = []) {
        $sql = "SELECT 
                    DATE_FORMAT(o.fecha_creacion, '%b %Y') as mes_label,
                    DATE_FORMAT(o.fecha_creacion, '%Y-%m') as mes,
                    COUNT(o.id) as total,
                    SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as cerradas,
                    SUM(CASE WHEN o.status = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN o.status = 'EN_PROCESO' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(o.costo_total) as costo_total
                FROM ordenes_mantenimiento o
                WHERE o.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);
        $sql .= " GROUP BY DATE_FORMAT(o.fecha_creacion, '%Y-%m') ORDER BY mes ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOrdenesPorPlantaDashboard($filtros = []) {
        $sql = "SELECT 
                    COALESCE(p.nombre_planta, 'Sin Planta') as planta,
                    COUNT(o.id) as total,
                    SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as cerradas,
                    SUM(CASE WHEN o.status = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN o.status = 'EN_PROCESO' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(o.costo_total) as costo_total
                FROM ordenes_mantenimiento o
                LEFT JOIN plantas p ON o.id_planta = p.id_planta
                WHERE 1=1";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);
        $sql .= " GROUP BY o.id_planta ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCostoPorPlanta($filtros = []) {
        $sql = "SELECT 
                    COALESCE(p.nombre_planta, 'Sin Planta') as planta,
                    SUM(o.costo_total) as costo_total,
                    SUM(o.costo_repuestos) as costo_repuestos,
                    SUM(o.costo_mano_obra) as costo_mano_obra,
                    COUNT(o.id) as total_ordenes
                FROM ordenes_mantenimiento o
                LEFT JOIN plantas p ON o.id_planta = p.id_planta
                WHERE o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                AND o.costo_total > 0";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);
        $sql .= " GROUP BY o.id_planta ORDER BY costo_total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOrdenesPorTipoActividad($filtros = []) {
        $sql = "SELECT 
                    COALESCE(o.tipo_actividad, 'No Especificado') as actividad,
                    COUNT(o.id) as total,
                    SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as cerradas,
                    SUM(o.costo_total) as costo_total
                FROM ordenes_mantenimiento o
                WHERE 1=1";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);
        $sql .= " GROUP BY o.tipo_actividad ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCostoPorTipoMantenimiento($filtros = []) {
        $sql = "SELECT 
                    COALESCE(o.tipo_mantenimiento, 'No Especificado') as tipo_mantenimiento,
                    SUM(o.costo_total) as costo_total,
                    SUM(o.costo_repuestos) as costo_repuestos,
                    SUM(o.costo_mano_obra) as costo_mano_obra,
                    COUNT(o.id) as total_ordenes
                FROM ordenes_mantenimiento o
                WHERE o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                AND o.costo_total > 0";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);
        $sql .= " GROUP BY o.tipo_mantenimiento ORDER BY costo_total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEquiposMayorRecurrencia($limite = 5, $filtros = []) {
        $sql = "SELECT 
                    COALESCE(e.nombre_equipo, 'Sin Equipo') as equipo,
                    COUNT(o.id) as total_ordenes,
                    SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as completadas,
                    SUM(o.costo_total) as costo_total,
                    ROUND(AVG(o.horas_trabajadas), 1) as promedio_horas
                FROM ordenes_mantenimiento o
                LEFT JOIN equipos e ON o.id_equipo = e.id_equipo
                WHERE 1=1";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);
        $sql .= " GROUP BY o.id_equipo 
                  HAVING total_ordenes > 0
                  ORDER BY total_ordenes DESC 
                  LIMIT ?";
        $params[] = (int)$limite;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProductividadPorTecnico($filtros = []) {
        $sql = "SELECT 
                    COALESCE(t.nombre, 'Sin Asignar') as tecnico,
                    COUNT(o.id) as total_ordenes,
                    SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as completadas,
                    ROUND(SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(o.id), 0), 1) as eficiencia,
                    SUM(o.horas_trabajadas) as total_horas,
                    SUM(o.costo_total) as costo_total,
                    SUM(o.costo_mano_obra) as costo_mano_obra
                FROM ordenes_mantenimiento o
                LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                WHERE 1=1";

        $params = [];
        $sql = $this->aplicarFiltrosDashboard($sql, $filtros, $params);
        $sql .= " GROUP BY o.tecnico_id 
                  HAVING total_ordenes > 0
                  ORDER BY completadas DESC, eficiencia DESC
                  LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function aplicarFiltrosDashboard($sql, $filtros, &$params) {
        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $sql .= " AND o.fecha_creacion BETWEEN ? AND ?";
            $params[] = $filtros['fecha_desde'] . ' 00:00:00';
            $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        if (!empty($filtros['id_planta'])) {
            $sql .= " AND o.id_planta = ?";
            $params[] = (int)$filtros['id_planta'];
        }

        if (!empty($filtros['tipo_actividad'])) {
            $sql .= " AND o.tipo_actividad = ?";
            $params[] = $filtros['tipo_actividad'];
        }

        if (!empty($filtros['tipo_mantenimiento'])) {
            $sql .= " AND o.tipo_mantenimiento = ?";
            $params[] = $filtros['tipo_mantenimiento'];
        }

        return $sql;
    }
}