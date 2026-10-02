<?php
// controller/EquiposController.php
// Gestión de Equipos/Máquinas - VERSIÓN CORREGIDA CON CONSULTOR
// ✅ FIX: unificado rango de año a 1901-2155 (antes vista usaba 1900-2030)
// ✅ FIX: header('Location: /proyecto/...') → $this->redirect()
// ✅ FIX: try/catch (Throwable) en métodos de escritura
// ✅ FIX: headers JSON con charset=utf-8

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
        }
        
        if (!$this->authHelper->isAdmin() && 
            !$this->authHelper->isSupervisor() && 
            !$this->authHelper->isConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
        }
        
        $this->equipoModel = new EquiposModel();
        $this->areaModel = new AreasModel();
        $this->plantaModel = new PlantasModel();
    }
    
    private function puedeEditar() {
        return $this->authHelper->isAdmin() || $this->authHelper->isSupervisor();
    }
    
    /**
     * Validar año de fabricación
     * ✅ FIX: rango unificado 1901-2155
     */
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
            'buscar' => $_GET['buscar'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'estado_operativo' => $_GET['estado_operativo'] ?? '',
            'id_area' => $_GET['id_area'] ?? ''
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
        
        unset($_SESSION['error']);
        unset($_SESSION['errores']);
        
        $areas = $this->areaModel->obtenerActivos();
        $plantas = $this->plantaModel->obtenerTodos();
        
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
                'id_area' => (int)$_POST['id_area'],
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? '',
                'descripcion_tec' => $_POST['descripcion_tec'] ?? '',
                'especificacion' => $_POST['especificacion'] ?? '',
                'fabricante' => $_POST['fabricante'] ?? '',
                'modelo' => $_POST['modelo'] ?? '',
                'marca' => $_POST['marca'] ?? '',
                'serie' => $_POST['serie'] ?? '',
                'codigo' => $_POST['codigo'] ?? '',
                'numero_se' => $_POST['numero_se'] ?? '',
                'año_fabricacion' => $año,
                'manual' => $_POST['manual'] ?? '',
                'fecha_instalacion' => !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : null,
                'ries' => $_POST['ries'] ?? '',
                'estado' => $_POST['estado'] ?? 'activo',
                'estado_operativo' => $_POST['estado_operativo'] ?? 'Operativo'
            ];
            
            $id = $this->equipoModel->crear($datos);
            
            if ($id) {
                unset($_SESSION['error'], $_SESSION['errores'], $_SESSION['old']);
                $this->redirectWithSuccess('/equipos', 'Equipo creado correctamente (ID: ' . $id . ')');
            } else {
                unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
                $errorReal = method_exists($this->equipoModel, 'getLastError') 
                    ? $this->equipoModel->getLastError() 
                    : 'Error desconocido al crear el equipo';
                $_SESSION['error'] = 'Error al crear el equipo: ' . $errorReal;
                $_SESSION['old'] = $_POST;
                $this->redirect('/equipos/crear');
            }
        } catch (Throwable $e) {
            error_log("Error en EquiposController::guardar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear el equipo';
            $_SESSION['old'] = $_POST;
            $this->redirect('/equipos/crear');
        }
    }

    public function ver($id) {
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
        
        unset($_SESSION['error']);
        unset($_SESSION['errores']);
        
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
                'id_area' => (int)$_POST['id_area'],
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? '',
                'descripcion_tec' => $_POST['descripcion_tec'] ?? '',
                'especificacion' => $_POST['especificacion'] ?? '',
                'fabricante' => $_POST['fabricante'] ?? '',
                'modelo' => $_POST['modelo'] ?? '',
                'marca' => $_POST['marca'] ?? '',
                'serie' => $_POST['serie'] ?? '',
                'codigo' => $_POST['codigo'] ?? '',
                'numero_se' => $_POST['numero_se'] ?? '',
                'año_fabricacion' => $año,
                'manual' => $_POST['manual'] ?? '',
                'fecha_instalacion' => !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : null,
                'ries' => $_POST['ries'] ?? '',
                'estado' => $_POST['estado'] ?? 'activo',
                'estado_operativo' => $_POST['estado_operativo'] ?? 'Operativo'
            ];
            
            $resultado = $this->equipoModel->actualizar($id, $datos);
            
            if ($resultado) {
                unset($_SESSION['error'], $_SESSION['errores']);
                $this->redirectWithSuccess('/equipos/ver/' . $id, 'Equipo actualizado correctamente');
            } else {
                unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
                $_SESSION['error'] = 'Error al actualizar el equipo';
                $this->redirect('/equipos/editar/' . $id);
            }
        } catch (Throwable $e) {
            error_log("Error en EquiposController::actualizar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar el equipo';
            $this->redirect('/equipos/editar/' . $id);
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
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/equipos', 'Token de seguridad inválido');
            return;
        }
        
        try {
            $resultado = $this->equipoModel->eliminar($id);
            
            if (!$resultado['error']) {
                unset($_SESSION['error']);
                $this->redirectWithSuccess('/equipos', $resultado['message']);
            } else {
                unset($_SESSION['mensaje']);
                $this->redirectWithError('/equipos', $resultado['message']);
            }
        } catch (Throwable $e) {
            error_log("Error en EquiposController::eliminar: " . $e->getMessage());
            $this->redirectWithError('/equipos', 'Error al eliminar el equipo');
        }
    }

    public function cambiarEstadoOperativo($id) {
        if (!$this->puedeEditar()) {
            $this->jsonResponse(['error' => 'No tienes permisos'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            $estado_operativo = $_POST['estado_operativo'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';
            
            $estados_validos = ['Operativo', 'En Mantenimiento', 'Averiado', 'Fuera de Servicio'];
            if (!in_array($estado_operativo, $estados_validos, true)) {
                $this->jsonResponse(['error' => 'Estado inválido'], 400);
                return;
            }
            
            $resultado = $this->equipoModel->cambiarEstadoOperativo($id, $estado_operativo, $observaciones);
            
            if ($resultado) {
                $this->jsonResponse(['success' => true, 'message' => 'Estado actualizado']);
            } else {
                $this->jsonResponse(['error' => 'Error al actualizar'], 500);
            }
        } catch (Throwable $e) {
            error_log("Error en cambiarEstadoOperativo: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error al actualizar'], 500);
        }
    }

    public function buscar() {
        header('Content-Type: application/json; charset=utf-8');
        
        $term = $_GET['term'] ?? '';
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