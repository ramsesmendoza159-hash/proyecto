<?php
// controller/SupervisoresController.php
// Gestión de supervisores - CON AUDITORÍA Y TRANSACCIONES
// ✅ FIX 1: return después de cada redirect() en el constructor
// ✅ FIX 2: NO exponer $e->getMessage() al usuario (regla del proyecto)
// ✅ FIX 3: try/catch defensivo en index(), crear(), editar()
// ✅ FIX 4: index() sanitiza filtros

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
            return;
        }

        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            return;
        }

        try {
            $this->model = new Supervisor();
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en SupervisoresController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el módulo');
            return;
        }
    }

    /**
     * Lista de supervisores
     * URL: /supervisores
     */
    public function index() {
        try {
            $filtros = [
                'estado' => trim((string)($_GET['estado'] ?? '')),
                'buscar' => trim((string)($_GET['buscar'] ?? '')),
                'area'   => trim((string)($_GET['area'] ?? '')),
            ];

            $supervisores = $this->model->obtenerTodos($filtros);
            $estadisticas = $this->model->obtenerEstadisticas();

            if (!isset($estadisticas['areas'])) {
                $sql = "SELECT COUNT(DISTINCT area) as areas 
                        FROM supervisores 
                        WHERE area IS NOT NULL AND area != ''";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $estadisticas['areas'] = (int)($result['areas'] ?? 0);
            }
        } catch (Throwable $e) {
            error_log("Error en SupervisoresController::index: " . $e->getMessage());
            $supervisores = [];
            $estadisticas = ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'areas' => 0];
            $_SESSION['error'] = 'Error al cargar los supervisores';
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
     * Guardar nuevo supervisor
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

        if (!empty($email)) {
            if ($this->model->emailExiste($email)) {
                $errores[] = 'El email ya está registrado como supervisor';
            }

            $usuarioModel = new UsuariosModel();
            if ($usuarioModel->emailExiste($email)) {
                $errores[] = 'El email ya está registrado como usuario del sistema';
            }
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
            $this->db->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $datosSupervisor = [
                'nombre'        => $nombre,
                'email'         => $email,
                'password_hash' => $passwordHash,
                'area'          => ValidationHelper::sanitize($_POST['area'] ?? ''),
                'telefono'      => ValidationHelper::sanitize($_POST['telefono'] ?? ''),
                'estado'        => $_POST['estado'] ?? 'activo',
            ];

            $supervisorId = $this->model->crear($datosSupervisor);

            if (!$supervisorId) {
                throw new RuntimeException('No se pudo crear el supervisor');
            }

            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado, fecha_creacion) 
                    VALUES (?, ?, ?, 'supervisor', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $nombre,
                $email,
                $passwordHash,
                $_POST['estado'] ?? 'activo',
            ]);

            if (!$result) {
                throw new RuntimeException('No se pudo crear el usuario');
            }

            $this->db->commit();

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
            } catch (Throwable $e) {
                error_log("Error auditoría crear supervisor: " . $e->getMessage());
            }

            unset($_SESSION['old']);
            $this->redirectWithSuccess('/supervisores', 'Supervisor creado correctamente. Ya puede iniciar sesión.');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisoresController::guardar: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            $_SESSION['error'] = 'Error al crear el supervisor. Intenta nuevamente.';
            $_SESSION['old'] = $_POST;
            $this->redirect('/supervisores/crear');
            return;
        }
    }

    /**
     * Formulario para editar supervisor
     * URL: /supervisores/editar/{id}
     */
    public function editar($id) {
        $id = (int)$id;

        try {
            $supervisor = $this->model->obtenerPorId($id);

            if (!$supervisor) {
                $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
                return;
            }
        } catch (Throwable $e) {
            error_log("Error en SupervisoresController::editar: " . $e->getMessage());
            $this->redirectWithError('/supervisores', 'Error al cargar el supervisor');
            return;
        }

        $seccion = 'supervisores';
        $titulo = 'Editar Supervisor';
        require_once __DIR__ . '/../views/supervisores/form.php';
    }

    /**
     * Actualizar supervisor
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

        try {
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

            if (!empty($email) && $this->model->emailExiste($email, $id)) {
                $errores[] = 'El email ya está registrado por otro supervisor';
            }

            if (!empty($email) && $email !== $supervisor_original['email']) {
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

            $this->db->beginTransaction();

            $datosSupervisor = [
                'nombre'   => $nombre,
                'email'    => $email,
                'area'     => ValidationHelper::sanitize($_POST['area'] ?? ''),
                'telefono' => ValidationHelper::sanitize($_POST['telefono'] ?? ''),
                'estado'   => $_POST['estado'] ?? 'activo',
            ];

            $password = $_POST['password'] ?? '';
            $passwordHash = null;

            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new RuntimeException('La contraseña debe tener al menos 6 caracteres');
                }
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $datosSupervisor['password_hash'] = $passwordHash;
            }

            $resultSupervisor = $this->model->actualizar($id, $datosSupervisor);

            if (!$resultSupervisor) {
                throw new RuntimeException('No se pudo actualizar el supervisor');
            }

            if ($passwordHash !== null) {
                $sql = "UPDATE usuarios SET 
                            nombre = ?, email = ?, password_hash = ?, estado = ?, fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'supervisor'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $passwordHash,
                    $_POST['estado'] ?? 'activo',
                    $supervisor_original['email'],
                ]);
            } else {
                $sql = "UPDATE usuarios SET 
                            nombre = ?, email = ?, estado = ?, fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'supervisor'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $_POST['estado'] ?? 'activo',
                    $supervisor_original['email'],
                ]);
            }

            if (!$resultUsuario) {
                throw new RuntimeException('No se pudo actualizar el usuario');
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
            } catch (Throwable $e) {
                error_log("Error auditoría actualizar supervisor: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/supervisores', 'Supervisor actualizado correctamente.');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisoresController::actualizar: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            $_SESSION['error'] = 'Error al actualizar el supervisor. Intenta nuevamente.';
            $this->redirect('/supervisores');
            return;
        }
    }

    /**
     * Cambiar estado del supervisor
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

        try {
            $supervisor = $this->model->obtenerPorId($id);
            if (!$supervisor) {
                $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
                return;
            }

            $this->db->beginTransaction();

            $resultSupervisor = $this->model->cambiarEstado($id, $estado);
            if (!$resultSupervisor) {
                throw new RuntimeException('No se pudo cambiar el estado del supervisor');
            }

            $sql = "UPDATE usuarios SET estado = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$estado, $supervisor['email']]);

            if (!$resultUsuario) {
                throw new RuntimeException('No se pudo cambiar el estado del usuario');
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
            } catch (Throwable $e) {
                error_log("Error auditoría cambiar estado: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/supervisores', 'Estado actualizado correctamente.');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisoresController::cambiarEstado: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            $_SESSION['error'] = 'Error al cambiar el estado. Intenta nuevamente.';
            $this->redirect('/supervisores');
            return;
        }
    }

    /**
     * Cambiar contraseña del supervisor
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

        try {
            $supervisor = $this->model->obtenerPorId($id);
            if (!$supervisor) {
                $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
                return;
            }

            $this->db->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $resultSupervisor = $this->model->actualizarPassword($id, $passwordHash);
            if (!$resultSupervisor) {
                throw new RuntimeException('No se pudo actualizar la contraseña del supervisor');
            }

            $sql = "UPDATE usuarios SET password_hash = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$passwordHash, $supervisor['email']]);

            if (!$resultUsuario) {
                throw new RuntimeException('No se pudo actualizar la contraseña del usuario');
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
            } catch (Throwable $e) {
                error_log("Error auditoría cambiar password: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/supervisores', 'Contraseña actualizada correctamente.');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisoresController::cambiarPassword: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            $_SESSION['error'] = 'Error al actualizar la contraseña. Intenta nuevamente.';
            $this->redirect('/supervisores');
            return;
        }
    }

    /**
     * Eliminar supervisor
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

        try {
            $supervisor = $this->model->obtenerPorId($id);

            if (!$supervisor) {
                $this->redirectWithError('/supervisores', 'Supervisor no encontrado');
                return;
            }

            $this->db->beginTransaction();

            $sql = "SELECT COUNT(*) as total FROM ordenes_mantenimiento WHERE id_supervisor = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (($result['total'] ?? 0) > 0) {
                throw new RuntimeException('No se puede eliminar el supervisor porque tiene órdenes asignadas');
            }

            $sqlSup = "SELECT COUNT(*) as total FROM supervisiones WHERE supervisor_id = ?";
            $stmtSup = $this->db->prepare($sqlSup);
            $stmtSup->execute([$id]);
            $resultSup = $stmtSup->fetch(PDO::FETCH_ASSOC);

            if (($resultSup['total'] ?? 0) > 0) {
                throw new RuntimeException('No se puede eliminar el supervisor porque tiene supervisiones registradas');
            }

            $resultSupervisor = $this->model->eliminar($id);
            if (!$resultSupervisor) {
                throw new RuntimeException('No se pudo eliminar el supervisor');
            }

            $sql = "DELETE FROM usuarios WHERE email = ? AND rol = 'supervisor'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$supervisor['email']]);

            if (!$resultUsuario) {
                throw new RuntimeException('No se pudo eliminar el usuario');
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
            } catch (Throwable $e) {
                error_log("Error auditoría eliminar supervisor: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/supervisores', 'Supervisor eliminado correctamente.');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en SupervisoresController::eliminar: " . $e->getMessage());

            // ✅ FIX: NO exponer $e->getMessage(), solo mensajes seguros predefinidos
            $msg = $e->getMessage();
            if (strpos($msg, 'tiene órdenes asignadas') !== false) {
                $_SESSION['error'] = 'No se puede eliminar el supervisor porque tiene órdenes asignadas.';
            } elseif (strpos($msg, 'tiene supervisiones registradas') !== false) {
                $_SESSION['error'] = 'No se puede eliminar el supervisor porque tiene supervisiones registradas.';
            } else {
                $_SESSION['error'] = 'Error al eliminar el supervisor. Intenta nuevamente.';
            }

            $this->redirect('/supervisores');
            return;
        }
    }
}