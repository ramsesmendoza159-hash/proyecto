<?php
// controller/DashboardController.php
// Panel de redirección según rol + Dashboard admin
require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../config/database.php';

class DashboardController extends Controller {

    private $db;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $_SESSION['error'] = 'Debes iniciar sesión para acceder al dashboard';
            $this->redirect('/auth/login');
            return;
        }

        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la BD en DashboardController: " . $e->getMessage());
            $this->redirectWithError('/auth/login', 'Error de conexión');
            return;
        }
    }

    public function index() {
        $rol = $this->authHelper->getRole();

        switch ($rol) {
            case 'admin':
                $this->adminDashboard();
                break;
            case 'supervisor':
                $this->redirect('/supervisor');
                return;
            case 'tecnico':
                $this->redirect('/tecnico');
                return;
            case 'almacen':
                $this->redirect('/almacen');
                return;
            case 'operador':
                $this->redirect('/operador');
                return;
            case 'consultor':
                $this->redirect('/consultor');
                return;
            case 'ingeniero':
                $this->redirect('/ingeniero');
                return;
            case 'calidad':
                $this->redirect('/calidad');
                return;
            case 'seguridad':
                $this->redirect('/seguridad');
                return;
            default:
                $_SESSION['error'] = 'Rol de usuario no válido';
                $this->redirect('/auth/login');
                return;
        }
    }

    private function adminDashboard() {
        try {
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

            // ✅ FIX 3: fallback si los métodos no existen
            $total_plantas = method_exists($plantasModel, 'obtenerTotal')
                ? $plantasModel->obtenerTotal()
                : 0;

            $total_gastado = method_exists($ordenModel, 'obtenerTotalGastado')
                ? $ordenModel->obtenerTotalGastado()
                : 0;

            $ordenes_recientes = $ordenModel->obtenerTodos([], 5, 0);

        } catch (Throwable $e) {
            error_log("Error en adminDashboard: " . $e->getMessage());

            $estadisticas_ordenes = [
                'total' => 0, 'pendientes' => 0, 'en_proceso' => 0,
                'cerradas' => 0, 'canceladas' => 0, 'aprobadas' => 0,
                'ejecutadas' => 0, 'rechazadas' => 0, 'devueltas' => 0
            ];
            $estadisticas_tecnicos = [
                'total' => 0, 'activos' => 0, 'inactivos' => 0,
                'especialidades' => 0, 'tarifa_promedio' => 0, 'total_ordenes' => 0
            ];
            $estadisticas_supervisores = [
                'total' => 0, 'activos' => 0, 'inactivos' => 0,
                'areas' => 0, 'total_ordenes' => 0
            ];
            $statsUsuarios = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
            $total_plantas = 0;
            $total_gastado = 0;
            $ordenes_recientes = [];

            $_SESSION['error'] = 'Error al cargar algunas estadísticas del dashboard';
        }

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
        } catch (Throwable $e) {
            error_log("Error en getUsuariosStats: " . $e->getMessage());
            return ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        }
    }

    /**
     * API: estadísticas globales (usada por admin/dashboard.php via fetch)
     * URL: /api/dashboard/stats
     */
    public function getStats() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->authHelper->isLoggedIn()) {
                echo json_encode(['success' => false, 'error' => 'No autenticado']);
                exit;
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

            $total_plantas = method_exists($plantasModel, 'obtenerTotal')
                ? $plantasModel->obtenerTotal()
                : 0;

            $data = [
                'ordenes' => $stats,
                'tecnicos' => $statsTecnicos,
                'supervisores' => $statsSupervisores,
                'usuarios' => $statsUsuarios,
                'plantas' => ['total' => $total_plantas]
            ];

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            exit;

        } catch (Throwable $e) {
            error_log("Error en getStats: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Error al procesar la solicitud'
            ]);
            exit;
        }
    }
}