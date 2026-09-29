<?php
// controller/SeguridadController.php
// Panel del rol Seguridad

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/FirmaModel.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';

class SeguridadController extends Controller {
    
    private $firmaModel;
    private $ordenModel;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            exit;
        }
        
        if (!$this->authHelper->hasRole('seguridad')) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            exit;
        }
        
        $this->firmaModel = new FirmaModel();
        $this->ordenModel = new OrdenTrabajo();
    }
    
    public function index() {
        $pendientes = $this->firmaModel->obtenerPendientesPorRol('seguridad');
        $total_pendientes = count($pendientes);
        
        $estadisticas = $this->ordenModel->obtenerEstadisticas();
        
        $titulo = 'Panel de Seguridad';
        $seccion = 'seguridad';
        
        require_once __DIR__ . '/../views/seguridad/index.php';
    }
}