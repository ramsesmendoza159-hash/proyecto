<?php
// controller/SupervisorController.php
// Panel del Supervisor - VERSIÓN COMPLETA CORREGIDA
// ✅ FIX NUEVO: ver_supervision() trae todos los campos necesarios con JOIN completo

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../model/Supervisor.php';
require_once __DIR__ . '/../model/Tecnico.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class SupervisorController extends Controller {
    
    private $db;
    private $ordenModel;
    private $supervisorModel;
    private $tecnicoModel;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit();
        }
        
        if (!$this->authHelper->isSupervisor() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit();
        }
        
        $this->db = Database::getInstance()->getConnection();
        $this->ordenModel = new OrdenTrabajo();
        $this->supervisorModel = new Supervisor();
        $this->tecnicoModel = new Tecnico();
    }

    private function obtenerSupervisorId() {
        try {
            $email = $_SESSION['email'] ?? '';
            if (empty($email)) return null;
            
            $sql = "SELECT id FROM supervisores WHERE email = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (int)$result['id'] : null;
        } catch (Throwable $e) {
            error_log("Error en obtenerSupervisorId: " . $e->getMessage());
            return null;
        }
    }

    public function index() {
        try {
            $kpis = $this->calcularKPIs();
            
            $sql = "SELECT o.id, o.num_om, o.titulo, o.status, o.prioridad,
                           o.fecha_finalizacion, o.fecha_creacion,
                           t.nombre as tecnico_nombre
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    WHERE o.status = 'CERRADA'
                    ORDER BY o.fecha_finalizacion DESC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $ordenes_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Throwable $e) {
            error_log("Error en SupervisorController::index - " . $e->getMessage());
            $kpis = [
                'total_ordenes' => 0,
                'pendientes_revision' => 0,
                'aprobadas' => 0,
                'rechazadas' => 0
            ];
            $ordenes_pendientes = [];
        }
        
        $titulo = 'Panel de Supervisor';
        $seccion = 'supervisor';
        
        $supervisor_kpis = $kpis;
        $supervisor_ordenes = $ordenes_pendientes;
        
        require_once __DIR__ . '/../views/supervisor/index.php';
    }

    public function dashboardData() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $kpis = $this->calcularKPIs();
            
            $sql = "SELECT o.id, o.num_om, o.titulo, o.prioridad,
                           o.fecha_finalizacion, o.status,
                           t.nombre as tecnico_nombre
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    WHERE o.status = 'CERRADA'
                    ORDER BY o.fecha_finalizacion DESC
                    LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($ordenes as &$orden) {
                $orden['fecha_cierre'] = $orden['fecha_finalizacion'] 
                    ? date('d/m/Y H:i', strtotime($orden['fecha_finalizacion'])) 
                    : 'N/A';
                $orden['tecnico'] = $orden['tecnico_nombre'] ?? 'Sin asignar';
            }
            unset($orden);
            
            echo json_encode([
                'success' => true,
                'total_ordenes' => $kpis['total_ordenes'],
                'pendientes_revision' => $kpis['pendientes_revision'],
                'aprobadas' => $kpis['aprobadas'],
                'rechazadas' => $kpis['rechazadas'],
                'ordenes' => $ordenes
            ]);
            
        } catch (Throwable $e) {
            error_log("Error en dashboardData: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'total_ordenes' => 0,
                'pendientes_revision' => 0,
                'aprobadas' => 0,
                'rechazadas' => 0,
                'ordenes' => []
            ]);
        }
        exit;
    }

    private function calcularKPIs() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_ordenes,
                        SUM(CASE WHEN status = 'CERRADA' THEN 1 ELSE 0 END) as pendientes_revision,
                        SUM(CASE WHEN status = 'APROBADA' THEN 1 ELSE 0 END) as aprobadas,
                        SUM(CASE WHEN status = 'RECHAZADA' THEN 1 ELSE 0 END) as rechazadas
                    FROM ordenes_mantenimiento";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_ordenes' => (int)($result['total_ordenes'] ?? 0),
                'pendientes_revision' => (int)($result['pendientes_revision'] ?? 0),
                'aprobadas' => (int)($result['aprobadas'] ?? 0),
                'rechazadas' => (int)($result['rechazadas'] ?? 0)
            ];
        } catch (Throwable $e) {
            error_log("Error en calcularKPIs: " . $e->getMessage());
            return [
                'total_ordenes' => 0,
                'pendientes_revision' => 0,
                'aprobadas' => 0,
                'rechazadas' => 0
            ];
        }
    }

    public function ordenes() {
        $titulo = 'Órdenes para Revisar';
        $seccion = 'supervisor';
        require_once __DIR__ . '/../views/supervisor/ordenes.php';
    }

    public function ordenesList() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $estado = $_GET['estado'] ?? '';
            $prioridad = $_GET['prioridad'] ?? '';
            $tecnico_id = !empty($_GET['tecnico']) ? (int)$_GET['tecnico'] : null;
            $fecha = $_GET['fecha'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, (int)($_GET['limit'] ?? 10));
            $offset = ($page - 1) * $limit;
            
            $where = ["1=1"];
            $params = [];
            
            if (!empty($estado)) {
                $where[] = "o.status = ?";
                $params[] = $estado;
            } else {
                $where[] = "o.status IN ('CERRADA', 'APROBADA', 'RECHAZADA')";
            }
            
            if (!empty($prioridad)) {
                $where[] = "o.prioridad = ?";
                $params[] = $prioridad;
            }
            
            if ($tecnico_id) {
                $where[] = "o.tecnico_id = ?";
                $params[] = $tecnico_id;
            }
            
            if (!empty($fecha)) {
                $where[] = "DATE(o.fecha_finalizacion) = ?";
                $params[] = $fecha;
            }
            
            $where_sql = implode(' AND ', $where);
            
            $sqlCount = "SELECT COUNT(*) as total 
                         FROM ordenes_mantenimiento o 
                         WHERE $where_sql";
            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            
            $sql = "SELECT o.id, o.num_om, o.titulo, o.status, o.prioridad,
                           o.fecha_finalizacion, o.fecha_creacion,
                           t.nombre as tecnico,
                           s.estado as supervision_estado
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    LEFT JOIN supervisiones s ON o.id = s.orden_id
                    WHERE $where_sql
                    ORDER BY o.fecha_finalizacion DESC, o.fecha_creacion DESC
                    LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($ordenes as &$orden) {
                $orden['estado'] = $orden['status'];
                $orden['fecha_creacion'] = $orden['fecha_finalizacion'] 
                    ? date('d/m/Y', strtotime($orden['fecha_finalizacion']))
                    : date('d/m/Y', strtotime($orden['fecha_creacion']));
            }
            unset($orden);
            
            echo json_encode([
                'success' => true,
                'ordenes' => $ordenes,
                'total' => $total,
                'paginas' => ceil($total / $limit)
            ]);
            
        } catch (Throwable $e) {
            error_log("Error en ordenesList: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'ordenes' => [],
                'total' => 0,
                'paginas' => 0
            ]);
        }
        exit;
    }

    public function revisar($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de orden inválido';
            header('Location: /proyecto/supervisor/ordenes');
            exit();
        }
        
        $orden = $this->ordenModel->obtenerPorId($id);
        
        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/supervisor/ordenes');
            exit();
        }
        
        $titulo = 'Revisar Orden #' . $id;
        $seccion = 'supervisor';
        require_once __DIR__ . '/../views/supervisor/revisar.php';
    }

    public function guardar_revision() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/supervisor/ordenes');
            exit();
        }
        
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/supervisor/ordenes');
            exit();
        }
        
        $orden_id = (int)($_POST['orden_id'] ?? 0);
        $calificacion = (int)($_POST['calificacion'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        $observaciones = trim($_POST['observaciones'] ?? '');
        $cumple = isset($_POST['cumple']) ? 1 : 0;
        
        if ($orden_id <= 0) {
            $_SESSION['error'] = 'ID de orden inválido';
            header('Location: /proyecto/supervisor/ordenes');
            exit();
        }
        
        if (!in_array($estado, ['APROBADA', 'RECHAZADA'])) {
            $_SESSION['error'] = 'Estado inválido';
            header('Location: /proyecto/supervisor/revisar/' . $orden_id);
            exit();
        }
        
        try {
            $this->db->beginTransaction();
            
            $supervisor_id = $this->obtenerSupervisorId();
            
            $sqlCheck = "SELECT id FROM supervisiones WHERE orden_id = ? LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$orden_id]);
            $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($existe) {
                $sql = "UPDATE supervisiones 
                        SET supervisor_id = ?,
                            calificacion = ?,
                            estado = ?,
                            observaciones = ?,
                            cumple = ?,
                            fecha_supervision = NOW(),
                            fecha_actualizacion = NOW()
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $supervisor_id,
                    $calificacion,
                    $estado,
                    $observaciones,
                    $cumple,
                    $existe['id']
                ]);
            } else {
                $sql = "INSERT INTO supervisiones 
                        (orden_id, supervisor_id, calificacion, estado, observaciones, cumple, fecha_supervision, fecha_creacion)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $orden_id,
                    $supervisor_id,
                    $calificacion,
                    $estado,
                    $observaciones,
                    $cumple
                ]);
            }
            
            $nuevoStatus = ($estado === 'APROBADA') ? 'APROBADA' : 'RECHAZADA';
            $sqlUpdate = "UPDATE ordenes_mantenimiento 
                          SET status = ?, fecha_actualizacion = NOW()
                          WHERE id = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$nuevoStatus, $orden_id]);
            
            try {
                require_once __DIR__ . '/../model/AuditoriaModel.php';
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Supervisor',
                    $_SESSION['rol'] ?? 'supervisor',
                    'revisar_orden',
                    'supervisiones',
                    $orden_id,
                    null,
                    ['estado' => $estado, 'calificacion' => $calificacion]
                );
            } catch (Throwable $e) {
                error_log("Error auditoría: " . $e->getMessage());
            }
            
            $this->db->commit();
            
            $_SESSION['mensaje'] = 'Orden ' . strtolower($nuevoStatus) . ' correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: /proyecto/supervisor/ordenes');
            exit();
            
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en guardar_revision: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar la revisión: ' . $e->getMessage();
            header('Location: /proyecto/supervisor/revisar/' . $orden_id);
            exit();
        }
    }

    public function supervisiones() {
        $titulo = 'Mis Supervisiones';
        $seccion = 'supervisor';
        require_once __DIR__ . '/../views/supervisor/supervisiones.php';
    }

    public function supervisionesList() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $estado = $_GET['estado'] ?? '';
            $calificacion = $_GET['calificacion'] ?? '';
            $fecha = $_GET['fecha'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, (int)($_GET['limit'] ?? 10));
            $offset = ($page - 1) * $limit;
            
            $where = ["1=1"];
            $params = [];
            
            if (!empty($estado)) {
                $where[] = "s.estado = ?";
                $params[] = $estado;
            }
            
            if (!empty($calificacion)) {
                $where[] = "s.calificacion = ?";
                $params[] = (int)$calificacion;
            }
            
            if (!empty($fecha)) {
                $where[] = "DATE(s.fecha_supervision) = ?";
                $params[] = $fecha;
            }
            
            $where_sql = implode(' AND ', $where);
            
            $sqlCount = "SELECT COUNT(*) as total FROM supervisiones s WHERE $where_sql";
            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            
            $sql = "SELECT s.id, s.orden_id, s.calificacion, s.estado, s.cumple,
                           s.observaciones, s.fecha_supervision,
                           o.num_om, o.titulo,
                           t.nombre as tecnico
                    FROM supervisiones s
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    WHERE $where_sql
                    ORDER BY s.fecha_supervision DESC, s.fecha_creacion DESC
                    LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $supervisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($supervisiones as &$sup) {
                $sup['fecha_creacion'] = $sup['fecha_supervision'] 
                    ? date('d/m/Y', strtotime($sup['fecha_supervision']))
                    : 'N/A';
            }
            unset($sup);
            
            echo json_encode([
                'success' => true,
                'supervisiones' => $supervisiones,
                'total' => $total,
                'paginas' => ceil($total / $limit)
            ]);
            
        } catch (Throwable $e) {
            error_log("Error en supervisionesList: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'supervisiones' => [],
                'total' => 0,
                'paginas' => 0
            ]);
        }
        exit;
    }

    public function ver_orden($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de orden inválido';
            header('Location: /proyecto/supervisor/ordenes');
            exit();
        }
        
        $orden = $this->ordenModel->obtenerPorId($id);
        
        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/supervisor/ordenes');
            exit();
        }
        
        $titulo = 'Ver Orden #' . $id;
        $seccion = 'supervisor';
        require_once __DIR__ . '/../views/supervisor/ver_orden.php';
    }

    /**
     * ✅ FIX NUEVO: JOIN completo con ordenes_mantenimiento, areas, plantas, tecnicos
     * para que la vista ver_supervision.php tenga todos los campos que necesita:
     * num_om, orden_titulo, nombre_area, prioridad, tecnico, estado de la orden, etc.
     */
    public function ver_supervision($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de supervisión inválido';
            header('Location: /proyecto/supervisor/supervisiones');
            exit();
        }
        
        try {
            $sql = "SELECT 
                        s.*,
                        o.num_om,
                        o.titulo AS orden_titulo,
                        o.status AS orden_status,
                        o.prioridad,
                        o.descripcion_mantenimiento,
                        o.fecha_creacion AS orden_fecha_creacion,
                        o.fecha_finalizacion AS orden_fecha_finalizacion,
                        a.nombre_area,
                        p.nombre_planta,
                        t.nombre AS tecnico,
                        u.nombre AS supervisor_nombre
                    FROM supervisiones s
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    LEFT JOIN areas a ON o.id_area = a.id_area
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    LEFT JOIN usuarios u ON s.supervisor_id = u.id
                    WHERE s.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $supervision = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$supervision) {
                $_SESSION['error'] = 'Supervisión no encontrada';
                header('Location: /proyecto/supervisor/supervisiones');
                exit();
            }
        } catch (Throwable $e) {
            error_log("Error en ver_supervision: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la supervisión';
            header('Location: /proyecto/supervisor/supervisiones');
            exit();
        }
        
        $titulo = 'Ver Supervisión #' . $id;
        $seccion = 'supervisor';
        require_once __DIR__ . '/../views/supervisor/ver_supervision.php';
    }

    public function tecnicosList() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $sql = "SELECT id, nombre, especialidad 
                    FROM tecnicos 
                    WHERE estado = 'activo' 
                    ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($tecnicos);
        } catch (Throwable $e) {
            error_log("Error en tecnicosList: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
}