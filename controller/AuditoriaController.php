<?php
// controller/AuditoriaController.php
// Panel de Auditoría - Solo Admin

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/database.php';

class AuditoriaController extends Controller {

    private $auditoriaModel;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }

        if (!$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }

        try {
            $this->auditoriaModel = new AuditoriaModel();
        } catch (Throwable $e) {
            error_log("Error al instanciar AuditoriaModel: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el módulo');
            return;
        }
    }

    /**
     * Panel de auditoría
     * URL: /auditoria
     */
    public function index() {
        // ✅ FIX 2: sanitizar filtros
        $filtros = [
            'usuario_id'  => !empty($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : null,
            'accion'      => !empty($_GET['accion']) ? trim((string)$_GET['accion']) : null,
            'tabla'       => !empty($_GET['tabla']) ? trim((string)$_GET['tabla']) : null,
            'fecha_desde' => !empty($_GET['fecha_desde']) ? trim((string)$_GET['fecha_desde']) : null,
            'fecha_hasta' => !empty($_GET['fecha_hasta']) ? trim((string)$_GET['fecha_hasta']) : null,
        ];

        // ✅ FIX 3: validar formato de fechas
        if (!empty($filtros['fecha_desde']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['fecha_desde'])) {
            $filtros['fecha_desde'] = null;
        }
        if (!empty($filtros['fecha_hasta']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['fecha_hasta'])) {
            $filtros['fecha_hasta'] = null;
        }

        try {
            $registros = $this->auditoriaModel->obtenerTodos($filtros);
            $estadisticas = $this->auditoriaModel->obtenerEstadisticas();
            $acciones = $this->auditoriaModel->obtenerAcciones();
        } catch (Throwable $e) {
            error_log("Error en AuditoriaController::index: " . $e->getMessage());
            $registros = [];
            $estadisticas = [
                'total' => 0, 'usuarios_distintos' => 0, 'acciones_distintas' => 0,
                'tablas_distintas' => 0, 'logins' => 0, 'logouts' => 0,
                'creaciones' => 0, 'actualizaciones' => 0, 'eliminaciones' => 0,
                'cambios_estado' => 0, 'cierres' => 0
            ];
            $acciones = [];
            $_SESSION['error'] = 'Error al cargar la auditoría';
        }

        $titulo = "Auditoría del Sistema";
        $seccion = "auditoria";

        require_once __DIR__ . '/../views/admin/auditoria/index.php';
    }

    /**
     * Ver detalle de un registro de auditoría
     * URL: /auditoria/ver/{id}
     */
    public function ver($id) {
        $id = (int)$id;

        if ($id <= 0) {
            $this->redirectWithError('/auditoria', 'ID inválido');
            return;
        }

        try {
            $registro = $this->auditoriaModel->obtenerPorId($id);

            if (!$registro) {
                $this->redirectWithError('/auditoria', 'Registro no encontrado');
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en AuditoriaController::ver: " . $e->getMessage());
            $this->redirectWithError('/auditoria', 'Error al cargar el registro');
            return;
        }

        $titulo = "Detalle de Auditoría";
        $seccion = "auditoria";

        require_once __DIR__ . '/../views/admin/auditoria/ver.php';
    }

    /**
     * Limpiar auditoría (solo admin)
     * URL: /auditoria/limpiar (POST)
     */
    public function limpiar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auditoria');
            return;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/auditoria', 'Token de seguridad inválido');
            return;
        }

        if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
            try {
                $db = Database::getInstance()->getConnection();
                $sql = "DELETE FROM auditoria WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 90 DAY)";
                $stmt = $db->prepare($sql);
                $stmt->execute();

                $cantidad = $stmt->rowCount();
                $this->redirectWithSuccess('/auditoria', "Auditoría limpiada ($cantidad registros eliminados)");
                return;

            } catch (Throwable $e) {
                error_log("Error al limpiar auditoría: " . $e->getMessage());
                $this->redirectWithError('/auditoria', 'Error al limpiar la auditoría');
                return;
            }
        }

        $this->redirect('/auditoria');
    }
}