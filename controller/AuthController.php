<?php
// controller/AuthController.php
// Controlador de autenticación - CON AUDITORÍA, AUTO-CREACIÓN DE MONITOREO Y CHECKLISTS
// ✅ NUEVO: Auto-creación de registros del día en checklist_registros

require_once __DIR__ . '/../model/UsuariosModel.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {

    private $usuarioModel;
    private $authHelper;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->usuarioModel = new UsuariosModel();
        $this->authHelper = new AuthHelper();
    }

    /**
     * Mostrar formulario de login
     */
    public function login() {
        if ($this->authHelper->isLoggedIn()) {
            $this->authHelper->redirectByRole();
            exit;
        }

        $titulo = 'Iniciar Sesión';
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesar autenticación - CON AUDITORÍA, AUTO-CREACIÓN DE MONITOREO Y CHECKLISTS
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/login');
            exit;
        }

        if (method_exists('SecurityHelper', 'verifyCSRFToken')) {
            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($token)) {
                $_SESSION['error'] = 'Token de seguridad inválido. Por favor, recarga la página.';
                header('Location: /proyecto/login');
                exit;
            }
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $_SESSION['error'] = 'El email es obligatorio';
            header('Location: /proyecto/login');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no es válido';
            header('Location: /proyecto/login');
            exit;
        }

        if (empty($password)) {
            $_SESSION['error'] = 'La contraseña es obligatoria';
            header('Location: /proyecto/login');
            exit;
        }

        try {
            $usuario = $this->usuarioModel->autenticar($email, $password);

            if ($usuario) {
                // REGISTRAR LOGIN EN AUDITORÍA
                try {
                    $auditoria = new AuditoriaModel();
                    $auditoria->registrar(
                        $usuario['id'],
                        $usuario['nombre'],
                        $usuario['rol'],
                        'login',
                        'usuarios',
                        $usuario['id']
                    );
                } catch (Throwable $e) {
                    error_log("Error al registrar auditoría de login: " . $e->getMessage());
                }

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre']     = htmlspecialchars($usuario['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
                $_SESSION['email']      = htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8');
                $_SESSION['rol']        = $usuario['rol'] ?? 'usuario';
                $_SESSION['login_time'] = time();

                try {
                    $db = Database::getInstance()->getConnection();
                    $sql = "UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$usuario['id']]);
                } catch (Throwable $e) {
                    error_log("Error al actualizar ultima_conexion: " . $e->getMessage());
                }

                session_regenerate_id(true);

                // ==========================================
                // AUTO-CREAR REGISTROS DE MONITOREO DEL DÍA
                // ==========================================
                if (in_array($usuario['rol'] ?? '', ['operador', 'tecnico'], true)) {
                    try {
                        require_once __DIR__ . '/../model/MonitoreoModel.php';
                        $monModel = new MonitoreoModel();

                        $monModel->marcarVencidos();

                        $resultado = $monModel->crearRegistrosDelDia(date('Y-m-d'), $usuario['id']);

                        if ($resultado['creados'] > 0) {
                            error_log("Monitoreo: {$resultado['creados']} registros auto-creados para usuario {$usuario['id']}");
                        }
                    } catch (Throwable $e) {
                        error_log("Error auto-creando registros de monitoreo: " . $e->getMessage());
                    }
                }

                // ==========================================
                // AUTO-CREAR REGISTROS DE CHECKLIST DEL DÍA
                // ==========================================
                if (in_array($usuario['rol'] ?? '', ['operador', 'tecnico'], true)) {
                    try {
                        require_once __DIR__ . '/../model/ChecklistModel.php';
                        $checkModel = new ChecklistModel();

                        $tipos = $checkModel->obtenerTiposPorRol($usuario['rol']);
                        $fecha_hoy = date('Y-m-d');
                        $creados = 0;

                        foreach ($tipos as $tipo_item) {
                            $turno_actual = !empty($tipo_item['requiere_turno'])
                                ? $checkModel->detectarTurnoActual()
                                : null;

                            $existente = $checkModel->obtenerRegistroPorTipoFechaTurno(
                                (int)$tipo_item['id'],
                                $fecha_hoy,
                                $turno_actual
                            );

                            if (!$existente) {
                                $nuevo = $checkModel->iniciarRegistro(
                                    (int)$tipo_item['id'],
                                    $fecha_hoy,
                                    $turno_actual,
                                    $usuario['id']
                                );
                                if ($nuevo) {
                                    $creados++;
                                }
                            }
                        }

                        if ($creados > 0) {
                            error_log("Checklist: {$creados} registros auto-creados para usuario {$usuario['id']}");
                        }
                    } catch (Throwable $e) {
                        error_log("Error auto-creando registros de checklists: " . $e->getMessage());
                    }
                }

                $this->authHelper->redirectByRole();
                exit;

            } else {
                $_SESSION['error'] = 'Credenciales incorrectas. Verifica tu email y contraseña.';
                header('Location: /proyecto/login');
                exit;
            }
        } catch (Throwable $e) {
            error_log("Error en authenticate: " . $e->getMessage());
            $_SESSION['error'] = 'Error al iniciar sesión. Intenta nuevamente.';
            header('Location: /proyecto/login');
            exit;
        }
    }

    /**
     * Cerrar sesión - CON AUDITORÍA
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['usuario_id'])) {
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $_SESSION['usuario_id'],
                    $_SESSION['nombre'] ?? 'Usuario',
                    $_SESSION['rol'] ?? 'usuario',
                    'logout',
                    'usuarios',
                    $_SESSION['usuario_id']
                );
            } catch (Throwable $e) {
                error_log("Error al registrar auditoría de logout: " . $e->getMessage());
            }
        }

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header('Location: /proyecto/login');
        exit;
    }
}