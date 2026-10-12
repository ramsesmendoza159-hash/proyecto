<?php
// controller/TecnicosController.php
// Gestión de técnicos - CON AUDITORÍA Y TRANSACCIONES

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
            return;
        }

        $rol = $this->authHelper->getRole();
        if ($rol !== 'admin') {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            return;
        }

        try {
            $this->model = new Tecnico();
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en TecnicosController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el módulo');
            return;
        }
    }

    /**
     * Lista de técnicos
     * URL: /tecnicos
     */
    public function index() {
        try {
            $filtros = [
                'estado'       => trim((string)($_GET['estado'] ?? '')),
                'buscar'       => trim((string)($_GET['buscar'] ?? '')),
                'especialidad' => trim((string)($_GET['especialidad'] ?? '')),
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

        } catch (Throwable $e) {
            error_log("Error en TecnicosController::index: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar los técnicos');
            return;
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
        } catch (Throwable $e) {
            error_log("Error en TecnicosController::crear: " . $e->getMessage());
            $this->redirectWithError('/tecnicos', 'Error al cargar el formulario');
            return;
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
                'nombre'       => $_POST['nombre'] ?? '',
                'email'        => $_POST['email'] ?? '',
                'telefono'     => $_POST['telefono'] ?? '',
                'especialidad' => $_POST['especialidad'] ?? '',
                'tarifa'       => $_POST['tarifa'] ?? 0,
                'estado'       => $_POST['estado'] ?? 'activo',
            ];

            $errores = [];

            $nombre = trim($_POST['nombre'] ?? '');
            if (empty($nombre)) {
                $errores[] = 'El nombre es obligatorio';
            } elseif (strlen($nombre) < 3 || strlen($nombre) > 100) {
                $errores[] = 'El nombre debe tener entre 3 y 100 caracteres';
            }

            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $errores[] = 'El email es obligatorio';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El email no es válido';
            }

            if (!empty($email)) {
                if ($this->model->emailExiste($email)) {
                    $errores[] = 'El email ya está registrado como técnico';
                }
                $usuarioModel = new UsuariosModel();
                if ($usuarioModel->emailExiste($email)) {
                    $errores[] = 'El email ya está registrado como usuario del sistema';
                }
            }

            $password = $_POST['password'] ?? '';
            $confirmar_password = $_POST['confirmar_password'] ?? '';

            if (empty($password)) {
                $errores[] = 'La contraseña es obligatoria';
            } elseif (strlen($password) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres';
            } elseif ($password !== $confirmar_password) {
                $errores[] = 'Las contraseñas no coinciden';
            }

            $especialidad = trim($_POST['especialidad'] ?? '');
            if (empty($especialidad)) {
                $errores[] = 'La especialidad es obligatoria';
            }

            $tarifa = (float)($_POST['tarifa'] ?? 0);
            if ($tarifa < 0) {
                $errores[] = 'La tarifa no puede ser negativa';
            }

            $telefono = trim($_POST['telefono'] ?? '');
            if (!empty($telefono) && !preg_match('/^[0-9\s\-+()]{6,15}$/', $telefono)) {
                $errores[] = 'El teléfono no es válido';
            }

            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $this->redirect('/tecnicos/crear');
                return;
            }

            $this->db->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $datos = [
                'nombre'        => $nombre,
                'email'         => $email,
                'password_hash' => $passwordHash,
                'especialidad'  => $especialidad,
                'tarifa'        => $tarifa,
                'telefono'      => $telefono,
                'estado'        => $_POST['estado'] ?? 'activo',
            ];

            $result = $this->model->crear($datos);

            if (!$result) {
                throw new RuntimeException('No se pudo crear el técnico');
            }

            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado, fecha_creacion) 
                    VALUES (?, ?, ?, 'tecnico', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([
                $nombre,
                $email,
                $passwordHash,
                $_POST['estado'] ?? 'activo',
            ]);

            if (!$resultUsuario) {
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
                    'tecnicos',
                    $result
                );
            } catch (Throwable $e) {
                error_log("Error auditoría crear técnico: " . $e->getMessage());
            }

            unset($_SESSION['old']);
            $this->redirectWithSuccess('/tecnicos', 'Técnico creado correctamente. Ya puede iniciar sesión.');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::guardar: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            $_SESSION['error'] = 'Error al crear el técnico. Intenta nuevamente.';
            $this->redirect('/tecnicos/crear');
            return;
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

        } catch (Throwable $e) {
            error_log("Error en TecnicosController::editar: " . $e->getMessage());
            $this->redirectWithError('/tecnicos', 'Error al cargar el técnico');
            return;
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
                $this->redirectWithError('/tecnicos/editar/' . $id, 'Token de seguridad inválido');
                return;
            }

            $tecnico_original = $this->model->obtenerPorId($id);
            if (!$tecnico_original) {
                $this->redirectWithError('/tecnicos', 'Técnico no encontrado');
                return;
            }

            $_SESSION['old'] = [
                'nombre'       => $_POST['nombre'] ?? '',
                'email'        => $_POST['email'] ?? '',
                'telefono'     => $_POST['telefono'] ?? '',
                'especialidad' => $_POST['especialidad'] ?? '',
                'tarifa'       => $_POST['tarifa'] ?? 0,
                'estado'       => $_POST['estado'] ?? 'activo',
            ];

            $errores = [];

            $nombre = trim($_POST['nombre'] ?? '');
            if (empty($nombre)) {
                $errores[] = 'El nombre es obligatorio';
            } elseif (strlen($nombre) < 3 || strlen($nombre) > 100) {
                $errores[] = 'El nombre debe tener entre 3 y 100 caracteres';
            }

            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $errores[] = 'El email es obligatorio';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El email no es válido';
            }

            if (!empty($email) && $this->model->emailExiste($email, $id)) {
                $errores[] = 'El email ya está registrado por otro técnico';
            }

            if (!empty($email) && $email !== $tecnico_original['email']) {
                $usuarioModel = new UsuariosModel();
                if ($usuarioModel->emailExiste($email)) {
                    $errores[] = 'El email ya está registrado como usuario del sistema';
                }
            }

            $password = $_POST['password'] ?? '';
            $confirmar_password = $_POST['confirmar_password'] ?? '';

            if (!empty($password) || !empty($confirmar_password)) {
                if (strlen($password) < 6) {
                    $errores[] = 'La contraseña debe tener al menos 6 caracteres';
                } elseif ($password !== $confirmar_password) {
                    $errores[] = 'Las contraseñas no coinciden';
                }
            }

            $especialidad = trim($_POST['especialidad'] ?? '');
            if (empty($especialidad)) {
                $errores[] = 'La especialidad es obligatoria';
            }

            $tarifa = (float)($_POST['tarifa'] ?? 0);
            if ($tarifa < 0) {
                $errores[] = 'La tarifa no puede ser negativa';
            }

            $telefono = trim($_POST['telefono'] ?? '');
            if (!empty($telefono) && !preg_match('/^[0-9\s\-+()]{6,15}$/', $telefono)) {
                $errores[] = 'El teléfono no es válido';
            }

            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $this->redirect('/tecnicos/editar/' . $id);
                return;
            }

            $this->db->beginTransaction();

            $datos = [
                'nombre'       => $nombre,
                'email'        => $email,
                'especialidad' => $especialidad,
                'tarifa'       => $tarifa,
                'telefono'     => $telefono,
                'estado'       => $_POST['estado'] ?? 'activo',
            ];

            $passwordHash = null;
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $datos['password_hash'] = $passwordHash;
            }

            $result = $this->model->actualizar($id, $datos);

            if (!$result) {
                throw new RuntimeException('No se pudo actualizar el técnico');
            }

            if ($passwordHash !== null) {
                $sql = "UPDATE usuarios SET 
                            nombre = ?, email = ?, password_hash = ?, estado = ?, fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'tecnico'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $passwordHash,
                    $_POST['estado'] ?? 'activo',
                    $tecnico_original['email'],
                ]);
            } else {
                $sql = "UPDATE usuarios SET 
                            nombre = ?, email = ?, estado = ?, fecha_actualizacion = NOW()
                        WHERE email = ? AND rol = 'tecnico'";
                $stmt = $this->db->prepare($sql);
                $resultUsuario = $stmt->execute([
                    $nombre,
                    $email,
                    $_POST['estado'] ?? 'activo',
                    $tecnico_original['email'],
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
                    'tecnicos',
                    $id
                );
            } catch (Throwable $e) {
                error_log("Error auditoría actualizar técnico: " . $e->getMessage());
            }

            unset($_SESSION['old']);
            $this->redirectWithSuccess('/tecnicos', 'Técnico actualizado correctamente');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::actualizar: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            $_SESSION['error'] = 'Error al actualizar el técnico. Intenta nuevamente.';
            $this->redirect('/tecnicos/editar/' . $id);
            return;
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

            $sql = "SELECT COUNT(*) as total FROM ordenes_mantenimiento WHERE tecnico_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (($result['total'] ?? 0) > 0) {
                throw new RuntimeException('HAS_ORDERS');
            }

            $resultTecnico = $this->model->eliminar($id);

            if (!$resultTecnico) {
                throw new RuntimeException('No se pudo eliminar el técnico');
            }

            $sql = "DELETE FROM usuarios WHERE email = ? AND rol = 'tecnico'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$tecnico['email']]);

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
                    'tecnicos',
                    $id
                );
            } catch (Throwable $e) {
                error_log("Error auditoría eliminar técnico: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/tecnicos', 'Técnico eliminado correctamente');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::eliminar: " . $e->getMessage());

            // ✅ FIX: mensaje seguro
            if ($e->getMessage() === 'HAS_ORDERS') {
                $_SESSION['error'] = 'No se puede eliminar el técnico porque tiene órdenes asignadas.';
            } else {
                $_SESSION['error'] = 'Error al eliminar el técnico. Intenta nuevamente.';
            }
            $this->redirect('/tecnicos');
            return;
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
                throw new RuntimeException('No se pudo cambiar el estado del técnico');
            }

            $sql = "UPDATE usuarios SET estado = ?, fecha_actualizacion = NOW() 
                    WHERE email = ? AND rol = 'tecnico'";
            $stmt = $this->db->prepare($sql);
            $resultUsuario = $stmt->execute([$estado, $tecnico['email']]);

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
                    'tecnicos',
                    $id
                );
            } catch (Throwable $e) {
                error_log("Error auditoría cambiar estado: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/tecnicos', 'Estado actualizado correctamente');
            return;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en TecnicosController::cambiarEstado: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            $_SESSION['error'] = 'Error al cambiar el estado. Intenta nuevamente.';
            $this->redirect('/tecnicos');
            return;
        }
    }

    /**
     * API de datos (JSON)
     */
    public function apiDatos() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filtros = [
                'estado'       => trim((string)($_GET['estado'] ?? '')),
                'buscar'       => trim((string)($_GET['buscar'] ?? '')),
                'especialidad' => trim((string)($_GET['especialidad'] ?? '')),
            ];

            $tecnicos = $this->model->obtenerTodos($filtros);

            echo json_encode([
                'success' => true,
                'data' => $tecnicos,
                'total' => count($tecnicos)
            ]);

        } catch (Throwable $e) {
            error_log("Error en TecnicosController::apiDatos: " . $e->getMessage());
            // ✅ FIX: NO exponer $e->getMessage()
            echo json_encode([
                'success' => false,
                'error' => 'Error al procesar la solicitud'
            ]);
        }
        exit;
    }
}