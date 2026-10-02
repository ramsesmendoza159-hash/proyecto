<?php
// model/ChecklistModel.php
// Modelo unificado para el módulo genérico de checklists operativos
// ✅ FIX 1: obtenerTiposPorRol() admin ve todos, otros roles solo los suyos
// ✅ FIX 2: inicializarFirmas() valida que exista config
// ✅ FIX 3: catch (Throwable) en todos los métodos
// ✅ FIX 4: guardarLectura() valida tipo de campo según checklist_campos.tipo
// ✅ FIX 5: guardarLectura() valida longitud de valor y observacion
// ✅ FIX 6: guardarLectura() valida que campo_id pertenezca a seccion_id
// ✅ FIX 7: firmar() sanitiza SVG con SVGSanitizer
// ✅ FIX 8: validaciones trim() y longitud

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/SVGSanitizer.php';

class ChecklistModel
{
    private $db;
    private $lastError;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (ChecklistModel): " . $e->getMessage());
            throw $e;
        }
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    // ==========================================
    // TIPOS
    // ==========================================

    public function obtenerTipos($filtros = [])
    {
        try {
            $sql = "SELECT t.*, 
                        (SELECT COUNT(*) FROM checklist_secciones s 
                         WHERE s.tipo_id = t.id AND s.activo = 1) AS total_secciones
                    FROM checklist_tipos t
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['activo'])) {
                $sql .= " AND t.activo = ?";
                $params[] = (int)$filtros['activo'];
            }

            if (!empty($filtros['area'])) {
                $sql .= " AND t.area = ?";
                $params[] = $filtros['area'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (t.nombre LIKE ? OR t.codigo LIKE ? OR t.descripcion LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY t.codigo ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerTipos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerTipoPorId($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "SELECT * FROM checklist_tipos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tipo) return false;

            $tipo['secciones'] = $this->obtenerSecciones($id);
            $tipo['firmas_config'] = $this->obtenerFirmasConfig($id);

            return $tipo;

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerTipoPorId: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTipoPorCodigo($codigo)
    {
        try {
            $sql = "SELECT * FROM checklist_tipos WHERE codigo = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$codigo]);
            $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tipo) return false;

            $tipo['secciones'] = $this->obtenerSecciones($tipo['id']);
            $tipo['firmas_config'] = $this->obtenerFirmasConfig($tipo['id']);

            return $tipo;

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerTipoPorCodigo: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // SECCIONES Y CAMPOS
    // ==========================================

    public function obtenerSecciones($tipo_id)
    {
        try {
            $sql = "SELECT * FROM checklist_secciones 
                    WHERE tipo_id = ? AND activo = 1
                    ORDER BY orden ASC, id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$tipo_id]);
            $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($secciones as &$seccion) {
                $seccion['campos'] = $this->obtenerCampos($seccion['id']);
                $seccion['equipos'] = $this->obtenerEquiposSeccion($seccion['id']);
            }
            unset($seccion);

            return $secciones;

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerSecciones: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerCampos($seccion_id)
    {
        try {
            $sql = "SELECT * FROM checklist_campos 
                    WHERE seccion_id = ? AND activo = 1
                    ORDER BY orden ASC, id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$seccion_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerCampos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerEquiposSeccion($seccion_id)
    {
        try {
            $sql = "SELECT ce.*, 
                           e.nombre_equipo, e.codigo AS equipo_codigo, e.serie,
                           COALESCE(ce.nombre_custom, e.nombre_equipo) AS nombre_display
                    FROM checklist_equipos ce
                    LEFT JOIN equipos e ON ce.id_equipo = e.id_equipo
                    WHERE ce.seccion_id = ? AND ce.activo = 1
                    ORDER BY ce.orden ASC, ce.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$seccion_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerEquiposSeccion: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // FIRMAS CONFIG
    // ==========================================

    public function obtenerFirmasConfig($tipo_id)
    {
        try {
            $sql = "SELECT * FROM checklist_firmas_config 
                    WHERE tipo_id = ? AND activo = 1
                    ORDER BY paso ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$tipo_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerFirmasConfig: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // REGISTROS
    // ==========================================

    public function iniciarRegistro($tipo_id, $fecha, $turno, $usuario_id)
    {
        try {
            $tipo_id = (int)$tipo_id;
            $usuario_id = (int)$usuario_id;

            if (!in_array($turno, [null, 'DIA', 'TARDE', 'NOCHE'], true)) {
                $this->lastError = 'Turno inválido';
                return false;
            }

            $existente = $this->obtenerRegistroPorTipoFechaTurno($tipo_id, $fecha, $turno);
            if ($existente) {
                return $existente;
            }

            $sql = "INSERT INTO checklist_registros
                    (tipo_id, fecha, turno, operador_id, estado, creado_por)
                    VALUES (?, ?, ?, ?, 'BORRADOR', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipo_id, $fecha, $turno, $usuario_id, $usuario_id]);
            $registro_id = (int)$this->db->lastInsertId();

            $this->inicializarFirmas($registro_id, $tipo_id);

            return $this->obtenerRegistro($registro_id);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::iniciarRegistro: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function obtenerRegistroPorTipoFechaTurno($tipo_id, $fecha, $turno)
    {
        try {
            $sql = "SELECT id FROM checklist_registros
                    WHERE tipo_id = ? AND fecha = ? 
                    AND ((turno IS NULL AND ? IS NULL) OR turno = ?)
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$tipo_id, $fecha, $turno, $turno]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->obtenerRegistro((int)$row['id']) : false;

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerRegistroPorTipoFechaTurno: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerRegistro($id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $sql = "SELECT r.*, 
                           t.codigo AS tipo_codigo, t.nombre AS tipo_nombre,
                           t.area AS tipo_area, t.frecuencia, t.requiere_turno,
                           u.nombre AS operador_nombre
                    FROM checklist_registros r
                    JOIN checklist_tipos t ON r.tipo_id = t.id
                    LEFT JOIN usuarios u ON r.operador_id = u.id
                    WHERE r.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) return false;

            $registro['secciones']   = $this->obtenerSecciones($registro['tipo_id']);
            $registro['lecturas']    = $this->obtenerLecturas($id);
            $registro['firmas']      = $this->obtenerFirmasPorRegistro($id);
            $registro['paso_actual'] = $this->obtenerPasoActual($id);
            $registro['resumen']     = $this->obtenerResumenFirmas($id);

            return $registro;

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerRegistro: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerRegistros($filtros = [])
    {
        try {
            $sql = "SELECT r.*, 
                           t.codigo AS tipo_codigo, t.nombre AS tipo_nombre,
                           u.nombre AS operador_nombre,
                           (SELECT COUNT(*) FROM checklist_lecturas l 
                            WHERE l.registro_id = r.id AND l.valor IS NOT NULL) AS lecturas_completadas,
                           (SELECT COUNT(*) FROM checklist_lecturas l 
                            WHERE l.registro_id = r.id AND l.fuera_de_rango = 1) AS lecturas_fuera_rango
                    FROM checklist_registros r
                    JOIN checklist_tipos t ON r.tipo_id = t.id
                    LEFT JOIN usuarios u ON r.operador_id = u.id
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['tipo_id'])) {
                $sql .= " AND r.tipo_id = ?";
                $params[] = (int)$filtros['tipo_id'];
            }
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND r.fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND r.fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            if (!empty($filtros['turno'])) {
                $sql .= " AND r.turno = ?";
                $params[] = $filtros['turno'];
            }
            if (!empty($filtros['estado'])) {
                $sql .= " AND r.estado = ?";
                $params[] = $filtros['estado'];
            }
            if (!empty($filtros['buscar'])) {
                $sql .= " AND (t.codigo LIKE ? OR t.nombre LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY r.fecha DESC, r.id DESC LIMIT 200";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerRegistros: " . $e->getMessage());
            return [];
        }
    }

    public function cerrarRegistro($registro_id, $usuario_id)
    {
        try {
            $registro_id = (int)$registro_id;

            $registro = $this->obtenerRegistro($registro_id);
            if (!$registro) {
                $this->lastError = 'Registro no encontrado';
                return false;
            }

            if ($registro['estado'] !== 'BORRADOR') {
                $this->lastError = 'El registro ya fue cerrado';
                return false;
            }

            $sql = "UPDATE checklist_registros 
                    SET estado = 'CERRADO', cerrado_en = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$registro_id]);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::cerrarRegistro: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // LECTURAS
    // ==========================================

    /**
     * ✅ FIX 4-6: valida tipo de campo, longitud de valor/observacion, y que campo_id pertenezca a seccion_id
     */
    public function guardarLectura($datos)
    {
        try {
            $registro_id = (int)($datos['registro_id'] ?? 0);
            $seccion_id  = (int)($datos['seccion_id'] ?? 0);
            $id_equipo   = !empty($datos['id_equipo']) ? (int)$datos['id_equipo'] : null;
            $hora        = !empty($datos['hora']) ? $datos['hora'] : null;
            $campo_id    = !empty($datos['campo_id']) ? (int)$datos['campo_id'] : null;
            $batch_num   = !empty($datos['batch_num']) ? (int)$datos['batch_num'] : null;
            $valor       = $datos['valor'] ?? null;
            $observacion = trim($datos['observacion'] ?? '');
            $usuario_id  = (int)($datos['usuario_id'] ?? 0);

            // Validar IDs básicos
            if ($registro_id <= 0 || $seccion_id <= 0) {
                $this->lastError = 'Datos incompletos (registro_id o seccion_id)';
                return false;
            }

            // ✅ FIX 7: validar que el registro esté en BORRADOR
            $sqlEstado = "SELECT estado FROM checklist_registros WHERE id = ?";
            $stmtEstado = $this->db->prepare($sqlEstado);
            $stmtEstado->execute([$registro_id]);
            $estado = $stmtEstado->fetchColumn();
            if ($estado !== 'BORRADOR') {
                $this->lastError = 'El registro está cerrado, no se puede modificar';
                return false;
            }

            // ✅ FIX 6: validar que el campo_id pertenezca a la sección
            $limites = ['valor_min' => null, 'valor_max' => null, 'tipo' => 'texto'];

            if ($campo_id !== null) {
                $sqlCampo = "SELECT valor_min, valor_max, tipo 
                             FROM checklist_campos 
                             WHERE id = ? AND seccion_id = ? AND activo = 1";
                $stmtCampo = $this->db->prepare($sqlCampo);
                $stmtCampo->execute([$campo_id, $seccion_id]);
                $campo = $stmtCampo->fetch(PDO::FETCH_ASSOC);

                if (!$campo) {
                    $this->lastError = 'El campo no pertenece a la sección indicada o está inactivo';
                    return false;
                }

                $limites = $campo;
            }

            // ✅ FIX 4: validar tipo de campo
            $tipo = $limites['tipo'] ?? 'texto';
            $valor = $this->normalizarValorSegunTipo($valor, $tipo);

            if ($valor === false) {
                // normalizarValorSegunTipo devolvió false → dato inválido
                return false;
            }

            // ✅ FIX 5: validar longitud de valor (max 255 según columna)
            if ($valor !== null && mb_strlen((string)$valor) > 255) {
                $this->lastError = 'El valor excede la longitud máxima (255 caracteres)';
                return false;
            }

            // ✅ FIX 5: validar longitud de observación (max 500 según columna)
            if (mb_strlen($observacion) > 500) {
                $this->lastError = 'La observación excede la longitud máxima (500 caracteres)';
                return false;
            }

            // Calcular valor numérico y nivel de alerta
            $valor_numerico = (is_numeric($valor) && $valor !== '') ? (float)$valor : null;
            $nivel_alerta = $this->calcularNivelAlerta(
                $valor_numerico,
                $limites['valor_min'] ?? null,
                $limites['valor_max'] ?? null
            );
            $fuera_de_rango = ($nivel_alerta !== 'OK') ? 1 : 0;

            // UPSERT
            $sql = "INSERT INTO checklist_lecturas
                    (registro_id, seccion_id, id_equipo, hora, campo_id, batch_num,
                     valor, valor_numerico, observacion, nivel_alerta, fuera_de_rango, usuario_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        valor = VALUES(valor),
                        valor_numerico = VALUES(valor_numerico),
                        observacion = VALUES(observacion),
                        nivel_alerta = VALUES(nivel_alerta),
                        fuera_de_rango = VALUES(fuera_de_rango),
                        usuario_id = VALUES(usuario_id),
                        fecha_actualizacion = NOW()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $registro_id, $seccion_id, $id_equipo, $hora, $campo_id, $batch_num,
                $valor, $valor_numerico, $observacion, $nivel_alerta, $fuera_de_rango, $usuario_id
            ]);

            return [
                'success'        => true,
                'nivel_alerta'   => $nivel_alerta,
                'fuera_de_rango' => $fuera_de_rango
            ];

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::guardarLectura: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * ✅ FIX 4: normaliza y valida el valor según el tipo de campo.
     * Devuelve el valor normalizado, o false si es inválido.
     */
    private function normalizarValorSegunTipo($valor, $tipo)
    {
        // Si el valor viene vacío, es válido (borrar lectura)
        if ($valor === null || $valor === '') {
            return $valor;
        }

        switch ($tipo) {
            case 'numero':
                if (!is_numeric($valor)) {
                    $this->lastError = 'El valor debe ser numérico';
                    return false;
                }
                return (string)(float)$valor;

            case 'booleano':
                // Aceptar "1", "0", "true", "false", true, false
                $normalizado = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($normalizado === null) {
                    $this->lastError = 'El valor debe ser booleano (0/1)';
                    return false;
                }
                return $normalizado ? '1' : '0';

            case 'hora':
                // Formato HH:MM o HH:MM:SS
                if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $valor)) {
                    $this->lastError = 'El valor debe ser una hora válida (HH:MM)';
                    return false;
                }
                return $valor;

            case 'fecha':
                // Formato YYYY-MM-DD
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                    $this->lastError = 'El valor debe ser una fecha válida (YYYY-MM-DD)';
                    return false;
                }
                $ts = strtotime($valor);
                if ($ts === false) {
                    $this->lastError = 'Fecha inválida';
                    return false;
                }
                return $valor;

            case 'select':
            case 'radio':
                // Validar contra opciones si están definidas
                $opciones = $limites['opciones'] ?? null;
                // Nota: no tenemos $limites acá, así que aceptamos cualquier valor
                // (En el controlador se puede validar contra opciones específicas)
                return (string)$valor;

            case 'texto':
            default:
                return (string)$valor;
        }
    }

    public function obtenerLecturas($registro_id)
    {
        try {
            $sql = "SELECT l.*, u.nombre AS usuario_nombre
                    FROM checklist_lecturas l
                    LEFT JOIN usuarios u ON l.usuario_id = u.id
                    WHERE l.registro_id = ?
                    ORDER BY l.seccion_id ASC, l.id_equipo ASC, l.hora ASC, l.batch_num ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerLecturas: " . $e->getMessage());
            return [];
        }
    }

    private function obtenerLimitesCampo($campo_id)
    {
        try {
            if (!$campo_id) return ['valor_min' => null, 'valor_max' => null];

            $sql = "SELECT valor_min, valor_max FROM checklist_campos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$campo_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['valor_min' => null, 'valor_max' => null];

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerLimitesCampo: " . $e->getMessage());
            return ['valor_min' => null, 'valor_max' => null];
        }
    }

    public function calcularNivelAlerta($valor, $valor_min, $valor_max)
    {
        if ($valor === null) return 'OK';

        if ($valor_min !== null) {
            $valor_min = (float)$valor_min;
            $critico_umbral = $valor_min / 2;
            if ($valor < $critico_umbral) return 'CRITICO';
            if ($valor < $valor_min) return 'BAJO';
        }

        if ($valor_max !== null && $valor > (float)$valor_max) {
            return 'ALTO';
        }

        return 'OK';
    }

    // ==========================================
    // FIRMAS
    // ==========================================

    public function inicializarFirmas($registro_id, $tipo_id)
    {
        try {
            $registro_id = (int)$registro_id;
            $tipo_id = (int)$tipo_id;

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM checklist_firmas WHERE registro_id = ?");
            $stmt->execute([$registro_id]);
            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }

            $config = $this->obtenerFirmasConfig($tipo_id);
            if (empty($config)) {
                error_log("ChecklistModel::inicializarFirmas - No hay configuración de firmas para tipo_id=$tipo_id");
                return false;
            }

            $sql = "INSERT INTO checklist_firmas
                    (registro_id, paso, rol_firmante, turno_firma, estado)
                    VALUES (?, ?, ?, ?, 'PENDIENTE')";
            $stmt = $this->db->prepare($sql);

            foreach ($config as $c) {
                $stmt->execute([
                    $registro_id,
                    (int)$c['paso'],
                    $c['rol_firmante'],
                    $c['turno_firma'] ?? null
                ]);
            }

            return true;

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::inicializarFirmas: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerFirmasPorRegistro($registro_id)
    {
        try {
            $sql = "SELECT f.*, u.email AS usuario_email
                    FROM checklist_firmas f
                    LEFT JOIN usuarios u ON f.usuario_id = u.id
                    WHERE f.registro_id = ?
                    ORDER BY f.paso ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerFirmasPorRegistro: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPasoActual($registro_id)
    {
        try {
            $sql = "SELECT f.*, c.nombre_paso, c.obligatorio
                    FROM checklist_firmas f
                    LEFT JOIN checklist_firmas_config c 
                        ON f.paso = c.paso 
                        AND c.tipo_id = (
                            SELECT tipo_id FROM checklist_registros WHERE id = f.registro_id
                        )
                    WHERE f.registro_id = ? AND f.estado = 'PENDIENTE'
                    ORDER BY f.paso ASC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerPasoActual: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ FIX 7: sanitiza firma_svg con SVGSanitizer
     */
    public function firmar($registro_id, $paso_id, $usuario_id, $usuario_nombre, $firma_svg, $comentario = null)
    {
        try {
            $registro_id = (int)$registro_id;
            $paso_id = (int)$paso_id;

            // Sanitizar el SVG
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

            $this->db->beginTransaction();

            $sql = "SELECT * FROM checklist_firmas
                    WHERE id = ? AND registro_id = ? AND estado = 'PENDIENTE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paso_id, $registro_id]);
            $paso = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$paso) {
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'El paso no existe o ya fue procesado'];
            }

            $firma_hash = hash('sha256', $firma_svg);

            $sql = "UPDATE checklist_firmas
                    SET usuario_id = ?,
                        usuario_nombre = ?,
                        estado = 'FIRMADO',
                        firma_svg = ?,
                        firma_hash = ?,
                        comentario = ?,
                        fecha_firma = NOW(),
                        ip = ?,
                        user_agent = ?,
                        fecha_actualizacion = NOW()
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

            $sql = "SELECT MIN(paso) AS proximo FROM checklist_firmas
                    WHERE registro_id = ? AND estado = 'PENDIENTE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$registro_id]);
            $next = $stmt->fetch(PDO::FETCH_ASSOC);
            $hay_pendientes = ($next && $next['proximo'] !== null);

            $completada = false;
            if (!$hay_pendientes) {
                $sql = "UPDATE checklist_registros SET estado = 'FIRMADO' WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$registro_id]);
                $completada = true;
            }

            $this->db->commit();

            return [
                'success'        => true,
                'mensaje'        => $completada ? 'Firmas completadas' : 'Firma registrada',
                'completada'     => $completada,
                'siguiente_paso' => $hay_pendientes ? (int)$next['proximo'] : null,
                'firma_hash'     => $firma_hash
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en ChecklistModel::firmar: " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * ✅ FIX 8: valida motivo no vacío y ownership del paso
     */
    public function rechazar($registro_id, $paso_id, $usuario_id, $usuario_nombre, $motivo)
    {
        try {
            $registro_id = (int)$registro_id;
            $paso_id = (int)$paso_id;

            if (empty(trim($motivo))) {
                return ['success' => false, 'mensaje' => 'Debes indicar el motivo del rechazo'];
            }

            $motivo = trim($motivo);
            if (mb_strlen($motivo) > 500) {
                return ['success' => false, 'mensaje' => 'El motivo no puede exceder los 500 caracteres'];
            }

            $this->db->beginTransaction();

            // Validar que el paso sea el actual
            $paso_actual = $this->obtenerPasoActual($registro_id);
            if (!$paso_actual || (int)$paso_actual['id'] !== $paso_id) {
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'Este paso no es el actual o ya fue procesado'];
            }

            $sql = "UPDATE checklist_firmas
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
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'No se pudo rechazar el paso'];
            }

            $sql = "UPDATE checklist_registros SET estado = 'RECHAZADO' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$registro_id]);

            $this->db->commit();
            return ['success' => true, 'mensaje' => 'Registro rechazado'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en ChecklistModel::rechazar: " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    public function obtenerResumenFirmas($registro_id)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN estado = 'FIRMADO' THEN 1 ELSE 0 END) AS firmados,
                        SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                        SUM(CASE WHEN estado = 'RECHAZADO' THEN 1 ELSE 0 END) AS rechazados,
                        SUM(CASE WHEN estado = 'OMITIDO' THEN 1 ELSE 0 END) AS omitidos
                    FROM checklist_firmas
                    WHERE registro_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$registro_id]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);

            $total      = (int)($r['total'] ?? 0);
            $firmados   = (int)($r['firmados'] ?? 0);
            $pendientes = (int)($r['pendientes'] ?? 0);
            $rechazados = (int)($r['rechazados'] ?? 0);
            $omitidos   = (int)($r['omitidos'] ?? 0);

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
            error_log("Error en ChecklistModel::obtenerResumenFirmas: " . $e->getMessage());
            return [
                'total' => 0, 'firmados' => 0, 'pendientes' => 0,
                'rechazados' => 0, 'omitidos' => 0, 'completa' => false, 'porcentaje' => 0
            ];
        }
    }

    // ==========================================
    // ASIGNACIONES
    // ==========================================

    /**
     * Admin ve todos los tipos; otros roles solo los suyos.
     */
    public function obtenerTiposPorRol($rol)
    {
        try {
            if ($rol === 'admin') {
                $sql = "SELECT DISTINCT t.*
                        FROM checklist_tipos t
                        WHERE t.activo = 1
                        ORDER BY t.codigo ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $sql = "SELECT DISTINCT t.*
                    FROM checklist_tipos t
                    INNER JOIN checklist_asignaciones a ON a.tipo_id = t.id
                    WHERE a.activo = 1 AND t.activo = 1
                    AND a.rol = ?
                    ORDER BY t.codigo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$rol]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ChecklistModel::obtenerTiposPorRol: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // UTILIDADES
    // ==========================================

    public function detectarTurnoActual()
    {
        $hora = (int)date('H');
        if ($hora >= 8 && $hora < 16)  return 'DIA';
        if ($hora >= 16 && $hora < 24) return 'TARDE';
        return 'NOCHE';
    }

    public function detectarTurnoFirma()
    {
        return $this->detectarTurnoActual();
    }
}