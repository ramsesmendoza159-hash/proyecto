<?php
// controller/IngenieroController.php
// Panel del rol Ingeniero

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
            exit;
        }
        
        if (!$this->authHelper->hasRole('ingeniero')) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            exit;
        }
        
        $this->firmaModel = new FirmaModel();
        $this->ordenModel = new OrdenTrabajo();
    }
    
    /**
     * Dashboard del Ingeniero
     */
    public function index() {
        $pendientes = $this->firmaModel->obtenerPendientesPorRol('ingeniero');
        $total_pendientes = count($pendientes);
        
        // Estadísticas generales
        $estadisticas = $this->ordenModel->obtenerEstadisticas();
        
        $titulo = 'Panel del Ingeniero';
        $seccion = 'ingeniero';
        
        require_once __DIR__ . '/../views/ingeniero/index.php';
    }
}