<?php
// controller/ConsultorController.php
// Panel de Consultor - VERSIÓN CORREGIDA

require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../helpers/Controller.php';

class ConsultorController extends Controller {
    
    private $ordenModel;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn() || !$this->authHelper->isConsultor()) {
            header('Location: /proyecto/auth/login');
            exit();
        }
        
        $this->ordenModel = new OrdenTrabajo();
    }

    /**
     * Dashboard del Consultor
     * URL: /consultor
     */
    public function index() {
        $estadisticas = $this->ordenModel->obtenerEstadisticas();
        $ordenes_recientes = $this->ordenModel->obtenerTodos([], 10, 0);
        
        $titulo = "Panel de Consultor";
        $seccion = "consultor"; // ✅ Resalta "Dashboard" en el sidebar
        require_once __DIR__ . '/../views/consultor/index.php';
    }

    /**
     * Ver todas las órdenes - CORREGIDO
     * URL: /consultor/ordenes
     */
    public function ordenes() {
        $ordenes = $this->ordenModel->obtenerTodos();
        
        $titulo = "Órdenes de Trabajo";
        $seccion = "ordenes"; // ✅ Resalta "Ver Órdenes" en el sidebar
        require_once __DIR__ . '/../views/consultor/ordenes.php';
    }

    /**
     * Ver detalle de una orden
     * URL: /consultor/ver_orden/{id}
     */
    public function ver_orden($id) {
        $orden = $this->ordenModel->obtenerPorId($id);
        
        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/consultor/ordenes');
            exit();
        }
        
        $titulo = "Detalle de Orden";
        $seccion = "ordenes"; // ✅ Resalta "Ver Órdenes" en el sidebar
        require_once __DIR__ . '/../views/consultor/ver_orden.php';
    }
}
?>
