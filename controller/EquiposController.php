<?php
// controller/EquiposController.php
// Gestión de Equipos/Máquinas
// ✅ FIX 1: return después de cada redirect() en el constructor
// ✅ FIX 2: usar getRole() en vez de isAdmin/isSupervisor/isConsultor
// ✅ FIX 3: try/catch defensivo en el constructor
// ✅ FIX 4: NO exponer errores de BD al usuario
// ✅ FIX 5: jsonResponse reemplazado por echo json_encode() + exit
// ✅ FIX 6: sanitizar filtros en index() y buscar()

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/EquiposModel.php';
require_once __DIR__ . '/../model/AreasModel.php';
require_once __DIR__ . '/../model/PlantasModel.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class EquiposController extends Controller {

    private $equipoModel;
    private $areaModel;
    private $plantaModel;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }

        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['admin', 'supervisor', 'consultor'], true)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            return;
        }

        try {
            $this->equipoModel = new EquiposModel();
            $this->areaModel = new AreasModel();
            $this->plantaModel = new PlantasModel();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en EquiposController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el módulo');
            return;
        }
    }

    private function puedeEditar() {
        $rol = $this->authHelper->getRole();
        return in_array($rol, ['admin', 'supervisor'], true);
    }

    private function validarAño($año_input) {
        if (empty($año_input)) {
            return ['valor' => null, 'error' => null];
        }

        $año_input = trim($año_input);

        if (!is_numeric($año_input)) {
            return ['valor' => null, 'error' => 'El año de fabricación debe ser un número válido'];
        }

        $año = (int)$año_input;

        if ($año < 1901 || $año > 2155) {
            return ['valor' => null, 'error' => 'El año de fabricación debe estar entre 1901 y 2155'];
        }

        return ['valor' => $año, 'error' => null];
    }

    public function index() {
        $filtros = [
            'buscar'           => trim((string)($_GET['buscar'] ?? '')),
            'estado'           => trim((string)($_GET['estado'] ?? '')),
            'estado_operativo' => trim((string)($_GET['estado_operativo'] ?? '')),
            'id_area'          => !empty($_GET['id_area']) ? (int)$_GET['id_area'] : '',
        ];

        try {
            $equipos = $this->equipoModel->obtenerTodos($filtros);
            $estadisticas = $this->equipoModel->obtenerEstadisticas();
            $areas = $this->areaModel->obtenerActivos();
            $plantas = $this->plantaModel->obtenerTodos();
        } catch (Throwable $e) {
            error_log("Error en EquiposController::index: " . $e->getMessage());
            $equipos = [];
            $estadisticas = ['total_equipos' => 0, 'activos' => 0];
            $areas = [];
            $plantas = [];
        }

        $titulo = 'Gestión de Equipos';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/index.php';
    }

    public function crear() {
        if (!$this->puedeEditar()) {
            $this->redirectWithError('/equipos', 'No tienes permisos para crear equipos');
            return;
        }

        unset($_SESSION['error'], $_SESSION['errores']);

        try {
            $areas = $this->areaModel->obtenerActivos();
            $plantas = $this->plantaModel->obtenerTodos();
        } catch (Throwable $e) {
            error_log("Error en EquiposController::crear: " . $e->getMessage());
            $areas = [];
            $plantas = [];
        }

        $titulo = 'Nuevo Equipo';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/crear.php';
    }

    public function guardar() {
        if (!$this->puedeEditar()) {
            $this->redirectWithError('/equipos', 'No tienes permisos para crear equipos');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/equipos');
            return;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/equipos/crear', 'Token de seguridad inválido');
            return;
        }

        try {
            $errores = [];

            if (empty($_POST['nombre'])) {
                $errores[] = 'El nombre del equipo es obligatorio';
            }
            if (empty($_POST['id_area'])) {
                $errores[] = 'El área es obligatoria';
            }

            $resultado_año = $this->validarAño($_POST['año_fabricacion'] ?? '');
            if ($resultado_año['error']) {
                $errores[] = $resultado_año['error'];
            }
            $año = $resultado_año['valor'];

            if (!empty($errores)) {
                unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
                $_SESSION['errores'] = $errores;
                $_SESSION['old'] = $_POST;
                $this->redirect('/equipos/crear');
                return;
            }

            $datos = [
                'id_area'           => (int)$_POST['id_area'],
                'nombre'            => $_POST['nombre'],
                'descripcion'       => $_POST['descripcion'] ?? '',
                'descripcion_tec'   => $_POST['descripcion_tec'] ?? '',
                'especificacion'    => $_POST['especificacion'] ?? '',
                'fabricante'        => $_POST['fabricante'] ?? '',
                'modelo'            => $_POST['modelo'] ?? '',
                'marca'             => $_POST['marca'] ?? '',
                'serie'             => $_POST['serie'] ?? '',
                'codigo'            => $_POST['codigo'] ?? '',
                'numero_se'         => $_POST['numero_se'] ?? '',
                'año_fabricacion'   => $año,
                'manual'            => $_POST['manual'] ?? '',
                'fecha_instalacion' => !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : null,
                'ries'              => $_POST['ries'] ?? '',
                'estado'            => $_POST['estado'] ?? 'activo',
                'estado_operativo'  => $_POST['estado_operativo'] ?? 'Operativo',
            ];

            $id = $this->equipoModel->crear($datos);

            if ($id) {
                unset($_SESSION['error'], $_SESSION['errores'], $_SESSION['old']);
                $this->redirectWithSuccess('/equipos', 'Equipo creado correctamente (ID: ' . $id . ')');
                return;
            } else {
                unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
                // ✅ FIX: NO exponer $e->getMessage() ni getLastError()
                $_SESSION['error'] = 'Error al crear el equipo. Verifica los datos e intenta nuevamente.';
                $_SESSION['old'] = $_POST;
                $this->redirect('/equipos/crear');
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en EquiposController::guardar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear el equipo. Intenta nuevamente.';
            $_SESSION['old'] = $_POST;
            $this->redirect('/equipos/crear');
            return;
        }
    }

    public function ver($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/equipos', 'ID de equipo inválido');
            return;
        }

        try {
            $equipo = $this->equipoModel->obtenerPorId($id);

            if (!$equipo) {
                $this->redirectWithError('/equipos', 'Equipo no encontrado');
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en EquiposController::ver: " . $e->getMessage());
            $this->redirectWithError('/equipos', 'Error al cargar el equipo');
            return;
        }

        $titulo = 'Detalle del Equipo';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/ver.php';
    }

    public function editar($id) {
        if (!$this->puedeEditar()) {
            $this->redirectWithError('/equipos', 'No tienes permisos para editar equipos');
            return;
        }

        unset($_SESSION['error'], $_SESSION['errores']);

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/equipos', 'ID de equipo inválido');
            return;
        }

        try {
            $equipo = $this->equipoModel->obtenerPorId($id);

            if (!$equipo) {
                $this->redirectWithError('/equipos', 'Equipo no encontrado');
                return;
            }

            $areas = $this->areaModel->obtenerActivos();
            $plantas = $this->plantaModel->obtenerTodos();
        } catch (Throwable $e) {
            error_log("Error en EquiposController::editar: " . $e->getMessage());
            $this->redirectWithError('/equipos', 'Error al cargar el equipo');
            return;
        }

        $titulo = 'Editar Equipo';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/editar.php';
    }

    public function actualizar($id) {
        if (!$this->puedeEditar()) {
            $this->redirectWithError('/equipos', 'No tienes permisos para editar equipos');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/equipos');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/equipos', 'ID de equipo inválido');
            return;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/equipos/editar/' . $id, 'Token de seguridad inválido');
            return;
        }

        try {
            $errores = [];

            if (empty($_POST['nombre'])) {
                $errores[] = 'El nombre del equipo es obligatorio';
            }
            if (empty($_POST['id_area'])) {
                $errores[] = 'El área es obligatoria';
            }

            $resultado_año = $this->validarAño($_POST['año_fabricacion'] ?? '');
            if ($resultado_año['error']) {
                $errores[] = $resultado_año['error'];
            }
            $año = $resultado_año['valor'];

            if (!empty($errores)) {
                unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
                $_SESSION['errores'] = $errores;
                $_SESSION['old'] = $_POST;
                $this->redirect('/equipos/editar/' . $id);
                return;
            }

            $datos = [
                'id_area'           => (int)$_POST['id_area'],
                'nombre'            => $_POST['nombre'],
                'descripcion'       => $_POST['descripcion'] ?? '',
                'descripcion_tec'   => $_POST['descripcion_tec'] ?? '',
                'especificacion'    => $_POST['especificacion'] ?? '',
                'fabricante'        => $_POST['fabricante'] ?? '',
                'modelo'            => $_POST['modelo'] ?? '',
                'marca'             => $_POST['marca'] ?? '',
                'serie'             => $_POST['serie'] ?? '',
                'codigo'            => $_POST['codigo'] ?? '',
                'numero_se'         => $_POST['numero_se'] ?? '',
                'año_fabricacion'   => $año,
                'manual'            => $_POST['manual'] ?? '',
                'fecha_instalacion' => !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : null,
                'ries'              => $_POST['ries'] ?? '',
                'estado'            => $_POST['estado'] ?? 'activo',
                'estado_operativo'  => $_POST['estado_operativo'] ?? 'Operativo',
            ];

            $resultado = $this->equipoModel->actualizar($id, $datos);

            if ($resultado) {
                unset($_SESSION['error'], $_SESSION['errores']);
                $this->redirectWithSuccess('/equipos/ver/' . $id, 'Equipo actualizado correctamente');
                return;
            } else {
                unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
                $_SESSION['error'] = 'Error al actualizar el equipo. Intenta nuevamente.';
                $this->redirect('/equipos/editar/' . $id);
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en EquiposController::actualizar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar el equipo. Intenta nuevamente.';
            $this->redirect('/equipos/editar/' . $id);
            return;
        }
    }

    public function eliminar($id) {
        if (!$this->authHelper->isAdmin()) {
            $this->redirectWithError('/equipos', 'No tienes permisos para eliminar equipos');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/equipos');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/equipos', 'ID de equipo inválido');
            return;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/equipos', 'Token de seguridad inválido');
            return;
        }

        try {
            $resultado = $this->equipoModel->eliminar($id);

            if (is_array($resultado) && empty($resultado['error'])) {
                unset($_SESSION['error']);
                $this->redirectWithSuccess('/equipos', $resultado['message'] ?? 'Equipo eliminado');
                return;
            } else {
                unset($_SESSION['mensaje']);
                $msg = is_array($resultado) ? ($resultado['message'] ?? 'Error al eliminar') : 'Error al eliminar';
                $this->redirectWithError('/equipos', $msg);
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en EquiposController::eliminar: " . $e->getMessage());
            $this->redirectWithError('/equipos', 'Error al eliminar el equipo. Intenta nuevamente.');
            return;
        }
    }

    public function cambiarEstadoOperativo($id) {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->puedeEditar()) {
            echo json_encode(['error' => 'No tienes permisos']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido']);
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        try {
            $estado_operativo = $_POST['estado_operativo'] ?? '';
            $observaciones    = trim((string)($_POST['observaciones'] ?? ''));

            $estados_validos = ['Operativo', 'En Mantenimiento', 'Averiado', 'Fuera de Servicio'];
            if (!in_array($estado_operativo, $estados_validos, true)) {
                echo json_encode(['error' => 'Estado inválido']);
                exit;
            }

            $resultado = $this->equipoModel->cambiarEstadoOperativo($id, $estado_operativo, $observaciones);

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            } else {
                echo json_encode(['error' => 'Error al actualizar']);
            }
        } catch (Throwable $e) {
            error_log("Error en cambiarEstadoOperativo: " . $e->getMessage());
            echo json_encode(['error' => 'Error al actualizar']);
        }
        exit;
    }

    public function buscar() {
        header('Content-Type: application/json; charset=utf-8');

        $term = trim((string)($_GET['term'] ?? ''));
        if (strlen($term) < 2) {
            echo json_encode([]);
            exit;
        }

        try {
            $equipos = $this->equipoModel->buscar($term);
            echo json_encode($equipos);
        } catch (Throwable $e) {
            error_log("Error en EquiposController::buscar: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
}