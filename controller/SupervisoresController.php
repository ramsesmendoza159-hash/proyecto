<?php
// controller/SupervisoresController.php
// Gestión de supervisores - CON AUDITORÍA Y TRANSACCIONES
// ✅ FIX: Transacción al crear/actualizar/eliminar para consistencia entre 'supervisores' y 'usuarios'

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/Supervisor.php';
require_once __DIR__ . '/../model/UsuariosModel.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/database.php';

class SupervisoresController extends Controller {
    
    private $model;
    private $db;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
        }
        
        $this->model = new Supervisor();
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista de supervisores
     * URL: /supervisores
     */
    public function index() {
        $filtros = [
            'estado' => $_GET['estado'] ?? '',
            'buscar' => $_GET['buscar'] ?? '',
            'area' => $_GET['area'] ?? ''
        ];
        
        $supervisores = $this->model->obtenerTodos($filtros);
        
        // ✅ Asegurar que 'areas' exista en las estadísticas
        $estadisticas = $this->model->obtenerEstadisticas();
        
        if (!isset($estadisticas['areas'])) {
            try {
                $sql = "SELECT COUNT(DISTINCT area) as areas 
                        FROM supervisores 
                        WHERE area IS NOT NULL AND area != ''";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $estadisticas['areas'] = (int)($result['areas'] ?? 0);
            } catch (Exception $e) {
                $estadisticas['areas'] = 0;
            }
        }
        
        $seccion = 'supervisores';
        $titulo = 'Gestión de Supervisores';
        require_once __DIR__ . '/../views/supervisores/index.php';
    }
    
    /**
     * Formulario para crear supervisor
     * URL: /supervisores/crear
     */
    public function crear() {
        $seccion = 'supervisores';
        $titulo = 'Crear Supervisor';
        require_once __DIR__ . '/../views/supervisores/form.php';
    }
    
    /**
     * Guardar nuevo supervisor - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /supervisores/guardar (POST)
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/supervisores');
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervisores/crear');
        }
        
        $errores = [];
        
        $nombre = ValidationHelper::sanitize($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            $errores[] = 'El nombre es obligatorio';
        } elseif (!ValidationHelper::validateLength($nombre, 3, 100)) {
            $errores[] = 'El nombre debe tener entre 3 y 100 caracteres';
        }
        
        $email = ValidationHelper::sanitize($_POST['email'] ?? '');
        if (empty($email)) {
            $errores[] = 'El email es obligatorio';
        } elseif (!ValidationHelper::validateEmail($email)) {
            $errores[] = 'El email no es válido';
        }
        
        // ✅ Verificar duplicado en tabla supervisores
        if ($this->model->emailExiste($email)) {
            $errores[] = 'El email ya está registrado como supervisor';
        }
        
        // ✅ Verificar duplicado en tabla usuarios
        $usuarioModel = new UsuariosModel();
        if ($usuarioModel->emailExiste($email)) {
            $errores[] = 'El email ya está registrado como usuario del sistema';
        }
        
        $password = $_POST['password'] ?? '';
        if (empty($password)) {
            $errores[] = 'La contraseña es obligatoria';
        } elseif (strlen($password) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $_SESSION['old'] = $_POST;
            $this->redirect('/supervisores/crear');
        }
        
        try {
            // ✅ Iniciar transacción
            $this->db->beginTransaction();
            
            // 1. Insertar en tabla supervisores
            $datosSupervisor = [
                'nombre' => $nombre,
                'email' => $email,
                'password' => $password,
                'area' => ValidationHelper::sanitize($_POST['area'] ?? ''),
                'telefono' => ValidationHelper::sanitize($_POST['telefono'] ?? ''),
                'estado' => $_POST['estado'] ?? 'activo'
            ];
            
            $supervisorId = $this->model->crear($datosSupervisor);
            
            if (!$supervisorId) {
                throw new Exception('Error al crear el supervisor en la tabla supervisores');
            }
            
            // 2. Insertar en tabla usuarios con rol 'supervisor'
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado, fecha_creacion) 
                    VALUES (?, ?, ?, 'supervisor', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $nombre,
                $email,
                $passwordHash,
                $_POST['estado'] ?? 'activo'
            ]);
            
            if (!$result) {
                throw new Exception('Error al crear el usuario en la tabla usuarios');
            }
            
            // ✅ Commit
            $this->db->commit();
            
            // Auditoría
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'crear',
                    'supervisores',
                    $supervisorId
                );
            } catch (Exception $e) {
                error_log("Error al registrar auditoría: " . $e->getMessage());
            }
            
            unset($_SESSION['old']);
            $_SESSION['mensaje'] = 'Supervisor creado correctamente. Ya puede iniciar sesión.';
            $_SESSION['mensaje_tipo'] = 'success';
            $this->redirect('/supervisores');
            
        } catch (Exception $e) {
            // ✅ Rollback
            $this->db->rollBack();
            error_log("Error en SupervisoresController::guardar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            $_SESSION['old'] = $_POST;
            $this->redirect('/supervisores/crear');
        }
    }
    
    /**
     * Formulario para editar supervisor
     * URL: /supervisores/editar/{id}
     */
    public function editar($id) {
        $id = (int)$id;
        $supervisor = $this->model->obtenerPorId($id);
        
        if (!$supervisor) {
            $_SESSION['error'] = 'Supervisor no encontrado';
            $this->redirect('/supervisores');
        }
        
        $seccion = 'supervisores';
        $titulo = 'Editar Supervisor';
        require_once __DIR__ . '/../views/supervisores/form.php';
    }
    
    /**
     * Actualizar supervisor - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /supervisores/actualizar/{id} (POST)
     */
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/supervisores');
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervisores/editar/' . $id);
        }
        
        $id = (int)$id;
        $supervisor_original = $this->model->obtenerPorId($id);
        
        if (!$supervisor_original) {
            $_SESSION['error'] = 'Supervisor no encontrado';
            $this->redirect('/supervisores');
        }
        
        $errores = [];
        
        $nombre = ValidationHelper::sanitize($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            $errores[] = 'El nombre es obligatorio';
        } elseif (!ValidationHelper::validateLength($nombre, 3, 100)) {
            $errores[] = 'El nombre debe tener entre 3 y 100 caracteres';
        }
        
        $email = ValidationHelper::sanitize($_POST['email'] ?? '');
        if (empty($email)) {
            $errores[] = 'El email es obligatorio';
        } elseif (!ValidationHelper::validateEmail($email)) {
            $errores[] = 'El email no es válido';
        }
        
        if ($this->model->emailExiste($email, $id)) {
            $errores[] = 'El email ya está registrado por otro supervisor';
        }
        
        // ✅ Verificar en usuarios solo si el email cambió
        if ($email !== $supervisor_original['email']) {
            $usuarioModel = new UsuariosModel();
            if ($usuarioModel->emailExiste($email)) {
                $errores[] = 'El email ya está registrado como usuario del sistema';
            }
        }
        
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirect('/supervisores/editar/' . $id);
        }
        
        try {
            // ✅ Iniciar transacción
            $this->db->beginTransaction();
            
            $datosSupervisor = [
                'nombre' => $nombre,
                'email' => $email,
                'area' => ValidationHelper::sanitize($_POST['area'] ?? ''),
                'telefono' => ValidationHelper::sanitize($_POST['telefono'] ?? ''),
                'estado' => $_POST['estado'] ?? 'activo'
            ];
            
            // Si se proporcionó nueva contraseña
            $password = $_POST['password'] ?? '';
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('La contraseña debe tener al menos 6 caracteres');
                }
                $datosSupervisor['password'] = $password;
            }
            
            $resultSupervisor = $this->model->actualizar($id, $datosSupervisor);
            
            if (!$resultSupervisor) {
                throw new Exception('Error al actualizar el supervisor');
            }
            
            // Actualizar también en la tabla usuarios
            $sql = "UPDATE usuarios SET 
                        nombre = ?,
                        email = ?,
                        estado = ?,
                        fecha_actualizacion = NOW()
                    WHERE email = ? AND rol = 'supervisor'";
            $params = [
                $nombre,
                $email,
                $_POST['estado'] ?? 'activo',
                $supervisor_original['email']
            ];
            
            // Si se cambió la contraseña
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET 
                            nombre = ?,
                            email = ?,
                            password_hash = ?,
                            estado = ?,
                            fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'supervisor'";
                $params = [
                    $nombre,
                    $email,
                    $passwordHash,
                    $_POST['estado'] ?? 'activo',
                    $supervisor_original['email']
                ];
            }
            
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute($params);
            
            if (!$resultUsuario) {
                throw new Exception('Error al actualizar el usuario');
            }
            
            // ✅ Commit
            $this->db->commit();
            
            // Auditoría
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'actualizar',
                    'supervisores',
                    $id
                );
            } catch (Exception $e) {
                error_log("Error al registrar auditoría: " . $e->getMessage());
            }
            
            $_SESSION['mensaje'] = 'Supervisor actualizado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en SupervisoresController::actualizar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        
        $this->redirect('/supervisores');
    }
    
    /**
     * Cambiar estado del supervisor - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /supervisores/cambiarEstado/{id} (POST)
     */
    public function cambiarEstado($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/supervisores');
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervisores');
        }
        
        $id = (int)$id;
        $estado = $_POST['estado'] ?? 'activo';
        
        if (!in_array($estado, ['activo', 'inactivo'])) {
            $_SESSION['error'] = 'Estado inválido';
            $this->redirect('/supervisores');
        }
        
        $supervisor = $this->model->obtenerPorId($id);
        if (!$supervisor) {
            $_SESSION['error'] = 'Supervisor no encontrado';
            $this->redirect('/supervisores');
        }
        
        try {
            $this->db->beginTransaction();
            
            // Actualizar en supervisores
            $resultSupervisor = $this->model->cambiarEstado($id, $estado);
            
            if (!$resultSupervisor) {
                throw new Exception('Error al cambiar el estado del supervisor');
            }
            
            // Actualizar también en usuarios
            $sql = "UPDATE usuarios SET estado = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$estado, $supervisor['email']]);
            
            if (!$resultUsuario) {
                throw new Exception('Error al cambiar el estado del usuario');
            }
            
            $this->db->commit();
            
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'actualizar',
                    'supervisores',
                    $id
                );
            } catch (Exception $e) {
                error_log("Error al registrar auditoría: " . $e->getMessage());
            }
            
            $_SESSION['mensaje'] = 'Estado actualizado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en SupervisoresController::cambiarEstado: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        
        $this->redirect('/supervisores');
    }
    
    /**
     * Cambiar contraseña del supervisor - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /supervisores/cambiarPassword/{id} (POST)
     */
    public function cambiarPassword($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/supervisores');
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervisores');
        }
        
        $id = (int)$id;
        $password = $_POST['password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        
        if (empty($password) || strlen($password) < 6) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres';
            $this->redirect('/supervisores');
        }
        
        if ($password !== $confirmar_password) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            $this->redirect('/supervisores');
        }
        
        $supervisor = $this->model->obtenerPorId($id);
        if (!$supervisor) {
            $_SESSION['error'] = 'Supervisor no encontrado';
            $this->redirect('/supervisores');
        }
        
        try {
            $this->db->beginTransaction();
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Actualizar en supervisores
            $resultSupervisor = $this->model->actualizarPassword($id, $passwordHash);
            
            if (!$resultSupervisor) {
                throw new Exception('Error al actualizar la contraseña del supervisor');
            }
            
            // Actualizar también en usuarios
            $sql = "UPDATE usuarios SET password_hash = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$passwordHash, $supervisor['email']]);
            
            if (!$resultUsuario) {
                throw new Exception('Error al actualizar la contraseña del usuario');
            }
            
            $this->db->commit();
            
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'actualizar',
                    'supervisores',
                    $id
                );
            } catch (Exception $e) {
                error_log("Error al registrar auditoría: " . $e->getMessage());
            }
            
            $_SESSION['mensaje'] = 'Contraseña actualizada correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en SupervisoresController::cambiarPassword: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        
        $this->redirect('/supervisores');
    }
    
    /**
     * Eliminar supervisor - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /supervisores/eliminar/{id} (POST)
     */
    public function eliminar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/supervisores');
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/supervisores');
        }
        
        $id = (int)$id;
        $supervisor = $this->model->obtenerPorId($id);
        
        if (!$supervisor) {
            $_SESSION['error'] = 'Supervisor no encontrado';
            $this->redirect('/supervisores');
        }
        
        try {
            $this->db->beginTransaction();
            
            // Verificar si tiene órdenes asignadas
            $sql = "SELECT COUNT(*) as total FROM ordenes_mantenimiento WHERE id_supervisor = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (($result['total'] ?? 0) > 0) {
                throw new Exception('No se puede eliminar el supervisor porque tiene órdenes asignadas');
            }
            
            // Eliminar de supervisores
            $resultSupervisor = $this->model->eliminar($id);
            
            if (!$resultSupervisor) {
                throw new Exception('Error al eliminar el supervisor');
            }
            
            // Eliminar también de usuarios
            $sql = "DELETE FROM usuarios WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$supervisor['email']]);
            
            if (!$resultUsuario) {
                throw new Exception('Error al eliminar el usuario');
            }
            
            $this->db->commit();
            
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'eliminar',
                    'supervisores',
                    $id
                );
            } catch (Exception $e) {
                error_log("Error al registrar auditoría: " . $e->getMessage());
            }
            
            $_SESSION['mensaje'] = 'Supervisor eliminado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en SupervisoresController::eliminar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        
        $this->redirect('/supervisores');
    }
}
?>
