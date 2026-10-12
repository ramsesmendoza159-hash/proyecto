<?php
// controller/CalidadController.php
// Panel del rol Calidad
// ✅ FIX 1: return después de cada redirect() en el constructor
// ✅ FIX 2: try/catch defensivo al instanciar modelos
// ✅ FIX 3: verificación de rol por getRole() (sin depender de hasRole)
// ✅ FIX 4: cast seguro de $estadisticas

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
            return;
        }

        $rol = $this->authHelper->getRole();
        if ($rol !== 'calidad') {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }

        try {
            $this->firmaModel = new FirmaModel();
            $this->ordenModel = new OrdenTrabajo();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en CalidadController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el panel');
            return;
        }
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