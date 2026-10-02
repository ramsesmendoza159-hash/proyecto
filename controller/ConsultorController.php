<?php
// controller/ConsultorController.php
// Panel de Consultor - VERSIÓN CORREGIDA
// ✅ FIX: header() → $this->redirect()
// ✅ FIX: try/catch (Throwable) en métodos de lectura

require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../helpers/Controller.php';

class ConsultorController extends Controller {
    
    private $ordenModel;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn() || !$this->authHelper->isConsultor()) {
            $this->redirect('/auth/login');
        }
        
        $this->ordenModel = new OrdenTrabajo();
    }

    public function index() {
        try {
            $estadisticas = $this->ordenModel->obtenerEstadisticas();
            $ordenes_recientes = $this->ordenModel->obtenerTodos([], 10, 0);
        } catch (Throwable $e) {
            error_log("Error en ConsultorController::index: " . $e->getMessage());
            $estadisticas = [];
            $ordenes_recientes = [];
        }
        
        $titulo = "Panel de Consultor";
        $seccion = "consultor";
        require_once __DIR__ . '/../views/consultor/index.php';
    }

    public function ordenes() {
        try {
            $ordenes = $this->ordenModel->obtenerTodos();
        } catch (Throwable $e) {
            error_log("Error en ConsultorController::ordenes: " . $e->getMessage());
            $ordenes = [];
        }
        
        $titulo = "Órdenes de Trabajo";
        $seccion = "ordenes";
        require_once __DIR__ . '/../views/consultor/ordenes.php';
    }

    public function ver_orden($id) {
        try {
            $orden = $this->ordenModel->obtenerPorId($id);
            
            if (!$orden) {
                $this->redirectWithError('/consultor/ordenes', 'Orden no encontrada');
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en ConsultorController::ver_orden: " . $e->getMessage());
            $this->redirectWithError('/consultor/ordenes', 'Error al cargar la orden');
            return;
        }
        
        $titulo = "Detalle de Orden";
        $seccion = "ordenes";
        require_once __DIR__ . '/../views/consultor/ver_orden.php';
    }
}