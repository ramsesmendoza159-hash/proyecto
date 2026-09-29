<?php
// controller/EquiposController.php
// Gestión de Equipos/Máquinas - VERSIÓN CORREGIDA CON CONSULTOR

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
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        // Permitir admin, supervisor Y consultor (solo lectura)
        if (!$this->authHelper->isAdmin() && 
            !$this->authHelper->isSupervisor() && 
            !$this->authHelper->isConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $this->equipoModel = new EquiposModel();
        $this->areaModel = new AreasModel();
        $this->plantaModel = new PlantasModel();
    }
    
    /**
     * Verificar si el usuario puede editar (admin o supervisor)
     */
    private function puedeEditar() {
        return $this->authHelper->isAdmin() || $this->authHelper->isSupervisor();
    }
    
    /**
     * Validar y convertir el año de fabricación
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

    /**
     * Listado de equipos
     * URL: /equipos
     */
    public function index() {
        $filtros = [
            'buscar' => $_GET['buscar'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'estado_operativo' => $_GET['estado_operativo'] ?? '',
            'id_area' => $_GET['id_area'] ?? ''
        ];
        
        $equipos = $this->equipoModel->obtenerTodos($filtros);
        $estadisticas = $this->equipoModel->obtenerEstadisticas();
        $areas = $this->areaModel->obtenerActivos();
        $plantas = $this->plantaModel->obtenerTodos();
        
        $titulo = 'Gestión de Equipos';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/index.php';
    }

    /**
     * Formulario crear equipo
     * URL: /equipos/crear
     */
    public function crear() {
        // Solo admin y supervisor pueden crear
        if (!$this->puedeEditar()) {
            $_SESSION['error'] = 'No tienes permisos para crear equipos';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        unset($_SESSION['error']);
        unset($_SESSION['errores']);
        
        $areas = $this->areaModel->obtenerActivos();
        $plantas = $this->plantaModel->obtenerTodos();
        
        $titulo = 'Nuevo Equipo';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/crear.php';
    }

    /**
     * Guardar nuevo equipo
     * URL: /equipos/guardar (POST)
     */
    public function guardar() {
        // Solo admin y supervisor pueden guardar
        if (!$this->puedeEditar()) {
            $_SESSION['error'] = 'No tienes permisos para crear equipos';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/equipos');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/equipos/crear');
            exit;
        }
        
        $errores = [];
        
        if (empty($_POST['nombre'])) {
            $errores[] = 'El nombre del equipo es obligatorio';
        }
        if (empty($_POST['id_area'])) {
            $errores[] = 'El área es obligatoria';
        }
        
        // Validar año
        $resultado_año = $this->validarAño($_POST['año_fabricacion'] ?? '');
        if ($resultado_año['error']) {
            $errores[] = $resultado_año['error'];
        }
        $año = $resultado_año['valor'];
        
        if (!empty($errores)) {
            unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
            $_SESSION['errores'] = $errores;
            $_SESSION['old'] = $_POST;
            header('Location: /proyecto/equipos/crear');
            exit;
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
            
            $_SESSION['mensaje'] = 'Equipo creado correctamente (ID: ' . $id . ')';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: /proyecto/equipos');
        } else {
            unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
            
            $errorReal = method_exists($this->equipoModel, 'getLastError') 
                ? $this->equipoModel->getLastError() 
                : 'Error desconocido al crear el equipo';
            
            $_SESSION['error'] = 'Error al crear el equipo: ' . $errorReal;
            $_SESSION['old'] = $_POST;
            header('Location: /proyecto/equipos/crear');
        }
        exit;
    }

    /**
     * Ver detalle de equipo
     * URL: /equipos/ver/{id}
     */
    public function ver($id) {
        $equipo = $this->equipoModel->obtenerPorId($id);
        
        if (!$equipo) {
            $_SESSION['error'] = 'Equipo no encontrado';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        $titulo = 'Detalle del Equipo';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/ver.php';
    }

    /**
     * Formulario editar equipo
     * URL: /equipos/editar/{id}
     */
    public function editar($id) {
        // Solo admin y supervisor pueden editar
        if (!$this->puedeEditar()) {
            $_SESSION['error'] = 'No tienes permisos para editar equipos';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        unset($_SESSION['error']);
        unset($_SESSION['errores']);
        
        $equipo = $this->equipoModel->obtenerPorId($id);
        
        if (!$equipo) {
            $_SESSION['error'] = 'Equipo no encontrado';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        $areas = $this->areaModel->obtenerActivos();
        $plantas = $this->plantaModel->obtenerTodos();
        
        $titulo = 'Editar Equipo';
        $seccion = 'equipos';
        require_once __DIR__ . '/../views/equipos/editar.php';
    }

    /**
     * Actualizar equipo
     * URL: /equipos/actualizar/{id} (POST)
     */
    public function actualizar($id) {
        // Solo admin y supervisor pueden actualizar
        if (!$this->puedeEditar()) {
            $_SESSION['error'] = 'No tienes permisos para editar equipos';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/equipos');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/equipos/editar/' . $id);
            exit;
        }
        
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
            header('Location: /proyecto/equipos/editar/' . $id);
            exit;
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
            
            $_SESSION['mensaje'] = 'Equipo actualizado correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: /proyecto/equipos/ver/' . $id);
        } else {
            unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
            
            $_SESSION['error'] = 'Error al actualizar el equipo';
            header('Location: /proyecto/equipos/editar/' . $id);
        }
        exit;
    }

    /**
     * Eliminar equipo
     * URL: /equipos/eliminar/{id} (POST)
     */
    public function eliminar($id) {
        // Solo admin puede eliminar
        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para eliminar equipos';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/equipos');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/equipos');
            exit;
        }
        
        $resultado = $this->equipoModel->eliminar($id);
        
        if (!$resultado['error']) {
            unset($_SESSION['error']);
            $_SESSION['mensaje'] = $resultado['message'];
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            unset($_SESSION['mensaje']);
            $_SESSION['error'] = $resultado['message'];
        }
        
        header('Location: /proyecto/equipos');
        exit;
    }

    /**
     * Cambiar estado operativo (AJAX)
     * URL: /equipos/cambiarEstadoOperativo/{id} (POST)
     */
    public function cambiarEstadoOperativo($id) {
        // Solo admin y supervisor pueden cambiar estado
        if (!$this->puedeEditar()) {
            $this->jsonResponse(['error' => 'No tienes permisos'], 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }
        
        $estado_operativo = $_POST['estado_operativo'] ?? '';
        $observaciones = $_POST['observaciones'] ?? '';
        
        $estados_validos = ['Operativo', 'En Mantenimiento', 'Averiado', 'Fuera de Servicio'];
        if (!in_array($estado_operativo, $estados_validos)) {
            $this->jsonResponse(['error' => 'Estado inválido'], 400);
        }
        
        $resultado = $this->equipoModel->cambiarEstadoOperativo($id, $estado_operativo, $observaciones);
        
        if ($resultado) {
            $this->jsonResponse(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            $this->jsonResponse(['error' => 'Error al actualizar'], 500);
        }
    }

    /**
     * Buscar equipos (AJAX)
     * URL: /equipos/buscar
     */
    public function buscar() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        if (strlen($term) < 2) {
            echo json_encode([]);
            exit;
        }
        
        $equipos = $this->equipoModel->buscar($term);
        echo json_encode($equipos);
        exit;
    }
}
?>
