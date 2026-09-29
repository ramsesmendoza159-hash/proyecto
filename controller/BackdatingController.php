<?php
// controller/BackdatingController.php
// Controlador para gestión de backdating

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/BackdatingHelper.php';
require_once __DIR__ . '/../model/BackdatingModel.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class BackdatingController extends Controller {
    
    private $backdatingModel;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        $this->backdatingModel = new BackdatingModel();
    }
    
    /**
     * Listado de registros de backdating (solo admin/ingeniero)
     * URL: /proyecto/backdating
     */
    public function index() {
        if (!$this->authHelper->hasRole(['admin', 'ingeniero'])) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $filtros = [
            'estado' => $_GET['estado'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
        ];
        
        $registros = $this->backdatingModel->obtenerTodos($filtros);
        $estadisticas = $this->backdatingModel->obtenerEstadisticas();
        
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
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $registro = $this->backdatingModel->obtenerPorId($id);
        
        if (!$registro) {
            $_SESSION['error'] = 'Registro no encontrado';
            header('Location: /proyecto/backdating');
            exit;
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
            header('Location: /proyecto/backdating');
            exit;
        }
        
        if (!$this->authHelper->hasRole(['admin', 'ingeniero'])) {
            $_SESSION['error'] = 'No tienes permisos para aprobar';
            header('Location: /proyecto/backdating');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/backdating');
            exit;
        }
        
        $resultado = $this->backdatingModel->aprobar($id, $this->authHelper->getUserId());
        
        if ($resultado) {
            $_SESSION['mensaje'] = '✅ Registro aprobado correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error al aprobar el registro';
        }
        
        header('Location: /proyecto/backdating');
        exit;
    }
    
    /**
     * Rechazar un registro
     * URL: /proyecto/backdating/rechazar/{id} (POST)
     */
    public function rechazar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/backdating');
            exit;
        }
        
        if (!$this->authHelper->hasRole(['admin', 'ingeniero'])) {
            $_SESSION['error'] = 'No tienes permisos para rechazar';
            header('Location: /proyecto/backdating');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/backdating');
            exit;
        }
        
        $motivo = $_POST['motivo_rechazo'] ?? '';
        
        if (empty($motivo)) {
            $_SESSION['error'] = 'Debes indicar el motivo del rechazo';
            header('Location: /proyecto/backdating');
            exit;
        }
        
        $resultado = $this->backdatingModel->rechazar($id, $this->authHelper->getUserId(), $motivo);
        
        if ($resultado) {
            $_SESSION['mensaje'] = '✅ Registro rechazado';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error al rechazar el registro';
        }
        
        header('Location: /proyecto/backdating');
        exit;
    }
    
    /**
     * API: validar una fecha retroactiva (AJAX)
     * URL: /proyecto/backdating/validar-fecha (POST)
     */
    public function validarFecha() {
        header('Content-Type: application/json');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['valido' => false, 'error' => 'No autorizado']);
            exit;
        }
        
        $fecha = $_POST['fecha'] ?? $_GET['fecha'] ?? '';
        $rol = $this->authHelper->getRole();
        
        $resultado = BackdatingHelper::validarFecha($fecha, $rol);
        
        // Info adicional
        $resultado['limite_dias'] = BackdatingHelper::getLimitePorRol($rol);
        $resultado['puede_backdating'] = BackdatingHelper::puedeHacerBackdating($rol);
        
        echo json_encode($resultado);
        exit;
    }
}
?>
