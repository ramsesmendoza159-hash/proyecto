<?php
// controller/PerfilController.php
// Controlador de perfil de usuario - VERSIÓN COMPLETA CON GUARDAR FIRMA

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/UsuariosModel.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/HashHelper.php';
require_once __DIR__ . '/../config/database.php';

class PerfilController extends Controller {
    
    private $usuarioModel;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        $this->usuarioModel = new UsuariosModel();
    }
    
    /**
     * Mostrar perfil del usuario
     */
    public function index() {
        $usuarioId = $this->authHelper->getUserId();
        $usuario = $this->usuarioModel->obtenerPorId($usuarioId);
        
        if (!$usuario) {
            $_SESSION['error'] = 'No se pudo cargar la información del usuario.';
            $this->redirect('/dashboard');
        }
        
        // Cargar firma actual
        $firma_actual = null;
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT firma_svg FROM usuarios WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$usuarioId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $firma_actual = $result['firma_svg'] ?? null;
            $usuario['firma_svg'] = $firma_actual;
        } catch (Exception $e) {
            error_log("Error al cargar firma: " . $e->getMessage());
        }
        
        $titulo = 'Mi Perfil';
        $seccion = 'perfil';
        require_once __DIR__ . '/../views/perfil/index.php';
    }
    
    /**
     * Mostrar formulario de edición de perfil
     */
    public function editar() {
        $usuarioId = $this->authHelper->getUserId();
        $usuario = $this->usuarioModel->obtenerPorId($usuarioId);
        
        if (!$usuario) {
            $_SESSION['error'] = 'No se pudo cargar la información del usuario.';
            $this->redirect('/perfil');
        }
        
        $titulo = 'Editar Perfil';
        $seccion = 'perfil';
        require_once __DIR__ . '/../views/perfil/editar.php';
    }
    
    /**
     * Actualizar datos del perfil
     */
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/perfil');
        }
        
        if (method_exists('SecurityHelper', 'verifyCSRFToken')) {
            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($token)) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/perfil');
            }
        }
        
        $usuarioId = $this->authHelper->getUserId();
        
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        $errores = [];
        
        if (empty($nombre)) {
            $errores[] = 'El nombre es obligatorio';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }
        
        if (!empty($email) && $this->usuarioModel->emailExiste($email, $usuarioId)) {
            $errores[] = 'El email ya está en uso por otro usuario';
        }
        
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $_SESSION['old'] = [
                'nombre' => $nombre,
                'email' => $email
            ];
            $this->redirect('/perfil/editar');
        }
        
        $datos = [
            'nombre' => $nombre,
            'email' => $email
        ];
        
        $resultado = $this->usuarioModel->actualizar($usuarioId, $datos);
        
        if ($resultado) {
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            $_SESSION['mensaje'] = 'Perfil actualizado correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = 'Error al actualizar el perfil';
        }
        
        $this->redirect('/perfil');
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/perfil');
        }
        
        if (method_exists('SecurityHelper', 'verifyCSRFToken')) {
            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($token)) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/perfil');
            }
        }
        
        $usuarioId = $this->authHelper->getUserId();
        
        $passwordActual = $_POST['password_actual'] ?? '';
        $passwordNueva = $_POST['password_nueva'] ?? '';
        $passwordConfirmar = $_POST['password_confirmar'] ?? '';
        
        $errores = [];
        
        if (empty($passwordActual)) {
            $errores[] = 'Debes ingresar tu contraseña actual';
        }
        
        if (strlen($passwordNueva) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres';
        }
        
        if ($passwordNueva !== $passwordConfirmar) {
            $errores[] = 'Las contraseñas no coinciden';
        }
        
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirect('/perfil');
        }
        
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT password_hash FROM usuarios WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);
        $hash = $stmt->fetchColumn();
        
        if (!HashHelper::verify($passwordActual, $hash)) {
            $_SESSION['error'] = 'La contraseña actual es incorrecta';
            $this->redirect('/perfil');
        }
        
        $resultado = $this->usuarioModel->cambiarPassword($usuarioId, $passwordNueva);
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Contraseña actualizada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = 'Error al actualizar la contraseña';
        }
        
        $this->redirect('/perfil');
    }
    
    /**
     * Guardar firma del usuario (AJAX)
     * URL: /perfil/guardar-firma (POST)
     */
     /**
     * Guardar firma del usuario (AJAX)
     * URL: /perfil/guardar-firma (POST)
     * ✅ FIX: Verifica rowCount() y limita a 200 KB (SVG vectorial no debería pasar de 30 KB)
     */
    public function guardarFirma() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
                exit;
            }
            
            if (!$this->authHelper->isLoggedIn()) {
                echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
                exit;
            }
            
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($csrf_token)) {
                echo json_encode(['success' => false, 'mensaje' => 'Token de seguridad inválido']);
                exit;
            }
            
            $firma_svg = $_POST['firma_svg'] ?? '';
            
            if (empty($firma_svg)) {
                echo json_encode(['success' => false, 'mensaje' => 'La firma está vacía']);
                exit;
            }
            
            // ✅ Límite bajado a 200 KB (SVG vectorial puro pesa 5-30 KB)
            if (strlen($firma_svg) > 204800) {
                echo json_encode([
                    'success' => false, 
                    'mensaje' => 'La firma es demasiado grande (' . round(strlen($firma_svg)/1024) . ' KB, máx 200 KB). Intenta dibujar más simple.'
                ]);
                exit;
            }
            
            if (strpos($firma_svg, '<svg') === false) {
                echo json_encode(['success' => false, 'mensaje' => 'El formato de la firma no es válido (falta <svg)']);
                exit;
            }
            
            // ✅ Validar que el SVG esté COMPLETO
            if (strpos($firma_svg, '</svg>') === false) {
                echo json_encode(['success' => false, 'mensaje' => 'La firma está incompleta (falta </svg>)']);
                exit;
            }
            
            $usuarioId = $this->authHelper->getUserId();
            $db = Database::getInstance()->getConnection();
            
            $sql = "UPDATE usuarios SET firma_svg = :firma_svg WHERE id = :id";
            $stmt = $db->prepare($sql);
            $resultado = $stmt->execute([
                ':firma_svg' => $firma_svg,
                ':id' => $usuarioId
            ]);
            
            // ✅ FIX: Verificar rowCount
            if ($resultado && $stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Firma guardada correctamente (' . round(strlen($firma_svg)/1024, 1) . ' KB)',
                    'bytes' => strlen($firma_svg)
                ]);
            } elseif ($resultado && $stmt->rowCount() === 0) {
                // El UPDATE no afectó filas — probablemente el usuario no existe
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'No se pudo guardar la firma (usuario no encontrado)'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'Error al guardar la firma'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error en guardarFirma: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}
?>