<?php
// model/FirmaModel.php
// Modelo de firmas secuenciales de órdenes
// ✅ FIX: sanitizar firma_svg con SVGSanitizer (defensa en profundidad)
// ✅ FIX: hash calculado sobre el SVG YA sanitizado

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/FirmaSecuenciaHelper.php';
require_once __DIR__ . '/../helpers/SVGSanitizer.php';

class FirmaModel {

    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (FirmaModel): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // INICIALIZAR FIRMAS
    // ==========================================

    /**
     * Inicializa las firmas de una orden según la secuencia configurada.
     * ✅ FIX 3: usa transacción + FOR UPDATE para evitar duplicados en concurrencia.
     *
     * @param int $orden_id
     * @param array $pasos_a_omitir Números de paso que se marcarán como OMITIDO
     * @return bool
     */
    public function inicializarFirmas($orden_id, $pasos_a_omitir = []) {
        try {
            $orden_id = (int)$orden_id;
            if ($orden_id <= 0) return false;

            $this->db->beginTransaction();

            // Bloquear la orden para evitar race condition
            $stmtLock = $this->db->prepare("SELECT id FROM ordenes_mantenimiento WHERE id = ? FOR UPDATE");
            $stmtLock->execute([$orden_id]);

            // Verificar si ya tiene firmas
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM firmas_orden WHERE orden_id = ?");
            $stmt->execute([$orden_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (($result['total'] ?? 0) > 0) {
                $this->db->commit();
                return true;
            }

            $secuencia = FirmaSecuenciaHelper::obtenerSecuencia();

            if (empty($secuencia)) {
                $this->db->rollBack();
                return false;
            }

            $pasos_a_omitir = array_map('intval', $pasos_a_omitir);

            $sql = "INSERT INTO firmas_orden 
                    (orden_id, paso, rol_firmante, estado, fecha_creacion) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);

            foreach ($secuencia as $paso) {
                $numero_paso = (int)$paso['paso'];

                if (in_array($numero_paso, $pasos_a_omitir, true)) {
                    $estado = 'OMITIDO';
                } else {
                    $estado = 'PENDIENTE';
                }

                $stmt->execute([
                    $orden_id,
                    $numero_paso,
                    $paso['rol_firmante'],
                    $estado
                ]);
            }

            // Actualizar la orden con el primer paso pendiente
            $sqlFirst = "SELECT MIN(paso) as primer_paso 
                         FROM firmas_orden 
                         WHERE orden_id = ? AND estado = 'PENDIENTE'";
            $stmtFirst = $this->db->prepare($sqlFirst);
            $stmtFirst->execute([$orden_id]);
            $firstResult = $stmtFirst->fetch(PDO::FETCH_ASSOC);
            $primer_paso = (int)($firstResult['primer_paso'] ?? 1);

            $sqlUpdate = "UPDATE ordenes_mantenimiento 
                          SET firma_paso_actual = ?, 
                              firma_estado = 'EN_PROCESO' 
                          WHERE id = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$primer_paso, $orden_id]);

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en FirmaModel::inicializarFirmas: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // OBTENER FIRMAS
    // ==========================================

    public function obtenerFirmasPorOrden($orden_id) {
        try {
            $sql = "SELECT f.*, u.email as usuario_email
                    FROM firmas_orden f
                    LEFT JOIN usuarios u ON f.usuario_id = u.id
                    WHERE f.orden_id = ?
                    ORDER BY f.paso ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$orden_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en FirmaModel::obtenerFirmasPorOrden: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener una firma específica por ID.
     */
    public function obtenerFirmaPorId($id) {
        try {
            $sql = "SELECT f.*, u.email as usuario_email
                    FROM firmas_orden f
                    LEFT JOIN usuarios u ON f.usuario_id = u.id
                    WHERE f.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("Error en FirmaModel::obtenerFirmaPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Filtra por c.activo = 1 para evitar traer configs antiguas.
     */
    public function obtenerPasoActual($orden_id) {
        try {
            $sql = "SELECT f.*, 
                           COALESCE(c.nombre_paso, CONCAT('Paso ', f.paso)) as nombre_paso,
                           COALESCE(c.obligatorio, 1) as obligatorio
                    FROM firmas_orden f
                    LEFT JOIN firma_secuencia_config c 
                        ON f.paso = c.paso 
                        AND c.activo = 1
                    WHERE f.orden_id = ? 
                    AND f.estado = 'PENDIENTE'
                    ORDER BY f.paso ASC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$orden_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en FirmaModel::obtenerPasoActual: " . $e->getMessage());
            return false;
        }
    }

    public function puedeFirmar($orden_id, $usuario_id, $rol) {
        $pasoActual = $this->obtenerPasoActual($orden_id);

        if (!$pasoActual) {
            return ['puede' => false, 'mensaje' => 'No hay pasos pendientes'];
        }

        if ($pasoActual['rol_firmante'] !== $rol) {
            return [
                'puede' => false,
                'mensaje' => "El paso actual requiere el rol: {$pasoActual['rol_firmante']}"
            ];
        }

        if ($pasoActual['estado'] !== 'PENDIENTE') {
            return ['puede' => false, 'mensaje' => 'Este paso ya fue procesado'];
        }

        return ['puede' => true, 'paso' => $pasoActual];
    }

    // ==========================================
    // FIRMAR
    // ==========================================

    /**
     * Registra una firma.
     * ✅ FIX 1: sanitiza el SVG con SVGSanitizer (defensa en profundidad)
     * ✅ FIX 2: el hash se calcula sobre el SVG YA sanitizado
     *
     * @param int $orden_id
     * @param int $paso_id
     * @param int $usuario_id
     * @param string $usuario_nombre
     * @param string|null $firma_svg
     * @param string|null $comentario
     * @return array ['success' => bool, 'mensaje' => string, ...]
     */
    public function firmar($orden_id, $paso_id, $usuario_id, $usuario_nombre, $firma_svg = null, $comentario = null) {
        try {
            $orden_id = (int)$orden_id;
            $paso_id  = (int)$paso_id;

            // ==========================================
            // ✅ FIX: Sanitizar el SVG ANTES de cualquier cosa
            // ==========================================
            if (!empty($firma_svg)) {
                // Validación básica de formato
                if (!SVGSanitizer::esFormatoValido($firma_svg)) {
                    return ['success' => false, 'mensaje' => 'La firma no tiene formato SVG válido o excede el tamaño máximo'];
                }

                // Sanitizar
                $firma_svg = SVGSanitizer::sanitize($firma_svg);

                if (empty($firma_svg)) {
                    return ['success' => false, 'mensaje' => 'La firma no pudo ser procesada (contiene elementos no permitidos)'];
                }
            }

            $this->db->beginTransaction();

            // Validar que el paso sea el actual
            $paso_actual = $this->obtenerPasoActual($orden_id);
            if (!$paso_actual || (int)$paso_actual['id'] !== $paso_id) {
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'Este paso no es el actual o ya fue procesado'];
            }

            // Validar el rol del usuario
            $rol_usuario = $_SESSION['rol'] ?? '';
            if ($rol_usuario !== $paso_actual['rol_firmante']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'mensaje' => "No tienes el rol requerido para este paso (se requiere: {$paso_actual['rol_firmante']})"
                ];
            }

            // Verificar que el paso existe y está pendiente
            $sql = "SELECT * FROM firmas_orden 
                    WHERE id = ? AND orden_id = ? AND estado = 'PENDIENTE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paso_id, $orden_id]);
            $paso = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$paso) {
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'El paso no existe o ya fue procesado'];
            }

            // Usar firma del perfil si no viene firma SVG
            $uso_firma_perfil = false;

            if (empty($firma_svg)) {
                $sqlFirma = "SELECT firma_svg FROM usuarios WHERE id = ?";
                $stmtFirma = $this->db->prepare($sqlFirma);
                $stmtFirma->execute([$usuario_id]);
                $usuario = $stmtFirma->fetch(PDO::FETCH_ASSOC);

                if (!empty($usuario['firma_svg'])) {
                    // ✅ FIX: sanitizar también la firma del perfil
                    $firma_perfil = SVGSanitizer::sanitize($usuario['firma_svg']);

                    if (empty($firma_perfil)) {
                        $this->db->rollBack();
                        return [
                            'success' => false,
                            'mensaje' => 'La firma guardada en tu perfil no es válida. Vuelve a dibujarla en /perfil'
                        ];
                    }

                    $firma_svg = $firma_perfil;
                    $uso_firma_perfil = true;
                }
            }

            if (empty($firma_svg)) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'mensaje' => 'No hay firma disponible. Dibuja tu firma o guárdala en tu perfil'
                ];
            }

            // ✅ FIX: hash sobre el SVG sanitizado
            $firma_hash = hash('sha256', $firma_svg);

            $sql = "UPDATE firmas_orden 
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

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'No se pudo actualizar el paso (rowCount = 0)'];
            }

