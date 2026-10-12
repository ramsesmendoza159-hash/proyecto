<?php

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
            return;
        }

        $rol = $this->authHelper->getRole();
        if ($rol !== 'seguridad') {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }

        try {
            $this->firmaModel = new FirmaModel();
            $this->ordenModel = new OrdenTrabajo();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en SeguridadController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el panel');
            return;
        }
    }

    public function index() {
        try {
            $pendientes = $this->firmaModel->obtenerPendientesPorRol('seguridad');
            $total_pendientes = count($pendientes);
            $estadisticas = $this->ordenModel->obtenerEstadisticas();
        } catch (Throwable $e) {
            error_log("Error en SeguridadController::index: " . $e->getMessage());
            $pendientes = [];
            $total_pendientes = 0;
            $estadisticas = [];
        }

        $titulo = 'Panel de Seguridad';
        $seccion = 'seguridad';

        require_once __DIR__ . '/../views/seguridad/index.php';
    }
}