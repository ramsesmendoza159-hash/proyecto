<?php
// controller/DashboardController.php
// VERSIÓN CORREGIDA - CON RUTAS DE NUEVOS ROLES

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../config/database.php';

class DashboardController extends Controller {
    
    private $db;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $_SESSION['error'] = 'Debes iniciar sesión para acceder al dashboard';
            $this->redirect('/auth/login');
        }
        
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $rol = $this->authHelper->getRole();
        
        switch ($rol) {
            case 'admin':
                $this->adminDashboard();
                break;
            case 'supervisor':
                $this->redirect('/supervisor');
                break;
            case 'tecnico':
                $this->redirect('/tecnico');
                break;
            case 'almacen':
                $this->redirect('/almacen');
                break;
            case 'operador':
                $this->redirect('/operador');
                break;
            case 'consultor':
                $this->redirect('/consultor');
                break;
            // ✅ NUEVOS ROLES PARA FIRMAS
            case 'ingeniero':
                $this->redirect('/ingeniero');
                break;
            case 'calidad':
                $this->redirect('/calidad');
                break;
            case 'seguridad':
                $this->redirect('/seguridad');
                break;
            default:
                $_SESSION['error'] = 'Rol de usuario no válido';
                $this->redirect('/auth/login');
                break;
        }
    }
    
    private function adminDashboard() {
        require_once __DIR__ . '/../model/OrdenTrabajo.php';
        require_once __DIR__ . '/../model/Tecnico.php';
        require_once __DIR__ . '/../model/Supervisor.php';
        require_once __DIR__ . '/../model/PlantasModel.php';
        
        $ordenModel = new OrdenTrabajo();
        $tecnicoModel = new Tecnico();
        $supervisorModel = new Supervisor();
        $plantasModel = new PlantasModel();
        
        $estadisticas_ordenes = $ordenModel->obtenerEstadisticas();
        $estadisticas_tecnicos = $tecnicoModel->obtenerEstadisticas();
        $estadisticas_supervisores = $supervisorModel->obtenerEstadisticas();
        $statsUsuarios = $this->getUsuariosStats();
        $total_plantas = $plantasModel->obtenerTotal();
        $total_gastado = $ordenModel->obtenerTotalGastado();
        $ordenes_recientes = $ordenModel->obtenerTodos([], 5, 0);
        
        $titulo = 'Dashboard - Administrador';
        $seccion = 'dashboard';
        $rol = 'admin';
        
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }
    
    private function getUsuariosStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
                    FROM usuarios";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total' => (int)($result['total'] ?? 0),
                'activos' => (int)($result['activos'] ?? 0),
                'inactivos' => (int)($result['inactivos'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Error en getUsuariosStats: " . $e->getMessage());
            return ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        }
    }
    
    public function getStats() {
        try {
            if (!$this->authHelper->isLoggedIn()) {
                $this->jsonResponse(['error' => 'No autenticado'], 401);
            }
            
            require_once __DIR__ . '/../model/OrdenTrabajo.php';
            require_once __DIR__ . '/../model/Tecnico.php';
            require_once __DIR__ . '/../model/Supervisor.php';
            require_once __DIR__ . '/../model/PlantasModel.php';
            
            $ordenModel = new OrdenTrabajo();
            $tecnicoModel = new Tecnico();
            $supervisorModel = new Supervisor();
            $plantasModel = new PlantasModel();
            
            $stats = $ordenModel->obtenerEstadisticas();
            $statsTecnicos = $tecnicoModel->obtenerEstadisticas();
            $statsSupervisores = $supervisorModel->obtenerEstadisticas();
            $statsUsuarios = $this->getUsuariosStats();
            $total_plantas = $plantasModel->obtenerTotal();
            
            $data = [
                'ordenes' => $stats,
                'tecnicos' => $statsTecnicos,
                'supervisores' => $statsSupervisores,
                'usuarios' => $statsUsuarios,
                'plantas' => ['total' => $total_plantas]
            ];
            
            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            error_log("Error en getStats: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