            // Buscar siguiente paso pendiente
            $sqlNext = "SELECT MIN(paso) as proximo_paso
                        FROM firmas_orden 
                        WHERE orden_id = ? AND estado = 'PENDIENTE'";
            $stmtNext = $this->db->prepare($sqlNext);
            $stmtNext->execute([$orden_id]);
            $nextResult = $stmtNext->fetch(PDO::FETCH_ASSOC);

            $hayPendientes = ($nextResult && $nextResult['proximo_paso'] !== null);

            if ($hayPendientes) {
                $nuevoPaso = (int)$nextResult['proximo_paso'];

                $sqlUpdate = "UPDATE ordenes_mantenimiento 
                              SET firma_paso_actual = ?
                              WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([$nuevoPaso, $orden_id]);

                $completada = false;
            } else {
                $sqlMaxPaso = "SELECT MAX(paso) as ultimo_paso FROM firmas_orden WHERE orden_id = ?";
                $stmtMax = $this->db->prepare($sqlMaxPaso);
                $stmtMax->execute([$orden_id]);
                $maxResult = $stmtMax->fetch(PDO::FETCH_ASSOC);
                $nuevoPaso = (int)($maxResult['ultimo_paso'] ?? 6) + 1;

                $sqlUpdate = "UPDATE ordenes_mantenimiento 
                              SET firma_estado = 'COMPLETADA',
                                  firma_paso_actual = ?,
                                  firma_completada_en = NOW()
                              WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([$nuevoPaso, $orden_id]);

                $completada = true;
            }

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => 'Firma registrada correctamente',
                'siguiente_paso' => $nuevoPaso,
                'completada' => $completada,
                'firma_hash' => $firma_hash,
                'uso_firma_perfil' => $uso_firma_perfil
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en FirmaModel::firmar: " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // ==========================================
    // RECHAZAR
    // ==========================================

