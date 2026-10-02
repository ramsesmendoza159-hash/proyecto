<?php
// controller/BackdatingController.php
// Controlador para gestión de backdating
// ✅ FIX: header('Location: /proyecto/...') → $this->redirect()
// ✅ FIX: try/catch (Throwable) en aprobar() y rechazar()
// ✅ FIX: header JSON con charset=utf-8 en validarFecha()
// ✅ FIX: uso de redirectWithSuccess/Error

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/BackdatingHelper.php';
require_once __DIR__ . '/../model/BackdatingModel.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class BackdatingController extends Controller {
    
    private $backdatingModel;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        $this->backdatingModel = new BackdatingModel();
    }
    
    /**
     * Listado de registros de backdating (solo admin/ingeniero)
     * URL: /proyecto/backdating
     */
    public function index() {
        if (!$this->authHelper->hasRole(['admin', 'ingeniero'])) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }
        
        try {
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'tipo' => $_GET['tipo'] ?? '',
                'fecha_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
            ];
            
            $registros = $this->backdatingModel->obtenerTodos($filtros);
            $estadisticas = $this->backdatingModel->obtenerEstadisticas();
        } catch (Throwable $e) {
            error_log("Error en BackdatingController::index: " . $e->getMessage());
            $registros = [];
            $estadisticas = ['total' => 0, 'pendientes' => 0, 'aprobados' => 0, 'rechazados' => 0, 'hoy' => 0];
            $_SESSION['error'] = 'Error al cargar los registros';
        }
        
        $titulo = 'Registros Retroactivos (Backdating)';
        $seccion = 'backdating';
        
        require_once __DIR__ . '/../views/backdating/index.php';
    }
    
    /**
     * Ver detalle de un registro
     * URL: /proyecto/backdating/ver/{id}
     */
    public function ver($id) {
        if (!$this->authHelper->hasRole(['admin', 'ingeniero', 'supervisor'])) {
            $this->redirectWithError('/dashboard', 'No tienes permisos');
            return;
        }
        
        try {
            $registro = $this->backdatingModel->obtenerPorId($id);
            
            if (!$registro) {
                $this->redirectWithError('/backdating', 'Registro no encontrado');
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en BackdatingController::ver: " . $e->getMessage());
            $this->redirectWithError('/backdating', 'Error al cargar el registro');
            return;
        }
        
        $titulo = 'Detalle de Backdating';
        $seccion = 'backdating';
        
        require_once __DIR__ . '/../views/backdating/ver.php';
    }
    
    /**
     * Aprobar un registro
     * URL: /proyecto/backdating/aprobar/{id} (POST)
     */
    public function aprobar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/backdating');
            return;
        }
        
        if (!$this->authHelper->hasRole(['admin', 'ingeniero'])) {
            $this->redirectWithError('/backdating', 'No tienes permisos para aprobar');
            return;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/backdating', 'Token de seguridad inválido');
            return;
        }
        
        try {
            $resultado = $this->backdatingModel->aprobar($id, $this->authHelper->getUserId());
            
            if ($resultado) {
                $this->redirectWithSuccess('/backdating', 'Registro aprobado correctamente');
            } else {
                $this->redirectWithError('/backdating', 'Error al aprobar el registro');
            }
        } catch (Throwable $e) {
            error_log("Error en BackdatingController::aprobar: " . $e->getMessage());
            $this->redirectWithError('/backdating', 'Error al aprobar el registro');
        }
    }
    
    /**
     * Rechazar un registro
     * URL: /proyecto/backdating/rechazar/{id} (POST)
     */
    public function rechazar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/backdating');
            return;
        }
        
        if (!$this->authHelper->hasRole(['admin', 'ingeniero'])) {
            $this->redirectWithError('/backdating', 'No tienes permisos para rechazar');
            return;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/backdating', 'Token de seguridad inválido');
            return;
        }
        
        $motivo = trim($_POST['motivo_rechazo'] ?? '');
        
        if (empty($motivo)) {
            $this->redirectWithError('/backdating', 'Debes indicar el motivo del rechazo');
            return;
        }
        
        try {
            $resultado = $this->backdatingModel->rechazar($id, $this->authHelper->getUserId(), $motivo);
            
            if ($resultado) {
                $this->redirectWithSuccess('/backdating', 'Registro rechazado');
            } else {
                $this->redirectWithError('/backdating', 'Error al rechazar el registro');
            }
        } catch (Throwable $e) {
            error_log("Error en BackdatingController::rechazar: " . $e->getMessage());
            $this->redirectWithError('/backdating', 'Error al rechazar el registro');
        }
    }
    
    /**
     * API: validar una fecha retroactiva (AJAX)
     * URL: /proyecto/backdating/validar-fecha (POST)
     */
    public function validarFecha() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['valido' => false, 'error' => 'No autorizado']);
            exit;
        }
        
        try {
            $fecha = $_POST['fecha'] ?? $_GET['fecha'] ?? '';
            $rol = $this->authHelper->getRole();
            
            $resultado = BackdatingHelper::validarFecha($fecha, $rol);
            
            // Info adicional
            $resultado['limite_dias'] = BackdatingHelper::getLimitePorRol($rol);
            $resultado['puede_backdating'] = BackdatingHelper::puedeHacerBackdating($rol);
            
            echo json_encode($resultado);
        } catch (Throwable $e) {
            error_log("Error en validarFecha: " . $e->getMessage());
            echo json_encode(['valido' => false, 'error' => 'Error al validar la fecha']);
        }
        exit;
    }
}