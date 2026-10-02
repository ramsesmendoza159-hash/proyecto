<?php
// controller/TecnicosController.php
// Gestión de técnicos - CON AUDITORÍA Y TRANSACCIONES
// ✅ FIX: catch (Throwable) en TODOS los catch (reemplaza Exception)
// ✅ FIX: throw new RuntimeException en vez de Exception
// ✅ FIX: password_hash calculado UNA sola vez (evita doble hash con salt distinto)
// ✅ FIX: rollBack() protegido con inTransaction() (ya estaba, se mantiene)
// ✅ FIX: uso de redirectWithSuccess/Error (ToastHelper)
// ✅ FIX: comentarios residuales eliminados

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/Tecnico.php';
require_once __DIR__ . '/../model/UsuariosModel.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/database.php';

class TecnicosController extends Controller {
    
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
        
        $this->model = new Tecnico();
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista de técnicos
     * URL: /tecnicos
     */
    public function index() {
        try {
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'buscar' => $_GET['buscar'] ?? '',
                'especialidad' => $_GET['especialidad'] ?? ''
            ];
            
            $tecnicos = $this->model->obtenerTodos($filtros);
            $estadisticas = $this->model->obtenerEstadisticas();
            $especialidades = $this->model->obtenerEspecialidades();
            
            $tarifa_promedio = 0;
            if (!empty($tecnicos)) {
                $total_tarifas = array_sum(array_column($tecnicos, 'tarifa'));
                $tarifa_promedio = $total_tarifas / count($tecnicos);
            }
            
            $sql = "SELECT COUNT(DISTINCT id) as total FROM ordenes_mantenimiento WHERE tecnico_id IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_ordenes = (int)($result['total'] ?? 0);
            
            $seccion = 'tecnicos';
            $titulo = 'Gestión de Técnicos';
            
            require_once __DIR__ . '/../views/tecnicos/index.php';
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            error_log("Error en TecnicosController::index: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar los técnicos');
        }
    }
    
    /**
     * Formulario para crear técnico
     * URL: /tecnicos/crear
     */
    public function crear() {
        try {
            $seccion = 'tecnicos';
            $titulo = 'Crear Técnico';
            require_once __DIR__ . '/../views/tecnicos/crear.php';
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            error_log("Error en TecnicosController::crear: " . $e->getMessage());
            $this->redirectWithError('/tecnicos', 'Error al cargar el formulario');
        }
    }
    