    /**
     * Rechaza un paso de firma.
     */
    public function rechazar($orden_id, $paso_id, $usuario_id, $usuario_nombre, $motivo) {
        try {
            $orden_id = (int)$orden_id;
            $paso_id  = (int)$paso_id;

            if (empty($motivo)) {
                return ['success' => false, 'mensaje' => 'Debes indicar el motivo del rechazo'];
            }

            $this->db->beginTransaction();

            $paso_actual = $this->obtenerPasoActual($orden_id);
            if (!$paso_actual || (int)$paso_actual['id'] !== $paso_id) {
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'Este paso no es el actual o ya fue procesado'];
            }

            $rol_usuario = $_SESSION['rol'] ?? '';
            if ($rol_usuario !== $paso_actual['rol_firmante']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'mensaje' => "No tienes el rol requerido para este paso (se requiere: {$paso_actual['rol_firmante']})"
                ];
            }

            $sql = "UPDATE firmas_orden 
                    SET usuario_id = ?,
                        usuario_nombre = ?,
                        estado = 'RECHAZADO',
                        motivo_rechazo = ?,
                        fecha_firma = NOW(),
                        ip = ?,
                        user_agent = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ? AND orden_id = ? AND estado = 'PENDIENTE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $usuario_id,
                $usuario_nombre,
                $motivo,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $paso_id,
                $orden_id
            ]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'mensaje' => 'No se pudo rechazar el paso'];
            }

            $sqlUpdate = "UPDATE ordenes_mantenimiento 
                          SET firma_estado = 'RECHAZADA'
                          WHERE id = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$orden_id]);

            $this->db->commit();

            return ['success' => true, 'mensaje' => 'Paso rechazado correctamente'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en FirmaModel::rechazar: " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // ==========================================
    // RESUMEN
    // ==========================================

    public function obtenerResumen($orden_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'FIRMADO' THEN 1 ELSE 0 END) as firmados,
                        SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado = 'RECHAZADO' THEN 1 ELSE 0 END) as rechazados,
                        SUM(CASE WHEN estado = 'OMITIDO' THEN 1 ELSE 0 END) as omitidos
                    FROM firmas_orden 
                    WHERE orden_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$orden_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total      = (int)($result['total'] ?? 0);
            $firmados   = (int)($result['firmados'] ?? 0);
            $pendientes = (int)($result['pendientes'] ?? 0);
            $rechazados = (int)($result['rechazados'] ?? 0);
            $omitidos   = (int)($result['omitidos'] ?? 0);

            $total_efectivo = $total - $omitidos;

            return [
                'total'      => $total,
                'firmados'   => $firmados,
                'pendientes' => $pendientes,
                'rechazados' => $rechazados,
                'omitidos'   => $omitidos,
                'completa'   => ($pendientes === 0 && $rechazados === 0),
                'porcentaje' => $total_efectivo > 0 ? round(($firmados / $total_efectivo) * 100, 1) : 0
            ];

        } catch (PDOException $e) {
            error_log("Error en FirmaModel::obtenerResumen: " . $e->getMessage());
            return [
                'total' => 0, 'firmados' => 0, 'pendientes' => 0,
                'rechazados' => 0, 'omitidos' => 0, 'completa' => false, 'porcentaje' => 0
            ];
        }
    }

    // ==========================================
    // PENDIENTES POR ROL
    // ==========================================

    public function obtenerPendientesPorRol($rol) {
        try {
            $sql = "SELECT o.id, o.num_om, o.titulo, o.status, o.fecha_creacion,
                           f.id as firma_id, f.paso, f.rol_firmante, f.estado as firma_estado,
                           c.nombre_paso
                    FROM ordenes_mantenimiento o
                    INNER JOIN firmas_orden f ON o.id = f.orden_id
                    LEFT JOIN firma_secuencia_config c 
                        ON f.paso = c.paso AND c.activo = 1
                    WHERE f.estado = 'PENDIENTE'
                      AND f.rol_firmante = ?
                      AND o.firma_estado = 'EN_PROCESO'
                      AND f.paso = o.firma_paso_actual
                    ORDER BY o.fecha_creacion ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$rol]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en FirmaModel::obtenerPendientesPorRol: " . $e->getMessage());
            return [];
        }
    }

    public function contarPendientesPorRol($rol) {
        try {
            $sql = "SELECT COUNT(*) as total
                    FROM ordenes_mantenimiento o
                    INNER JOIN firmas_orden f ON o.id = f.orden_id
                    WHERE f.estado = 'PENDIENTE'
                      AND f.rol_firmante = ?
                      AND o.firma_estado = 'EN_PROCESO'
                      AND f.paso = o.firma_paso_actual";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$rol]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error en FirmaModel::contarPendientesPorRol: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Alias semántico más claro.
     */
    public function contarFirmasPendientesPorRol($rol) {
        return $this->contarPendientesPorRol($rol);
    }

    // ==========================================
    // CONFIGURACIÓN DE SECUENCIA
    // ==========================================

    /**
     * Actualiza la secuencia de firmas configurada.
     */
    public function actualizarSecuencia($pasos) {
        try {
            if (empty($pasos) || !is_array($pasos)) {
                return false;
            }

            $this->db->beginTransaction();

            $sql = "UPDATE firma_secuencia_config 
                    SET rol_firmante = ?, 
                        nombre_paso = ?, 
                        obligatorio = ?, 
                        activo = ?
                    WHERE paso = ?";
            $stmt = $this->db->prepare($sql);

            foreach ($pasos as $paso) {
                if (!isset($paso['paso'])) continue;

                $stmt->execute([
                    $paso['rol_firmante'] ?? '',
                    $paso['nombre_paso'] ?? '',
                    (int)($paso['obligatorio'] ?? 1),
                    (int)($paso['activo'] ?? 1),
                    (int)$paso['paso']
                ]);
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en FirmaModel::actualizarSecuencia: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // VERIFICACIÓN DE HASH
    // ==========================================

    /**
     * Verifica que el hash de una firma coincida con su SVG.
     */
    public function verificarFirmaHash($firma_id) {
        try {
            $sql = "SELECT firma_svg, firma_hash FROM firmas_orden WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$firma_id]);
            $firma = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$firma) {
                return ['valida' => false, 'mensaje' => 'Firma no encontrada'];
            }

            if (empty($firma['firma_hash'])) {
                return ['valida' => false, 'mensaje' => 'Esta firma no tiene hash registrado'];
            }

            if (empty($firma['firma_svg'])) {
                return ['valida' => false, 'mensaje' => 'Esta firma no tiene SVG registrado'];
            }

            $hash_calculado = hash('sha256', $firma['firma_svg']);
            $valida = hash_equals($firma['firma_hash'], $hash_calculado);

            return [
                'valida' => $valida,
                'mensaje' => $valida 
                    ? 'La firma no ha sido alterada' 
                    : '⚠️ ALERTA: La firma ha sido alterada'
            ];

        } catch (PDOException $e) {
            error_log("Error en FirmaModel::verificarFirmaHash: " . $e->getMessage());
            return ['valida' => false, 'mensaje' => 'Error al verificar'];
        }
    }
}