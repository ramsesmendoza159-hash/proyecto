<?php
// controller/SupervisionController.php
// VERSIÓN COMPLETA CORREGIDA - TABLA 'supervisiones'
// ✅ FIX CRÍTICO: JOIN a 'supervisores' (no a 'usuarios')
// ✅ FIX: index() normaliza estado con strtoupper()
// ✅ FIX: transacciones en actualizar(), aprobar(), rechazar()
// ✅ FIX: catch (Throwable)
// ✅ FIX: return después de cada redirect()

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../model/Supervisor.php';
require_once __DIR__ . '/../model/Tecnico.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class SupervisionController extends Controller {

    private $db;
    private $ordenModel;
    private $supervisorModel;
    private $tecnicoModel;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }

        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['admin', 'supervisor', 'consultor'], true)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            return;
        }

        try {
            $this->db = Database::getInstance()->getConnection();
            $this->ordenModel = new OrdenTrabajo();
            $this->supervisorModel = new Supervisor();
            $this->tecnicoModel = new Tecnico();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en SupervisionController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el módulo');
            return;
        }
    }

    private function esConsultor() {
        return $this->authHelper->getRole() === 'consultor';
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function index() {
        $supervisiones = [];
        $estadisticas = ['total' => 0, 'pendientes' => 0, 'aprobadas' => 0, 'rechazadas' => 0];
        $tecnicos = [];

        try {
            // ✅ FIX CRÍTICO: JOIN a 'supervisores' (no a 'usuarios')
            $sql = "SELECT s.*,
                           o.num_om,
                           o.titulo,
                           o.status as orden_estado,
                           sup.nombre as supervisor_nombre,
                           t.nombre as tecnico_nombre
                    FROM supervisiones s
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    LEFT JOIN supervisores sup ON s.supervisor_id = sup.id
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    ORDER BY s.fecha_creacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $supervisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ✅ FIX: normalizar estado con strtoupper() porque la BD guarda minúsculas
            $estadisticas = [
                'total'      => count($supervisiones),
                'pendientes' => count(array_filter($supervisiones, fn($s) => strtoupper($s['estado'] ?? '') === 'PENDIENTE')),
                'aprobadas'  => count(array_filter($supervisiones, fn($s) => strtoupper($s['estado'] ?? '') === 'APROBADA')),
                'rechazadas' => count(array_filter($supervisiones, fn($s) => strtoupper($s['estado'] ?? '') === 'RECHAZADA')),
            ];

            $tecnicos = $this->tecnicoModel->obtenerTodos(['estado' => 'activo']);

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::index - " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar las supervisiones';
        }

        $this->view('supervision/index', [
            'supervisiones' => $supervisiones,
            'estadisticas'  => $estadisticas,
            'tecnicos'      => $tecnicos,
        ]);
    }

    // ==========================================
    // VER
    // ==========================================

    public function ver($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/supervision', 'ID de supervisión inválido');
            return;
        }

        try {
            // ✅ FIX CRÍTICO: JOIN a 'supervisores'
            $sql = "SELECT s.*,
                           o.num_om,
                           o.titulo,
                           o.descripcion_mantenimiento,
                           o.status as orden_estado,
                           sup.nombre as supervisor_nombre,
                           t.nombre as tecnico_nombre
                    FROM supervisiones s
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    LEFT JOIN supervisores sup ON s.supervisor_id = sup.id
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    WHERE s.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $supervision = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supervision) {
                $this->redirectWithError('/supervision', 'Supervisión no encontrada');
                return;
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::ver - " . $e->getMessage());
            $this->redirectWithError('/supervision', 'Error al cargar la supervisión');
            return;
        }

        $this->view('supervision/ver', ['supervision' => $supervision]);
    }

    // ==========================================
    // EDITAR
    // ==========================================

    public function editar($id) {
        if ($this->esConsultor()) {
            $this->redirectWithError('/supervision', 'No tienes permisos para editar supervisiones');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/supervision', 'ID de supervisión inválido');
            return;
        }

        try {
            $sql = "SELECT s.*,
                           o.num_om,
                           o.titulo
                    FROM supervisiones s
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    WHERE s.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $supervision = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supervision) {
                $this->redirectWithError('/supervision', 'Supervisión no encontrada');
                return;
            }

            $ordenes       = $this->ordenModel->obtenerTodos();
            $supervisores  = $this->supervisorModel->obtenerTodos(['estado' => 'activo']);

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::editar - " . $e->getMessage());
            $this->redirectWithError('/supervision', 'Error al cargar la supervisión');
            return;
        }

        $this->view('supervision/editar', [
            'supervision'   => $supervision,
            'ordenes'       => $ordenes,
            'supervisores'  => $supervisores,
        ]);
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    public function actualizar($id) {
        if ($this->esConsultor()) {
            $this->redirectWithError('/supervision', 'No tienes permisos para actualizar supervisiones');
            return;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/supervision', 'Token de seguridad inválido');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/supervision', 'ID de supervisión inválido');
            return;
        }

        $calificacion  = (int)$this->post('calificacion', 0);
        $estado        = trim($this->post('estado', 'pendiente'));
        $observaciones = trim($this->post('observaciones', ''));
        $cumple        = $this->post('cumple', 0) ? 1 : 0;
        $orden_id      = (int)$this->post('orden_id', 0);
        $supervisor_id = (int)$this->post('supervisor_id', 0);

        $fecha_supervision = $this->post('fecha_supervision', '');
        if (!empty($fecha_supervision)) {
            $timestamp = strtotime($fecha_supervision);
            $fecha_supervision = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
        } else {
            $fecha_supervision = null;
        }

        $estados_validos = ['pendiente', 'aprobada', 'rechazada', 'PENDIENTE', 'APROBADA', 'RECHAZADA'];
        if (!in_array($estado, $estados_validos, true)) {
            $this->redirectWithError('/supervision/ver/' . $id, 'Estado inválido');
            return;
        }

        try {
            $this->db->beginTransaction();

            $sql = "UPDATE supervisiones SET
                        orden_id = ?,
                        supervisor_id = ?,
                        calificacion = ?,
                        estado = ?,
                        observaciones = ?,
                        cumple = ?,
                        fecha_supervision = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([
                $orden_id,
                $supervisor_id,
                $calificacion,
                $estado,
                $observaciones,
                $cumple,
                $fecha_supervision,
                $id,
            ]);

            if ($resultado && $orden_id > 0 && in_array(strtoupper($estado), ['APROBADA', 'RECHAZADA'], true)) {
                $nuevoEstado = (strtoupper($estado) === 'APROBADA') ? 'APROBADA' : 'RECHAZADA';
                $sqlOrden = "UPDATE ordenes_mantenimiento
                             SET status = ?, fecha_actualizacion = NOW()
                             WHERE id = ?";
                $stmtOrden = $this->db->prepare($sqlOrden);
                $stmtOrden->execute([$nuevoEstado, $orden_id]);
            }

            $this->db->commit();

            if ($resultado) {
                $this->redirectWithSuccess('/supervision/ver/' . $id, 'Supervisión actualizada correctamente');
                return;
            } else {
                $this->redirectWithError('/supervision/ver/' . $id, 'Error al actualizar la supervisión');
                return;
            }

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisionController::actualizar - " . $e->getMessage());
            $this->redirectWithError('/supervision/ver/' . $id, 'Error al actualizar la supervisión');
            return;
        }
    }

    // ==========================================
    // APROBAR
    // ==========================================

    public function aprobar($id) {
        if ($this->esConsultor()) {
            $this->redirectWithError('/supervision', 'No tienes permisos para aprobar');
            return;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/supervision', 'Token de seguridad inválido');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/supervision', 'ID de supervisión inválido');
            return;
        }

        $observaciones = trim($this->post('observaciones_aprobacion', 'Aprobada por supervisor'));

        try {
            $this->db->beginTransaction();

            $sqlGet = "SELECT orden_id FROM supervisiones WHERE id = ?";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$id]);
            $result = $stmtGet->fetch(PDO::FETCH_ASSOC);
            $orden_id = $result['orden_id'] ?? null;

            $sql = "UPDATE supervisiones SET
                        estado = 'APROBADA',
                        observaciones = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$observaciones, $id]);

            if ($resultado && $orden_id) {
                $sqlOrden = "UPDATE ordenes_mantenimiento
                             SET status = 'APROBADA', fecha_actualizacion = NOW()
                             WHERE id = ?";
                $stmtOrden = $this->db->prepare($sqlOrden);
                $stmtOrden->execute([$orden_id]);
            }

            $this->db->commit();

            if ($resultado) {
                $this->redirectWithSuccess('/supervision', 'Orden aprobada correctamente');
            } else {
                $this->redirectWithError('/supervision', 'Error al aprobar la orden');
            }

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisionController::aprobar - " . $e->getMessage());
            $this->redirectWithError('/supervision', 'Error al aprobar la orden');
        }
    }

    // ==========================================
    // RECHAZAR
    // ==========================================

    public function rechazar($id) {
        if ($this->esConsultor()) {
            $this->redirectWithError('/supervision', 'No tienes permisos para rechazar');
            return;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/supervision', 'Token de seguridad inválido');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/supervision', 'ID de supervisión inválido');
            return;
        }

        $motivo = trim($this->post('motivo_rechazo', 'Rechazada por supervisor'));

        try {
            $this->db->beginTransaction();

            $sqlGet = "SELECT orden_id FROM supervisiones WHERE id = ?";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$id]);
            $result = $stmtGet->fetch(PDO::FETCH_ASSOC);
            $orden_id = $result['orden_id'] ?? null;

            $sql = "UPDATE supervisiones SET
                        estado = 'RECHAZADA',
                        observaciones = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$motivo, $id]);

            if ($resultado && $orden_id) {
                $sqlOrden = "UPDATE ordenes_mantenimiento
                             SET status = 'RECHAZADA', fecha_actualizacion = NOW()
                             WHERE id = ?";
                $stmtOrden = $this->db->prepare($sqlOrden);
                $stmtOrden->execute([$orden_id]);
            }

            $this->db->commit();

            if ($resultado) {
                $this->redirectWithSuccess('/supervision', 'Orden rechazada correctamente');
            } else {
                $this->redirectWithError('/supervision', 'Error al rechazar la orden');
            }

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisionController::rechazar - " . $e->getMessage());
            $this->redirectWithError('/supervision', 'Error al rechazar la orden');
        }
    }

    // ==========================================
    // ELIMINAR (solo admin)
    // ==========================================

    public function eliminar($id) {
        if (!$this->authHelper->isAdmin()) {
            $this->redirectWithError('/supervision', 'Solo administradores pueden eliminar supervisiones');
            return;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/supervision', 'Token de seguridad inválido');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/supervision', 'ID de supervisión inválido');
            return;
        }

        try {
            $sqlCheck = "SELECT id FROM supervisiones WHERE id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id]);

            if (!$stmtCheck->fetch()) {
                $this->redirectWithError('/supervision', 'Supervisión no encontrada');
                return;
            }

            $sql = "DELETE FROM supervisiones WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$id]);

            if ($resultado) {
                try {
                    require_once __DIR__ . '/../model/AuditoriaModel.php';
                    $auditoria = new AuditoriaModel();
                    $auditoria->registrar(
                        $this->authHelper->getUserId(),
                        $_SESSION['nombre'] ?? 'Sistema',
                        $_SESSION['rol'] ?? 'admin',
                        'eliminar',
                        'supervisiones',
                        $id
                    );
                } catch (Throwable $e) {
                    error_log("Error auditoría eliminar supervision: " . $e->getMessage());
                }

                $this->redirectWithSuccess('/supervision', 'Supervisión eliminada correctamente');
            } else {
                $this->redirectWithError('/supervision', 'Error al eliminar la supervisión');
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::eliminar - " . $e->getMessage());
            $this->redirectWithError('/supervision', 'Error al eliminar la supervisión');
        }
    }

    // ==========================================
    // APIs JSON
    // ==========================================

    public function orden($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            $sql = "SELECT id, num_om, titulo FROM ordenes_mantenimiento WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($orden) {
                $this->jsonResponse($orden);
            } else {
                $this->jsonResponse(['error' => 'Orden no encontrada'], 404);
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::orden - " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    public function ordenes() {
        try {
            $ordenes = $this->ordenModel->obtenerTodos();
            $this->jsonResponse($ordenes);
        } catch (Throwable $e) {
            error_log("Error en SupervisionController::ordenes - " . $e->getMessage());
            $this->jsonResponse([]);
        }
    }

    public function supervisores() {
        try {
            $supervisores = $this->supervisorModel->obtenerTodos(['estado' => 'activo']);
            $this->jsonResponse($supervisores);
        } catch (Throwable $e) {
            error_log("Error en SupervisionController::supervisores - " . $e->getMessage());
            $this->jsonResponse([]);
        }
    }

    // ==========================================
    // REPORTE
    // ==========================================

    public function reporte() {
        $reporte = [];

        try {
            $sql = "SELECT
                        DATE_FORMAT(s.fecha_creacion, '%Y-%m') as mes,
                        COUNT(*) as total,
                        SUM(CASE WHEN UPPER(s.estado) = 'APROBADA'  THEN 1 ELSE 0 END) as aprobadas,
                        SUM(CASE WHEN UPPER(s.estado) = 'RECHAZADA' THEN 1 ELSE 0 END) as rechazadas,
                        AVG(s.calificacion) as promedio_calificacion
                    FROM supervisiones s
                    GROUP BY DATE_FORMAT(s.fecha_creacion, '%Y-%m')
                    ORDER BY mes DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::reporte - " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el reporte';
        }

        $this->view('supervision/reporte', ['reporte' => $reporte]);
    }
}