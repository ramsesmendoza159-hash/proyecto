<?php
// controller/SupervisoresController.php
// Gestión de supervisores - CON AUDITORÍA Y TRANSACCIONES
// ✅ FIX: Transacción al crear/actualizar/eliminar para consistencia entre 'supervisores' y 'usuarios'
// ✅ FIX: catch (Throwable) en TODOS los catch
// ✅ FIX: rollBack() protegido con inTransaction()
// ✅ FIX: password_hash calculado UNA sola vez (evita doble hash con salt distinto)
// ✅ FIX: uso de ToastHelper vía redirectWithSuccess/Error (más consistente)
// ✅ FIX: index() normaliza estado con LOWER() para contar correctamente

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
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al contar áreas de supervisores: " . $e->getMessage());
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
            return;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/supervisores/crear', 'Token de seguridad inválido');
            return;
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
            return;
        }
        
        try {
            // ✅ Iniciar transacción
            $this->db->beginTransaction();
            
            // ✅ FIX: calcular el hash UNA sola vez
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // 1. Insertar en tabla supervisores (pasamos hash ya calculado)
            $datosSupervisor = [
                'nombre' => $nombre,
                'email' => $email,
                'password_hash' => $passwordHash,   // ✅ FIX: evita doble hash con salt distinto
                'area' => ValidationHelper::sanitize($_POST['area'] ?? ''),
                'telefono' => ValidationHelper::sanitize($_POST['telefono'] ?? ''),
                'estado' => $_POST['estado'] ?? 'activo'
            ];
            
            $supervisorId = $this->model->crear($datosSupervisor);
            
            if (!$supervisorId) {
                throw new RuntimeException('Error al crear el supervisor en la tabla supervisores');
            }
            
            // 2. Insertar en tabla usuarios con rol 'supervisor'
            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado, fecha_creacion) 
                    VALUES (?, ?, ?, 'supervisor', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $nombre,
                $email,
                $passwordHash,   // ✅ FIX: mismo hash que en supervisores
                $_POST['estado'] ?? 'activo'
            ]);
            
            if (!$result) {
                throw new RuntimeException('Error al crear el usuario en la tabla usuarios');
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
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de creación de supervisor: " . $e->getMessage());
            }
            
            unset($_SESSION['old']);
            $this->redirectWithSuccess('/supervisores', 'Supervisor creado correctamente. Ya puede iniciar sesión.');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ FIX: rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
            $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
            return;
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
            return;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/supervisores/editar/' . $id, 'Token de seguridad inválido');
            return;
        }
        
        $id = (int)$id;
        $supervisor_original = $this->model->obtenerPorId($id);
        
        if (!$supervisor_original) {
            $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
            return;
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
            return;
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
            $passwordHash = null;
            
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new RuntimeException('La contraseña debe tener al menos 6 caracteres');
                }
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $datosSupervisor['password_hash'] = $passwordHash;   // ✅ FIX: hash ya calculado
            }
            
            $resultSupervisor = $this->model->actualizar($id, $datosSupervisor);
            
            if (!$resultSupervisor) {
                throw new RuntimeException('Error al actualizar el supervisor');
            }
            
            // Actualizar también en la tabla usuarios
            if ($passwordHash !== null) {
                // Con cambio de contraseña
                $sql = "UPDATE usuarios SET 
                            nombre = ?,
                            email = ?,
                            password_hash = ?,
                            estado = ?,
                            fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'supervisor'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $passwordHash,
                    $_POST['estado'] ?? 'activo',
                    $supervisor_original['email']
                ]);
            } else {
                // Sin cambio de contraseña
                $sql = "UPDATE usuarios SET 
                            nombre = ?,
                            email = ?,
                            estado = ?,
                            fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'supervisor'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $_POST['estado'] ?? 'activo',
                    $supervisor_original['email']
                ]);
            }
            
            if (!$resultUsuario) {
                throw new RuntimeException('Error al actualizar el usuario');
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
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de actualización de supervisor: " . $e->getMessage());
            }
            
            $this->redirectWithSuccess('/supervisores', 'Supervisor actualizado correctamente.');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ FIX: rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
            return;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/supervisores', 'Token de seguridad inválido');
            return;
        }
        
        $id = (int)$id;
        $estado = $_POST['estado'] ?? 'activo';
        
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $this->redirectWithError('/supervisores', 'Estado inválido');
            return;
        }
        
        $supervisor = $this->model->obtenerPorId($id);
        if (!$supervisor) {
            $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Actualizar en supervisores
            $resultSupervisor = $this->model->cambiarEstado($id, $estado);
            
            if (!$resultSupervisor) {
                throw new RuntimeException('Error al cambiar el estado del supervisor');
            }
            
            // Actualizar también en usuarios
            $sql = "UPDATE usuarios SET estado = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$estado, $supervisor['email']]);
            
            if (!$resultUsuario) {
                throw new RuntimeException('Error al cambiar el estado del usuario');
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
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de cambio de estado: " . $e->getMessage());
            }
            
            $this->redirectWithSuccess('/supervisores', 'Estado actualizado correctamente.');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ FIX: rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
            return;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/supervisores', 'Token de seguridad inválido');
            return;
        }
        
        $id = (int)$id;
        $password = $_POST['password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        
        if (empty($password) || strlen($password) < 6) {
            $this->redirectWithError('/supervisores', 'La contraseña debe tener al menos 6 caracteres');
            return;
        }
        
        if ($password !== $confirmar_password) {
            $this->redirectWithError('/supervisores', 'Las contraseñas no coinciden');
            return;
        }
        
        $supervisor = $this->model->obtenerPorId($id);
        if (!$supervisor) {
            $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // ✅ FIX: calcular hash UNA sola vez
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Actualizar en supervisores
            $resultSupervisor = $this->model->actualizarPassword($id, $passwordHash);
            
            if (!$resultSupervisor) {
                throw new RuntimeException('Error al actualizar la contraseña del supervisor');
            }
            
            // Actualizar también en usuarios
            $sql = "UPDATE usuarios SET password_hash = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$passwordHash, $supervisor['email']]);
            
            if (!$resultUsuario) {
                throw new RuntimeException('Error al actualizar la contraseña del usuario');
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
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de cambio de contraseña: " . $e->getMessage());
            }
            
            $this->redirectWithSuccess('/supervisores', 'Contraseña actualizada correctamente.');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ FIX: rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
            return;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('/supervisores', 'Token de seguridad inválido');
            return;
        }
        
        $id = (int)$id;
        $supervisor = $this->model->obtenerPorId($id);
        
        if (!$supervisor) {
            $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Verificar si tiene órdenes asignadas
            $sql = "SELECT COUNT(*) as total FROM ordenes_mantenimiento WHERE id_supervisor = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (($result['total'] ?? 0) > 0) {
                throw new RuntimeException('No se puede eliminar el supervisor porque tiene órdenes asignadas');
            }
            
            // ✅ NUEVO: verificar supervisiones
            $sqlSup = "SELECT COUNT(*) as total FROM supervisiones WHERE supervisor_id = ?";
            $stmtSup = $this->db->prepare($sqlSup);
            $stmtSup->execute([$id]);
            $resultSup = $stmtSup->fetch(PDO::FETCH_ASSOC);
            
            if (($resultSup['total'] ?? 0) > 0) {
                throw new RuntimeException('No se puede eliminar el supervisor porque tiene supervisiones registradas');
            }
            
            // Eliminar de supervisores
            $resultSupervisor = $this->model->eliminar($id);
            
            if (!$resultSupervisor) {
                throw new RuntimeException('Error al eliminar el supervisor');
            }
            
            // Eliminar también de usuarios
            $sql = "DELETE FROM usuarios WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$supervisor['email']]);
            
            if (!$resultUsuario) {
                throw new RuntimeException('Error al eliminar el usuario');
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
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de eliminación: " . $e->getMessage());
            }
            
            $this->redirectWithSuccess('/supervisores', 'Supervisor eliminado correctamente.');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ FIX: rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisoresController::eliminar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        
        $this->redirect('/supervisores');
    }
}