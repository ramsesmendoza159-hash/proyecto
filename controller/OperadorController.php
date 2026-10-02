<?php
// controller/OperadorController.php
// Panel de Operador - VERSIÓN COMPLETA CON PERMISOS DE ADMIN
// ✅ FIX: header() → $this->redirect()
// ✅ FIX: try/catch (Throwable)

require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../model/PlantasModel.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Controller.php';

class OperadorController extends Controller {
    
    private $ordenModel;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn() || !$this->authHelper->isOperador()) {
            $this->redirect('/auth/login');
        }
        
        $this->ordenModel = new OrdenTrabajo();
    }

    public function index() {
        try {
            $ordenes = $this->ordenModel->obtenerTodos();
            $estadisticas = $this->calcularEstadisticas($ordenes);
            
            $plantasModel = new PlantasModel();
            $plantas = $plantasModel->obtenerTodos();
        } catch (Throwable $e) {
            error_log("Error en OperadorController::index: " . $e->getMessage());
            $ordenes = [];
            $estadisticas = ['total' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'completadas' => 0];
            $plantas = [];
        }
        
        $titulo = "Panel de Operador";
        $seccion = "operador";
        require_once __DIR__ . '/../views/operador/index.php';
    }

    public function ordenes() {
        try {
            $ordenes = $this->ordenModel->obtenerTodos();
        } catch (Throwable $e) {
            error_log("Error en OperadorController::ordenes: " . $e->getMessage());
            $ordenes = [];
        }
        
        $titulo = "Gestión de Órdenes";
        $seccion = "ordenes";
        require_once __DIR__ . '/../views/operador/ordenes.php';
    }

    public function crear_orden() {
        $this->redirect('/ordenes/crear');
    }

    private function calcularEstadisticas($ordenes) {
        $total = count($ordenes);
        $pendientes = 0;
        $en_proceso = 0;
        $completadas = 0;
        
        foreach ($ordenes as $orden) {
            $status = $orden['status'] ?? 'PENDIENTE';
            if ($status === 'PENDIENTE') {
                $pendientes++;
            } elseif ($status === 'EN_PROCESO') {
                $en_proceso++;
            } elseif ($status === 'CERRADA' || $status === 'APROBADA') {
                $completadas++;
            }
        }
        
        return [
            'total' => $total, 
            'pendientes' => $pendientes, 
            'en_proceso' => $en_proceso, 
            'completadas' => $completadas
        ];
    }
}