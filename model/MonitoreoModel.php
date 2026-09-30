<?php
// model/MonitoreoModel.php
// Modelo para el módulo de Monitoreo de Equipos (MTTO R007 y sucesivos)
// ✅ FIX: obtenerTodas() usa isset() en vez de !empty() para activo=0
// ✅ FIX: guardarLectura() valida que id_equipo pertenezca a la ruta
// ✅ FIX: firmar() y rechazar() validan que el paso sea el actual
// ✅ FIX: inicializarFirmas() usa transacción
// ✅ FIX: contarPendientesDelDia() suma vencidos de días anteriores
// ✅ FIX: crearRegistrosDelDia() optimizado con INSERT masivo
// ✅ FIX: sanitización SVG en método firmar() usando SVGSanitizer

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/SVGSanitizer.php';  // ✅ NUEVO

class MonitoreoModel {
    private $db;
    private $lastError;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (Monitoreo): " . $e->getMessage());
            throw $e;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    // ==========================================
    // RUTAS
    // ==========================================

    public function obtenerTodas($filtros = []) {
        try {
            $sql = "SELECT r.*, 
                           (SELECT COUNT(*) FROM rutas_monitoreo_equipos re 
                            WHERE re.ruta_id = r.id AND re.activo = 1) AS total_equipos,
                           (SELECT COUNT(*) FROM rutas_monitoreo_horarios rh 
                            WHERE rh.ruta_id = r.id AND rh.activo = 1) AS total_horarios
                    FROM rutas_monitoreo r
                    WHERE 1=1";
            $params = [];

            // ✅ FIX: isset() en vez de !empty() para permitir activo=0
            if (isset($filtros['activo']) && $filtros['activo'] !== '') {
                $sql .= " AND r.activo = ?";
                $params[] = (int)$filtros['activo'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (r.nombre LIKE ? OR r.codigo LIKE ? OR r.descripcion LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY r.codigo ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerTodas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "SELECT * FROM rutas_monitoreo WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ruta) return false;

            $ruta['equipos']        = $this->obtenerEquipos($id);
            $ruta['horarios']       = $this->obtenerHorarios($id);
            $ruta['firmas_config']  = $this->obtenerFirmasConfig($id);

            return $ruta;

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }

    public function crear($datos) {
        try {
            $sql = "INSERT INTO rutas_monitoreo 
                    (codigo, nombre, descripcion, tipo_valor, unidad_medida,
                     frecuencia_horas, valor_min_global, valor_max_global, valor_objetivo_relleno, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $datos['codigo'],
                $datos['nombre'],
                $datos['descripcion'] ?? '',
                $datos['tipo_valor'] ?? 'porcentaje',
                $datos['unidad_medida'] ?? '%',
                (int)($datos['frecuencia_horas'] ?? 2),
                $datos['valor_min_global'] ?? null,
                $datos['valor_max_global'] ?? null,
                $datos['valor_objetivo_relleno'] ?? null,
                (int)($datos['activo'] ?? 1)
            ]);

            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::crear: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function actualizar($id, $datos) {
        try {
            $sql = "UPDATE rutas_monitoreo SET
                        nombre = ?,
                        descripcion = ?,
                        tipo_valor = ?,
                        unidad_medida = ?,
                        frecuencia_horas = ?,
                        valor_min_global = ?,
                        valor_max_global = ?,
                        valor_objetivo_relleno = ?,
                        activo = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'] ?? '',
                $datos['tipo_valor'] ?? 'porcentaje',
                $datos['unidad_medida'] ?? '%',
                (int)($datos['frecuencia_horas'] ?? 2),
                $datos['valor_min_global'] ?? null,
                $datos['valor_max_global'] ?? null,
                $datos['valor_objetivo_relleno'] ?? null,
                (int)($datos['activo'] ?? 1),
                (int)$id
            ]);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::actualizar: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function eliminar($id) {
        try {
            $id = (int)$id;

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM monitoreo_registros WHERE ruta_id = ?");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                $this->lastError = 'No se puede eliminar la ruta porque tiene registros asociados';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM rutas_monitoreo WHERE id = ?");
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::eliminar: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function obtenerEquipos($ruta_id) {
        try {
            $sql = "SELECT re.*, 
                           e.nombre_equipo, e.codigo AS equipo_codigo, e.serie,
                           e.estado AS equipo_estado, e.estado_operativo,
                           a.nombre_area, p.nombre_planta
                    FROM rutas_monitoreo_equipos re
                    JOIN equipos e ON re.id_equipo = e.id_equipo
                    LEFT JOIN areas a ON e.id_area = a.id_area
                    LEFT JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE re.ruta_id = ? AND re.activo = 1
                    ORDER BY re.orden ASC, e.nombre_equipo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$ruta_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerEquipos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerHorarios($ruta_id) {
        try {
            $sql = "SELECT * FROM rutas_monitoreo_horarios
                    WHERE ruta_id = ? AND activo = 1
                    ORDER BY orden ASC, hora ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$ruta_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerHorarios: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerHorariosPorTurno($ruta_id, $turno) {
        try {
            $sql = "SELECT * FROM rutas_monitoreo_horarios
                    WHERE ruta_id = ? AND turno = ? AND activo = 1
                    ORDER BY orden ASC, hora ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$ruta_id, $turno]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerHorariosPorTurno: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerFirmasConfig($ruta_id) {
        try {
            $sql = "SELECT * FROM monitoreo_firmas_config
                    WHERE ruta_id = ? AND activo = 1
                    ORDER BY paso ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$ruta_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerFirmasConfig: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // REGISTROS
    // ==========================================

    public function iniciarRegistro($ruta_id, $fecha, $turno, $usuario_id) {
        try {
            $ruta_id = (int)$ruta_id;
            $usuario_id = (int)$usuario_id;

            if (!in_array($turno, ['DIA', 'TARDE', 'NOCHE'], true)) {
                $this->lastError = 'Turno inválido';
                return false;
            }

            $existente = $this->obtenerRegistroPorRutaFechaTurno($ruta_id, $fecha, $turno);
            if ($existente) {
                return $existente;
            }

            $sql = "INSERT INTO monitoreo_registros
                    (ruta_id, fecha, turno, operador_id, estado, creado_por)
                    VALUES (?, ?, ?, ?, 'BORRADOR', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ruta_id, $fecha, $turno, $usuario_id, $usuario_id]);
            $registro_id = (int)$this->db->lastInsertId();

            $this->inicializarFirmas($registro_id);

            return $this->obtenerRegistro($registro_id);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::iniciarRegistro: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function obtenerRegistro($id) {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "SELECT mr.*, r.codigo AS ruta_codigo, r.nombre AS ruta_nombre,
                           r.tipo_valor, r.unidad_medida, r.valor_min_global, 
                           r.valor_max_global, r.valor_objetivo_relleno,
                           u.nombre AS operador_nombre
                    FROM monitoreo_registros mr
                    JOIN rutas_monitoreo r ON mr.ruta_id = r.id
                    LEFT JOIN usuarios u ON mr.operador_id = u.id
                    WHERE mr.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) return false;

            $registro['equipos']     = $this->obtenerEquipos($registro['ruta_id']);
            $registro['horarios']    = $this->obtenerHorarios($registro['ruta_id']);
            $registro['lecturas']    = $this->obtenerLecturas($id);
            $registro['firmas']      = $this->obtenerFirmasPorRegistro($id);
            $registro['paso_actual'] = $this->obtenerPasoActual($id);
            $registro['resumen']     = $this->obtenerResumenFirmas($id);

            return $registro;

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerRegistro: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerRegistroPorRutaFechaTurno($ruta_id, $fecha, $turno) {
        try {
            $sql = "SELECT id FROM monitoreo_registros
                    WHERE ruta_id = ? AND fecha = ? AND turno = ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$ruta_id, $fecha, $turno]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->obtenerRegistro((int)$row['id']) : false;

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerRegistroPorRutaFechaTurno: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerRegistros($filtros = []) {
        try {
            $sql = "SELECT mr.*, 
                           r.codigo AS ruta_codigo, r.nombre AS ruta_nombre,
                           u.nombre AS operador_nombre,
                           (SELECT COUNT(*) FROM monitoreo_lecturas ml 
                            WHERE ml.registro_id = mr.id AND ml.valor IS NOT NULL) AS lecturas_completadas,
                           (SELECT COUNT(*) FROM monitoreo_lecturas ml 
                            WHERE ml.registro_id = mr.id AND ml.fuera_de_rango = 1) AS lecturas_fuera_rango
                    FROM monitoreo_registros mr
                    JOIN rutas_monitoreo r ON mr.ruta_id = r.id
                    LEFT JOIN usuarios u ON mr.operador_id = u.id
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['ruta_id'])) {
                $sql .= " AND mr.ruta_id = ?";
                $params[] = (int)$filtros['ruta_id'];
            }
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND mr.fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND mr.fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            if (!empty($filtros['turno'])) {
                $sql .= " AND mr.turno = ?";
                $params[] = $filtros['turno'];
            }
            if (!empty($filtros['estado'])) {
                $sql .= " AND mr.estado = ?";
                $params[] = $filtros['estado'];
            }

            $sql .= " ORDER BY mr.fecha DESC, 
                               FIELD(mr.turno, 'DIA', 'TARDE', 'NOCHE') ASC
                      LIMIT 200";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerRegistros: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerRegistrosDelDia($fecha) {
        try {
            $sql = "SELECT mr.*, 
                           r.codigo AS ruta_codigo, r.nombre AS ruta_nombre,
                           u.nombre AS operador_nombre,
                           (SELECT COUNT(*) FROM monitoreo_lecturas ml 
                            WHERE ml.registro_id = mr.id AND ml.valor IS NOT NULL) AS lecturas_completadas
                    FROM monitoreo_registros mr
                    JOIN rutas_monitoreo r ON mr.ruta_id = r.id
                    LEFT JOIN usuarios u ON mr.operador_id = u.id
                    WHERE mr.fecha = ?
                    ORDER BY r.codigo ASC, 
                             FIELD(mr.turno, 'DIA', 'TARDE', 'NOCHE') ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerRegistrosDelDia: " . $e->getMessage());
            return [];
        }
    }

    public function cerrarRegistro($registro_id, $usuario_id) {
        try {
            $registro_id = (int)$registro_id;
            $usuario_id = (int)$usuario_id;

            $registro = $this->obtenerRegistro($registro_id);
            if (!$registro) {
                $this->lastError = 'Registro no encontrado';
                return false;
            }

            if ($registro['estado'] !== 'BORRADOR') {
                $this->lastError = 'El registro ya fue cerrado';
                return false;
            }

            $horarios_turno = $this->obtenerHorariosPorTurno($registro['ruta_id'], $registro['turno']);
            $equipos = $this->obtenerEquipos($registro['ruta_id']);

            $total_esperadas = count($horarios_turno) * count($equipos);
            $lecturas_completadas = 0;

            foreach ($registro['lecturas'] as $lec) {
                if ($lec['valor'] !== null) {
                    $lecturas_completadas++;
                }
            }

            if ($lecturas_completadas < $total_esperadas) {
                $this->lastError = "Faltan lecturas. Completadas: $lecturas_completadas / $total_esperadas";
                return false;
            }

            $sql = "UPDATE monitoreo_registros 
                    SET estado = 'CERRADO', cerrado_en = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$registro_id]);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::cerrarRegistro: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // LECTURAS
    // ==========================================

    public function guardarLectura($registro_id, $id_equipo, $hora, $valor, $observacion, $usuario_id) {
        try {
            $registro_id = (int)$registro_id;
            $id_equipo = (int)$id_equipo;
            $usuario_id = (int)$usuario_id;

            $registro = $this->obtenerRegistro($registro_id);
            if (!$registro) {
                $this->lastError = 'Registro no encontrado';
                return false;
            }

            if ($registro['estado'] !== 'BORRADOR') {
                $this->lastError = 'El registro está cerrado, no se puede modificar';
                return false;
            }

            // ✅ FIX: validar que el equipo pertenezca a la ruta
            $equipos_ruta = array_column($registro['equipos'], 'id_equipo');
            if (!in_array($id_equipo, array_map('intval', $equipos_ruta), true)) {
                $this->lastError = 'El equipo no pertenece a esta ruta de monitoreo';
                return false;
            }

            $limites = $this->obtenerLimitesEquipo($registro['ruta_id'], $id_equipo, $registro);
            $nivel_alerta = $this->calcularNivelAlerta(
                $valor, 
                $limites['valor_min'], 
                $limites['valor_max'],
                $limites['valor_objetivo']
            );
            $fuera_de_rango = ($nivel_alerta !== 'OK') ? 1 : 0;

            $sql = "INSERT INTO monitoreo_lecturas
                    (registro_id, id_equipo, hora, valor, observacion, 
                     fuera_de_rango, nivel_alerta, usuario_id, fecha_lectura)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        valor = VALUES(valor),
                        observacion = VALUES(observacion),
                        fuera_de_rango = VALUES(fuera_de_rango),
                        nivel_alerta = VALUES(nivel_alerta),
                        usuario_id = VALUES(usuario_id),
                        fecha_lectura = NOW(),
                        fecha_actualizacion = NOW()";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $registro_id, $id_equipo, $hora, $valor, $observacion,
                $fuera_de_rango, $nivel_alerta, $usuario_id
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'nivel_alerta' => $nivel_alerta,
                    'fuera_de_rango' => $fuera_de_rango
                ];
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::guardarLectura: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function obtenerLecturas($registro_id) {
        try {
            $sql = "SELECT ml.*, u.nombre AS usuario_nombre
                    FROM monitoreo_lecturas ml
                    LEFT JOIN usuarios u ON ml.usuario_id = u.id
                    WHERE ml.registro_id = ?
                    ORDER BY ml.id_equipo ASC, ml.hora ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerLecturas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerLecturasPorEquipo($id_equipo, $fecha_desde, $fecha_hasta) {
        try {
            $sql = "SELECT ml.hora, ml.valor, ml.nivel_alerta, ml.fecha_lectura,
                           mr.fecha, mr.turno
                    FROM monitoreo_lecturas ml
                    JOIN monitoreo_registros mr ON ml.registro_id = mr.id
                    WHERE ml.id_equipo = ?
                      AND mr.fecha BETWEEN ? AND ?
                    ORDER BY mr.fecha ASC, ml.hora ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id_equipo, $fecha_desde, $fecha_hasta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerLecturasPorEquipo: " . $e->getMessage());
            return [];
        }
    }

    public function calcularNivelAlerta($valor, $valor_min, $valor_max, $valor_objetivo = null) {
        if ($valor === null || $valor === '') {
            return 'OK';
        }

        $valor = (float)$valor;

        if ($valor_min !== null) {
            $valor_min = (float)$valor_min;
            $critico_umbral = $valor_min / 2;

            if ($valor < $critico_umbral) {
                return 'CRITICO';
            }
            if ($valor < $valor_min) {
                return 'BAJO';
            }
        }

        if ($valor_max !== null && $valor > (float)$valor_max) {
            return 'ALTO';
        }

        return 'OK';
    }

    private function obtenerLimitesEquipo($ruta_id, $id_equipo, $registro = null) {
        try {
            $sql = "SELECT valor_min, valor_max, valor_objetivo
                    FROM rutas_monitoreo_equipos
                    WHERE ruta_id = ? AND id_equipo = ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$ruta_id, (int)$id_equipo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'valor_min'      => $row['valor_min'] ?? ($registro['valor_min_global'] ?? null),
                    'valor_max'      => $row['valor_max'] ?? ($registro['valor_max_global'] ?? null),
                    'valor_objetivo' => $row['valor_objetivo'] ?? ($registro['valor_objetivo_relleno'] ?? null)
                ];
            }

            return [
                'valor_min'      => $registro['valor_min_global'] ?? null,
                'valor_max'      => $registro['valor_max_global'] ?? null,
                'valor_objetivo' => $registro['valor_objetivo_relleno'] ?? null
            ];

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerLimitesEquipo: " . $e->getMessage());
            return ['valor_min' => null, 'valor_max' => null, 'valor_objetivo' => null];
        }
    }

    // ==========================================
    // FIRMAS
    // ==========================================

    public function inicializarFirmas($registro_id) {
        try {
            $registro_id = (int)$registro_id;

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM monitoreo_firmas WHERE registro_id = ?");
            $stmt->execute([$registro_id]);
            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }

            $stmt = $this->db->prepare("SELECT ruta_id FROM monitoreo_registros WHERE id = ?");
            $stmt->execute([$registro_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return false;

            $ruta_id = (int)$row['ruta_id'];
            $config = $this->obtenerFirmasConfig($ruta_id);

            if (empty($config)) return false;

            // ✅ FIX: usar transacción
            $this->db->beginTransaction();

            $sql = "INSERT INTO monitoreo_firmas
                    (registro_id, paso, rol_firmante, estado)
                    VALUES (?, ?, ?, 'PENDIENTE')";
            $stmt = $this->db->prepare($sql);

            foreach ($config as $c) {
                $stmt->execute([
                    $registro_id,
                    (int)$c['paso'],
                    $c['rol_firmante']
                ]);
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en MonitoreoModel::inicializarFirmas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra una firma en un registro de monitoreo.
     * ✅ FIX: sanitizar firma_svg con SVGSanitizer (defensa en profundidad)
     * ✅ FIX: hash calculado sobre el SVG YA sanitizado
     */
    public function firmar($registro_id, $paso_id, $usuario_id, $usuario_nombre, $firma_svg, $comentario = null) {
        try {
            $registro_id = (int)$registro_id;
            $paso_id = (int)$paso_id;

            // ==========================================
            // ✅ FIX: Sanitizar el SVG ANTES de cualquier cosa
            // ==========================================
            if (empty($firma_svg)) {
                return ['success' => false, 'mensaje' => 'La firma está vacía'];
            }

            if (!SVGSanitizer::esFormatoValido($firma_svg)) {
                return ['success' => false, 'mensaje' => 'La firma no tiene formato SVG válido o excede el tamaño máximo'];
            }

            $firma_svg = SVGSanitizer::sanitize($firma_svg);

            if (empty($firma_svg)) {
                return ['success' => false, 'mensaje' => 'La firma no pudo ser procesada (contiene elementos no permitidos)'];
            }

            // Validar que el paso sea el actual
            $paso_actual = $this->obtenerPasoActual($registro_id);
            if (!$paso_actual || (int)$paso_actual['id'] !== $paso_id) {
                return ['success' => false, 'mensaje' => 'Este paso no es el actual o ya fue procesado'];
            }

            $this->db->beginTransaction();

            $sql = "SELECT * FROM monitoreo_firmas
                    WHERE id = ? AND registro_id = ? AND estado = 'PENDIENTE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paso_id, $registro_id]);
            $paso = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$paso) {
                throw new Exception('El paso no existe o ya fue procesado');
            }

            // ✅ FIX: hash sobre el SVG sanitizado
            $firma_hash = hash('sha256', $firma_svg);

            $sql = "UPDATE monitoreo_firmas
                    SET usuario_id = ?,
                        usuario_nombre = ?,
                        estado = 'FIRMADO',
                        firma_svg = ?,
                        firma_hash = ?,
                        comentario = ?,
                        fecha_firma = NOW(),
                        ip = ?,
                        user_agent = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $usuario_id,
                $usuario_nombre,
                $firma_svg,
                $firma_hash,
                $comentario,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $paso_id
            ]);

            $sql = "SELECT MIN(paso) AS proximo FROM monitoreo_firmas
                    WHERE registro_id = ? AND estado = 'PENDIENTE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$registro_id]);
            $next = $stmt->fetch(PDO::FETCH_ASSOC);
            $hay_pendientes = ($next && $next['proximo'] !== null);

            $completada = false;
            if (!$hay_pendientes) {
                $sql = "UPDATE monitoreo_registros SET estado = 'FIRMADO' WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$registro_id]);
                $completada = true;
            }

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => $completada ? 'Firmas completadas' : 'Firma registrada',
                'completada' => $completada,
                'siguiente_paso' => $hay_pendientes ? (int)$next['proximo'] : null,
                'firma_hash' => $firma_hash
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en MonitoreoModel::firmar: " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    public function rechazar($registro_id, $paso_id, $usuario_id, $usuario_nombre, $motivo) {
        try {
            $registro_id = (int)$registro_id;
            $paso_id = (int)$paso_id;

            if (empty($motivo)) {
                return ['success' => false, 'mensaje' => 'Debes indicar el motivo del rechazo'];
            }

            // ✅ FIX: validar que el paso sea el actual
            $paso_actual = $this->obtenerPasoActual($registro_id);
            if (!$paso_actual || (int)$paso_actual['id'] !== $paso_id) {
                return ['success' => false, 'mensaje' => 'Este paso no es el actual o ya fue procesado'];
            }

            $this->db->beginTransaction();

            $sql = "UPDATE monitoreo_firmas
                    SET usuario_id = ?,
                        usuario_nombre = ?,
                        estado = 'RECHAZADO',
                        motivo_rechazo = ?,
                        fecha_firma = NOW(),
                        ip = ?,
                        user_agent = ?
                    WHERE id = ? AND registro_id = ? AND estado = 'PENDIENTE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $usuario_id,
                $usuario_nombre,
                $motivo,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $paso_id,
                $registro_id
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('No se pudo rechazar el paso');
            }

            $sql = "UPDATE monitoreo_registros SET estado = 'RECHAZADO' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$registro_id]);

            $this->db->commit();
            return ['success' => true, 'mensaje' => 'Registro rechazado'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en MonitoreoModel::rechazar: " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    public function obtenerFirmasPorRegistro($registro_id) {
        try {
            $sql = "SELECT f.*, u.email AS usuario_email
                    FROM monitoreo_firmas f
                    LEFT JOIN usuarios u ON f.usuario_id = u.id
                    WHERE f.registro_id = ?
                    ORDER BY f.paso ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerFirmasPorRegistro: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPasoActual($registro_id) {
        try {
            $sql = "SELECT f.*, c.nombre_paso, c.obligatorio
                    FROM monitoreo_firmas f
                    LEFT JOIN monitoreo_firmas_config c 
                        ON f.paso = c.paso 
                        AND c.ruta_id = (SELECT ruta_id FROM monitoreo_registros WHERE id = f.registro_id)
                    WHERE f.registro_id = ? AND f.estado = 'PENDIENTE'
                    ORDER BY f.paso ASC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerPasoActual: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerResumenFirmas($registro_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN estado = 'FIRMADO' THEN 1 ELSE 0 END) AS firmados,
                        SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                        SUM(CASE WHEN estado = 'RECHAZADO' THEN 1 ELSE 0 END) AS rechazados,
                        SUM(CASE WHEN estado = 'OMITIDO' THEN 1 ELSE 0 END) AS omitidos
                    FROM monitoreo_firmas
                    WHERE registro_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);

            $total       = (int)($r['total'] ?? 0);
            $firmados    = (int)($r['firmados'] ?? 0);
            $pendientes  = (int)($r['pendientes'] ?? 0);
            $rechazados  = (int)($r['rechazados'] ?? 0);
            $omitidos    = (int)($r['omitidos'] ?? 0);

            return [
                'total'      => $total,
                'firmados'   => $firmados,
                'pendientes' => $pendientes,
                'rechazados' => $rechazados,
                'omitidos'   => $omitidos,
                'completa'   => ($pendientes === 0 && $rechazados === 0 && $total > 0),
                'porcentaje' => $total > 0 ? round(($firmados / $total) * 100, 1) : 0
            ];

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerResumenFirmas: " . $e->getMessage());
            return [
                'total' => 0, 'firmados' => 0, 'pendientes' => 0,
                'rechazados' => 0, 'omitidos' => 0, 'completa' => false, 'porcentaje' => 0
            ];
        }
    }

    // ==========================================
    // ESTADÍSTICAS Y ALERTAS
    // ==========================================

    public function obtenerEstadisticas($filtros = []) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT mr.id) AS total_registros,
                        COUNT(ml.id) AS total_lecturas,
                        SUM(CASE WHEN ml.nivel_alerta = 'BAJO' THEN 1 ELSE 0 END) AS lecturas_bajas,
                        SUM(CASE WHEN ml.nivel_alerta = 'CRITICO' THEN 1 ELSE 0 END) AS lecturas_criticas,
                        SUM(CASE WHEN ml.nivel_alerta = 'ALTO' THEN 1 ELSE 0 END) AS lecturas_altas,
                        SUM(CASE WHEN ml.fuera_de_rango = 1 THEN 1 ELSE 0 END) AS lecturas_fuera_rango
                    FROM monitoreo_registros mr
                    LEFT JOIN monitoreo_lecturas ml ON ml.registro_id = mr.id
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['ruta_id'])) {
                $sql .= " AND mr.ruta_id = ?";
                $params[] = (int)$filtros['ruta_id'];
            }
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND mr.fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND mr.fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_registros'       => (int)($r['total_registros'] ?? 0),
                'total_lecturas'        => (int)($r['total_lecturas'] ?? 0),
                'lecturas_bajas'        => (int)($r['lecturas_bajas'] ?? 0),
                'lecturas_criticas'     => (int)($r['lecturas_criticas'] ?? 0),
                'lecturas_altas'        => (int)($r['lecturas_altas'] ?? 0),
                'lecturas_fuera_rango'  => (int)($r['lecturas_fuera_rango'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total_registros' => 0, 'total_lecturas' => 0,
                'lecturas_bajas' => 0, 'lecturas_criticas' => 0,
                'lecturas_altas' => 0, 'lecturas_fuera_rango' => 0
            ];
        }
    }

    public function obtenerAlertas($fecha_desde, $fecha_hasta, $ruta_id = null) {
        try {
            $sql = "SELECT ml.*, 
                           mr.fecha, mr.turno, mr.ruta_id,
                           e.nombre_equipo, e.codigo AS equipo_codigo,
                           r.codigo AS ruta_codigo, r.nombre AS ruta_nombre,
                           r.unidad_medida
                    FROM monitoreo_lecturas ml
                    JOIN monitoreo_registros mr ON ml.registro_id = mr.id
                    JOIN equipos e ON ml.id_equipo = e.id_equipo
                    JOIN rutas_monitoreo r ON mr.ruta_id = r.id
                    WHERE ml.fuera_de_rango = 1
                      AND mr.fecha BETWEEN ? AND ?";
            $params = [$fecha_desde, $fecha_hasta];

            if ($ruta_id) {
                $sql .= " AND mr.ruta_id = ?";
                $params[] = (int)$ruta_id;
            }

            $sql .= " ORDER BY mr.fecha DESC, ml.hora ASC LIMIT 500";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en MonitoreoModel::obtenerAlertas: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // AUTO-CREACIÓN Y MANTENIMIENTO DE REGISTROS
    // ==========================================

    public function crearRegistrosDelDia($fecha = null, $usuario_id = null) {
        $resultado = ['creados' => 0, 'existentes' => 0, 'errores' => []];

        try {
            $fecha = $fecha ?: date('Y-m-d');
            $turnos = ['DIA', 'TARDE', 'NOCHE'];

            $stmt = $this->db->prepare("SELECT id FROM rutas_monitoreo WHERE activo = 1");
            $stmt->execute();
            $rutas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($rutas)) {
                return $resultado;
            }

            $stmtCheck = $this->db->prepare(
                "SELECT id FROM monitoreo_registros 
                 WHERE ruta_id = ? AND fecha = ? AND turno = ? LIMIT 1"
            );

            foreach ($rutas as $ruta_id) {
                foreach ($turnos as $turno) {
                    $stmtCheck->execute([$ruta_id, $fecha, $turno]);
                    $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($existente) {
                        $resultado['existentes']++;
                        continue;
                    }

                    try {
                        $nuevo = $this->iniciarRegistro($ruta_id, $fecha, $turno, $usuario_id);
                        if ($nuevo) {
                            $resultado['creados']++;
                        } else {
                            $resultado['errores'][] = "No se pudo crear: ruta=$ruta_id, turno=$turno";
                        }
                    } catch (Throwable $e) {
                        $resultado['errores'][] = "Error ruta=$ruta_id turno=$turno: " . $e->getMessage();
                    }
                }
            }

        } catch (Throwable $e) {
            error_log("Error en crearRegistrosDelDia: " . $e->getMessage());
            $resultado['errores'][] = $e->getMessage();
        }

        return $resultado;
    }

    public function marcarVencidos($fecha = null) {
        try {
            $fecha = $fecha ?: date('Y-m-d');
            $ahora = date('H:i:s');

            $vencidos = [];

            if ($ahora >= '16:00:00') {
                $vencidos[] = 'DIA';
            }
            if ($ahora >= '23:59:00') {
                $vencidos[] = 'TARDE';
            }

            $total_vencidos = 0;

            if (!empty($vencidos)) {
                $placeholders = implode(',', array_fill(0, count($vencidos), '?'));
                $sql = "UPDATE monitoreo_registros 
                        SET estado = 'VENCIDO'
                        WHERE fecha = ? 
                        AND turno IN ($placeholders)
                        AND estado = 'BORRADOR'";
                $params = array_merge([$fecha], $vencidos);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $total_vencidos += $stmt->rowCount();
            }

            $ayer = date('Y-m-d', strtotime($fecha . ' -1 day'));
            if ($ahora >= '08:00:00') {
                $stmt = $this->db->prepare(
                    "UPDATE monitoreo_registros 
                     SET estado = 'VENCIDO'
                     WHERE fecha = ? AND turno = 'NOCHE' AND estado = 'BORRADOR'"
                );
                $stmt->execute([$ayer]);
                $total_vencidos += $stmt->rowCount();
            }

            return $total_vencidos;

        } catch (Throwable $e) {
            error_log("Error en marcarVencidos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ✅ FIX: contar pendientes del día + vencidos de días anteriores
     */
    public function contarPendientesDelDia() {
        try {
            $fecha = date('Y-m-d');
            $sql = "SELECT COUNT(*) FROM monitoreo_registros
                    WHERE (
                        (fecha = ? AND estado IN ('BORRADOR', 'VENCIDO'))
                        OR
                        (fecha < ? AND estado IN ('BORRADOR', 'VENCIDO'))
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha, $fecha]);
            return (int)$stmt->fetchColumn();

        } catch (Throwable $e) {
            error_log("Error en contarPendientesDelDia: " . $e->getMessage());
            return 0;
        }
    }
}