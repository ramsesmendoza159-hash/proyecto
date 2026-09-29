<?php
// controller/AuditoriaController.php
// Panel de Auditoría - Solo Admin - VERSIÓN COMPLETA CORREGIDA

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/database.php';

class AuditoriaController extends Controller {
    
    private $auditoriaModel;
    
    public function __construct() {
        parent::__construct();
        
        // Verificar autenticación
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        // ✅ SOLO ADMIN puede ver auditoría
        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $this->auditoriaModel = new AuditoriaModel();
    }
    
    /**
     * Panel de auditoría
     * URL: /auditoria
     */
    public function index() {
        $filtros = [
            'usuario_id' => $_GET['usuario_id'] ?? null,
            'accion' => $_GET['accion'] ?? null,
            'tabla' => $_GET['tabla'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null
        ];
        
        $registros = $this->auditoriaModel->obtenerTodos($filtros);
        $estadisticas = $this->auditoriaModel->obtenerEstadisticas();
        $acciones = $this->auditoriaModel->obtenerAcciones();
        
        $titulo = "Auditoría del Sistema";
        $seccion = "auditoria";
        
        require_once __DIR__ . '/../views/admin/auditoria/index.php';
    }
    
    /**
     * Ver detalle de un registro de auditoría
     * URL: /auditoria/ver/{id}
     */
    public function ver($id) {
        $id = (int)$id;
        $registro = $this->auditoriaModel->obtenerPorId($id);
        
        if (!$registro) {
            $_SESSION['error'] = 'Registro no encontrado';
            $this->redirect('/auditoria');
            return;
        }
        
        $titulo = "Detalle de Auditoría";
        $seccion = "auditoria";
        
        require_once __DIR__ . '/../views/admin/auditoria/ver.php';
    }
    
    /**
     * Limpiar auditoría (solo admin)
     * URL: /auditoria/limpiar (POST)
     */
    public function limpiar() {
        // Solo POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auditoria');
            return;
        }
        
        // Verificar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/auditoria');
            return;
        }
        
        // Confirmar acción
        if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
            try {
                $db = Database::getInstance()->getConnection();
                $sql = "DELETE FROM auditoria WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 90 DAY)";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                
                $cantidad = $stmt->rowCount();
                $_SESSION['mensaje'] = "✅ Auditoría limpiada ($cantidad registros eliminados)";
                $_SESSION['mensaje_tipo'] = 'success';
                
            } catch (PDOException $e) {
                error_log("Error al limpiar auditoría: " . $e->getMessage());
                $_SESSION['error'] = '❌ Error al limpiar la auditoría: ' . $e->getMessage();
            }
        }
        
        $this->redirect('/auditoria');
    }
}
?>