    /**
     * Guardar nuevo técnico - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /tecnicos/guardar (POST)
     */
    public function guardar() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/tecnicos');
                return;
            }
            
            if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError('/tecnicos/crear', 'Token de seguridad inválido. Por favor, recarga la página.');
                return;
            }
            
            $_SESSION['old'] = [
                'nombre' => $_POST['nombre'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'especialidad' => $_POST['especialidad'] ?? '',
                'tarifa' => $_POST['tarifa'] ?? 0,
                'estado' => $_POST['estado'] ?? 'activo'
            ];
            
            $errores = [];
            
            $nombre = trim($_POST['nombre'] ?? '');
            if (empty($nombre)) {
                $errores['nombre'] = 'El nombre es obligatorio';
            } elseif (strlen($nombre) < 3 || strlen($nombre) > 100) {
                $errores['nombre'] = 'El nombre debe tener entre 3 y 100 caracteres';
            }
            
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $errores['email'] = 'El email es obligatorio';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores['email'] = 'El email no es válido';
            }
            
            // ✅ Verificar duplicado en tabla técnicos
            if ($this->model->emailExiste($email)) {
                $errores['email'] = 'El email ya está registrado como técnico';
            }
            
            // ✅ Verificar duplicado en tabla usuarios
            $usuarioModel = new UsuariosModel();
            if ($usuarioModel->emailExiste($email)) {
                $errores['email'] = 'El email ya está registrado como usuario del sistema';
            }
            
            $password = $_POST['password'] ?? '';
            $confirmar_password = $_POST['confirmar_password'] ?? '';
            
            if (empty($password)) {
                $errores['password'] = 'La contraseña es obligatoria';
            } elseif (strlen($password) < 6) {
                $errores['password'] = 'La contraseña debe tener al menos 6 caracteres';
            } elseif ($password !== $confirmar_password) {
                $errores['confirmar_password'] = 'Las contraseñas no coinciden';
            }
            
            $especialidad = trim($_POST['especialidad'] ?? '');
            if (empty($especialidad)) {
                $errores['especialidad'] = 'La especialidad es obligatoria';
            }
            
            $tarifa = (float)($_POST['tarifa'] ?? 0);
            if ($tarifa < 0) {
                $errores['tarifa'] = 'La tarifa no puede ser negativa';
            }
            
            $telefono = trim($_POST['telefono'] ?? '');
            if (!empty($telefono) && !preg_match('/^[0-9\s\-+()]{6,15}$/', $telefono)) {
                $errores['telefono'] = 'El teléfono no es válido';
            }
            
            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $this->redirect('/tecnicos/crear');
                return;
            }
            
            // ✅ Iniciar transacción
            $this->db->beginTransaction();
            
            // ✅ FIX: calcular el hash UNA sola vez
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // 1. Insertar en tabla técnicos (pasamos hash ya calculado)
            $datos = [
                'nombre' => $nombre,
                'email' => $email,
                'password_hash' => $passwordHash,   // ✅ FIX: evita doble hash
                'especialidad' => $especialidad,
                'tarifa' => $tarifa,
                'telefono' => $telefono,
                'estado' => $_POST['estado'] ?? 'activo'
            ];
            
            $result = $this->model->crear($datos);
            
            if (!$result) {
                throw new RuntimeException('Error al crear el técnico en la tabla técnicos');
            }
            
            // 2. Insertar en tabla usuarios con rol 'tecnico'
            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado, fecha_creacion) 
                    VALUES (?, ?, ?, 'tecnico', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([
                $nombre,
                $email,
                $passwordHash,   // ✅ FIX: mismo hash
                $_POST['estado'] ?? 'activo'
            ]);
            
            if (!$resultUsuario) {
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
                    'tecnicos',
                    $result
                );
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de creación de técnico: " . $e->getMessage());
            }
            
            unset($_SESSION['old']);
            $this->redirectWithSuccess('/tecnicos', 'Técnico creado correctamente. Ya puede iniciar sesión.');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::guardar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar el técnico: ' . $e->getMessage();
            $this->redirect('/tecnicos/crear');
        }
    }
    
    /**
     * Formulario para editar técnico
     * URL: /tecnicos/editar/{id}
     */
    public function editar($id) {
        try {
            $id = (int)$id;
            
            if ($id <= 0) {
                $this->redirectWithError('/tecnicos', 'ID de técnico inválido');
                return;
            }
            
            $tecnico = $this->model->obtenerPorId($id);
            
            if (!$tecnico) {
                $this->redirectWithError('/tecnicos', 'Técnico no encontrado');
                return;
            }
            
            $seccion = 'tecnicos';
            $titulo = 'Editar Técnico';
            
            require_once __DIR__ . '/../views/tecnicos/editar.php';
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            error_log("Error en TecnicosController::editar: " . $e->getMessage());
            $this->redirectWithError('/tecnicos', 'Error al cargar el técnico');
        }
    }
    
    /**
     * Actualizar técnico - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /tecnicos/actualizar/{id} (POST)
     */
    public function actualizar($id) {
        try {
            $id = (int)$id;
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/tecnicos');
                return;
            }
            
            if ($id <= 0) {
                $this->redirectWithError('/tecnicos', 'ID de técnico inválido');
                return;
            }
            
            if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError('/tecnicos/editar/' . $id, 'Token de seguridad inválido. Por favor, recarga la página.');
                return;
            }
            
            $tecnico_original = $this->model->obtenerPorId($id);
            if (!$tecnico_original) {
                $this->redirectWithError('/tecnicos', 'Técnico no encontrado');
                return;
            }
            
            $_SESSION['old'] = [
                'nombre' => $_POST['nombre'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'especialidad' => $_POST['especialidad'] ?? '',
                'tarifa' => $_POST['tarifa'] ?? 0,
                'estado' => $_POST['estado'] ?? 'activo'
            ];
            
            $errores = [];
            
            $nombre = trim($_POST['nombre'] ?? '');
            if (empty($nombre)) {
                $errores['nombre'] = 'El nombre es obligatorio';
            } elseif (strlen($nombre) < 3 || strlen($nombre) > 100) {
                $errores['nombre'] = 'El nombre debe tener entre 3 y 100 caracteres';
            }
            
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $errores['email'] = 'El email es obligatorio';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores['email'] = 'El email no es válido';
            }
            
            if ($this->model->emailExiste($email, $id)) {
                $errores['email'] = 'El email ya está registrado por otro técnico';
            }
            
            // ✅ Verificar en usuarios solo si el email cambió
            if ($email !== $tecnico_original['email']) {
                $usuarioModel = new UsuariosModel();
                if ($usuarioModel->emailExiste($email)) {
                    $errores['email'] = 'El email ya está registrado como usuario del sistema';
                }
            }
            
            $password = $_POST['password'] ?? '';
            $confirmar_password = $_POST['confirmar_password'] ?? '';
            
            if (!empty($password) || !empty($confirmar_password)) {
                if (strlen($password) < 6) {
                    $errores['password'] = 'La contraseña debe tener al menos 6 caracteres';
                } elseif ($password !== $confirmar_password) {
                    $errores['confirmar_password'] = 'Las contraseñas no coinciden';
                }
            }
            
            $especialidad = trim($_POST['especialidad'] ?? '');
            if (empty($especialidad)) {
                $errores['especialidad'] = 'La especialidad es obligatoria';
            }
            
            $tarifa = (float)($_POST['tarifa'] ?? 0);
            if ($tarifa < 0) {
                $errores['tarifa'] = 'La tarifa no puede ser negativa';
            }
            
            $telefono = trim($_POST['telefono'] ?? '');
            if (!empty($telefono) && !preg_match('/^[0-9\s\-+()]{6,15}$/', $telefono)) {
                $errores['telefono'] = 'El teléfono no es válido';
            }
            
            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $this->redirect('/tecnicos/editar/' . $id);
                return;
            }
            
            // ✅ Iniciar transacción
            $this->db->beginTransaction();
            
            $datos = [
                'nombre' => $nombre,
                'email' => $email,
                'especialidad' => $especialidad,
                'tarifa' => $tarifa,
                'telefono' => $telefono,
                'estado' => $_POST['estado'] ?? 'activo'
            ];
            
            // ✅ FIX: calcular hash UNA vez y pasarlo al modelo
            $passwordHash = null;
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $datos['password_hash'] = $passwordHash;   // ✅ FIX
            }
            
            $result = $this->model->actualizar($id, $datos);
            
            if (!$result) {
                throw new RuntimeException('Error al actualizar el técnico');
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
                        WHERE email = ? AND rol = 'tecnico'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $passwordHash,
                    $_POST['estado'] ?? 'activo',
                    $tecnico_original['email']
                ]);
            } else {
                // Sin cambio de contraseña
                $sql = "UPDATE usuarios SET 
                            nombre = ?,
                            email = ?,
                            estado = ?,
                            fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'tecnico'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $_POST['estado'] ?? 'activo',
                    $tecnico_original['email']
                ]);
            }
            
            if (!$resultUsuario) {
                throw new RuntimeException('Error al actualizar el usuario');
            }
            
            // ✅ Commit
            $this->db->commit();
            
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'actualizar',
                    'tecnicos',
                    $id
                );
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de actualización: " . $e->getMessage());
            }
            
            unset($_SESSION['old']);
            $this->redirectWithSuccess('/tecnicos', 'Técnico actualizado correctamente');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::actualizar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar el técnico: ' . $e->getMessage();
            $this->redirect('/tecnicos/editar/' . $id);
        }
    }
    
    /**
     * Eliminar técnico - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /tecnicos/eliminar/{id} (POST)
     */
    public function eliminar($id) {
        try {
            $id = (int)$id;
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/tecnicos');
                return;
            }
            
            if ($id <= 0) {
                $this->redirectWithError('/tecnicos', 'ID de técnico inválido');
                return;
            }
            
            if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError('/tecnicos', 'Token de seguridad inválido');
                return;
            }
            
            $tecnico = $this->model->obtenerPorId($id);
            if (!$tecnico) {
                $this->redirectWithError('/tecnicos', 'Técnico no encontrado');
                return;
            }
            
            $this->db->beginTransaction();
            
            // Verificar si tiene órdenes
            $sql = "SELECT COUNT(*) as total FROM ordenes_mantenimiento WHERE tecnico_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (($result['total'] ?? 0) > 0) {
                throw new RuntimeException('No se puede eliminar el técnico porque tiene órdenes asignadas');
            }
            
            // Eliminar de técnicos
            $resultTecnico = $this->model->eliminar($id);
            
            if (!$resultTecnico) {
                throw new RuntimeException('Error al eliminar el técnico');
            }
            
            // Eliminar también de usuarios
            $sql = "DELETE FROM usuarios WHERE email = ? AND rol = 'tecnico'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$tecnico['email']]);
            
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
                    'tecnicos',
                    $id
                );
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de eliminación: " . $e->getMessage());
            }
            
            $this->redirectWithSuccess('/tecnicos', 'Técnico eliminado correctamente');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::eliminar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar el técnico: ' . $e->getMessage();
            $this->redirect('/tecnicos');
        }
    }
    
    /**
     * Cambiar estado del técnico - CON AUDITORÍA Y TRANSACCIÓN
     * URL: /tecnicos/cambiarEstado/{id} (POST)
     */
    public function cambiarEstado($id) {
        try {
            $id = (int)$id;
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/tecnicos');
                return;
            }
            
            if ($id <= 0) {
                $this->redirectWithError('/tecnicos', 'ID de técnico inválido');
                return;
            }
            
            if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError('/tecnicos', 'Token de seguridad inválido');
                return;
            }
            
            $estado = $_POST['estado'] ?? 'activo';
            
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                $this->redirectWithError('/tecnicos', 'Estado inválido');
                return;
            }
            
            $tecnico = $this->model->obtenerPorId($id);
            if (!$tecnico) {
                $this->redirectWithError('/tecnicos', 'Técnico no encontrado');
                return;
            }
            
            $this->db->beginTransaction();
            
            $result = $this->model->cambiarEstado($id, $estado);
            
            if (!$result) {
                throw new RuntimeException('Error al cambiar el estado del técnico');
            }
            
            // Actualizar también en usuarios
            $sql = "UPDATE usuarios SET estado = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'tecnico'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$estado, $tecnico['email']]);
            
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
                    'tecnicos',
                    $id
                );
            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error al registrar auditoría de cambio de estado: " . $e->getMessage());
            }
            
            $this->redirectWithSuccess('/tecnicos', 'Estado actualizado correctamente');
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            // ✅ rollBack protegido
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::cambiarEstado: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cambiar el estado: ' . $e->getMessage();
            $this->redirect('/tecnicos');
        }
    }
    
    /**
     * API de datos (JSON)
     */
    public function apiDatos() {
        try {
            header('Content-Type: application/json; charset=utf-8');
            
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'buscar' => $_GET['buscar'] ?? '',
                'especialidad' => $_GET['especialidad'] ?? ''
            ];
            
            $tecnicos = $this->model->obtenerTodos($filtros);
            
            echo json_encode([
                'success' => true,
                'data' => $tecnicos,
                'total' => count($tecnicos)
            ]);
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            error_log("Error en TecnicosController::apiDatos: " . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}