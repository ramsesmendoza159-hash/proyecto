<?php
// controller/OperadorController.php
// Panel de Operador - VERSIÓN COMPLETA CON PERMISOS DE ADMIN
// ✅ FIX: El operador ahora puede ver TODAS las órdenes, no solo las que él creó

require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../model/PlantasModel.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Controller.php';

class OperadorController extends Controller {
    
    private $ordenModel;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn() || !$this->authHelper->isOperador()) {
            header('Location: /proyecto/auth/login');
            exit();
        }
        
        $this->ordenModel = new OrdenTrabajo();
    }

    /**
     * Dashboard del Operador - CON DASHBOARD COMPLETO
     * ✅ FIX: Ahora ve TODAS las órdenes del sistema, igual que admin
     * URL: /operador
     */
    public function index() {
        // ✅ FIX: Ver TODAS las órdenes, no solo las del operador
        $ordenes = $this->ordenModel->obtenerTodos();
        $estadisticas = $this->calcularEstadisticas($ordenes);
        
        // Cargar plantas para el filtro
        $plantasModel = new PlantasModel();
        $plantas = $plantasModel->obtenerTodos();
        
        $titulo = "Panel de Operador";
        $seccion = "operador";
        require_once __DIR__ . '/../views/operador/index.php';
    }

    /**
     * Ver TODAS las órdenes (igual que admin)
     * ✅ FIX: Ahora ve todas las órdenes, no solo las suyas
     * URL: /operador/ordenes
     */
    public function ordenes() {
        // ✅ FIX: Ver TODAS las órdenes
        $ordenes = $this->ordenModel->obtenerTodos();
        
        $titulo = "Gestión de Órdenes";
        $seccion = "ordenes";
        require_once __DIR__ . '/../views/operador/ordenes.php';
    }

    /**
     * Crear nueva orden (redirige al formulario de órdenes)
     * URL: /operador/crear_orden
     */
    public function crear_orden() {
        $seccion = "crear_orden";
        header('Location: /proyecto/ordenes/crear');
        exit();
    }

    /**
     * Calcular estadísticas de órdenes
     */
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
?>