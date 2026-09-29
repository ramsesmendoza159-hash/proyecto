<?php
// controller/SupervisionController.php
// VERSIÓN COMPLETA CORREGIDA - TABLA 'supervisiones'
// ✅ FIX: Reemplazado TecnicosModel (eliminado) por Tecnico
// ✅ NUEVO: Método eliminar() agregado para completar el CRUD
// ✅ FIX: catch (Throwable) en vez de Exception
// ✅ FIX: redirect() con rutas relativas (el helper agrega /proyecto)
// ✅ FIX: exit; después de cada redirect()

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
            exit;
        }

        if (!$this->authHelper->isAdmin() &&
            !$this->authHelper->isSupervisor() &&
            !$this->authHelper->isConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            exit;
        }

        $this->db = Database::getInstance()->getConnection();
        $this->ordenModel = new OrdenTrabajo();
        $this->supervisorModel = new Supervisor();
        $this->tecnicoModel = new Tecnico();
    }

    /**
     * ¿El usuario actual es consultor?
     */
    private function esConsultor() {
        return $this->authHelper->isConsultor();
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function index() {
        $supervisiones = [];
        $estadisticas = ['total' => 0, 'pendientes' => 0, 'aprobadas' => 0, 'rechazadas' => 0];
        $tecnicos = [];

        try {
            $sql = "SELECT s.*, 
                           o.num_om,
                           o.titulo,
                           o.status as orden_estado,
                           u.nombre as supervisor_nombre,
                           t.nombre as tecnico_nombre
                    FROM supervisiones s
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    LEFT JOIN usuarios u ON s.supervisor_id = u.id
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    ORDER BY s.fecha_creacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $supervisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $estadisticas = [
                'total'      => count($supervisiones),
                'pendientes' => count(array_filter($supervisiones, fn($s) => ($s['estado'] ?? '') === 'pendiente')),
                'aprobadas'  => count(array_filter($supervisiones, fn($s) => ($s['estado'] ?? '') === 'aprobada')),
                'rechazadas' => count(array_filter($supervisiones, fn($s) => ($s['estado'] ?? '') === 'rechazada')),
            ];

            // ✅ FIX: Usar Tecnico (el modelo actual) en lugar de TecnicosModel (eliminado)
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
            $_SESSION['error'] = 'ID de supervisión inválido';
            $this->redirect('/supervision');
            exit;
        }

        try {
            $sql = "SELECT s.*, 
                           o.num_om,
                           o.titulo,
                           o.descripcion_mantenimiento,
                           o.status as orden_estado,
                           u.nombre as supervisor_nombre,
                           t.nombre as tecnico_nombre
                    FROM supervisiones s
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    LEFT JOIN usuarios u ON s.supervisor_id = u.id
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    WHERE s.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $supervision = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supervision) {
                $_SESSION['error'] = 'Supervisión no encontrada';
                $this->redirect('/supervision');
                exit;
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::ver - " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la supervisión';
            $this->redirect('/supervision');
            exit;
        }

        $this->view('supervision/ver', ['supervision' => $supervision]);
    }

    // ==========================================
    // EDITAR
    // ==========================================

    public function editar($id) {
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para editar supervisiones';
            $this->redirect('/supervision');
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de supervisión inválido';
            $this->redirect('/supervision');
            exit;
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
                $_SESSION['error'] = 'Supervisión no encontrada';
                $this->redirect('/supervision');
                exit;
            }

            $ordenes       = $this->ordenModel->obtenerTodos();
            $supervisores  = $this->supervisorModel->obtenerTodos(['estado' => 'activo']);

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::editar - " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la supervisión';
            $this->redirect('/supervision');
            exit;
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
            $_SESSION['error'] = 'No tienes permisos para actualizar supervisiones';
            $this->redirect('/supervision');
            exit;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervision');
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de supervisión inválido';
            $this->redirect('/supervision');
            exit;
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

        try {
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

            // Si se aprueba o rechaza, actualizar el estado de la orden
            if ($resultado && $orden_id > 0 && in_array(strtoupper($estado), ['APROBADA', 'RECHAZADA'], true)) {
                $nuevoEstado = (strtoupper($estado) === 'APROBADA') ? 'APROBADA' : 'RECHAZADA';
                $sqlOrden = "UPDATE ordenes_mantenimiento 
                             SET status = ?, fecha_actualizacion = NOW() 
                             WHERE id = ?";
                $stmtOrden = $this->db->prepare($sqlOrden);
                $stmtOrden->execute([$nuevoEstado, $orden_id]);
            }

            if ($resultado) {
                $_SESSION['mensaje'] = 'Supervisión actualizada correctamente';
                $_SESSION['mensaje_tipo'] = 'success';
            } else {
                $_SESSION['error'] = 'Error al actualizar la supervisión';
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::actualizar - " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar la supervisión: ' . $e->getMessage();
        }

        $this->redirect('/supervision/ver/' . $id);
        exit;
    }

    // ==========================================
    // APROBAR
    // ==========================================

    public function aprobar($id) {
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para aprobar';
            $this->redirect('/supervision');
            exit;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervision');
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de supervisión inválido';
            $this->redirect('/supervision');
            exit;
        }

        $observaciones = trim($this->post('observaciones_aprobacion', 'Aprobada por supervisor'));

        try {
            $sqlGet = "SELECT orden_id FROM supervisiones WHERE id = ?";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$id]);
            $result = $stmtGet->fetch(PDO::FETCH_ASSOC);
            $orden_id = $result['orden_id'] ?? null;

            $sql = "UPDATE supervisiones SET 
                        estado = 'aprobada',
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

            if ($resultado) {
                $_SESSION['mensaje'] = 'Orden aprobada correctamente';
                $_SESSION['mensaje_tipo'] = 'success';
            } else {
                $_SESSION['error'] = 'Error al aprobar la orden';
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::aprobar - " . $e->getMessage());
            $_SESSION['error'] = 'Error al aprobar la orden';
        }

        $this->redirect('/supervision');
        exit;
    }

    // ==========================================
    // RECHAZAR
    // ==========================================

    public function rechazar($id) {
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para rechazar';
            $this->redirect('/supervision');
            exit;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervision');
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de supervisión inválido';
            $this->redirect('/supervision');
            exit;
        }

        $motivo = trim($this->post('motivo_rechazo', 'Rechazada por supervisor'));

        try {
            $sqlGet = "SELECT orden_id FROM supervisiones WHERE id = ?";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$id]);
            $result = $stmtGet->fetch(PDO::FETCH_ASSOC);
            $orden_id = $result['orden_id'] ?? null;

            $sql = "UPDATE supervisiones SET 
                        estado = 'rechazada',
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

            if ($resultado) {
                $_SESSION['mensaje'] = 'Orden rechazada correctamente';
                $_SESSION['mensaje_tipo'] = 'success';
            } else {
                $_SESSION['error'] = 'Error al rechazar la orden';
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::rechazar - " . $e->getMessage());
            $_SESSION['error'] = 'Error al rechazar la orden';
        }

        $this->redirect('/supervision');
        exit;
    }

    // ==========================================
    // ELIMINAR (solo admin)
    // ==========================================

    public function eliminar($id) {
        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'Solo administradores pueden eliminar supervisiones';
            $this->redirect('/supervision');
            exit;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervision');
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de supervisión inválido';
            $this->redirect('/supervision');
            exit;
        }

        try {
            // Verificar que existe
            $sqlCheck = "SELECT id FROM supervisiones WHERE id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id]);

            if (!$stmtCheck->fetch()) {
                $_SESSION['error'] = 'Supervisión no encontrada';
                $this->redirect('/supervision');
                exit;
            }

            // Eliminar
            $sql = "DELETE FROM supervisiones WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$id]);

            if ($resultado) {
                // Auditoría
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
                    error_log("Error auditoría: " . $e->getMessage());
                }

                $_SESSION['mensaje'] = 'Supervisión eliminada correctamente';
                $_SESSION['mensaje_tipo'] = 'success';
            } else {
                $_SESSION['error'] = 'Error al eliminar la supervisión';
            }

        } catch (Throwable $e) {
            error_log("Error en SupervisionController::eliminar - " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar la supervisión: ' . $e->getMessage();
        }

        $this->redirect('/supervision');
        exit;
    }

    // ==========================================
    // APIs JSON
    // ==========================================

    /**
     * Obtener datos de una orden
     * URL: /supervision/orden/{id}
     */
    public function orden($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
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
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Listar todas las órdenes
     * URL: /supervision/ordenes
     */
    public function ordenes() {
        try {
            $ordenes = $this->ordenModel->obtenerTodos();
            $this->jsonResponse($ordenes);
        } catch (Throwable $e) {
            error_log("Error en SupervisionController::ordenes - " . $e->getMessage());
            $this->jsonResponse([]);
        }
    }

    /**
     * Listar supervisores activos
     * URL: /supervision/supervisores
     */
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
                        SUM(CASE WHEN s.estado = 'aprobada'  THEN 1 ELSE 0 END) as aprobadas,
                        SUM(CASE WHEN s.estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
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