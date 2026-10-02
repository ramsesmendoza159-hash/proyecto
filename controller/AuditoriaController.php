<?php
// controller/AuditoriaController.php
// Panel de Auditoría - Solo Admin - VERSIÓN COMPLETA CORREGIDA
// ✅ FIX: header('Location: /proyecto/...') → $this->redirect()
// ✅ FIX: catch (PDOException) → catch (Throwable)
// ✅ FIX: NO exponer $e->getMessage() al usuario
// ✅ FIX: try/catch en index() y ver()
// ✅ FIX: uso consistente de redirectWithSuccess/Error

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
            $this->redirect('/auth/login');
            return;
        }
        
        // ✅ SOLO ADMIN puede ver auditoría
        if (!$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
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
        
        try {
            $registros = $this->auditoriaModel->obtenerTodos($filtros);
            $estadisticas = $this->auditoriaModel->obtenerEstadisticas();
            $acciones = $this->auditoriaModel->obtenerAcciones();
        } catch (Throwable $e) {
            error_log("Error en AuditoriaController::index: " . $e->getMessage());
            $registros = [];
            $estadisticas = [
                'total' => 0, 'usuarios_distintos' => 0, 'acciones_distintas' => 0,
                'tablas_distintas' => 0, 'logins' => 0, 'logouts' => 0,
                'creaciones' => 0, 'actualizaciones' => 0, 'eliminaciones' => 0,
                'cambios_estado' => 0, 'cierres' => 0
            ];
            $acciones = [];
            $_SESSION['error'] = 'Error al cargar la auditoría';
        }
        
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
        
        if ($id <= 0) {
            $this->redirectWithError('/auditoria', 'ID inválido');
            return;
        }
        
        try {
            $registro = $this->auditoriaModel->obtenerPorId($id);
            
            if (!$registro) {
                $this->redirectWithError('/auditoria', 'Registro no encontrado');
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en AuditoriaController::ver: " . $e->getMessage());
            $this->redirectWithError('/auditoria', 'Error al cargar el registro');
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
            $this->redirectWithError('/auditoria', 'Token de seguridad inválido');
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
                $this->redirectWithSuccess('/auditoria', "Auditoría limpiada ($cantidad registros eliminados)");
                return;
                
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al limpiar auditoría: " . $e->getMessage());
                // ✅ FIX: NO exponer $e->getMessage() al usuario
                $this->redirectWithError('/auditoria', 'Error al limpiar la auditoría');
                return;
            }
        }
        
        $this->redirect('/auditoria');
    }
}