<?php
// controller/CalidadController.php
// Panel del rol Calidad
// ✅ FIX: try/catch (Throwable) en index()

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/FirmaModel.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';

class CalidadController extends Controller {
    
    private $firmaModel;
    private $ordenModel;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        if (!$this->authHelper->hasRole('calidad')) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }
        
        $this->firmaModel = new FirmaModel();
        $this->ordenModel = new OrdenTrabajo();
    }
    
    public function index() {
        try {
            $pendientes = $this->firmaModel->obtenerPendientesPorRol('calidad');
            $total_pendientes = count($pendientes);
            $estadisticas = $this->ordenModel->obtenerEstadisticas();
        } catch (Throwable $e) {
            error_log("Error en CalidadController::index: " . $e->getMessage());
            $pendientes = [];
            $total_pendientes = 0;
            $estadisticas = [];
        }
        
        $titulo = 'Panel de Calidad';
        $seccion = 'calidad';
        
        require_once __DIR__ . '/../views/calidad/index.php';
    }
}