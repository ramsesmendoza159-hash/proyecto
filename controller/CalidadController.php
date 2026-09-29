<?php
// controller/CalidadController.php
// Panel del rol Calidad

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
            exit;
        }
        
        if (!$this->authHelper->hasRole('calidad')) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            exit;
        }
        
        $this->firmaModel = new FirmaModel();
        $this->ordenModel = new OrdenTrabajo();
    }
    
    public function index() {
        $pendientes = $this->firmaModel->obtenerPendientesPorRol('calidad');
        $total_pendientes = count($pendientes);
        
        $estadisticas = $this->ordenModel->obtenerEstadisticas();
        
        $titulo = 'Panel de Calidad';
        $seccion = 'calidad';
        
        require_once __DIR__ . '/../views/calidad/index.php';
    }
}
