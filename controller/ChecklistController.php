<?php
// controller/ChecklistController.php
// Controlador del módulo genérico de checklists (CRUD de tipos, secciones, campos)

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../model/ChecklistModel.php';

class ChecklistController extends Controller
{
    private $model;

    public function __construct()
    {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }

        $this->model = new ChecklistModel();
    }

    /**
     * Listado de tipos de checklist
     * URL: /checklists
     */
    public function index()
    {
        try {
            $filtros = [
                'buscar' => $this->get('buscar', ''),
                'area'   => $this->get('area', ''),
                'activo' => 1,
            ];

            $tipos = $this->model->obtenerTipos($filtros);

        } catch (Throwable $e) {
            error_log("Error en ChecklistController::index: " . $e->getMessage());
            $tipos = [];
            $_SESSION['error'] = 'Error al cargar los checklists';
        }

        $titulo = 'Checklists Operativos';
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/index.php';
    }

    /**
     * Ver detalle de un tipo
     * URL: /checklists/ver/{id}
     */
    public function ver($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido';
            $this->redirect('/checklists');
        }

        try {
            $tipo = $this->model->obtenerTipoPorId($id);
            if (!$tipo) {
                $_SESSION['error'] = 'Checklist no encontrado';
                $this->redirect('/checklists');
            }
        } catch (Throwable $e) {
            error_log("Error en ChecklistController::ver: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el checklist';
            $this->redirect('/checklists');
        }

        $titulo = 'Detalle: ' . $tipo['nombre'];
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/ver.php';
    }
}
?>
