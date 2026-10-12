<?php
// controller/IngenieroController.php
// Panel del rol Ingeniero
// ✅ FIX: admin también puede acceder (hace de ingeniero en la práctica)
// ✅ FIX: try/catch (Throwable) en el constructor
// ✅ FIX: verificación de rol por getRole() (sin depender de hasRole)

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
            return;
        }

        // ✅ FIX: admin también puede acceder (hace de ingeniero en la práctica)
        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['ingeniero', 'admin'], true)) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }

        try {
            $this->firmaModel = new FirmaModel();
            $this->ordenModel = new OrdenTrabajo();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en IngenieroController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el panel');
            return;
        }
    }

    public function index() {
        try {
            // ✅ Si es admin, mostrar pendientes de ingeniero (los que puede firmar con override)
            $rol_a_consultar = 'ingeniero';
            $pendientes = $this->firmaModel->obtenerPendientesPorRol($rol_a_consultar);
            $total_pendientes = count($pendientes);
            $estadisticas = $this->ordenModel->obtenerEstadisticas();
        } catch (Throwable $e) {
            error_log("Error en IngenieroController::index: " . $e->getMessage());
            $pendientes = [];
            $total_pendientes = 0;
            $estadisticas = [];
        }

        $titulo = 'Panel del Ingeniero';
        $seccion = 'ingeniero';

        require_once __DIR__ . '/../views/ingeniero/index.php';
    }
}