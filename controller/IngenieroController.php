<?php
// controller/IngenieroController.php
// Panel del rol Ingeniero
// ✅ FIX: try/catch (Throwable) en index()

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/FirmaModel.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';

class IngenieroController extends Controller {
    
    private $firmaModel;
    private $ordenModel;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        if (!$this->authHelper->hasRole('ingeniero')) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }
        
        $this->firmaModel = new FirmaModel();
        $this->ordenModel = new OrdenTrabajo();
    }
    
    public function index() {
        try {
            $pendientes = $this->firmaModel->obtenerPendientesPorRol('ingeniero');
            $total_pendientes = count($pendientes);
            $estadisticas = $this->ordenModel->obtenerEstadisticas();
        } catch (Throwable $e) {
            error_log("Error en IngenieroController::index: " . $e->getMessage());
            $pendientes = [];
            $total_pendientes = 0;
            $estadisticas = [];
        }
        
        $titulo = 'Panel del Ingeniero';
        $seccion = 'ingeniero';
        
        require_once __DIR__ . '/../views/ingeniero/index.php';
    }
}